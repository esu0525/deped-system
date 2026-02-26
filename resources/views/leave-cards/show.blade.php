@extends('layouts.app')

@section('header_title', 'Employee Leave Card')

@section('content')
<div class="animate-fade">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div class="card glass" style="flex: 1; margin-bottom: 0; display: flex; align-items: center; gap: 20px;">
            <div style="width: 80px; height: 80px; border-radius: 20px; background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: 800;">
                {{ substr($employee->full_name, 0, 1) }}
            </div>
            <div>
                <h3 style="font-weight: 700; margin-bottom: 5px;">{{ $employee->full_name }}</h3>
                <p style="color: var(--secondary); margin: 0;">{{ $employee->position }} | {{ $employee->department?->name }}</p>
                <span class="badge badge-info" style="margin-top: 8px;">Employee ID: {{ $employee->employee_id }}</span>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-left: 24px;">
            <form action="{{ route('leave-cards.show', $employee) }}" method="GET">
                <select name="year" onchange="this.form.submit()" class="form-control" style="width: 120px;">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                    @endforeach
                </select>
            </form>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Card
            </button>
        </div>
    </div>

    <!-- Credit Summary -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px;">
        <div class="card animate-fade" style="border-bottom: 4px solid var(--success);">
            <p style="font-size: 0.8rem; color: var(--secondary);">Total VL Balance</p>
            <h2 style="font-weight: 800;">{{ number_format($leaveCard->vl_balance, 3) }}</h2>
        </div>
        <div class="card animate-fade" style="border-bottom: 4px solid var(--primary);">
            <p style="font-size: 0.8rem; color: var(--secondary);">Total SL Balance</p>
            <h2 style="font-weight: 800;">{{ number_format($leaveCard->sl_balance, 3) }}</h2>
        </div>
        <div class="card animate-fade" style="border-bottom: 4px solid var(--info);">
            <p style="font-size: 0.8rem; color: var(--secondary);">Forced Leave</p>
            <h2 style="font-weight: 800;">{{ number_format($leaveCard->forced_leave_balance, 3) }}</h2>
        </div>
        <div class="card animate-fade" style="border-bottom: 4px solid var(--accent);">
            <p style="font-size: 0.8rem; color: var(--secondary);">Special Privilege</p>
            <h2 style="font-weight: 800;">{{ number_format($leaveCard->special_leave_balance, 3) }}</h2>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 style="font-weight: 700;">Leave Ledger / Transaction History</h4>
            <div style="display: flex; gap: 10px;">
                @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
                <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#adjustModal">
                    <i class="fas fa-adjust"></i> Manual Adjustment
                </button>
                @endif
            </div>
        </div>

        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="text-align: left; background: #f8fafc; border-bottom: 2px solid #eee;">
                        <th style="padding: 12px; border: 1px solid #eee;">Date</th>
                        <th style="padding: 12px; border: 1px solid #eee;">Type</th>
                        <th style="padding: 12px; border: 1px solid #eee;">Particulars/Remarks</th>
                        <th style="padding: 12px; border: 1px solid #eee;">Earned</th>
                        <th style="padding: 12px; border: 1px solid #eee;">Used</th>
                        <th style="padding: 12px; border: 1px solid #eee;">VL Bal</th>
                        <th style="padding: 12px; border: 1px solid #eee;">SL Bal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #fffbea;">
                        <td style="padding: 12px; border: 1px solid #eee;">--</td>
                        <td style="padding: 12px; border: 1px solid #eee;">N/A</td>
                        <td style="padding: 12px; border: 1px solid #eee; font-weight: 700;">BEGINNING BALANCE</td>
                        <td style="padding: 12px; border: 1px solid #eee;">--</td>
                        <td style="padding: 12px; border: 1px solid #eee;">--</td>
                        <td style="padding: 12px; border: 1px solid #eee; font-weight: 700;">{{ number_format($leaveCard->vl_beginning_balance, 3) }}</td>
                        <td style="padding: 12px; border: 1px solid #eee; font-weight: 700;">{{ number_format($leaveCard->sl_beginning_balance, 3) }}</td>
                    </tr>
                    @foreach($transactions as $trans)
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px; border: 1px solid #eee;">{{ $trans->transaction_date->format('M d, Y') }}</td>
                        <td style="padding: 12px; border: 1px solid #eee;">{{ $trans->leaveType->code }}</td>
                        <td style="padding: 12px; border: 1px solid #eee; font-size: 0.8rem;">
                            {{ $trans->remarks }}
                            @if($trans->encoder) <div style="font-size: 0.7rem; color: var(--secondary);">Encoded by: {{ $trans->encoder->name }}</div> @endif
                        </td>
                        <td style="padding: 12px; border: 1px solid #eee; color: var(--success); font-weight: 600;">{{ $trans->transaction_type == 'earned' ? '+' . number_format($trans->days, 3) : '--' }}</td>
                        <td style="padding: 12px; border: 1px solid #eee; color: var(--danger); font-weight: 600;">{{ $trans->transaction_type == 'used' ? '-' . number_format($trans->days, 3) : '--' }}</td>
                        <td style="padding: 12px; border: 1px solid #eee;">{{ number_format($trans->vl_balance_after, 3) }}</td>
                        <td style="padding: 12px; border: 1px solid #eee;">{{ number_format($trans->sl_balance_after, 3) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Simple Adjustment Modal (Pseudo-modal for illustration, normally would use JS) -->
<div id="adjustModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div class="card animate-fade" style="width: 400px; background:white;">
        <h4 style="margin-bottom:20px;">Manual Credit Adjustment</h4>
        <form action="{{ route('leave-cards.adjust', $employee) }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Leave Type</label>
                <select name="leave_type_code" class="form-control">
                    <option value="VL">Vacation Leave (VL)</option>
                    <option value="SL">Sick Leave (SL)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Days (+ to add, - to subtract)</label>
                <input type="number" step="0.001" name="days" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" required></textarea>
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Adjustment</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('adjustModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Toggle modal visibility
    document.querySelector('[data-target="#adjustModal"]').addEventListener('click', () => {
        document.getElementById('adjustModal').style.display = 'flex';
    });
</script>
@endpush
@endsection
