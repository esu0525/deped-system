@extends('layouts.app')

@section('header_title', 'System Dashboard')

@section('content')
<div class="animate-fade">
    <!-- Stat Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 30px;">
        <div class="card stat-card" style="border-left: 5px solid var(--primary);">
            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: var(--primary);">
                <i class="fas fa-users-viewfinder"></i>
            </div>
            <div>
                <p style="font-size: 0.9rem; color: var(--secondary); margin-bottom: 4px;">Total Employees</p>
                <h3 style="font-size: 1.8rem; font-weight: 700;">{{ $totalEmployees }}</h3>
            </div>
        </div>

        <div class="card stat-card" style="border-left: 5px solid var(--info);">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--info);">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <p style="font-size: 0.9rem; color: var(--secondary); margin-bottom: 4px;">Leave Applications</p>
                <h3 style="font-size: 1.8rem; font-weight: 700;">{{ $totalApplications }}</h3>
            </div>
        </div>

        <div class="card stat-card" style="border-left: 5px solid var(--warning);">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <p style="font-size: 0.9rem; color: var(--secondary); margin-bottom: 4px;">Pending Requests</p>
                <h3 style="font-size: 1.8rem; font-weight: 700;">{{ $pendingCount }}</h3>
            </div>
        </div>

        <div class="card stat-card" style="border-left: 5px solid var(--danger);">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <p style="font-size: 0.9rem; color: var(--secondary); margin-bottom: 4px;">AI Alerts</p>
                <h3 style="font-size: 1.8rem; font-weight: 700;">{{ $suspiciousAlerts->count() }}</h3>
            </div>
        </div>
    </div>

    <!-- Charts & Tables -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        <!-- Monthly Summary Chart -->
        <div class="card" style="height: 450px; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="font-weight: 700; margin: 0;">Monthly Leave Usage</h4>
                <div class="badge badge-light">Last 6 Months</div>
            </div>
            <div style="flex: 1; position: relative;">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card" style="height: 450px; display: flex; flex-direction: column;">
            <h4 style="font-weight: 700; margin-bottom: 25px;">Recent Activity Logs</h4>
            <div class="activity-feed" style="flex: 1; overflow-y: auto; padding-right: 5px;">
                @foreach($recentActivity as $log)
                <div style="display: flex; gap: 15px; margin-bottom: 20px; position: relative; padding-left: 20px;">
                    <div style="position: absolute; left: 0; top: 5px; width: 10px; height: 10px; border-radius: 50%; background: var(--primary);"></div>
                    <div style="position: absolute; left: 4.5px; top: 15px; bottom: -20px; width: 1px; background: #eee;"></div>
                    <div>
                        <p style="font-size: 0.85rem; margin: 0; font-weight: 600; color: var(--dark);">{{ $log->action }}</p>
                        <p style="font-size: 0.8rem; opacity: 0.8; margin: 2px 0;">{{ $log->description }}</p>
                        <small style="font-size: 0.7rem; color: var(--secondary); display: flex; align-items: center; gap: 4px;">
                            <i class="far fa-clock" style="font-size: 0.65rem;"></i> {{ $log->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- AI Alerts Table -->
    @if($suspiciousAlerts->count() > 0)
    <div class="card" style="margin-top: 24px; border: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.02);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="font-weight: 700; color: var(--danger);"><i class="fas fa-robot"></i> AI Suspicious Pattern Alerts</h4>
            <a href="{{ route('ai.index') }}" class="btn btn-sm btn-outline-danger">View All Alerts</a>
        </div>
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #eee;">
                        <th style="padding: 12px;">Employee</th>
                        <th style="padding: 12px;">Risk Score</th>
                        <th style="padding: 12px;">Reason</th>
                        <th style="padding: 12px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suspiciousAlerts as $alert)
                    <tr style="border-bottom: 1px solid #f9f9f9;">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                    {{ substr($alert->employee->full_name, 0, 1) }}
                                </div>
                                <span style="font-weight: 600;">{{ $alert->employee->full_name }}</span>
                            </div>
                        </td>
                        <td style="padding: 12px;">
                            <div style="width: 100%; height: 8px; background: #eee; border-radius: 4px; overflow: hidden; margin-bottom: 5px;">
                                <div style="width: {{ $alert->risk_score }}%; height: 100%; background: var(--danger);"></div>
                            </div>
                            <small style="font-weight: 700; color: var(--danger);">{{ $alert->risk_score }}% {{ $alert->risk_level }}</small>
                        </td>
                        <td style="padding: 12px; font-size: 0.85rem; max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $alert->generated_reason }}
                        </td>
                        <td style="padding: 12px;">
                            <a href="{{ route('ai.show', $alert) }}" class="btn btn-sm btn-primary">Review</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    const ctx = document.getElementById('usageChart').getContext('2d');
    const monthlyData = @json($monthlySummary);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Leave Days Used',
                data: monthlyData.map(d => d.days),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
@endpush
