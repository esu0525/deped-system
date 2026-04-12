<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'user_id', 'category', 'full_name', 'suffix', 'gender',
        'position', 'access', 'employment_status', 'date_hired', 'email',
        'contact_number', 'address', 'profile_picture', 'status', 'department_id',
    ];

    protected $casts = [
        'date_hired' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->employee_id)) {
                $employee->employee_id = self::generateUniqueId();
            }
        });
    }

    private static function generateUniqueId()
    {
        do {
            $id = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('employee_id', $id)->exists());

        return $id;
    }

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

    // Helpers

    /**
     * Format the employee name as: Lastname, Firstname M.I. Suffix
     * Uses the linked user's individual name columns when available.
     * Falls back to the stored full_name column.
     */
    public function getFullNameAttribute(): string
    {
        // Use the user relationship if already eager-loaded (avoids N+1)
        $user = $this->relationLoaded('user') ? $this->user : null;

        if ($user && (!empty($user->last_name) || !empty($user->first_name))) {
            $lastName      = trim($user->last_name   ?? '');
            $firstName     = trim($user->first_name  ?? '');
            $middleName    = trim($user->middle_name  ?? '');
            $suffix        = trim($user->suffix       ?? '');

            $middleInitial = !empty($middleName)
                ? strtoupper(mb_substr($middleName, 0, 1)) . '.'
                : '';

            $name = $lastName;
            if (!empty($firstName))     $name .= ', ' . $firstName;
            if (!empty($middleInitial)) $name .= ' ' . $middleInitial;
            if (!empty($suffix))        $name .= ' ' . $suffix;

            return $name;
        }

        // Fallback: return the stored full_name from the DB column
        return $this->attributes['full_name'] ?? '';
    }

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

    public function ctoBalances()
    {
        return $this->leaveTransactions()
            ->whereNotNull('cto_title')
            ->where('cto_title', '!=', '')
            ->select('cto_title')
            ->selectRaw('SUM(CAST(COALESCE(cto_earned, 0) AS DECIMAL(10,3))) - SUM(CAST(COALESCE(cto_used, 0) AS DECIMAL(10,3))) as balance')
            ->groupBy('cto_title')
            ->having('balance', '>', 0)
            ->get();
    }
}
