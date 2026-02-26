<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'role', 'is_active',
        'otp_code', 'otp_expires_at', 'otp_attempts',
        'last_login_at', 'last_login_ip', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Role helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
    public function isHrAdmin(): bool
    {
        return $this->role === 'hr_admin';
    }
    public function isEncoder(): bool
    {
        return $this->role === 'encoder';
    }
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }
        return $this->role === $roles;
    }

    public function canManageEmployees(): bool
    {
        return in_array($this->role, ['super_admin', 'hr_admin']);
    }

    public function canApproveLeave(): bool
    {
        return in_array($this->role, ['super_admin', 'hr_admin']);
    }

    public function canDeleteFinalized(): bool
    {
        return in_array($this->role, ['super_admin', 'hr_admin']);
    }

    // OTP helpers
    public function generateOtp(): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry = (int)config('app.otp_expiry_minutes', env('OTP_EXPIRY_MINUTES', 5));
        $this->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes($expiry),
            'otp_attempts' => 0,
        ]);
        return $otp;
    }

    public function isOtpValid(string $otp): bool
    {
        return $this->otp_code === $otp && $this->otp_expires_at && $this->otp_expires_at->isFuture();
    }

    public function clearOtp(): void
    {
        $this->update(['otp_code' => null, 'otp_expires_at' => null, 'otp_attempts' => 0]);
    }

    // Relationships
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }

    public function getRoleDisplayAttribute(): string
    {
        return match ($this->role) {
                'super_admin' => 'Super Admin',
                'hr_admin' => 'HR Admin',
                'encoder' => 'Encoder',
                'employee' => 'Employee',
                default => ucfirst($this->role),
            };
    }
}
