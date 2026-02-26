<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active', 'is_earnable', 'monthly_credit'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_earnable' => 'boolean',
        'monthly_credit' => 'decimal:2',
    ];

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveTransactions()
    {
        return $this->hasMany(LeaveTransaction::class);
    }
}
