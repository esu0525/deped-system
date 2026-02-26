<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_no', 'employee_id', 'leave_type_id',
        'date_from', 'date_to', 'num_days', 'reason', 'attachment',
        'status', 'approved_by', 'approved_at', 'remarks', 'encoded_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'approved_at' => 'datetime',
        'num_days' => 'decimal:3',
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
