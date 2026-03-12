<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'year',
        'vl_beginning_balance', 'sl_beginning_balance',
        'vl_earned', 'sl_earned',
        'vl_used', 'sl_used',
        'vl_balance', 'sl_balance',
        'forced_leave_balance', 'special_leave_balance',
    ];

    protected $casts = [
        'vl_beginning_balance' => 'float',
        'sl_beginning_balance' => 'float',
        'vl_earned' => 'float',
        'sl_earned' => 'float',
        'vl_used' => 'float',
        'sl_used' => 'float',
        'vl_balance' => 'float',
        'sl_balance' => 'float',
        'forced_leave_balance' => 'float',
        'special_leave_balance' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactions()
    {
        return $this->hasMany(LeaveTransaction::class);
    }

    public function recalculate(): void
    {
        $allTransactions = $this->transactions()
            ->with('leaveType')
            ->orderBy('id', 'asc')
            ->get();

        $runningVl = $this->vl_beginning_balance;
        $runningSl = $this->sl_beginning_balance;
        $vlUsedTotal = 0;
        $slUsedTotal = 0;
        $vlEarnedTotal = 0;
        $slEarnedTotal = 0;

        foreach ($allTransactions as $tx) {
            // Credits
            $vlEarned = (float)($tx->vl_earned ?? 0);
            $slEarned = (float)($tx->sl_earned ?? 0);
            
            // Deductions
            $vlUsed = (float)($tx->vl_used ?? 0);
            $slUsed = (float)($tx->sl_used ?? 0);

            // Fallback for old system-generated/non-grid data if needed
            if ($vlUsed == 0 && $slUsed == 0 && $vlEarned == 0 && $slEarned == 0) {
                $code = $tx->leaveType ? $tx->leaveType->code : '';
                $days = (float)($tx->days ?? 0);
                if ($tx->transaction_type === 'earned') {
                    if ($code === 'VL') { $vlEarned = $days; }
                    elseif ($code === 'SL') { $slEarned = $days; }
                } elseif ($tx->transaction_type === 'used') {
                    if (in_array($code, ['VL', 'FL'])) { $vlUsed = $days; }
                    elseif ($code === 'SL') { $slUsed = $days; }
                }
            }

            $hasVlActivity = ($vlEarned != 0 || $vlUsed != 0);
            $hasSlActivity = ($slEarned != 0 || $slUsed != 0);

            $runningVl += $vlEarned - $vlUsed;
            $runningSl += $slEarned - $slUsed;
            
            $vlEarnedTotal += $vlEarned;
            $slEarnedTotal += $slEarned;
            $vlUsedTotal += $vlUsed;
            $slUsedTotal += $slUsed;

            $tx->update([
                'vl_balance_after' => $hasVlActivity ? $runningVl : null,
                'sl_balance_after' => $hasSlActivity ? $runningSl : null,
            ]);
        }

        $this->update([
            'vl_earned' => $vlEarnedTotal,
            'sl_earned' => $slEarnedTotal,
            'vl_used' => $vlUsedTotal,
            'sl_used' => $slUsedTotal,
            'vl_balance' => $runningVl,
            'sl_balance' => $runningSl,
        ]);
    }
}
