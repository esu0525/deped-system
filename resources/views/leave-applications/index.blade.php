@extends('layouts.app')

@section('header_title', 'Leave Applications')

@section('content')
<div class="animate-fade">
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
        <div class="card glass" style="text-align: center;">
            <i class="fas fa-file-signature fa-2x" style="color: var(--primary); margin-bottom: 10px;"></i>
            <h2 style="font-weight: 800; color: var(--primary);">{{ $applications->total() }}</h2>
            <small style="color: var(--secondary); font-weight: 600;">Total Applications</small>
        </div>
        <div class="card glass" style="text-align: center;">
            <i class="fas fa-clock fa-2x" style="color: var(--warning); margin-bottom: 10px;"></i>
            <h2 style="font-weight: 800; color: var(--warning);">{{ $applications->where('status', 'Pending')->count() }}</h2>
            <small style="color: var(--secondary); font-weight: 600;">Pending</small>
        </div>
        <div class="card glass" style="text-align: center;">
            <i class="fas fa-check-circle fa-2x" style="color: var(--success); margin-bottom: 10px;"></i>
            <h2 style="font-weight: 800; color: var(--success);">{{ $applications->where('status', 'Approved')->count() }}</h2>
            <small style="color: var(--secondary); font-weight: 600;">Approved</small>
        </div>
        <div class="card glass" style="text-align: center;">
            <i class="fas fa-times-circle fa-2x" style="color: var(--danger); margin-bottom: 10px;"></i>
            <h2 style="font-weight: 800; color: var(--danger);">{{ $applications->where('status', 'Rejected')->count() }}</h2>
            <small style="color: var(--secondary); font-weight: 600;">Rejected</small>
        </div>
    </div>

    <!-- Filters & Actions -->
    <div class="card glass animate-fade" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <form method="GET" action="{{ route('leave-applications.index') }}" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <input type="text" name="search" class="form-control" placeholder="Search employee..." value="{{ request('search') }}" style="width: 220px;">
                <select name="status" class="form-control" style="width: 160px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                    <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select name="leave_type" class="form-control" style="width: 180px;" onchange="this.form.submit()">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
            </form>
            <a href="{{ route('leave-applications.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Application
            </a>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="card glass animate-fade">
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>App No.</th>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Date</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Filed On</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                    <tr>
                        <td><strong style="color: var(--primary);">{{ $app->application_no }}</strong></td>
                        <td>
                            <div>
                                <strong>{{ $app->employee->full_name ?? 'N/A' }}</strong>
                                <br><small style="color: var(--secondary);">{{ $app->employee->department->name ?? '' }}</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $app->leaveType->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <small>{{ $app->date_from->format('M d') }} - {{ $app->date_to->format('M d, Y') }}</small>
                        </td>
                        <td><strong>{{ $app->num_days }}</strong></td>
                        <td>{!! $app->status_badge !!}</td>
                        <td><small>{{ $app->created_at->format('M d, Y') }}</small></td>
                        <td style="text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="{{ route('leave-applications.show', $app) }}" class="btn btn-sm btn-secondary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($app->status === 'Pending' && auth()->user()->canApproveLeave())
                                <form action="{{ route('leave-applications.approve', $app) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve this application?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form action="{{ route('leave-applications.reject', $app) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="remarks" value="Rejected by admin">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Reject this application?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: var(--secondary);">
                            <i class="fas fa-inbox fa-3x" style="margin-bottom: 15px; opacity: 0.3;"></i>
                            <p style="font-weight: 600;">No leave applications found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;">
            {{ $applications->links() }}
        </div>
    </div>
</div>
@endsection
