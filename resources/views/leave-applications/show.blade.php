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
                    <p style="font-weight: 700; margin-top: 5px;">
                        {{ $leaveApplication->leaveType->name ?? 'N/A' }}
                        @php 
                            $hasWellness = false;
                            foreach($leaveApplication->details as $d) {
                                if($d->leaveType && stripos($d->leaveType->name, 'Wellness') !== false) {
                                    $hasWellness = true; break;
                                }
                            }
                        @endphp
                        @if($hasWellness)
                            <span style="display: block; font-size: 0.8rem; color: #16a34a; font-weight: 800; margin-top: 4px;">
                                <i class="fas fa-info-circle"></i> Remaining Wellness: 
                                @php $dispW = rtrim(rtrim(number_format($wellnessBalance, 1), '0'), '.'); @endphp
                                {{ $dispW === '' ? '0' : $dispW }} days
                            </span>
                        @endif
                    </p>
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
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">6.C Inclusive Dates breakdown</label>
                <div style="margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 25px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="padding: 10px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Leave Type</th>
                                <th style="padding: 10px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Inclusive Dates</th>
                                <th style="padding: 10px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Pay Status</th>
                                <th style="padding: 10px 14px; text-align: center; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveApplication->details as $detail)
                            <tr>
                                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">{{ $detail->leaveType->name ?? ($detail->other_type ?: 'N/A') }}</td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; color: var(--secondary);">{{ $detail->inclusive_dates }}</td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9;">
                                    <span style="font-size: 0.7rem; padding: 3px 8px; border-radius: 6px; border: 1px solid {{ $detail->is_with_pay ? '#10b981' : '#f59e0b' }}; color: {{ $detail->is_with_pay ? '#10b981' : '#f59e0b' }}; font-weight: 700;">
                                        {{ $detail->is_with_pay ? 'WITH PAY' : 'WITHOUT PAY' }}
                                    </span>
                                    @if(!$detail->is_with_pay && $detail->lwop_reason)
                                        <div style="font-size: 0.75rem; color: #dc2626; margin-top: 6px; font-weight: 600;">
                                            Reason: {{ $detail->lwop_reason }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; text-align: center; font-weight: 700;">{{ $detail->num_days }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Reason / Details</label>
                <p style="margin-top: 8px; line-height: 1.6;">{{ $leaveApplication->reason ?? 'No reason provided.' }}</p>
            </div>

            @if($leaveApplication->status === 'Approved' && $leaveApplication->cert_vl_total_earned !== null)
            <div style="margin-top: 20px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">7.A Certification of Leave Credits</label>
                <div style="margin-top: 10px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="background: #f1f5f9;">
                                <th style="padding: 10px 14px; text-align: left; font-weight: 700; border-bottom: 1px solid #e2e8f0;"></th>
                                <th style="padding: 10px 14px; text-align: center; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Vacation Leave</th>
                                <th style="padding: 10px 14px; text-align: center; font-weight: 700; border-bottom: 1px solid #e2e8f0;">Sick Leave</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0;"><em>Total Earned</em></td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 600; font-family: monospace; border-bottom: 1px solid #e2e8f0;">{{ number_format($leaveApplication->cert_vl_total_earned, 3) }}</td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 600; font-family: monospace; border-bottom: 1px solid #e2e8f0;">{{ number_format($leaveApplication->cert_sl_total_earned, 3) }}</td>
                            </tr>
                            <tr style="background: #fff7ed;">
                                <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0; color: #c2410c;"><em>Less this application</em></td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 600; font-family: monospace; border-bottom: 1px solid #e2e8f0; color: #c2410c;">{{ number_format($leaveApplication->cert_vl_less_this, 3) }}</td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 600; font-family: monospace; border-bottom: 1px solid #e2e8f0; color: #c2410c;">{{ number_format($leaveApplication->cert_sl_less_this, 3) }}</td>
                            </tr>
                            <tr style="background: #f0f9ff;">
                                <td style="padding: 10px 14px;"><strong>Balance</strong></td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 800; font-family: monospace; color: var(--primary); font-size: 0.95rem;">{{ number_format($leaveApplication->cert_vl_balance, 3) }}</td>
                                <td style="padding: 10px 14px; text-align: center; font-weight: 800; font-family: monospace; color: var(--primary); font-size: 0.95rem;">{{ number_format($leaveApplication->cert_sl_balance, 3) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
            @if(isset($aiLog) && $leaveApplication->status === 'Pending')
            <div class="card" style="margin-bottom: 20px; border: 1px solid {{ $aiLog->risk_level === 'High' ? '#ef4444' : ($aiLog->risk_level === 'Medium' ? '#f59e0b' : '#10b981') }}; background: {{ $aiLog->risk_level === 'High' ? '#fef2f2' : ($aiLog->risk_level === 'Medium' ? '#fffbeb' : '#f0fdf4') }};">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h5 style="font-weight: 700; color: {{ $aiLog->risk_level === 'High' ? '#b91c1c' : ($aiLog->risk_level === 'Medium' ? '#b45309' : '#15803d') }}; margin-bottom: 0;">
                        <i class="fas fa-microchip"></i> AI Insights
                    </h5>
                    <span class="badge" style="background: {{ $aiLog->risk_level === 'High' ? '#ef4444' : ($aiLog->risk_level === 'Medium' ? '#f59e0b' : '#10b981') }}; color: white;">
                        {{ $aiLog->risk_level }} Risk
                    </span>
                </div>
                
                <div style="margin-bottom: 12px;">
                    <small style="color: var(--secondary); font-weight: 600;">Risk Score:</small>
                    <div style="height: 6px; background: #e2e8f0; border-radius: 3px; margin: 4px 0;">
                        <div style="width: {{ $aiLog->risk_score }}%; height: 100%; border-radius: 3px; background: {{ $aiLog->risk_level === 'High' ? '#ef4444' : ($aiLog->risk_level === 'Medium' ? '#f59e0b' : '#10b981') }};"></div>
                    </div>
                </div>

                @if(!empty($aiLog->suspicious_flags))
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.8rem;">
                    @foreach($aiLog->suspicious_flags as $flag)
                    <li style="margin-bottom: 8px; display: flex; gap: 8px; align-items: flex-start; color: {{ $aiLog->risk_level === 'High' ? '#991b1b' : '#374151' }};">
                        <i class="fas fa-triangle-exclamation" style="margin-top: 2px;"></i>
                        <span>{{ $flag }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p style="font-size: 0.82rem; color: #15803d; font-weight: 600; margin-bottom: 0;">
                    <i class="fas fa-check-circle"></i> No suspicious patterns detected.
                </p>
                @endif
            </div>
            @endif

            @if($leaveApplication->status === 'Pending' && auth()->user()->canApproveLeave())
            <div class="card glass" style="margin-bottom: 20px;">
                <h5 style="font-weight: 700; margin-bottom: 15px;"><i class="fas fa-gavel"></i> Actions</h5>
                <form id="approveForm" action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST" style="margin-bottom: 10px;">
                    @csrf
                    <textarea name="remarks" id="approveRemarksInput" class="form-control" rows="2" placeholder="Optional remarks..." style="margin-bottom: 10px;"></textarea>
                    <button type="button" class="btn btn-success" style="width: 100%;" onclick="confirmApprove()">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                <form id="rejectForm" action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST">
                    @csrf
                    <input type="hidden" name="remarks" id="rejectRemarksInput">
                    <button type="button" class="btn btn-danger" style="width: 100%;" onclick="confirmReject()">
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

@push('scripts')
<script>
    function confirmApprove() {
        Swal.fire({
            title: 'Approve Leave Application?',
            text: "This action will systematically process and potentially deduct leave credits according to the rules.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Approve'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approveForm').submit();
            }
        });
    }

    function confirmReject() {
        Swal.fire({
            title: 'Reject Leave Application',
            input: 'textarea',
            inputLabel: 'Reason for rejection',
            inputPlaceholder: 'Enter the reason why this is rejected...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Reject',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to write a reason to reject this application!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('rejectRemarksInput').value = result.value;
                document.getElementById('rejectForm').submit();
            }
        });
    }
</script>
@endpush
@endsection
