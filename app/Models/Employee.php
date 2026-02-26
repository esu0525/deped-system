<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'user_id', 'department_id', 'full_name', 'gender',
        'position', 'employment_status', 'date_hired', 'email',
        'contact_number', 'address', 'profile_picture', 'status',
    ];

    protected $casts = [
        'date_hired' => 'date',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function leaveCards()
    {
        return $this->hasMany(LeaveCard::class);
    }

    public function currentLeaveCard()
    {
        return $this->hasOne(LeaveCard::class)->where('year', now()->year);
    }

    public function leaveApplications()
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveTransactions()
    {
        return $this->hasMany(LeaveTransaction::class);
    }

    public function aiDetectionLogs()
    {
        return $this->hasMany(AiDetectionLog::class)->latest();
    }

    public function latestAiLog()
    {
        return $this->hasOne(AiDetectionLog::class)->latest();
    }

    // Helpers
    public function getProfilePictureUrlAttribute(): string
    {
        if ($this->profile_picture && file_exists(storage_path('app/public/' . $this->profile_picture))) {
            return asset('storage/' . $this->profile_picture);
        }
        return asset('images/default-avatar.png');
    }

    public function getYearsOfServiceAttribute(): string
    {
        if (!$this->date_hired)
            return 'N/A';
        return $this->date_hired->diffForHumans(now(), true);
    }
}
