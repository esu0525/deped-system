@extends('layouts.app')

@section('header_title', 'Audit Trails')

@section('content')
<div class="animate-fade">
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-clock-rotate-left text-primary"></i> Activity Logs</h4>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td><small>{{ $log->created_at->format('M d, Y h:i A') }}</small></td>
                        <td><strong>{{ $log->user->name ?? 'System' }}</strong></td>
                        <td><span class="badge badge-info">{{ $log->action }}</span></td>
                        <td>{{ $log->module }}</td>
                        <td style="max-width: 300px;"><small>{{ $log->description }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; padding: 30px; color: var(--secondary);">No audit logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 20px;">{{ $logs->links() }}</div>
    </div>
</div>
@endsection
