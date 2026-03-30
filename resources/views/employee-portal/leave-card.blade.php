@extends('layouts.app')

@section('header_title', 'My Leave Card')

@section('content')
<div class="animate-fade">
    {{-- Tab Pill Switcher (no-print) --}}
    <div class="no-print" style="margin-bottom: 25px;">
        <div style="display: flex; gap: 8px; background: #f1f5f9; padding: 6px; border-radius: 12px; width: fit-content; border: 1px solid #e2e8f0;">
            <button type="button" 
                    id="ledgerTabBtn" 
                    class="btn-tab-pill active" 
                    onclick="switchTab('ledger')" 
                    style="padding: 10px 24px; border-radius: 10px; font-weight: 800; border: none; font-size: 0.75rem; letter-spacing: 0.5px; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-address-book"></i> LEAVE LEDGER
            </button>
            <button type="button" 
                    id="ctoTabBtn" 
                    class="btn-tab-pill" 
                    onclick="switchTab('cto')" 
                    style="padding: 10px 24px; border-radius: 10px; font-weight: 800; border: none; font-size: 0.75rem; letter-spacing: 0.5px; background: transparent; color: #64748b; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-clock"></i> CTO BALANCES
            </button>
        </div>
    </div>

    {{-- Actions Bar --}}
    <div class="no-print" id="ledgerControls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <a href="{{ route('employee.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <form action="{{ route('employee.leave-card') }}" method="GET" style="display: flex; gap: 8px; align-items: center;">
                <select name="year" onchange="this.form.submit()" class="form-control" style="width: 130px;">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div id="leaveLedgerSection">
    {{-- Leave Balance Summary (no-print) --}}
    <div class="no-print" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div class="card glass" style="padding: 20px; border-left: 4px solid #3b82f6;">
            <p style="font-size: 0.75rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; margin: 0 0 4px;">VL Balance</p>
            <h3 style="font-weight: 800; color: #1e40af; margin: 0;">{{ number_format($leaveCard->vl_balance ?? 0, 3) }}</h3>
        </div>
        <div class="card glass" style="padding: 20px; border-left: 4px solid #10b981;">
            <p style="font-size: 0.75rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; margin: 0 0 4px;">SL Balance</p>
            <h3 style="font-weight: 800; color: #065f46; margin: 0;">{{ number_format($leaveCard->sl_balance ?? 0, 3) }}</h3>
        </div>
        <div class="card glass" style="padding: 20px; border-left: 4px solid #f59e0b;">
            <p style="font-size: 0.75rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; margin: 0 0 4px;">VL Beginning Balance</p>
            <h3 style="font-weight: 800; color: #92400e; margin: 0;">{{ number_format($leaveCard->vl_beginning_balance ?? 0, 3) }}</h3>
        </div>
        <div class="card glass" style="padding: 20px; border-left: 4px solid #8b5cf6;">
            <p style="font-size: 0.75rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; margin: 0 0 4px;">SL Beginning Balance</p>
            <h3 style="font-weight: 800; color: #5b21b6; margin: 0;">{{ number_format($leaveCard->sl_beginning_balance ?? 0, 3) }}</h3>
        </div>
    </div>

    {{-- Official Leave Card Form (read-only) --}}
    <div class="card leave-card-form front-page-card" id="printable-card">
        {{-- Form Header --}}
        <div style="text-align: center; margin-bottom: 20px;">
            <p style="font-size: 0.95rem; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Models\SystemSetting::get('division_office_name', 'SCHOOLS DIVISION OFFICE-QUEZON CITY') }}</p>
            <p style="font-size: 0.82rem; color: var(--dark); margin: 3px 0 14px;">{{ \App\Models\SystemSetting::get('division_office_address', 'Nueva Ecija St., Bago Bantay, Quezon City') }}</p>
            <h3 style="font-weight: 800; font-size: 1.1rem; text-decoration: underline; text-transform: uppercase; letter-spacing: 1px;">Leave Card Non-Teaching Personnel</h3>
        </div>

        {{-- Employee Info Fields --}}
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

        {{-- Official Ledger Table (READ-ONLY) --}}
        <div style="overflow-x: auto;">
            <table class="leave-card-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 100px;">PERIOD</th>
                        <th rowspan="2" style="width: 160px;">PARTICULARS</th>
                        <th colspan="4" class="group-header vl-header">Vacation Leave</th>
                        <th colspan="4" class="group-header sl-header">Sick Leave</th>
                        <th rowspan="2" style="width: 100px;">Date & Action<br>Taken on<br>Appl. for Leave</th>
                    </tr>
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
                    {{-- Beginning Balance Row --}}
                    <tr class="beginning-row">
                        <td class="date-col" style="font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">BAL. AS OF: 12/31/{{ ($leaveCard->year ?? $year) - 1 }}</td>
                        <td class="particulars-col"></td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell">{{ number_format($leaveCard->vl_beginning_balance ?? 0, 3) }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell">{{ number_format($leaveCard->sl_beginning_balance ?? 0, 3) }}</td>
                        <td></td>
                        <td></td>
                    </tr>

                    @forelse($transactions as $trans)
                    @php
                        $code = $trans->leaveType->code ?? '';
                        $isVL = in_array($code, ['VL', 'FL']);
                        $isSL = $code === 'SL';
                        $isEarned = $trans->transaction_type === 'earned';
                        $isUsed = $trans->transaction_type === 'used';
                        
                        $isStrictlyVL = ($isUsed && $isVL);
                        $isStrictlySL = ($isUsed && $isSL);
                        $isSpecialLeave = ($isUsed && !$isVL && !$isSL);
                    @endphp
                    <tr class="tx-row">
                        @php
                            $rawPeriod = $trans->period ?? ($trans->transaction_date ? $trans->transaction_date->format('m/d/Y') : '');
                            $rawParticulars = $trans->remarks ?: ($isEarned ? '' : ($trans->leaveType->code ?? ''));
                            
                            $isLess = strpos(strtoupper($rawPeriod), 'LESS') !== false || $isUsed;
                            $textColor = $isLess ? 'color: #dc2626;' : 'color: #000;';
                        @endphp
                        <td class="date-col" style="{{ $textColor }} font-weight: 600;">{{ $rawPeriod }}</td>
                        <td class="particulars-col" style="font-size: 0.85rem; {{ $textColor }} font-weight: 600;">{{ $rawParticulars }}</td>

                        {{-- VL columns --}}
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->vl_earned !== null ? (float)$trans->vl_earned : (($isVL && $isEarned) ? (float)$trans->days : ($isStrictlySL ? '' : ''))) }}</td>
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->vl_used !== null ? (float)$trans->vl_used : (($isVL && $isUsed) ? (float)$trans->days : ($isStrictlySL ? '' : ''))) }}</td>
                        <td class="bal-cell" style="{{ $textColor }}">{{ ($isStrictlySL || $isSpecialLeave) ? '-' : (float)$trans->vl_balance_after }}</td>
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->vl_wop !== null ? (float)$trans->vl_wop : '') }}</td>

                        {{-- SL columns --}}
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->sl_earned !== null ? (float)$trans->sl_earned : (($isSL && $isEarned) ? (float)$trans->days : ($isStrictlyVL ? '' : ''))) }}</td>
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->sl_used !== null ? (float)$trans->sl_used : (($isSL && $isUsed) ? (float)$trans->days : ($isStrictlyVL ? '' : ''))) }}</td>
                        <td class="bal-cell" style="{{ $textColor }}">{{ ($isStrictlyVL || $isSpecialLeave) ? '-' : (float)$trans->sl_balance_after }}</td>
                        <td class="num-cell" style="{{ $textColor }}">{{ $isSpecialLeave ? '' : ($trans->sl_wop !== null ? (float)$trans->sl_wop : '') }}</td>

                        {{-- Date & Action --}}
                        <td style="{{ $textColor }} font-size: 0.72rem; text-align: center;">{{ $trans->action_taken ?: ($isUsed ? (($trans->encoder ? explode(' ', trim($trans->encoder->name))[0] . ' ' : '') . $trans->transaction_date->format('m/d/Y')) : '') }}</td>
                    </tr>
                    @empty
                    @endforelse

                    {{-- Empty rows for print appearance --}}
                    @for($i = $transactions->count(); $i < 20; $i++)
                    <tr class="tx-row empty-row">
                        <td class="date-col"></td>
                        <td class="particulars-col"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="bal-cell"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="bal-cell"></td>
                        <td class="num-cell"></td>
                        <td></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    {{-- Back Page --}}
    <div class="card leave-card-form back-page-card" id="back-page">
        <div style="text-align: center; margin-bottom: 8px;" class="no-print">
            <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px;">
                <i class="fas fa-rotate"></i> Back Page (Continuation)
            </span>
        </div>

        <div style="overflow-x: auto;">
            <table class="leave-card-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 100px;">PERIOD</th>
                        <th rowspan="2" style="width: 160px;">PARTICULARS</th>
                        <th colspan="4" class="group-header vl-header">Vacation Leave</th>
                        <th colspan="4" class="group-header sl-header">Sick Leave</th>
                        <th rowspan="2" style="width: 100px;">Date & Action<br>Taken on<br>Appl. for Leave</th>
                    </tr>
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
                    @for($i = 0; $i < 25; $i++)
                    <tr class="tx-row empty-row back-page-row">
                        <td class="date-col"></td>
                        <td class="particulars-col"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="bal-cell"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="num-cell"></td>
                        <td class="bal-cell"></td>
                        <td class="num-cell"></td>
                        <td class="action-col"></td>
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    </div>

    {{-- CTO Balances Section --}}
    <div id="ctoBalancesSection" style="display: none;">
        {{-- Official CTO Card Form (read-only) --}}
        <div class="card leave-card-form front-page-card" id="cto-printable-card">
            {{-- Form Header --}}
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 0.95rem; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">{{ \App\Models\SystemSetting::get('division_office_name', 'SCHOOLS DIVISION OFFICE-QUEZON CITY') }}</p>
                <p style="font-size: 0.82rem; color: var(--dark); margin: 3px 0 14px;">{{ \App\Models\SystemSetting::get('division_office_address', 'Nueva Ecija St., Bago Bantay, Quezon City') }}</p>
                <h3 style="font-weight: 800; font-size: 1.1rem; text-decoration: underline; text-transform: uppercase; letter-spacing: 1px;">COMPENSATORY TIME OFF (CTO) CARD NON-TEACHING PERSONNEL</h3>
            </div>

            {{-- Employee Info Fields (Same as main card) --}}
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

            {{-- CTO Ledger Table --}}
            <div style="overflow-x: auto;">
                <table class="leave-card-table">
                    <colgroup>
                        <col style="width: 100px;"> {{-- Period --}}
                        <col style="width: 150px;"> {{-- Particulars --}}
                        <col style="width: 280px;"> {{-- Title --}}
                        <col style="width: 65px;">  {{-- Earned --}}
                        <col style="width: 65px;">  {{-- Used --}}
                        <col style="width: 65px;">  {{-- Bal --}}
                        <col style="width: 120px;"> {{-- Action --}}
                    </colgroup>
                    <thead>
                        <tr>
                            <th rowspan="2">PERIOD</th>
                            <th rowspan="2">PARTICULARS</th>
                            <th colspan="4" class="group-header" style="background: #ecfdf5; color: #065f46;">COMPENSATORY TIME OFF (CTO)</th>
                            <th rowspan="2">DATE & ACTION<br>TAKEN ON<br>APPL. FOR LEAVE</th>
                        </tr>
                        <tr>
                            <th class="sub-header">TITLE</th>
                            <th class="sub-header">EARNED</th>
                            <th class="sub-header">USED</th>
                            <th class="sub-header">BAL.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ctoTransactions = $transactions->filter(function($t) {
                                return $t->cto_title || ($t->leaveType && str_contains(strtoupper($t->leaveType->code), 'CTO'));
                            });
                        @endphp

                        @forelse($ctoTransactions as $trans)
                        <tr class="tx-row">
                            <td class="date-col" style="font-weight: 600;">{{ $trans->period ?? ($trans->transaction_date ? $trans->transaction_date->format('m/d/Y') : '') }}</td>
                            <td class="particulars-col" style="font-size: 0.85rem; font-weight: 600;">{{ $trans->remarks ?: '' }}</td>
                            <td style="font-size: 0.72rem; text-align: left; padding-left: 5px;">{{ $trans->cto_title }}</td>
                            <td class="num-cell" style="color: #065f46;">{{ $trans->cto_earned ? (float)$trans->cto_earned : '' }}</td>
                            <td class="num-cell" style="color: #dc2626;">{{ $trans->cto_used ? (float)$trans->cto_used : '' }}</td>
                            <td class="bal-cell" style="font-weight: 800;">{{ (float)$trans->cto_balance_after }}</td>
                            <td style="font-size: 0.72rem; text-align: center;">{{ $trans->action_taken ?: (($trans->encoder ? explode(' ', trim($trans->encoder->name))[0] . ' ' : '') . $trans->transaction_date->format('m/d/Y')) }}</td>
                        </tr>
                        @empty
                        @endforelse

                        {{-- Finalize appearance with blank rows (always show 15 rows) --}}
                        @for($i = $ctoTransactions->count(); $i < 15; $i++)
                        <tr class="tx-row empty-row">
                            <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- CTO Balances Summary (Current total) --}}
            <div class="no-print" style="margin-top: 24px;">
                <h5 style="font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-wallet" style="color: #7c3aed;"></i> Current CTO Balances
                </h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    @forelse($employee->ctoBalances() as $cto)
                    <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; border-left: 4px solid #7c3aed;">
                        <p style="font-size: 0.65rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin: 0 0 4px;">{{ $cto->cto_title }}</p>
                        <h4 style="font-weight: 800; color: #1e293b; margin: 0;">{{ number_format($cto->balance, 3) }} <small style="font-size: 0.65rem;">Days</small></h4>
                    </div>
                    @empty
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .btn-tab-pill.active {
        background: #fff;
        color: #1e293b;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* ═══════════════════════════════════════════════════════════
       OFFICIAL LEAVE CARD TABLE STYLES (Employee Read-Only)
       ═══════════════════════════════════════════════════════════ */
    .leave-card-form {
        background: white;
        padding: 32px;
    }

    .leave-card-table {
        width: 100%;
        table-layout: fixed;
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

    .leave-card-table .date-col {
        position: relative;
        z-index: 10;
        text-align: left;
        padding-left: 10px;
        white-space: nowrap;
        overflow: visible;
    }

    .leave-card-table .particulars-col {
        text-align: left;
        padding-left: 5px;
        white-space: nowrap;
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
    @page {
        size: 8in 5in landscape;
        margin: 4mm 5mm;
    }

    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 7pt !important;
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
            border-radius: 0 !important;
            padding: 3mm 4mm !important;
            margin: 0 !important;
        }

        .leave-card-form > div:first-child p {
            font-size: 7pt !important;
            margin: 0 !important;
        }
        .leave-card-form > div:first-child h3 {
            font-size: 8pt !important;
            margin: 2px 0 6px !important;
        }

        .leave-card-form > div:nth-child(2) {
            gap: 4px 20px !important;
            margin-bottom: 6px !important;
            font-size: 7pt !important;
        }

        .leave-card-table {
            font-size: 6.5pt !important;
        }

        .leave-card-table th,
        .leave-card-table td {
            border: 1px solid #000 !important;
            padding: 1px 2px !important;
            line-height: 1.15 !important;
        }

        .leave-card-table thead th {
            background: #e8e8e8 !important;
            font-size: 5.5pt !important;
            padding: 1px 1px !important;
        }

        .leave-card-table .group-header {
            font-size: 6.5pt !important;
            padding: 2px !important;
        }

        .leave-card-table .sub-header {
            font-size: 5pt !important;
            padding: 1px 1px !important;
        }

        .leave-card-table .vl-header,
        .leave-card-table .sl-header {
            background: #e0e0e0 !important;
            color: #000 !important;
        }

        .leave-card-table tbody td {
            font-size: 6.5pt !important;
            height: 14px !important;
        }

        .leave-card-table .date-col {
            font-size: 6pt !important;
            padding-left: 2px !important;
        }

        .leave-card-table .particulars-col {
            font-size: 6pt !important;
            padding-left: 2px !important;
        }

        .leave-card-table .bal-cell {
            background: transparent !important;
        }

        .leave-card-table tbody tr:hover {
            background: transparent !important;
        }

        .leave-card-table .beginning-row {
            background: transparent !important;
        }

        .front-page-card .empty-row {
            display: none !important;
        }

        .back-page-card {
            page-break-before: always !important;
            margin-top: 0 !important;
        }
        
        .back-page-row {
            display: table-row !important;
        }
    }
</style>
@endpush
@push('scripts')
<script>
    function switchTab(tab) {
        const ledgerSection = document.getElementById('leaveLedgerSection');
        const ctoSection = document.getElementById('ctoBalancesSection');
        const ledgerControls = document.getElementById('ledgerControls');
        const ledgerBtn = document.getElementById('ledgerTabBtn');
        const ctoBtn = document.getElementById('ctoTabBtn');

        if (!ledgerSection || !ctoSection) return;

        if (tab === 'ledger') {
            ledgerSection.style.display = 'block';
            ctoSection.style.display = 'none';
            if (ledgerControls) ledgerControls.style.display = 'flex';
            
            ledgerBtn.classList.add('active');
            ledgerBtn.style.background = '#fff';
            ledgerBtn.style.color = '#1e293b';
            ledgerBtn.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';

            ctoBtn.classList.remove('active');
            ctoBtn.style.background = 'transparent';
            ctoBtn.style.color = '#64748b';
            ctoBtn.style.boxShadow = 'none';
        } else {
            ledgerSection.style.display = 'none';
            ctoSection.style.display = 'block';
            if (ledgerControls) ledgerControls.style.display = 'none';

            ctoBtn.classList.add('active');
            ctoBtn.style.background = '#fff';
            ctoBtn.style.color = '#1e293b';
            ctoBtn.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';

            ledgerBtn.classList.remove('active');
            ledgerBtn.style.background = 'transparent';
            ledgerBtn.style.color = '#64748b';
            ledgerBtn.style.boxShadow = 'none';
        }
    }
</script>
@endpush
@endsection
