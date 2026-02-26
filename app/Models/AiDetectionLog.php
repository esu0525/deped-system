<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiDetectionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id', 'risk_score', 'risk_level',
        'suspicious_flags', 'generated_reason',
        'is_reviewed', 'reviewed_by', 'reviewed_at', 'date_generated',
    ];

    protected $casts = [
        'suspicious_flags' => 'array',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'date_generated' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class , 'reviewed_by');
    }

    public function getRiskBadgeAttribute(): string
    {
        return match ($this->risk_level) {
                'High' => '<span class="badge badge-danger">High Risk</span>',
                'Medium' => '<span class="badge badge-warning">Medium Risk</span>',
                default => '<span class="badge badge-success">Low Risk</span>',
            };
    }
}
