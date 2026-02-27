<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_no', 'employee_id', 'leave_type_id', 'date_filed',
        'date_from', 'date_to', 'num_days', 'reason', 'attachment',
        'status', 'approved_by', 'approved_at', 'remarks', 'encoded_by',
        'other_leave_type',
        // 6.B Details of Leave
        'leave_location', 'leave_location_detail',
        'sick_leave_type', 'sick_leave_detail',
        'women_leave_detail', 'study_leave_type', 'other_leave_detail',
        // 6.D Commutation
        'commutation', 'salary',
        // 7.A Certification of Leave Credits
        'cert_vl_total_earned', 'cert_vl_less_this', 'cert_vl_balance',
        'cert_sl_total_earned', 'cert_sl_less_this', 'cert_sl_balance',
    ];

    protected $casts = [
        'date_filed' => 'date',
        'date_from' => 'date',
        'date_to' => 'date',
        'approved_at' => 'datetime',
        'num_days' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class , 'approved_by');
    }

    public function encoder()
    {
        return $this->belongsTo(User::class , 'encoded_by');
    }

    public function details()
    {
        return $this->hasMany(LeaveApplicationDetail::class);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
                'Approved' => '<span class="badge badge-success">Approved</span>',
                'Rejected' => '<span class="badge badge-danger">Rejected</span>',
                'Cancelled' => '<span class="badge badge-secondary">Cancelled</span>',
                default => '<span class="badge badge-warning">Pending</span>',
            };
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->application_no)) {
                $model->application_no = 'LA-' . date('Y') . '-' . str_pad(
                    (static::whereYear('created_at', date('Y'))->count() + 1),
                    5, '0', STR_PAD_LEFT
                );
            }
        });
    }
}
