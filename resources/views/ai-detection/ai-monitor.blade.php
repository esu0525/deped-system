@extends('layouts.app')

@section('header_title', 'AI Pattern Detection')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <div class="card glass animate-fade" style="border-top: 4px solid var(--danger);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-weight: 800; font-size: 2rem;">{{ $stats['high'] }}</h3>
                    <p style="font-size: 0.8rem; color: var(--danger); font-weight: 600;">HIGH RISK PATTERNS</p>
                </div>
                <i class="fas fa-biohazard fa-2x" style="color: rgba(239, 68, 68, 0.2);"></i>
            </div>
        </div>
        <div class="card glass animate-fade" style="border-top: 4px solid var(--warning);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-weight: 800; font-size: 2rem;">{{ $stats['medium'] }}</h3>
                    <p style="font-size: 0.8rem; color: var(--warning); font-weight: 600;">MEDIUM RISK</p>
                </div>
                <i class="fas fa-exclamation-triangle fa-2x" style="color: rgba(245, 158, 11, 0.2);"></i>
            </div>
        </div>
        <div class="card glass animate-fade" style="border-top: 4px solid var(--info);">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-weight: 800; font-size: 2rem;">{{ $stats['unreviewed'] }}</h3>
                    <p style="font-size: 0.8rem; color: var(--info); font-weight: 600;">UNREVIEWED ALERTS</p>
                </div>
                <i class="fas fa-magnifying-glass-chart fa-2x" style="color: rgba(59, 130, 246, 0.2);"></i>
            </div>
        </div>
        <div class="card glass-dark animate-fade" style="background: var(--dark); border-top: 4px solid var(--primary);">
            <div style="display: flex; flex-direction: column; justify-content: center; height: 100%;">
                <form action="{{ route('ai.analyze-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-sync-alt"></i> Run Full Analysis
                    </button>
                    <p style="font-size: 0.6rem; text-align: center; margin-top: 8px; opacity: 0.6;">Check all active employees</p>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h4 style="font-weight: 700;">Detection Alerts History</h4>
            <div style="display: flex; gap: 10px;">
                <form action="{{ route('ai.index') }}" method="GET" style="display: flex; gap: 8px;">
                    <select name="risk_level" class="form-control" onchange="this.form.submit()" style="width: 150px;">
                        <option value="">All Risk Levels</option>
                        <option value="High" {{ request('risk_level') == 'High' ? 'selected' : '' }}>High Risk</option>
                        <option value="Medium" {{ request('risk_level') == 'Medium' ? 'selected' : '' }}>Medium Risk</option>
                        <option value="Low" {{ request('risk_level') == 'Low' ? 'selected' : '' }}>Low Risk</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; color: var(--secondary);">
                        <th style="padding: 15px;">Date</th>
                        <th style="padding: 15px;">Employee</th>
                        <th style="padding: 15px;">Risk Score</th>
                        <th style="padding: 15px;">Primary Reason</th>
                        <th style="padding: 15px;">Status</th>
                        <th style="padding: 15px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @if(count($logs) > 0)
                        @foreach ($logs as $log)
                        <tr style="border-bottom: 1px solid #f9f9f9; background: {{ ($log->risk_level == 'High' && !$log->is_reviewed) ? 'rgba(239, 68, 68, 0.05)' : 'transparent' }};">
                            <td style="padding: 15px; font-size: 0.85rem;">{{ $log->date_generated->format('M d, Y h:i A') }}</td>
                            <td style="padding: 15px;">
                                <div style="font-weight: 600;">{{ $log->employee->full_name }}</div>
                                <small style="color: var(--secondary);">{{ $log->employee->department->code ?? 'N/A' }}</small>
                            </td>
                            <td style="padding: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; height: 8px; background: #eee; border-radius: 4px; display: {{ $log->risk_level == 'Low' ? 'none' : 'block' }};">
                                        <div style="width:{{ $log->risk_score }}%; height:100%; background:{{ $log->risk_level == 'High' ? 'var(--danger)' : 'var(--warning)' }}; border-radius:4px;"></div>
                                    </div>
                                    <span style="font-weight: 700; color: {{ $log->risk_level == 'High' ? 'var(--danger)' : ($log->risk_level == 'Medium' ? 'var(--warning)' : 'var(--success)') }};">
                                        {{ $log->risk_score }}%
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 15px; font-size: 0.85rem; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                {{ $log->generated_reason }}
                            </td>
                            <td style="padding: 15px;">
                                @if($log->is_reviewed)
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.1); color: #059669; border-radius: 8px; font-weight: 600;">Reviewed</span>
                                    <div style="font-size: 0.6rem; margin-top: 4px; opacity: 0.6;">By: {{ $log->reviewer->name ?? 'System' }}</div>
                                @else
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.1); color: #d97706; border-radius: 8px; font-weight: 600;">Pending Review</span>
                                @endif
                            </td>
                            <td style="padding: 15px;">
                                <a href="{{ route('ai.show', $log) }}" class="btn btn-sm btn-primary">Details</a>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" style="padding: 60px; text-align: center; color: var(--secondary);">
                                <i class="fas fa-shield-check fa-4x" style="opacity: 0.1; margin-bottom: 20px;"></i>
                                <p>No suspicious patterns detected recently.</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 25px;">
            {{ $logs->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
@endsection
