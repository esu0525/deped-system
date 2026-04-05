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
    public function deductLeaveCredits(LeaveApplication $application, ?string $remarks = null): bool
    {
        $employee = $application->employee;
        $leaveCard = $this->getOrCreateLeaveCard($employee);

        // Load details with leave types
        $application->load('details.leaveType');

        // Check if this is a monetization application
        $monetType = null;
        foreach ($application->details as $detail) {
            if ($detail->leaveType && stripos($detail->leaveType->name, '50% Monetization') !== false) {
                $monetType = '50%';
                break;
            } else if ($detail->leaveType && stripos($detail->leaveType->name, 'CTO') !== false) {
                $monetType = 'CTO';
                break;
            }
        }

        if ($monetType === '50%') {
            return $this->processMonetization($application, $leaveCard, $remarks);
        } else if ($monetType === '10-30') {
            return $this->processMonetization10to30($application, $leaveCard, $remarks);
        } else if ($monetType === 'CTO') {
            return $this->processCto($application, $leaveCard, $remarks);
        }

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
        $approverName = $remarks ?: (auth()->user()->employee ? auth()->user()->employee->full_name : auth()->user()->name);
        $approverSign = explode(' ', trim($approverName))[0];

        // Use local running balances
        $runningVl = floatval($leaveCard->vl_balance);
        $runningSl = floatval($leaveCard->sl_balance);

        $totalVlUsed = null;
        $totalSlUsed = null;
        $totalVlWop = null;
        $totalSlWop = null;
        $vlWopReasons = [];
        $slWopReasons = [];
        $periodStrings = [];
        $remarksParts = [];

        foreach ($application->details as $detail) {
            $type = $detail->leaveType;
            if (!$type) continue;
            $days = floatval($detail->num_days);
            $isWop = !$detail->is_with_pay;

            if ($type->code === 'VL' || $type->code === 'FL') {
                if ($isWop) {
                    $totalVlWop = ($totalVlWop ?? 0) + $days;
                    if ($detail->lwop_reason) $vlWopReasons[] = $detail->lwop_reason;
                } else {
                    $runningVl -= $days;
                    $leaveCard->vl_used += $days;
                    if ($type->code === 'FL') {
                        $leaveCard->forced_leave_balance -= $days;
                    }
                    $totalVlUsed = ($totalVlUsed ?? 0) + $days;
                }
            } elseif ($type->code === 'SL') {
                if ($isWop) {
                    $totalSlWop = ($totalSlWop ?? 0) + $days;
                    if ($detail->lwop_reason) $slWopReasons[] = $detail->lwop_reason;
                } else {
                    $runningSl -= $days;
                    $leaveCard->sl_used += $days;
                    $totalSlUsed = ($totalSlUsed ?? 0) + $days;
                }
            } else {
                if (!$isWop) {
                    $leaveCard->special_leave_balance -= $days;
                }
            }

            $per = $detail->inclusive_dates ?? date('n/j/y', strtotime($detail->date_from ?? $application->date_from));
            $periodStrings[] = $per;
            $standardCodes = ['VL', 'SL', 'FL', 'SPL', 'ML', 'PL', 'SoP', 'STL', 'VAWC', 'RL', 'SLW', 'SEC'];
            $remarksParts[] = in_array($type->code, $standardCodes) ? $type->code : $type->name;
        }

        // Combine period
        $uniquePeriods = array_unique($periodStrings);
        $combinedPeriod = count($uniquePeriods) > 1 ? implode(' - ', $uniquePeriods) : ($uniquePeriods[0] ?? '');
        $combinedRemarks = implode('/', array_unique($remarksParts));
        
        // Combine Reasons
        $combinedVlWopReason = implode(', ', array_unique($vlWopReasons));
        $combinedSlWopReason = implode(', ', array_unique($slWopReasons));

        LeaveTransaction::create([
            'employee_id' => $employee->id,
            'leave_card_id' => $leaveCard->id,
            'leave_application_id' => $application->id,
            'leave_type_id' => $application->details->first()->leave_type_id,
            'transaction_date' => $application->date_from ?? now(),
            'period' => "LESS: {$combinedPeriod}",
            'transaction_type' => 'used',
            'days' => $application->num_days,
            'vl_used' => $totalVlUsed,
            'sl_used' => $totalSlUsed,
            'vl_wop' => $totalVlWop,
            'vl_wop_reason' => $combinedVlWopReason ?: null,
            'sl_wop' => $totalSlWop,
            'sl_wop_reason' => $combinedSlWopReason ?: null,
            'vl_balance_after' => ($totalVlUsed !== null) ? $runningVl : null,
            'sl_balance_after' => ($totalSlUsed !== null) ? $runningSl : null,
            'remarks' => $combinedRemarks,
            'action_taken' => $approverSign . ' ' . now()->format('m/d/Y'),
            'encoded_by' => auth()->id(),
        ]);

        // Update final balances
        $leaveCard->vl_balance = $runningVl;
        $leaveCard->sl_balance = $runningSl;
        $leaveCard->save();

        return true;
    }

    /**
     * Special handling for 50% Monetization
     */
    protected function processMonetization(LeaveApplication $application, LeaveCard $leaveCard, ?string $remarks = null): bool
    {
        $approverName = $remarks ?: (auth()->user()->employee ? auth()->user()->employee->full_name : auth()->user()->name);
        $approverSign = explode(' ', trim($approverName))[0];

        $vlCurrent = floatval($leaveCard->vl_balance);
        $slCurrent = floatval($leaveCard->sl_balance);

        $vlHalf = $vlCurrent / 2;
        $slHalf = $slCurrent / 2;
        $totalHalves = $vlHalf + $slHalf;

        // Perform deduction
        $leaveCard->vl_used += $vlHalf;
        $leaveCard->sl_used += $slHalf;
        $leaveCard->vl_balance = $vlCurrent - $vlHalf;
        $leaveCard->sl_balance = $slCurrent - $slHalf;
        $leaveCard->save();

        $year = $application->date_from ? $application->date_from->year : now()->year;
        $monetizationPeriod = "LESS: 50% Monetization {$year}";

        LeaveTransaction::create([
            'employee_id' => $leaveCard->employee_id,
            'leave_card_id' => $leaveCard->id,
            'leave_application_id' => $application->id,
            'leave_type_id' => $application->leave_type_id,
            'transaction_date' => $application->date_from ?? now(),
            'period' => $monetizationPeriod,
            'transaction_type' => 'used',
            'days' => $totalHalves,
            'vl_used' => $vlHalf,
            'sl_used' => $slHalf,
            'vl_balance_after' => $leaveCard->vl_balance,
            'sl_balance_after' => $leaveCard->sl_balance,
            'sl_wop' => $totalHalves, // As requested: total goes to column beside action
            'remarks' => '',
            'action_taken' => $approverSign . ' ' . now()->format('m/d/Y'),
            'encoded_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Process logic for "10-30 Days Monetization" (Subtract from VL only, SL shows hyphen)
     */
    protected function processMonetization10to30(LeaveApplication $application, LeaveCard $leaveCard, ?string $remarks = null): bool
    {
        $approverName = $remarks ?: (auth()->user()->employee ? auth()->user()->employee->full_name : auth()->user()->name);
        $approverSign = explode(' ', trim($approverName))[0];

        $totalDays = 0;
        foreach ($application->details as $detail) {
            $totalDays += floatval($detail->num_days);
        }

        if ($totalDays <= 0) return false;

        $vlCurrent = $leaveCard->vl_balance;
        
        // Deduct only from VL
        $leaveCard->vl_balance = $vlCurrent - $totalDays;
        // SL balance remains unchanged but we'll set it to null in the transaction to show hyphen
        $leaveCard->save();

        $year = $application->date_from ? $application->date_from->year : now()->year;
        $monetizationPeriod = "LESS: 10-30 Days Monetization {$year}";

        LeaveTransaction::create([
            'employee_id' => $leaveCard->employee_id,
            'leave_card_id' => $leaveCard->id,
            'leave_application_id' => $application->id,
            'leave_type_id' => $application->leave_type_id,
            'transaction_date' => $application->date_from ?? now(),
            'period' => $monetizationPeriod,
            'transaction_type' => 'used',
            'days' => $totalDays,
            'vl_used' => $totalDays,
            'sl_used' => null,
            'vl_balance_after' => $leaveCard->vl_balance,
            'sl_balance_after' => null, // This ensures hyphen in SL column
            'sl_wop' => null, // No total for 10-30 days as requested
            'remarks' => '',
            'action_taken' => $approverSign . ' ' . now()->format('m/d/Y'),
            'encoded_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Process logic for "CTO" (Add then Less)
     */
    protected function processCto(LeaveApplication $application, LeaveCard $leaveCard, ?string $remarks = null): bool
    {
        $approverName = $remarks ?: (auth()->user()->employee ? auth()->user()->employee->full_name : auth()->user()->name);
        $approverSign = explode(' ', trim($approverName))[0];

        foreach ($application->details as $detail) {
            $formattedDates = $detail->inclusive_dates ? $detail->inclusive_dates : "";
            $title = trim($formattedDates . ' ' . ($detail->cto_title ?? '')) ?: 'Untitled';
            $earned = floatval($detail->cto_earned_days);
            $used = floatval($detail->num_days);

            // 1. ADD row (only if new credits are being added)
            if ($earned > 0) {
                $leaveCard->cto_balance += $earned;
                $leaveCard->save();

                LeaveTransaction::create([
                    'employee_id' => $leaveCard->employee_id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_application_id' => $application->id,
                    'leave_type_id' => $detail->leave_type_id,
                    'transaction_date' => $application->date_from ?? now(),
                    'period' => "ADD: CTO: {$title}",
                    'cto_title' => $detail->cto_title ?? 'Untitled',
                    'transaction_type' => 'earned',
                    'days' => $earned,
                    'cto_earned' => $earned,
                    'cto_balance_after' => $leaveCard->cto_balance,
                    'action_taken' => $approverSign . ' ' . now()->format('m/d/Y'),
                    'encoded_by' => auth()->id(),
                ]);
            }

            // 2. LESS row
            if ($used > 0) {
                $leaveCard->cto_balance -= $used;
                $leaveCard->save();

                LeaveTransaction::create([
                    'employee_id' => $leaveCard->employee_id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_application_id' => $application->id,
                    'leave_type_id' => $detail->leave_type_id,
                    'transaction_date' => $application->date_from ?? now(),
                    'period' => "LESS: CTO: {$title}",
                    'cto_title' => $detail->cto_title ?? 'Untitled',
                    'transaction_type' => 'used',
                    'days' => $used,
                    'cto_used' => $used,
                    'cto_balance_after' => $leaveCard->cto_balance,
                    'action_taken' => $approverSign . ' ' . now()->format('m/d/Y'),
                    'encoded_by' => auth()->id(),
                ]);
            }
        }

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
            if (!$type) continue;
            $days = floatval($detail->num_days);

            if ($detail->is_with_pay) {
                if ($type->code === 'VL' || $type->code === 'FL') {
                    $leaveCard->vl_used -= $days;
                } elseif ($type->code === 'SL') {
                    $leaveCard->sl_used -= $days;
                }
            }
        }

        // Delete associated transaction
        LeaveTransaction::where('leave_application_id', $application->id)->delete();

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
