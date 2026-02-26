@extends('layouts.app')

@section('header_title', 'Leave Application Details')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Main Info -->
        <div class="card glass">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                <h4 style="font-weight: 700;"><i class="fas fa-file-signature text-primary"></i> Application #{{ $leaveApplication->application_no }}</h4>
                {!! $leaveApplication->status_badge !!}
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Employee</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $leaveApplication->employee->full_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Department</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $leaveApplication->employee->department->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Leave Type</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $leaveApplication->leaveType->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Number of Days</label>
                    <p style="font-weight: 800; color: var(--primary); font-size: 1.2rem; margin-top: 5px;">{{ $leaveApplication->num_days }} days</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Date From</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $leaveApplication->date_from->format('F d, Y') }}</p>
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Date To</label>
                    <p style="font-weight: 700; margin-top: 5px;">{{ $leaveApplication->date_to->format('F d, Y') }}</p>
                </div>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 25px 0;">

            <div>
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Reason / Details</label>
                <p style="margin-top: 8px; line-height: 1.6;">{{ $leaveApplication->reason ?? 'No reason provided.' }}</p>
            </div>

            @if($leaveApplication->attachment)
            <div style="margin-top: 20px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Attachment</label>
                <a href="{{ asset('storage/' . $leaveApplication->attachment) }}" target="_blank" class="btn btn-sm btn-secondary" style="margin-top: 8px;">
                    <i class="fas fa-paperclip"></i> View Attachment
                </a>
            </div>
            @endif

            @if($leaveApplication->remarks)
            <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 12px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Admin Remarks</label>
                <p style="margin-top: 8px;">{{ $leaveApplication->remarks }}</p>
            </div>
            @endif
        </div>

        <!-- Actions & Timeline -->
        <div>
            @if($leaveApplication->status === 'Pending' && auth()->user()->canApproveLeave())
            <div class="card glass" style="margin-bottom: 20px;">
                <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-gavel"></i> Actions</h5>
                <form action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST" style="margin-bottom: 10px;">
                    @csrf
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Optional remarks..." style="margin-bottom: 10px;"></textarea>
                    <button type="submit" class="btn btn-success" style="width: 100%;" onclick="return confirm('Approve this leave application?')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                <form action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST">
                    @csrf
                    <input type="hidden" name="remarks" id="reject-remarks">
                    <button type="submit" class="btn btn-danger" style="width: 100%;" onclick="var r = prompt('Reason for rejection:'); if(!r) return false; document.getElementById('reject-remarks').value = r; return true;">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
            </div>
            @endif

            <div class="card glass">
                <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Details</h5>
                <div style="font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: var(--secondary);">Filed by</span>
                        <strong>{{ $leaveApplication->encoder->name ?? 'System' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: var(--secondary);">Filed on</span>
                        <strong>{{ $leaveApplication->created_at->format('M d, Y h:i A') }}</strong>
                    </div>
                    @if($leaveApplication->approved_at)
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: var(--secondary);">Acted by</span>
                        <strong>{{ $leaveApplication->approver->name ?? 'N/A' }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <span style="color: var(--secondary);">Acted on</span>
                        <strong>{{ $leaveApplication->approved_at->format('M d, Y h:i A') }}</strong>
                    </div>
                    @endif
                </div>
            </div>

            <div style="margin-top: 20px;">
                <a href="{{ route('leave-applications.index') }}" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
