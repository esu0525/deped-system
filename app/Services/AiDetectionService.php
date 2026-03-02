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
        $allDetails = $applications->pluck('details')->flatten();
        for ($i = 0; $i < count($allDetails); $i++) {
            for ($j = $i + 1; $j < count($allDetails); $j++) {
                $d1 = $allDetails[$i];
                $d2 = $allDetails[$j];

                // Simplified overlap check (if they share any dates)
                // Assuming inclusive_dates is parsed or we use date_from/to for basic check
                if ($d1->date_from <= $d2->date_to && $d2->date_from <= $d1->date_to) {
                    $overlappingCount++;
                }
            }
        }

        if ($overlappingCount > 0) {
            $flags[] = "Detected {$overlappingCount} overlapping/duplicate date entries";
            $score += 40; // High risk for double entries
        }

        // Check 2: Filing Deadlines (Based on Instruction Image)
        $deadlineViolations = 0;
        foreach ($applications as $app) {
            $dateFiled = Carbon::parse($app->date_filed);
            $dateFrom = Carbon::parse($app->date_from);
            $code = $app->leaveType->code ?? '';
            $daysDiff = $dateFiled->diffInDays($dateFrom, false);

            if ($code === 'VL' && $daysDiff < 5) {
                $flags[] = "VL filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 5)";
                $deadlineViolations++;
            }
            elseif ($code === 'SPL' && $daysDiff < 7) {
                $flags[] = "SPL filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 7)";
                $deadlineViolations++;
            }
            elseif ($code === 'SOLO' && $daysDiff < 5) {
                $flags[] = "Solo Parent Leave filed late on {$app->date_filed->format('m/d/Y')} ({$daysDiff} days notice instead of 5)";
                $deadlineViolations++;
            }
        }
        if ($deadlineViolations > 0)
            $score += min($deadlineViolations * 10, 30);

        // Check 3: Frequent Monday/Friday (potential long weekend abuse)
        $mondayFridayCount = $applications->filter(function ($app) {
            $from = Carbon::parse($app->date_from);
            return $from->isMonday() || $from->isFriday();
        })->count();

        if ($mondayFridayCount >= 5) {
            $flags[] = 'Frequent Monday/Friday leaves (' . $mondayFridayCount . ' occurrences)';
            $score += min($mondayFridayCount * 3, 15);
        }

        // Check 4: Consecutive short leaves (splitting)
        $shortLeaves = $applications->filter(fn($app) => $app->num_days <= 1)->count();
        if ($shortLeaves >= 8) {
            $flags[] = 'High frequency of single-day leaves (' . $shortLeaves . ' occurrences)';
            $score += min($shortLeaves * 2, 10);
        }

        // Check 5: Leave near holidays
        $nearHolidayCount = $this->checkNearHolidayLeaves($applications);
        if ($nearHolidayCount >= 3) {
            $flags[] = 'Leaves frequently taken near holidays (' . $nearHolidayCount . ' occurrences)';
            $score += min($nearHolidayCount * 5, 15);
        }

        // Check 6: Sick leave without documentation (Target: > 5 days as per image)
        $undocumentedSL = $applications->filter(function ($app) {
            return $app->leaveType && $app->leaveType->code === 'SL'
            && $app->num_days > 5
            && !$app->attachment;
        })->count();

        if ($undocumentedSL > 0) {
            $flags[] = 'Sick Leave exceeding 5 days without documentation (' . $undocumentedSL . ' occurrences)';
            $score += $undocumentedSL * 20;
        }

        // Check 7: Yearly Leave Type Limits
        $currentYear = now()->year;
        $leaveTypeUsage = $applications->filter(fn($app) => Carbon::parse($app->date_from)->year == $currentYear)
            ->groupBy(fn($app) => $app->leaveType->code ?? '')
            ->map(fn($group) => $group->sum('num_days'));

        $limits = [
            'FL' => 5,
            'SPL' => 3,
            'SOLO' => 7,
            'CAL' => 5,
        ];

        foreach ($limits as $code => $limit) {
            $usage = $leaveTypeUsage->get($code, 0);
            if ($usage > $limit) {
                $flags[] = "Exceeded yearly limit for {$code} (Used: {$usage} days, Limit: {$limit} days)";
                $score += ($usage - $limit) * 15; // Significant penalty for exceeding limits
            }
        }

        // Cap score at 100
        $score = min($score, 100);

        // Determine risk level
        $riskLevel = match (true) {
                $score >= 70 => 'High',
                $score >= 40 => 'Medium',
                default => 'Low',
            };

        // Generate reason summary
        $reason = empty($flags)
            ? 'No suspicious patterns detected.'
            : 'Detected: ' . implode('; ', $flags);

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
