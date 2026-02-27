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

        // Load details with leave types
        $application->load('details.leaveType');

        // Check if there are sufficient credits for ALL entries first
        foreach ($application->details as $detail) {
            $type = $detail->leaveType;
            if (!$type)
                continue;

            $days = floatval($detail->num_days);
            if ($type->code === 'VL' || $type->code === 'FL') {
                if ($leaveCard->vl_balance < $days)
                    return false;
            }
            elseif ($type->code === 'SL') {
                if ($leaveCard->sl_balance < $days)
                    return false;
            }
        }

        // Get approver info for action taken
        $approverName = auth()->user()->employee ? auth()->user()->employee->full_name : auth()->user()->name;

        // Use local running balances for progressive saving per transaction
        $runningVl = floatval($leaveCard->vl_balance);
        $runningSl = floatval($leaveCard->sl_balance);

        // Now perform the ACTUAL deduction and record transactions
        foreach ($application->details as $detail) {
            $type = $detail->leaveType;
            if (!$type)
                continue;
            $days = floatval($detail->num_days);

            $vlUsed = null;
            $slUsed = null;

            if ($type->code === 'VL' || $type->code === 'FL') {
                $runningVl -= $days;
                $leaveCard->vl_used += $days;
                if ($type->code === 'FL') {
                    $leaveCard->forced_leave_balance -= $days;
                }
                $vlUsed = $days;
            }
            elseif ($type->code === 'SL') {
                $runningSl -= $days;
                $leaveCard->sl_used += $days;
                $slUsed = $days;
            }
            else {
                $leaveCard->special_leave_balance -= $days;
            }

            $periodDates = date('n/j/y', strtotime($detail->date_from ?? $application->date_from));
            if ($detail->inclusive_dates) {
                // if they typed '1/21/26', output 'LESS: 1/21/26'
                $periodDates = $detail->inclusive_dates;
            }

            LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_card_id' => $leaveCard->id,
                'leave_type_id' => $type->id,
                'transaction_date' => now(),
                'period' => "LESS: {$periodDates}",
                'transaction_type' => 'used',
                'days' => $days,
                'vl_used' => $vlUsed,
                'sl_used' => $slUsed,
                'vl_balance_after' => $runningVl,
                'sl_balance_after' => $runningSl,
                'remarks' => $type->code,
                'action_taken' => explode(' ', trim($approverName))[0] . ' ' . now()->format('m/d/Y'),
                'encoded_by' => auth()->id(),
            ]);
        }

        // Update final balances
        $leaveCard->vl_balance = $leaveCard->vl_beginning_balance + $leaveCard->vl_earned - $leaveCard->vl_used;
        $leaveCard->sl_balance = $leaveCard->sl_beginning_balance + $leaveCard->sl_earned - $leaveCard->sl_used;
        $leaveCard->save();

        return true;
    }

    /**
     * Restore leave credits when approved application is deleted
     */
    public function restoreLeaveCredits(LeaveApplication $application): void
    {
        $employee = $application->employee;
        $leaveCard = $this->getOrCreateLeaveCard($employee);
        $application->load('details.leaveType');

        foreach ($application->details as $detail) {
            $type = $detail->leaveType;
            if (!$type)
                continue;
            $days = floatval($detail->num_days);

            if ($type->code === 'VL' || $type->code === 'FL') {
                $leaveCard->vl_used -= $days;
            }
            elseif ($type->code === 'SL') {
                $leaveCard->sl_used -= $days;
            }

            LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_card_id' => $leaveCard->id,
                'leave_type_id' => $type->id,
                'transaction_date' => now(),
                'transaction_type' => 'restored',
                'days' => $days,
                'remarks' => "Restored: Leave App #{$application->application_no} ({$type->code})",
                'encoded_by' => auth()->id(),
            ]);
        }

        $leaveCard->vl_balance = $leaveCard->vl_beginning_balance + $leaveCard->vl_earned - $leaveCard->vl_used;
        $leaveCard->sl_balance = $leaveCard->sl_beginning_balance + $leaveCard->sl_earned - $leaveCard->sl_used;
        $leaveCard->save();
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

            // Compute month span: '1/1-31/26'
            $periodStr = "ADD: " . Carbon::createFromDate($year, $month, 1)->format('n/1-t/y');

            $targetMonthStr = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            if ($vlType || $slType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $vlType ? $vlType->id : ($slType ? $slType->id : null),
                    'transaction_date' => $targetMonthStr,
                    'period' => $periodStr,
                    'transaction_type' => 'earned',
                    'days' => $vlCredit,
                    'vl_earned' => $vlCredit,
                    'sl_earned' => $slCredit,
                    'vl_balance_after' => $leaveCard->vl_balance,
                    'sl_balance_after' => $leaveCard->sl_balance,
                    'remarks' => '',
                    'encoded_by' => auth()->id(),
                ]);
            }

            $count++;
        }

        return $count;
    }
}
