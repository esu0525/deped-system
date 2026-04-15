<div class="modal-body animate-fade">
    @php 
        $hasCto = $leaveApplication->details->contains(fn($d) => stripos($d->leaveType->name ?? '', 'CTO') !== false);
        $year = now()->year;
        $leaveCard = \App\Models\LeaveCard::where('employee_id', $leaveApplication->employee_id)
            ->where('year', $year)
            ->first();
    @endphp
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

            <div style="background: white; border-radius: 16px; border: 1px solid #edf2f7; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                    <div>
                        <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Employee Name</label>
                        <p style="font-weight: 800; font-size: 0.95rem; color: #1e293b; margin: 0;">{{ $leaveApplication->employee->full_name }}</p>
                    </div>
                    <div>
                        <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Department & Rank</label>
                        <p style="font-size: 0.85rem; color: #475569; margin: 0; font-weight: 600;">{{ $leaveApplication->employee->department->name ?? 'N/A' }} <span style="color: #cbd5e1; margin: 0 4px;">|</span> {{ $leaveApplication->employee->position ?? 'N/A' }}</p>
                    </div>
                    <div style="grid-column: span 2; border-top: 1px solid #f1f5f9; padding-top: 15px; margin-top: 5px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">{{ $hasCto ? 'CTO Source' : 'Primary Type' }}</label>
                            <p style="font-weight: 700; font-size: 0.85rem; color: #1e293b; margin: 0; line-height: 1.3;">
                                @if($hasCto)
                                    @php $ctoDetail = $leaveApplication->details->firstWhere(fn($d) => stripos($d->leaveType->name ?? '', 'CTO') !== false); @endphp
                                    {{ \Illuminate\Support\Str::limit($ctoDetail->cto_title ?? 'Untitled', 60, '...') }}
                                @else
                                    {{ $leaveApplication->details->first()->leaveType->name ?? 'N/A' }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Inclusive Dates</label>
                            <p style="font-weight: 700; font-size: 0.85rem; color: #1e293b; margin: 0;">
                                @php $firstWithDate = $leaveApplication->details->first(fn($d) => !empty($d->inclusive_dates)); @endphp
                                {{ $firstWithDate->inclusive_dates ?? ($leaveApplication->date_from->format('M d') . ' - ' . $leaveApplication->date_to->format('M d, Y')) }}
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <label style="font-size: 0.65rem; color: #94a3b8; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Total Volume</label>
                            <p style="font-weight: 900; color: var(--primary); font-size: 1.25rem; margin: 0;">{{ (float)$leaveApplication->num_days }} <span style="font-size: 0.7rem; font-weight: 700; color: #64748b;">DAYS</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <label style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; text-transform: uppercase;">6.C Inclusive Dates breakdown</label>
                <div style="margin-top: 8px; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Leave Type</th>
                                <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">{{ $hasCto ? 'CTO Title / Period' : 'Inclusive Dates' }}</th>
                                @if(!$hasCto)
                                    <th style="padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0;">Pay Status</th>
                                @else
                                    <th style="padding: 8px 12px; text-align: center; border-bottom: 1px solid #e2e8f0;">Earned</th>
                                @endif
                                <th style="padding: 8px 12px; text-align: center; border-bottom: 1px solid #e2e8f0;">{{ $hasCto ? 'Used' : 'Days' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($leaveApplication->details as $detail)
                            @php $isCtoRow = stripos($detail->leaveType->name ?? '', 'CTO') !== false; @endphp
                            <tr>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">{{ $detail->leaveType->name ?? ($detail->other_type ?: 'N/A') }}</td>
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; color: var(--secondary);">
                                    @if($isCtoRow)
                                        {{ \Illuminate\Support\Str::limit($detail->cto_title ?: 'Untitled', 80, '...') }}
                                        @if($detail->inclusive_dates)
                                            <div style="font-size: 0.7rem; color: var(--secondary); margin-top: 2px;">
                                                <i class="far fa-calendar-alt"></i> {{ $detail->inclusive_dates }}
                                            </div>
                                        @endif
                                    @else
                                        {{ $detail->inclusive_dates }}
                                    @endif
                                </td>
                                @if(!$hasCto)
                                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">
                                        <span style="font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; border: 1px solid {{ $detail->is_with_pay ? '#10b981' : '#f59e0b' }}; color: {{ $detail->is_with_pay ? '#10b981' : '#f59e0b' }}; font-weight: 700;">
                                            {{ $detail->is_with_pay ? 'WITH PAY' : 'WITHOUT PAY' }}
                                        </span>
                                    </td>
                                @else
                                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: center; font-weight: 700; color: #059669;">{{ (float)($detail->cto_earned_days ?? 0) > 0 ? rtrim(rtrim(number_format($detail->cto_earned_days, 6), '0'), '.') : '—' }}</td>
                                @endif
                                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: center; font-weight: 700; color: {{ $isCtoRow ? '#dc2626' : 'inherit' }};">{{ rtrim(rtrim(number_format($detail->num_days, 6), '0'), '.') }}</td>
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

        <!-- Right Column: Current Credits & Status -->
        <div>
            @if($leaveCard)
            <div style="background: white; border: 1px solid #e2e8f0; padding: 15px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 15px;">
                <h6 style="font-weight: 700; margin-bottom: 12px; font-size: 0.75rem;"><i class="fas fa-wallet"></i> CURRENT CREDITS ({{ $year }})</h6>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f0f9ff; padding: 10px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <span style="font-size: 0.65rem; font-weight: 700; color: #1e3a8a;">VACATION LEAVE</span>
                        <span style="font-weight: 800; color: #1e3a8a;">{{ number_format($leaveCard->vl_balance, 3) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #fdf2f8; padding: 10px; border-radius: 8px; border-left: 4px solid #db2777;">
                        <span style="font-size: 0.65rem; font-weight: 700; color: #831843;">SICK LEAVE</span>
                        <span style="font-weight: 800; color: #831843;">{{ number_format($leaveCard->sl_balance, 3) }}</span>
                    </div>
                    @php 
                        $isWellnessApp = $leaveApplication->details->contains(fn($d) => stripos($d->leaveType->name ?? '', 'Wellness') !== false);
                    @endphp
                    @if($isWellnessApp)
                    <div style="display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; padding: 10px; border-radius: 8px; border-left: 4px solid #16a34a;">
                         <span style="font-size: 0.65rem; font-weight: 700; color: #166534;">WELLNESS BALANCE</span>
                         <span style="font-weight: 800; color: #166534;">
                            @php $dispM = rtrim(rtrim(number_format($wellnessBalance ?? 0, 1), '0'), '.'); @endphp
                            {{ $dispM === '' ? '0' : $dispM }}
                         </span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 12px; font-size: 0.75rem;">
                <div>
                    <span style="color: var(--secondary); font-weight: 600;">Current Status:</span> {!! $leaveApplication->status_badge !!}
                </div>

                @if($leaveApplication->status === 'Pending' && auth()->user()->canApproveLeave())
                <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px; display: flex; flex-direction: column; gap: 8px;">
                    <form id="modalApproveForm" action="{{ route('leave-applications.approve', $leaveApplication) }}" method="POST">
                        @csrf

                        <button type="submit" class="btn btn-success" style="width: 100%; font-size: 0.8rem; padding: 10px; font-weight: 700;">
                            <i class="fas fa-check"></i> Approve Application
                        </button>
                    </form>
                    <form id="modalRejectForm" action="{{ route('leave-applications.reject', $leaveApplication) }}" method="POST">
                        @csrf
                        <input type="hidden" name="remarks" id="modalRejectRemarks">
                        <button type="button" class="btn btn-danger" style="width: 100%; font-size: 0.8rem; padding: 10px; font-weight: 700;" onclick="confirmModalReject()">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>


