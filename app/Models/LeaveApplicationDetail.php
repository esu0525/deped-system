<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApplicationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'leave_application_id', 'leave_type_id',
        'inclusive_dates', 'other_type',
        'date_from', 'date_to', 'num_days',
        'is_with_pay',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'num_days' => 'float',
        'is_with_pay' => 'boolean',
    ];

    public function leaveApplication()
    {
        return $this->belongsTo(LeaveApplication::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }
}
