<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'leave_card_id', 'leave_type_id',
        'transaction_date', 'transaction_type', 'days',
        'vl_balance_after', 'sl_balance_after', 'remarks', 'encoded_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'days' => 'decimal:3',
        'vl_balance_after' => 'decimal:3',
        'sl_balance_after' => 'decimal:3',
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
