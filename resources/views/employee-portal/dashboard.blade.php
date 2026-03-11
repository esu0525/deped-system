@extends('layouts.app')

@section('header_title', 'My Dashboard')

@section('content')
<div class="animate-fade">
    {{-- Welcome Banner --}}
    <div class="card glass animate-fade" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%); color: white; padding: 36px; margin-bottom: 24px; position: relative; overflow: hidden;">
        <div style="position: absolute; top: -40px; right: -40px; width: 200px; height: 200px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -60px; right: 60px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
        <div style="position: relative; z-index: 1;">
            <h2 style="font-weight: 800; font-size: 1.6rem; margin: 0 0 6px;">
                <i class="fas fa-sun" style="margin-right: 8px; color: #fbbf24;"></i>
                Magandang Araw, {{ $employee->full_name }}!
            </h2>
            <p style="opacity: 0.85; font-size: 0.95rem; margin: 0;">
                {{ $employee->position ?? 'Employee' }} &mdash; {{ $employee->department->name ?? 'No Department' }}
            </p>
        </div>
    </div>

    {{-- Leave Balance Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px;">
        {{-- VL Balance --}}
        <div class="card glass animate-fade" style="padding: 28px; position: relative; overflow: hidden; border-left: 5px solid #3b82f6;">
            <div style="position: absolute; top: 12px; right: 16px; font-size: 2.5rem; opacity: 0.08; color: #3b82f6;">
                <i class="fas fa-umbrella-beach"></i>
            </div>
            <p style="font-size: 0.78rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 6px;">Vacation Leave</p>
            <h2 style="font-weight: 800; font-size: 2rem; color: #1e40af; margin: 0;">
                {{ $currentLeaveCard ? number_format($currentLeaveCard->vl_balance, 3) : '0.000' }}
            </h2>
            <p style="font-size: 0.78rem; color: var(--secondary); margin: 4px 0 0;">
                days remaining for {{ now()->year }}
            </p>
        </div>

        {{-- SL Balance --}}
        <div class="card glass animate-fade" style="padding: 28px; position: relative; overflow: hidden; border-left: 5px solid #10b981;">
            <div style="position: absolute; top: 12px; right: 16px; font-size: 2.5rem; opacity: 0.08; color: #10b981;">
                <i class="fas fa-heart-pulse"></i>
            </div>
            <p style="font-size: 0.78rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 6px;">Sick Leave</p>
            <h2 style="font-weight: 800; font-size: 2rem; color: #065f46; margin: 0;">
                {{ $currentLeaveCard ? number_format($currentLeaveCard->sl_balance, 3) : '0.000' }}
            </h2>
            <p style="font-size: 0.78rem; color: var(--secondary); margin: 4px 0 0;">
                days remaining for {{ now()->year }}
            </p>
        </div>

        {{-- Total Earned --}}
        <div class="card glass animate-fade" style="padding: 28px; position: relative; overflow: hidden; border-left: 5px solid #f59e0b;">
            <div style="position: absolute; top: 12px; right: 16px; font-size: 2.5rem; opacity: 0.08; color: #f59e0b;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <p style="font-size: 0.78rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 6px;">Total Earned (VL + SL)</p>
            <h2 style="font-weight: 800; font-size: 2rem; color: #92400e; margin: 0;">
                @php
                    $totalEarned = $currentLeaveCard
                        ? ($currentLeaveCard->vl_earned + $currentLeaveCard->sl_earned)
                        : 0;
                @endphp
                {{ number_format($totalEarned, 3) }}
            </h2>
            <p style="font-size: 0.78rem; color: var(--secondary); margin: 4px 0 0;">
                days earned this year
            </p>
        </div>

        {{-- Employment Info --}}
        <div class="card glass animate-fade" style="padding: 28px; position: relative; overflow: hidden; border-left: 5px solid #8b5cf6;">
            <div style="position: absolute; top: 12px; right: 16px; font-size: 2.5rem; opacity: 0.08; color: #8b5cf6;">
                <i class="fas fa-id-badge"></i>
            </div>
            <p style="font-size: 0.78rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; margin: 0 0 6px;">Employee ID</p>
            <h2 style="font-weight: 800; font-size: 1.5rem; color: #5b21b6; margin: 0;">
                {{ $employee->employee_id }}
            </h2>
            <p style="font-size: 0.78rem; color: var(--secondary); margin: 4px 0 0;">
                {{ $employee->employment_status ?? 'Permanent' }} &bull; {{ $employee->years_of_service }}
            </p>
        </div>
    </div>

    {{-- Main Content Row --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">


        {{-- Recent Activity History --}}
        <div class="card glass animate-fade" style="padding: 28px;">
            <h4 style="font-weight: 700; margin: 0 0 20px; font-size: 1rem;">
                <i class="fas fa-history text-primary" style="margin-right: 8px;"></i> Recent Activity
            </h4>
            @if($recentLogs->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    @foreach($recentLogs as $log)
                        <div style="display: flex; align-items: flex-start; gap: 12px; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: {{ str_contains($log->action, 'LOGIN') ? '#eff6ff' : '#f8fafc' }}; display: flex; align-items: center; justify-content: center; color: {{ str_contains($log->action, 'LOGIN') ? 'var(--primary)' : 'var(--secondary)' }}; flex-shrink: 0;">
                                <i class="fas {{ str_contains($log->action, 'LOGIN') ? 'fa-sign-in-alt' : (str_contains($log->action, 'VIEW') ? 'fa-eye' : 'fa-info-circle') }}"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 0.85rem; font-weight: 600; margin: 0; color: var(--dark);">
                                    {{ $log->description }}
                                </p>
                                <p style="font-size: 0.72rem; color: var(--secondary); margin: 2px 0 0;">
                                    {{ $log->created_at->format('M d, Y') }} at {{ $log->created_at->format('h:i A') }} &bull; IP: {{ $log->ip_address }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 30px; color: var(--secondary);">
                    <i class="fas fa-clock fa-2x" style="opacity: 0.3; margin-bottom: 10px;"></i>
                    <p style="font-weight: 600; margin: 0;">No activity recorded yet.</p>
                </div>
            @endif
        </div>

        {{-- Recent Leave Applications --}}
        <div class="card glass animate-fade" style="padding: 28px;">
            <h4 style="font-weight: 700; margin: 0 0 20px; font-size: 1rem;">
                <i class="fas fa-file-invoice text-primary" style="margin-right: 8px;"></i> My Leave History
            </h4>
            @if($recentApplications->count() > 0)
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($recentApplications as $app)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0;">
                            <div>
                                <p style="font-weight: 600; font-size: 0.88rem; margin: 0; color: var(--dark);">
                                    {{ $app->leaveType->name ?? 'Leave' }}
                                </p>
                                <p style="font-size: 0.75rem; color: var(--secondary); margin: 2px 0 0;">
                                    {{ $app->date_from?->format('M d') }} - {{ $app->date_to?->format('M d, Y') }} &bull; {{ $app->num_days }} day(s)
                                </p>
                            </div>
                            <span class="badge badge-{{ $app->status === 'Approved' ? 'success' : ($app->status === 'Pending' ? 'warning' : 'danger') }}" style="font-size: 0.72rem;">
                                {{ $app->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 30px; color: var(--secondary);">
                    <i class="fas fa-inbox fa-2x" style="opacity: 0.3; margin-bottom: 10px;"></i>
                    <p style="font-weight: 600; margin: 0;">No leave applications yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
