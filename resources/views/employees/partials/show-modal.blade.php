<div class="modal-body animate-fade">
    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 24px;">
        <!-- Profile Card -->
        <div style="text-align: center; border-right: 1px solid #f1f5f9; padding-right: 24px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.5rem; margin: 0 auto 15px;">
                {{ substr($employee->full_name, 0, 1) }}
            </div>
            <h5 style="font-weight: 800; margin-bottom: 5px;">{{ $employee->full_name }}</h5>
            <p style="color: var(--secondary); font-size: 0.85rem; font-weight: 600; margin-bottom: 10px;">{{ $employee->position ?? 'No Position' }}</p>
            <span class="badge" style="background: {{ $employee->status === 'Active' ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; color: {{ $employee->status === 'Active' ? '#059669' : '#ef4444' }}; padding: 4px 12px; border-radius: 6px; font-size: 0.72rem; font-weight: 700;">
                {{ $employee->status }}
            </span>

            <div style="text-align: left; font-size: 0.82rem; margin-top: 25px; background: #f8fafc; padding: 15px; border-radius: 12px;">
                <div style="margin-bottom: 10px;"><i class="fas fa-id-badge" style="width: 18px; color: var(--primary); opacity: 0.7;"></i> <strong>Ref ID: {{ $employee->employee_id }}</strong></div>
                <div style="margin-bottom: 10px;"><i class="fas fa-building" style="width: 18px; color: var(--primary); opacity: 0.7;"></i> {{ $employee->department->name ?? 'No Office/Department' }}</div>
                <div style="margin-bottom: 10px;"><i class="fas fa-briefcase" style="width: 18px; color: var(--primary); opacity: 0.7;"></i> {{ $employee->employment_status ?? 'N/A' }}</div>

            </div>

            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <a href="{{ route('leave-cards.show', $employee) }}" class="btn btn-secondary" style="font-size: 0.75rem; padding: 8px;" target="_self">
                    <i class="fas fa-address-card"></i> Ledger
                </a>
                <button type="button" class="btn btn-primary" style="font-size: 0.75rem; padding: 8px;" onclick="closeViewModal(); openEditModal('{{ route('employees.edit', $employee) }}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
        </div>

        <!-- Leave Details -->
        <div>
            @if($currentLeaveCard)
            <div style="margin-bottom: 25px;">
                <h6 style="font-weight: 800; margin-bottom: 15px; font-size: 0.8rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px;">Current Leave Credits</h6>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div style="padding: 15px; background: #f0f9ff; border-radius: 16px; border: 1px solid #bae6fd; text-align: center;">
                        <small style="color: #0369a1; font-weight: 700;">VL Balance</small>
                        <h4 style="font-weight: 800; color: #075985; margin-top: 4px;">{{ (float)$currentLeaveCard->vl_balance }}</h4>
                    </div>
                    <div style="padding: 15px; background: #f0fdf4; border-radius: 16px; border: 1px solid #bbf7d0; text-align: center;">
                        <small style="color: #15803d; font-weight: 700;">SL Balance</small>
                        <h4 style="font-weight: 800; color: #166534; margin-top: 4px;">{{ (float)$currentLeaveCard->sl_balance }}</h4>
                    </div>
                </div>
            </div>
            @endif

            <h6 style="font-weight: 800; margin-bottom: 12px; font-size: 0.8rem; color: var(--secondary); text-transform: uppercase;">Recent Applications</h6>
            <div style="border: 1px solid #f1f5f9; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                    <thead style="background: #f8fafc;">
                        <tr>
                            <th style="padding: 10px; text-align: left; color: var(--secondary);">Type</th>
                            <th style="padding: 10px; text-align: left; color: var(--secondary);">Inclusive Dates</th>
                            <th style="padding: 10px; text-align: center; color: var(--secondary);">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->leaveApplications->take(5) as $app)
                        <tr style="border-top: 1px solid #f1f5f9;">
                            <td style="padding: 10px; font-weight: 600;">{{ $app->leaveType->name ?? 'N/A' }}</td>
                            <td style="padding: 10px;"><small>{{ $app->date_from->format('M d') }} - {{ $app->date_to->format('M d, Y') }}</small></td>
                            <td style="padding: 10px; text-align: center;">{!! $app->status_badge !!}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="padding: 20px; text-align: center; color: var(--secondary);">No leave history.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
