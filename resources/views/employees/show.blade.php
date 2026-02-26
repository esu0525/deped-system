@extends('layouts.app')

@section('header_title', 'Employee Profile')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Profile Card -->
        <div class="card glass" style="text-align: center;">
            <img src="{{ $employee->profile_picture_url }}" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; border: 4px solid var(--primary);">
            <h4 style="font-weight: 800;">{{ $employee->full_name }}</h4>
            <p style="color: var(--secondary); font-weight: 600;">{{ $employee->position ?? 'No Position' }}</p>
            <span class="badge {{ $employee->status === 'Active' ? 'badge-success' : 'badge-danger' }}">{{ $employee->status }}</span>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

            <div style="text-align: left; font-size: 0.85rem;">
                <div style="margin-bottom: 12px;"><i class="fas fa-id-badge" style="width: 20px; color: var(--primary);"></i> <strong>{{ $employee->employee_id }}</strong></div>
                <div style="margin-bottom: 12px;"><i class="fas fa-building" style="width: 20px; color: var(--primary);"></i> {{ $employee->department->name ?? 'No Department' }}</div>
                <div style="margin-bottom: 12px;"><i class="fas fa-briefcase" style="width: 20px; color: var(--primary);"></i> {{ $employee->employment_status ?? 'N/A' }}</div>
                <div style="margin-bottom: 12px;"><i class="fas fa-calendar" style="width: 20px; color: var(--primary);"></i> Hired: {{ $employee->date_hired?->format('M d, Y') ?? 'N/A' }}</div>
                <div style="margin-bottom: 12px;"><i class="fas fa-envelope" style="width: 20px; color: var(--primary);"></i> {{ $employee->email ?? 'N/A' }}</div>
                <div style="margin-bottom: 12px;"><i class="fas fa-phone" style="width: 20px; color: var(--primary);"></i> {{ $employee->contact_number ?? 'N/A' }}</div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary" style="flex: 1;"><i class="fas fa-edit"></i> Edit</a>
                <a href="{{ route('leave-cards.show', $employee) }}" class="btn btn-secondary" style="flex: 1;"><i class="fas fa-address-card"></i> Ledger</a>
            </div>
        </div>

        <!-- Leave Info -->
        <div>
            @if($currentLeaveCard)
            <div class="card glass" style="margin-bottom: 20px;">
                <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-address-card text-primary"></i> Leave Credits ({{ now()->year }})</h5>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                    <div style="text-align: center; padding: 15px; background: #f0f9ff; border-radius: 12px;">
                        <small style="color: var(--secondary); font-weight: 600;">VL Balance</small>
                        <h3 style="font-weight: 800; color: var(--primary);">{{ number_format($currentLeaveCard->vl_balance, 3) }}</h3>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #f0fdf4; border-radius: 12px;">
                        <small style="color: var(--secondary); font-weight: 600;">SL Balance</small>
                        <h3 style="font-weight: 800; color: var(--success);">{{ number_format($currentLeaveCard->sl_balance, 3) }}</h3>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #fffbeb; border-radius: 12px;">
                        <small style="color: var(--secondary); font-weight: 600;">VL Used</small>
                        <h3 style="font-weight: 800; color: var(--warning);">{{ number_format($currentLeaveCard->vl_used, 3) }}</h3>
                    </div>
                    <div style="text-align: center; padding: 15px; background: #fef2f2; border-radius: 12px;">
                        <small style="color: var(--secondary); font-weight: 600;">SL Used</small>
                        <h3 style="font-weight: 800; color: var(--danger);">{{ number_format($currentLeaveCard->sl_used, 3) }}</h3>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Leave Applications -->
            <div class="card glass">
                <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-file-signature text-primary"></i> Recent Leave Applications</h5>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Days</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->leaveApplications->take(10) as $app)
                        <tr>
                            <td>{{ $app->leaveType->name ?? 'N/A' }}</td>
                            <td><small>{{ $app->date_from->format('M d') }} - {{ $app->date_to->format('M d, Y') }}</small></td>
                            <td>{{ $app->num_days }}</td>
                            <td>{!! $app->status_badge !!}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align: center; color: var(--secondary); padding: 20px;">No leave applications yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
