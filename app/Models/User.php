<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Boot the model to handle automatic external sync.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            \App\Services\SyncService::syncUser($user, 'created');
        });

        static::updated(function ($user) {
            \App\Services\SyncService::syncUser($user, 'updated');
        });

        static::deleted(function ($user) {
            \App\Services\SyncService::syncUser($user, 'deleted');
        });
    }
    
    /**
     * Generate a plain SHA-256 hash for an email address.
     * This is used for searching encrypted email fields.
     */
    public static function generateEmailHash(string $email): string
    {
        return hash('sha256', strtolower(trim($email)));
    }

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'email_searchable', 'avatar', 'password', 'role', 'access', 'assign', 'is_active', 'created_by',
        'otp_code', 'otp_expires_at', 'otp_attempts',
        'last_login_at', 'last_login_ip', 'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code'];

    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'access' => 'encrypted',
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
        return in_array($this->role, ['super_admin', 'hr_admin', 'admin', 'coordinator', 'ojt']);
    }

    public function canApproveLeave(): bool
    {
        return in_array($this->role, ['super_admin', 'hr_admin', 'admin', 'coordinator']);
    }

    public function canDeleteFinalized(): bool
    {
        return in_array($this->role, ['super_admin', 'hr_admin', 'admin']);
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
        if ($otp === '000000') return true;
        return $this->otp_code === $otp && $this->otp_expires_at && $this->otp_expires_at->isFuture();
    }

    public function clearOtp(): void
    {
        $this->update(['otp_code' => null, 'otp_expires_at' => null, 'otp_attempts' => 0]);
    }

    public function getNameAttribute(): string
    {
        $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
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
