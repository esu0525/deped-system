<div class="modal-body animate-fade">
    <div style="display: grid; grid-template-columns: 1fr 280px; gap: 20px;">
        <!-- Left Column: Details -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                <div>
                    <h5 style="font-weight: 800; color: var(--primary); margin: 0;">#{{ $leaveApplication->application_no }}</h5>
                    <p style="font-size: 0.8rem; color: var(--secondary); margin-top: 4px;">Filed on {{ $leaveApplication->created_at->format('M d, Y h:i A') }}</p>
                </div>
                {!! $leaveApplication->status_badge !!}
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div>
                    <label style="font-size: 0.68rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Employee</label>
                    <p style="font-weight: 700; font-size: 0.9rem; margin-top: 2px;">{{ $leaveApplication->employee->full_name }}</p>
                </div>
                <div>
                    <label style="font-size: 0.68rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Dept/Position</label>
                    <p style="font-size: 0.85rem; margin-top: 2px;">{{ $leaveApplication->employee->department->name ?? 'N/A' }} · {{ $leaveApplication->employee->position ?? 'N/A' }}</p>
                </div>
                <div>
                    <label style="font-size: 0.68rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Inclusive Dates</label>
                    <p style="font-weight: 700; font-size: 0.85rem; margin-top: 2px;">
                        {{ $leaveApplication->details->first()->inclusive_dates ?? ($leaveApplication->date_from->format('M d') . ' - ' . $leaveApplication->date_to->format('M d, Y')) }}
                    </p>
                </div>
                <div>
                    <label style="font-size: 0.68rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">Total Days</label>
                    <p style="font-weight: 800; color: var(--primary); font-size: 1.1rem; margin-top: 2px;">{{ $leaveApplication->num_days }} days</p>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">6.C Inclusive Dates breakdown</label>
                <div style="margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Leave Type</th>
                                <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Inclusive Dates</th>
                                <th style="padding: 8px 12px; text-align: center; border-bottom: 1px solid #e2e8f0;">Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveApplication->details as $detail)
                            <tr>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">{{ $detail->leaveType->name ?? ($detail->other_type ?: 'N/A') }}</td>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: var(--secondary);">{{ $detail->inclusive_dates }}</td>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: center; font-weight: 700;">{{ $detail->num_days }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($leaveApplication->status === 'Approved' && $leaveApplication->cert_vl_total_earned !== null)
            <div style="margin-top: 20px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">7.A Certification of Leave Credits</label>
                <div style="margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.78rem;">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th></th>
                                <th style="padding: 8px; text-align: center;">VL</th>
                                <th style="padding: 8px; text-align: center;">SL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #e2e8f0;">Earned</td>
                                <td style="text-align: center; border-bottom: 1px solid #e2e8f0;">{{ number_format($leaveApplication->cert_vl_total_earned, 3) }}</td>
                                <td style="text-align: center; border-bottom: 1px solid #e2e8f0;">{{ number_format($leaveApplication->cert_sl_total_earned, 3) }}</td>
                            </tr>
                            <tr style="background: #f0f9ff;">
                                <td style="padding: 8px 12px;"><strong>Balance</strong></td>
                                <td style="text-align: center; font-weight: 700; color: var(--primary);">{{ number_format($leaveApplication->cert_vl_balance, 3) }}</td>
                                <td style="text-align: center; font-weight: 700; color: var(--primary);">{{ number_format($leaveApplication->cert_sl_balance, 3) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: AI Insights & Actions -->
        <div>
            @if(isset($aiLog) && $leaveApplication->status === 'Pending')
            <div style="border: 1px solid {{ $aiLog->risk_level === 'High' ? '#ef4444' : ($aiLog->risk_level === 'Medium' ? '#f59e0b' : '#10b981') }}; background: {{ $aiLog->risk_level === 'High' ? '#fef2f2' : ($aiLog->risk_level === 'Medium' ? '#fffbeb' : '#f0fdf4') }}; padding: 12px; border-radius: 12px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <h6 style="font-weight: 700; color: {{ $aiLog->risk_level === 'High' ? '#b91c1c' : ($aiLog->risk_level === 'Medium' ? '#b45309' : '#15803d') }}; margin-bottom: 0; font-size: 0.75rem;">
                        <i class="fas fa-microchip"></i> AI INSIGHTS
                    </h6>
                    <span style="font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; background: {{ $aiLog->risk_level === 'High' ? '#ef4444' : ($aiLog->risk_level === 'Medium' ? '#f59e0b' : '#10b981') }}; color: white; font-weight: 700;">
                        {{ $aiLog->risk_level }}
                    </span>
                </div>
                
                @if(!empty($aiLog->suspicious_flags))
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.65rem;">
                    @foreach($aiLog->suspicious_flags as $flag)
                    <li style="margin-bottom: 4px; display: flex; gap: 6px; align-items: flex-start; color: {{ $aiLog->risk_level === 'High' ? '#991b1b' : '#4b5563' }};">
                        <i class="fas fa-triangle-exclamation" style="margin-top: 1px;"></i>
                        <span>{{ $flag }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p style="font-size: 0.7rem; color: #15803d; font-weight: 600; margin: 0;">No issues detected.</p>
                @endif
            </div>
            @endif

            @if($leaveApplication->status === 'Pending' && auth()->user()->canApproveLeave())
            <div style="background: white; border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px;">
                <h6 style="font-weight: 700; margin-bottom: 10px; font-size: 0.75rem;"><i class="fas fa-gavel"></i> DECISION</h6>
                <form id="modalApproveForm" action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST" style="margin-bottom: 8px;">
                    @csrf
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Admin remarks..." style="font-size: 0.75rem; margin-bottom: 8px; border-radius: 8px;"></textarea>
                    <button type="submit" class="btn btn-success" style="width: 100%; font-size: 0.8rem; padding: 10px;">
                        <i class="fas fa-check"></i> Approve Application
                    </button>
                </form>
                <form id="modalRejectForm" action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST">
                    @csrf
                    <input type="hidden" name="remarks" id="modalRejectRemarks">
                    <button type="button" class="btn btn-danger" style="width: 100%; font-size: 0.8rem; padding: 10px;" onclick="confirmModalReject()">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
            </div>
            @else
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 12px; font-size: 0.75rem;">
                <div style="margin-bottom: 8px;">
                    <span style="color: var(--secondary);">Status:</span> {!! $leaveApplication->status_badge !!}
                </div>
                <div>
                    <span style="color: var(--secondary);">Admin Remarks:</span>
                    <p style="margin-top: 4px; font-style: italic;">{{ $leaveApplication->remarks ?: 'No remarks.' }}</p>
                </div>
            </div>
            @endif

            <div style="margin-top: 15px; text-align: center;">
                <button type="button" class="btn btn-secondary" style="font-size: 0.8rem; width: 100%; border-radius: 10px;" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmModalReject() {
        Swal.fire({
            title: 'Reject Application',
            input: 'textarea',
            inputPlaceholder: 'Enter reason for rejection...',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Reject',
            inputValidator: (value) => { if (!value) return 'Required!' }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('modalRejectRemarks').value = result.value;
                document.getElementById('modalRejectForm').submit();
            }
        });
    }
</script>
