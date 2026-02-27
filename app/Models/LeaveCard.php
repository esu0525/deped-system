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
        return $this->hasMany(LeaveTransaction::class)->orderBy('transaction_date');
    }

    public function recalculate(): void
    {
        $used = $this->transactions()
            ->where('transaction_type', 'used')
            ->get();

        $vlUsed = $used->where('leaveType.code', 'VL')->sum('days');
        $slUsed = $used->where('leaveType.code', 'SL')->sum('days');

        $earned = $this->transactions()
            ->where('transaction_type', 'earned')
            ->get();

        $vlEarned = $earned->where('leaveType.code', 'VL')->sum('days');
        $slEarned = $earned->where('leaveType.code', 'SL')->sum('days');

        $this->update([
            'vl_earned' => $vlEarned,
            'sl_earned' => $slEarned,
            'vl_used' => $vlUsed,
            'sl_used' => $slUsed,
            'vl_balance' => $this->vl_beginning_balance + $vlEarned - $vlUsed,
            'sl_balance' => $this->sl_beginning_balance + $slEarned - $slUsed,
        ]);
    }
}
