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

        // Get approved leave applications from the past 12 months
        $applications = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->where('date_from', '>=', now()->subYear())
            ->with('leaveType')
            ->get();

        // Check 1: Frequent Monday/Friday leaves (potential long weekend abuse)
        $mondayFridayCount = $applications->filter(function ($app) {
            $from = Carbon::parse($app->date_from);
            return $from->isMonday() || $from->isFriday();
        })->count();

        if ($mondayFridayCount >= 5) {
            $flags[] = 'Frequent Monday/Friday leaves (' . $mondayFridayCount . ' occurrences)';
            $score += min($mondayFridayCount * 5, 25);
        }

        // Check 2: Consecutive short leaves (splitting to avoid scrutiny)
        $shortLeaves = $applications->filter(fn($app) => $app->num_days <= 1)->count();
        if ($shortLeaves >= 8) {
            $flags[] = 'High frequency of single-day leaves (' . $shortLeaves . ' occurrences)';
            $score += min($shortLeaves * 3, 20);
        }

        // Check 3: Leave near holidays (potential bridging)
        $nearHolidayCount = $this->checkNearHolidayLeaves($applications);
        if ($nearHolidayCount >= 3) {
            $flags[] = 'Leaves frequently taken near holidays (' . $nearHolidayCount . ' occurrences)';
            $score += min($nearHolidayCount * 5, 20);
        }

        // Check 4: Excessive total leave usage
        $totalDays = $applications->sum('num_days');
        if ($totalDays > 30) {
            $flags[] = 'Excessive leave usage (' . $totalDays . ' days in 12 months)';
            $score += min(($totalDays - 30) * 2, 20);
        }

        // Check 5: Pattern of same day-of-week leaves
        $dayOfWeekCounts = $applications->groupBy(fn($app) => Carbon::parse($app->date_from)->dayOfWeek)
            ->map->count();

        foreach ($dayOfWeekCounts as $day => $count) {
            if ($count >= 4) {
                $dayName = Carbon::create()->startOfWeek()->addDays($day)->format('l');
                $flags[] = "Recurring {$dayName} leave pattern ({$count} occurrences)";
                $score += 10;
                break;
            }
        }

        // Check 6: Sick leave without documentation for extended periods
        $undocumentedSL = $applications->filter(function ($app) {
            return $app->leaveType && $app->leaveType->code === 'SL'
                && $app->num_days > 3
                && !$app->attachment;
        })->count();

        if ($undocumentedSL > 0) {
            $flags[] = 'Extended sick leave without documentation (' . $undocumentedSL . ' occurrences)';
            $score += $undocumentedSL * 10;
        }

        // Cap score at 100
        $score = min($score, 100);

        // Determine risk level
        $riskLevel = match (true) {
            $score >= 60 => 'High',
            $score >= 30 => 'Medium',
            default => 'Low',
        };

        // Generate reason summary
        $reason = empty($flags)
            ? 'No suspicious patterns detected.'
            : 'Detected patterns: ' . implode('; ', $flags);

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
