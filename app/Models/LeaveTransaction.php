<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_card_id', 'leave_type_id',
        'transaction_date',
        'period',
        'transaction_type', 'days',
        'vl_balance_after', 'sl_balance_after', 'remarks', 'encoded_by',
        'vl_earned', 'vl_used', 'vl_wop',
        'sl_earned', 'sl_used', 'sl_wop',
        'action_taken'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'days' => 'float',
        'vl_balance_after' => 'float',
        'sl_balance_after' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveCard()
    {
        return $this->belongsTo(LeaveCard::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function encoder()
    {
        return $this->belongsTo(User::class , 'encoded_by');
    }
}
