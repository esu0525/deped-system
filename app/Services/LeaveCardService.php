<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\LeaveApplication;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Carbon\Carbon;

class LeaveCardService
{
    /**
     * Get or create a leave card for the current year
     */
    public function getOrCreateLeaveCard(Employee $employee, ?int $year = null): LeaveCard
    {
        $year = $year ?? now()->year;

        return LeaveCard::firstOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            [
                'vl_beginning_balance' => 0,
                'sl_beginning_balance' => 0,
                'vl_earned' => 0,
                'sl_earned' => 0,
                'vl_used' => 0,
                'sl_used' => 0,
                'vl_balance' => 0,
                'sl_balance' => 0,
                'forced_leave_balance' => 0,
                'special_leave_balance' => 0,
            ]
        );
    }

    /**
     * Calculate working days between two dates (excluding weekends)
     */
    public function calculateWorkingDays(Carbon $from, Carbon $to): float
    {
        $count = 0;
        $current = $from->copy();

        while ($current->lte($to)) {
            if (!$current->isWeekend()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }

    /**
     * Deduct leave credits when application is approved
     */
    public function deductLeaveCredits(LeaveApplication $application): bool
    {
        $employee = $application->employee;
        $leaveCard = $this->getOrCreateLeaveCard($employee);
        $leaveType = $application->leaveType;

        if (!$leaveType) {
            return false;
        }

        $days = $application->num_days;

        // Check if VL or SL
        if ($leaveType->code === 'VL') {
            if ($leaveCard->vl_balance < $days) {
                return false;
            }
            $leaveCard->vl_used += $days;
            $leaveCard->vl_balance = $leaveCard->vl_beginning_balance + $leaveCard->vl_earned - $leaveCard->vl_used;
        } elseif ($leaveType->code === 'SL') {
            if ($leaveCard->sl_balance < $days) {
                return false;
            }
            $leaveCard->sl_used += $days;
            $leaveCard->sl_balance = $leaveCard->sl_beginning_balance + $leaveCard->sl_earned - $leaveCard->sl_used;
        } elseif ($leaveType->code === 'FL') {
            if ($leaveCard->forced_leave_balance < $days) {
                return false;
            }
            $leaveCard->forced_leave_balance -= $days;
        } else {
            if ($leaveCard->special_leave_balance < $days) {
                return false;
            }
            $leaveCard->special_leave_balance -= $days;
        }

        $leaveCard->save();

        // Record transaction
        LeaveTransaction::create([
            'employee_id' => $employee->id,
            'leave_card_id' => $leaveCard->id,
            'leave_type_id' => $leaveType->id,
            'transaction_date' => now(),
            'transaction_type' => 'used',
            'days' => $days,
            'vl_balance_after' => $leaveCard->vl_balance,
            'sl_balance_after' => $leaveCard->sl_balance,
            'remarks' => "Leave Application #{$application->application_no} approved",
            'encoded_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Restore leave credits when approved application is deleted
     */
    public function restoreLeaveCredits(LeaveApplication $application): void
    {
        $employee = $application->employee;
        $leaveCard = $this->getOrCreateLeaveCard($employee);
        $leaveType = $application->leaveType;

        if (!$leaveType) {
            return;
        }

        $days = $application->num_days;

        if ($leaveType->code === 'VL') {
            $leaveCard->vl_used -= $days;
            $leaveCard->vl_balance = $leaveCard->vl_beginning_balance + $leaveCard->vl_earned - $leaveCard->vl_used;
        } elseif ($leaveType->code === 'SL') {
            $leaveCard->sl_used -= $days;
            $leaveCard->sl_balance = $leaveCard->sl_beginning_balance + $leaveCard->sl_earned - $leaveCard->sl_used;
        } elseif ($leaveType->code === 'FL') {
            $leaveCard->forced_leave_balance += $days;
        } else {
            $leaveCard->special_leave_balance += $days;
        }

        $leaveCard->save();

        // Record restore transaction
        LeaveTransaction::create([
            'employee_id' => $employee->id,
            'leave_card_id' => $leaveCard->id,
            'leave_type_id' => $leaveType->id,
            'transaction_date' => now(),
            'transaction_type' => 'restored',
            'days' => $days,
            'vl_balance_after' => $leaveCard->vl_balance,
            'sl_balance_after' => $leaveCard->sl_balance,
            'remarks' => "Leave Application #{$application->application_no} deleted/restored",
            'encoded_by' => auth()->id(),
        ]);
    }

    /**
     * Add monthly earned credits (1.25 VL + 1.25 SL per month for permanent employees)
     */
    public function addMonthlyCredits(?int $year = null, ?int $month = null): int
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;
        $count = 0;

        $employees = Employee::where('status', 'Active')
            ->where('employment_status', 'Permanent')
            ->get();

        foreach ($employees as $employee) {
            $leaveCard = $this->getOrCreateLeaveCard($employee, $year);

            $vlCredit = 1.250;
            $slCredit = 1.250;

            $leaveCard->vl_earned += $vlCredit;
            $leaveCard->sl_earned += $slCredit;
            $leaveCard->vl_balance = $leaveCard->vl_beginning_balance + $leaveCard->vl_earned - $leaveCard->vl_used;
            $leaveCard->sl_balance = $leaveCard->sl_beginning_balance + $leaveCard->sl_earned - $leaveCard->sl_used;
            $leaveCard->save();

            // Record earned transaction
            $vlType = LeaveType::where('code', 'VL')->first();
            $slType = LeaveType::where('code', 'SL')->first();

            if ($vlType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $vlType->id,
                    'transaction_date' => Carbon::createFromDate($year, $month, 1),
                    'transaction_type' => 'earned',
                    'days' => $vlCredit,
                    'vl_balance_after' => $leaveCard->vl_balance,
                    'sl_balance_after' => $leaveCard->sl_balance,
                    'remarks' => "Monthly VL credit for " . Carbon::createFromDate($year, $month, 1)->format('F Y'),
                    'encoded_by' => auth()->id(),
                ]);
            }

            if ($slType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $slType->id,
                    'transaction_date' => Carbon::createFromDate($year, $month, 1),
                    'transaction_type' => 'earned',
                    'days' => $slCredit,
                    'vl_balance_after' => $leaveCard->vl_balance,
                    'sl_balance_after' => $leaveCard->sl_balance,
                    'remarks' => "Monthly SL credit for " . Carbon::createFromDate($year, $month, 1)->format('F Y'),
                    'encoded_by' => auth()->id(),
                ]);
            }

            $count++;
        }

        return $count;
    }
}
