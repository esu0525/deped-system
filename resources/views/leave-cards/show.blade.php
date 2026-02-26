@extends('layouts.app')

@section('header_title', 'Employee Leave Card')

@section('content')
<div class="animate-fade">
    <!-- Actions Bar (hidden on print) -->
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <a href="{{ route('leave-cards.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <form action="{{ route('leave-cards.show', $employee) }}" method="GET" style="display: flex; gap: 8px; align-items: center;">
                <select name="year" onchange="this.form.submit()" class="form-control" style="width: 130px;">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div style="display: flex; gap: 10px;">
            @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
            <button class="btn btn-success" onclick="document.getElementById('balanceModal').style.display='flex'">
                <i class="fas fa-edit"></i> Edit Balance
            </button>
            @endif
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print"></i> Print Leave Card
            </button>
        </div>
    </div>

    @if($leaveCard)
    <!-- Official Leave Card Form -->
    <div class="card leave-card-form" id="printable-card">
        <!-- Form Header -->
        <div style="text-align: center; margin-bottom: 20px;">
            <p style="font-size: 0.95rem; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Models\SystemSetting::get('division_office_name', 'SCHOOLS DIVISION OFFICE-QUEZON CITY') }}</p>
            <p style="font-size: 0.82rem; color: var(--dark); margin: 3px 0 14px;">{{ \App\Models\SystemSetting::get('division_office_address', 'Nueva Ecija St., Bago Bantay, Quezon City') }}</p>
            <h3 style="font-weight: 800; font-size: 1.1rem; text-decoration: underline; text-transform: uppercase; letter-spacing: 1px;">Leave Card Non-Teaching Personnel</h3>
        </div>

        <!-- Employee Info Fields -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px 40px; margin-bottom: 24px; font-size: 0.88rem;">
            <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1px solid #1e293b; padding-bottom: 4px;">
                <span style="font-weight: 700; white-space: nowrap;">Name:</span>
                <span style="flex: 1;">{{ $employee->full_name }}</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1px solid #1e293b; padding-bottom: 4px;">
                <span style="font-weight: 700; white-space: nowrap;">Designation:</span>
                <span style="flex: 1;">{{ $employee->position ?? '—' }}</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1px solid #1e293b; padding-bottom: 4px;">
                <span style="font-weight: 700; white-space: nowrap;">Station:</span>
                <span style="flex: 1;">{{ $employee->department->name ?? '—' }}</span>
            </div>
            <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1px solid #1e293b; padding-bottom: 4px;">
                <span style="font-weight: 700; white-space: nowrap;">Status:</span>
                <span style="flex: 1;">{{ $employee->employment_status ?? 'Permanent' }}</span>
            </div>
        </div>

        <!-- Official Ledger Table -->
        <div style="overflow-x: auto;">
            <table class="leave-card-table">
                <thead>
                    <!-- First header row with merged cells -->
                    <tr>
                        <th rowspan="2" style="width: 90px;">PERIOD</th>
                        <th rowspan="2" style="min-width: 140px;">PARTICULARS</th>
                        <th colspan="4" class="group-header vl-header">Vacation Leave</th>
                        <th colspan="4" class="group-header sl-header">Sick Leave</th>
                        <th rowspan="2" style="width: 100px;">Date & Action<br>Taken on<br>Appl. for Leave</th>
                    </tr>
                    <!-- Second header row with sub-columns -->
                    <tr>
                        <th class="sub-header">EARNED</th>
                        <th class="sub-header">ABS.<br>UND.<br>W/P.</th>
                        <th class="sub-header">BAL.</th>
                        <th class="sub-header">ABS.<br>UND.<br>WOP.</th>
                        <th class="sub-header">EARNED</th>
                        <th class="sub-header">ABS.<br>UND.<br>W/P.</th>
                        <th class="sub-header">BAL.</th>
                        <th class="sub-header">ABS.<br>UND.<br>WOP.</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Beginning Balance Row -->
                    <tr class="beginning-row">
                        <td></td>
                        <td style="font-weight: 700;">BEGINNING BALANCE</td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell">{{ number_format($leaveCard->vl_beginning_balance, 3) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell">{{ number_format($leaveCard->sl_beginning_balance, 3) }}</td>
                        <td></td>
                        <td></td>
                    </tr>

                    @forelse($transactions as $trans)
                    @php
                        $isVL = ($trans->leaveType->code ?? '') === 'VL';
                        $isSL = ($trans->leaveType->code ?? '') === 'SL';
                        $isEarned = $trans->transaction_type === 'earned';
                        $isUsed = $trans->transaction_type === 'used';
                    @endphp
                    <tr>
                        <!-- Period -->
                        <td>{{ $trans->transaction_date->format('m/d/Y') }}</td>
                        <!-- Particulars -->
                        <td style="font-size: 0.78rem;">{{ $trans->remarks ?: ($isEarned ? 'Monthly Credit' : ($trans->leaveType->name ?? '')) }}</td>

                        <!-- VL columns -->
                        <td class="num-cell">{{ ($isVL && $isEarned) ? number_format($trans->days, 3) : '' }}</td>
                        <td class="num-cell">{{ ($isVL && $isUsed) ? number_format($trans->days, 3) : '' }}</td>
                        <td class="bal-cell">{{ number_format($trans->vl_balance_after, 3) }}</td>
                        <td class="num-cell"></td>

                        <!-- SL columns -->
                        <td class="num-cell">{{ ($isSL && $isEarned) ? number_format($trans->days, 3) : '' }}</td>
                        <td class="num-cell">{{ ($isSL && $isUsed) ? number_format($trans->days, 3) : '' }}</td>
                        <td class="bal-cell">{{ number_format($trans->sl_balance_after, 3) }}</td>
                        <td class="num-cell"></td>

                        <!-- Date & Action -->
                        <td style="font-size: 0.72rem; text-align: center;">{{ $isUsed ? $trans->transaction_date->format('m/d/Y') : '' }}</td>
                    </tr>
                    @empty
                    @endforelse

                    <!-- Empty rows to fill the form (for print appearance) -->
                    @for($i = $transactions->count(); $i < 20; $i++)
                    <tr class="empty-row">
                        <td>&nbsp;</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    @else
    <!-- No Leave Card for this year - Create one -->
    <div class="card no-print" style="text-align: center; padding: 50px;">
        <i class="fas fa-folder-open" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 16px;"></i>
        <h4 style="font-weight: 700; color: var(--dark); margin-bottom: 8px;">No Leave Card for {{ $year }}</h4>
        <p style="color: var(--secondary); margin-bottom: 24px;">Set the beginning balance to create a leave card for this employee.</p>

        @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
        <form action="{{ route('leave-cards.adjust', $employee) }}" method="POST" style="max-width: 500px; margin: 0 auto; text-align: left;">
            @csrf
            <input type="hidden" name="year" value="{{ $year }}">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">VL Beginning Balance</label>
                    <input type="number" name="vl_beginning_balance" class="form-control" step="0.001" value="0.000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">SL Beginning Balance</label>
                    <input type="number" name="sl_beginning_balance" class="form-control" step="0.001" value="0.000" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Forced Leave</label>
                    <input type="number" name="forced_leave_balance" class="form-control" step="0.001" value="5.000">
                </div>
                <div class="form-group">
                    <label class="form-label">Special Privilege Leave</label>
                    <input type="number" name="special_leave_balance" class="form-control" step="0.001" value="3.000">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                <i class="fas fa-plus"></i> Create Leave Card for {{ $year }}
            </button>
        </form>
        @endif
    </div>
    @endif

    <!-- Balance Edit Modal (hidden on print) -->
    <div id="balanceModal" class="no-print" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
        <div class="card animate-fade" style="width: 480px; max-width: 95%; background: white;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h4 style="font-weight: 700; margin: 0;"><i class="fas fa-edit" style="color: var(--primary); margin-right: 8px;"></i>Edit Leave Balance</h4>
                <button type="button" style="background:none; border:none; font-size:1.3rem; cursor:pointer; color: var(--secondary);" onclick="document.getElementById('balanceModal').style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p style="color: var(--secondary); font-size: 0.85rem; margin-bottom: 20px;">Set the <strong>beginning balance</strong> for <strong>{{ $employee->full_name }}</strong> for Year <strong>{{ $year }}</strong>. The running balance will be recalculated automatically from transactions.</p>
            <form action="{{ route('leave-cards.adjust', $employee) }}" method="POST">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label class="form-label">VL Beginning Balance</label>
                        <input type="number" name="vl_beginning_balance" class="form-control" step="0.001" value="{{ $leaveCard->vl_beginning_balance ?? 0 }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">SL Beginning Balance</label>
                        <input type="number" name="sl_beginning_balance" class="form-control" step="0.001" value="{{ $leaveCard->sl_beginning_balance ?? 0 }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Forced Leave</label>
                        <input type="number" name="forced_leave_balance" class="form-control" step="0.001" value="{{ $leaveCard->forced_leave_balance ?? 5 }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Special Privilege Leave</label>
                        <input type="number" name="special_leave_balance" class="form-control" step="0.001" value="{{ $leaveCard->special_leave_balance ?? 3 }}">
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="flex:1; justify-content: center;">
                        <i class="fas fa-save"></i> Save Balance
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('balanceModal').style.display='none'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* ═══════════════════════════════════════════════════════════
       OFFICIAL LEAVE CARD TABLE STYLES
       ═══════════════════════════════════════════════════════════ */
    .leave-card-form {
        background: white;
        padding: 32px;
    }

    .leave-card-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.78rem;
        font-family: 'Outfit', sans-serif;
    }

    .leave-card-table th,
    .leave-card-table td {
        border: 1.5px solid #334155;
        padding: 6px 8px;
        text-align: center;
        vertical-align: middle;
    }

    .leave-card-table thead th {
        background: #f8fafc;
        font-weight: 800;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        color: #1e293b;
    }

    .leave-card-table .group-header {
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 8px;
    }

    .leave-card-table .vl-header {
        background: #eff6ff;
        color: #1e40af;
    }

    .leave-card-table .sl-header {
        background: #ecfdf5;
        color: #065f46;
    }

    .leave-card-table .sub-header {
        font-size: 0.65rem;
        padding: 5px 4px;
        line-height: 1.2;
        background: #f8fafc;
    }

    .leave-card-table tbody td {
        font-size: 0.8rem;
        height: 28px;
    }

    .leave-card-table .beginning-row {
        background: #fffbeb;
    }

    .leave-card-table .beginning-row td {
        font-weight: 600;
    }

    .leave-card-table .num-cell {
        font-family: 'Outfit', monospace;
        font-weight: 500;
    }

    .leave-card-table .bal-cell {
        font-family: 'Outfit', monospace;
        font-weight: 700;
        background: #fafbfc;
    }

    .leave-card-table .empty-row td {
        height: 26px;
    }

    .leave-card-table tbody tr:hover {
        background: #f1f5f9;
    }

    .leave-card-table .empty-row:hover {
        background: transparent;
    }

    /* ═══════════════════════════════════════════════════════════
       PRINT STYLES
       ═══════════════════════════════════════════════════════════ */
    @media print {
        body {
            background: white !important;
        }

        .no-print,
        .sidebar,
        .header {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
            padding: 0 !important;
        }

        .leave-card-form {
            box-shadow: none !important;
            border: none !important;
            padding: 20px !important;
        }

        .leave-card-table th,
        .leave-card-table td {
            border-color: #000 !important;
        }

        .leave-card-table thead th {
            background: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .leave-card-table .vl-header,
        .leave-card-table .sl-header {
            background: #e8e8e8 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .leave-card-table tbody tr:hover {
            background: transparent !important;
        }
    }
</style>
@endpush
@endsection
