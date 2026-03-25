<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\AiDetectionLog;
use App\Models\LeaveApplication;
use Carbon\Carbon;

class AiDetectionService
{
    /**
     * Analyze a single employee's leave patterns for suspicious behavior
     */
    public function analyze(Employee $employee): AiDetectionLog
    {
        $flags = [];
        $score = 0;

        // Get approved and pending leave applications from the past 12 months
        $applications = LeaveApplication::where('employee_id', $employee->id)
            ->whereIn('status', ['Approved', 'Pending'])
            ->where('date_from', '>=', now()->subYear())
            ->with(['leaveType', 'details'])
            ->get();

        // Check 1: Double Entry / Overlapping Dates (High Priority)
        $overlappingCount = 0;
        $allDetails = $applications->pluck('details')->flatten()->values();
        for ($i = 0; $i < count($allDetails); $i++) {
            for ($j = $i + 1; $j < count($allDetails); $j++) {
                $d1 = $allDetails[$i]; $d2 = $allDetails[$j];
                if ($d1->leave_application_id === $d2->leave_application_id) continue;
                $app1 = $d1->leaveApplication; $app2 = $d2->leaveApplication;
                if ($app1 && $d1->date_from->isSameDay($app1->date_filed)) continue;
                if ($app2 && $d2->date_from->isSameDay($app2->date_filed)) continue;
                if ($d1->date_from <= $d2->date_to && $d2->date_from <= $d1->date_to) {
                    $overlappingCount++;
                }
            }
        }
        if ($overlappingCount > 0) {
            $flags[] = "Detected {$overlappingCount} overlapping date-entries with separate records";
            $score += 40;
        }

        // Check 2: Filing Deadlines (Based on CSC Form 6 Instructions)
        $deadlineViolations = 0;
        foreach ($applications as $app) {
            $dateFiled = Carbon::parse($app->date_filed);
            $dateFrom = Carbon::parse($app->date_from);
            if ($dateFrom->isSameDay($dateFiled)) continue; // Skip placeholders

            $code = $app->leaveType->code ?? '';
            $daysDiff = $dateFiled->diffInDays($dateFrom, false);

            if ($code === 'VL' && $daysDiff < 5) {
                $flags[] = "VL filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 5 days advance)";
                $deadlineViolations++;
            }
            elseif ($code === 'SPL' && $daysDiff < 7) {
                $flags[] = "SPL filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 1 week advance)";
                $deadlineViolations++;
            }
            elseif ($code === 'SOLO' && $daysDiff < 5) {
                $flags[] = "Solo Parent Leave filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 5 days advance)";
                $deadlineViolations++;
            }
        }
        if ($deadlineViolations > 0)
            $score += min($deadlineViolations * 15, 45);

        // Check 3: Sick Leave Documentation (Target: > 5 days or if filed in advance)
        $undocumentedSL = $applications->filter(function ($app) {
            if (!$app->leaveType || $app->leaveType->code !== 'SL') return false;
            $dateFiled = Carbon::parse($app->date_filed);
            $dateFrom = Carbon::parse($app->date_from);
            $isFileInAdvance = $dateFiled->lt($dateFrom);
            
            return ($app->num_days > 5 || $isFileInAdvance) && !$app->attachment;
        })->count();

        if ($undocumentedSL > 0) {
            $flags[] = 'Sick Leave (exceeding 5 days or filed in advance) lacking medical certificate (' . $undocumentedSL . ' occurrences)';
            $score += $undocumentedSL * 25;
        }

        // Check 4: Sequential Filing after Rejection
        $rejections = LeaveApplication::where('employee_id', $employee->id)->where('status', 'Rejected')->where('updated_at', '>=', now()->subMonths(3))->get();
        $sequentialRejections = 0;
        foreach ($applications as $app) {
            foreach ($rejections as $rej) {
                $timeDiff = $app->created_at->diffInDays($rej->updated_at);
                if ($timeDiff >= 0 && $timeDiff <= 2) { $sequentialRejections++; }
            }
        }
        if ($sequentialRejections > 0) {
            $flags[] = "Application refiled shortly after a rejection ({$sequentialRejections} occurrences)";
            $score += 20;
        }

        if ($score > 0 && $score < 100) { $score += rand(-3, 3); }
        $score = max(0, min($score, 100));

        $riskLevel = match (true) {
            $score >= 80 => 'High',
            $score >= 50 => 'Medium',
            default => 'Low',
        };

        $reason = empty($flags) ? 'No policy violations detected.' : 'Detected: ' . implode('; ', $flags);

        return AiDetectionLog::create([
            'employee_id' => $employee->id,
            'risk_score' => $score,
            'risk_level' => $riskLevel,
            'suspicious_flags' => $flags,
            'generated_reason' => $reason,
            'is_reviewed' => false,
            'date_generated' => now(),
        ]);
    }

    /**
     * Analyze all active employees
     */
    public function analyzeAll(): int
    {
        $employees = Employee::where('status', 'Active')->get();

        foreach ($employees as $employee) {
            $this->analyze($employee);
        }

        return $employees->count();
    }

    /**
     * Check for leaves taken near common Philippine holidays
     */
    protected function checkNearHolidayLeaves($applications): int
    {
        $count = 0;
        $year = now()->year;

        // Common Philippine holidays (month-day)
        $holidays = [
            '01-01', '02-25', '04-09', '05-01', '06-12',
            '08-21', '08-26', '11-01', '11-30', '12-25',
            '12-30', '12-31',
        ];

        foreach ($applications as $app) {
            $leaveDate = Carbon::parse($app->date_from);
            foreach ($holidays as $holiday) {
                $holidayDate = Carbon::createFromFormat('m-d', $holiday)->year($leaveDate->year);
                if (abs($leaveDate->diffInDays($holidayDate)) <= 2) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }
}
