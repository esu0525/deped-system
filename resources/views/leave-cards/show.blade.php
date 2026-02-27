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
                        <th rowspan="2" style="width: 100px;">PERIOD</th>
                        <th rowspan="2" style="width: 160px;">PARTICULARS</th>
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
                        <td class="date-col" style="font-weight: 700; text-transform: uppercase; font-size: 0.75rem;">BAL. AS OF: 12/31/{{ $leaveCard->year - 1 }}</td>
                        <td class="particulars-col"></td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell" style="padding: 0;">
                            @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
                                <input type="number" id="vlBeginningBalance" value="{{ $leaveCard->vl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                            @else
                                {{ number_format($leaveCard->vl_beginning_balance, 3) }}
                            @endif
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="bal-cell" style="padding: 0;">
                            @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
                                <input type="number" id="slBeginningBalance" value="{{ $leaveCard->sl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                            @else
                                {{ number_format($leaveCard->sl_beginning_balance, 3) }}
                            @endif
                        </td>
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
                    @endphp
                    <tr class="tx-row" data-id="{{ $trans->id }}">
                        <!-- Period -->
                        @php
                            // If it's an empty transaction row that exists but has no date
                            $rawPeriod = $trans->period ?? ($trans->transaction_date ? $trans->transaction_date->format('m/d/Y') : '');
                            $rawParticulars = $trans->remarks ?: ($isEarned ? '' : ($trans->leaveType->code ?? ''));
                            
                            $isLess = strpos(strtoupper($rawPeriod), 'LESS') !== false || $isUsed;
                            $textColor = $isLess ? 'color: #dc2626;' : 'color: #000;';
                        @endphp
                        <td class="edit-cell date-col" style="{{ $textColor }} font-weight: 600;" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $rawPeriod }}</td>
                        <!-- Particulars -->
                        <td class="edit-cell particulars-col" style="font-size: 0.85rem; {{ $textColor }} font-weight: 600;" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $rawParticulars }}</td>

                        <!-- VL columns -->
                        <td class="num-cell edit-cell vl-earned-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->vl_earned !== null ? (float)$trans->vl_earned : (($isVL && $isEarned) ? (float)$trans->days : ($isStrictlySL ? '' : '')) }}</td>
                        <td class="num-cell edit-cell vl-used-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->vl_used !== null ? (float)$trans->vl_used : (($isVL && $isUsed) ? (float)$trans->days : ($isStrictlySL ? '' : '')) }}</td>
                        <td class="bal-cell edit-cell vl-bal-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $isStrictlySL ? '-' : (float)$trans->vl_balance_after }}</td>
                        <td class="num-cell edit-cell vl-wop-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->vl_wop !== null ? (float)$trans->vl_wop : '' }}</td>

                        <!-- SL columns -->
                        <td class="num-cell edit-cell sl-earned-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->sl_earned !== null ? (float)$trans->sl_earned : (($isSL && $isEarned) ? (float)$trans->days : ($isStrictlyVL ? '' : '')) }}</td>
                        <td class="num-cell edit-cell sl-used-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->sl_used !== null ? (float)$trans->sl_used : (($isSL && $isUsed) ? (float)$trans->days : ($isStrictlyVL ? '' : '')) }}</td>
                        <td class="bal-cell edit-cell sl-bal-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $isStrictlyVL ? '-' : (float)$trans->sl_balance_after }}</td>
                        <td class="num-cell edit-cell sl-wop-col" style="{{ $textColor }}" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->sl_wop !== null ? (float)$trans->sl_wop : '' }}</td>

                        <!-- Date & Action -->
                        <td class="edit-cell action-col" style="{{ $textColor }} font-size: 0.72rem; text-align: center;" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}>{{ $trans->action_taken ?: ($isUsed ? (($trans->encoder ? explode(' ', trim($trans->encoder->name))[0] . ' ' : '') . $trans->transaction_date->format('m/d/Y')) : '') }}</td>
                    </tr>
                    @empty
                    @endforelse

                    <!-- Empty rows to fill the form (for print appearance) OR for new manual additions -->
                    @for($i = $transactions->count(); $i < 20; $i++)
                    <tr class="tx-row empty-row">
                        <td class="edit-cell date-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="edit-cell particulars-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell vl-earned-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell vl-used-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="bal-cell edit-cell vl-bal-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell vl-wop-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell sl-earned-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell sl-used-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="bal-cell edit-cell sl-bal-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="num-cell edit-cell sl-wop-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
                        <td class="edit-cell action-col" {{ auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']) ? 'contenteditable=true' : '' }}></td>
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
        table-layout: fixed; /* Rigid dimensions to allow textual bleed over true borders */
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

    /* Seamless "Period -> Particulars" bleed (tumagos sa particulars) with actual border! */
    .leave-card-table .date-col {
        position: relative;
        z-index: 10;
        text-align: left;
        padding-left: 10px;
        white-space: nowrap; 
        overflow: visible; /* Text bursts through the table wall */
        outline: none;
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

    .inline-edit-input {
        width: 100%;
        height: 100%;
        border: none;
        background: transparent;
        text-align: center;
        font-family: inherit;
        font-weight: inherit;
        outline: none;
        color: inherit;
        padding: 6px; /* matching td padding */
        min-width: 60px;
    }
    
    .inline-edit-input:focus {
        background: rgba(255, 255, 255, 0.5);
        box-shadow: inset 0 0 0 2px var(--primary);
    }
    
    /* hide arrows */
    .inline-edit-input::-webkit-outer-spin-button,
    .inline-edit-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .inline-edit-input[type=number] {
        -moz-appearance: textfield;
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
    /* Auto-save visual indicator */
    .save-indicator {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        opacity: 0;
        transition: opacity 0.3s;
        z-index: 9999;
        pointer-events: none;
    }

    .edit-cell:focus {
        outline: 2px solid var(--primary);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: inset 0 0 5px rgba(0,0,0,0.1);
    }
</style>

<div class="save-indicator" id="saveIndicator"><i class="fas fa-check-circle"></i> LEDGER SAVED</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Debounce timer for auto-save
        let saveTimeout;
        const indicator = document.getElementById('saveIndicator');

        // Beginning Balance Auto-Save
        document.querySelectorAll('.inline-edit-input').forEach(input => {
            input.addEventListener('input', function() {
                scheduleSave();
            });
        });

        // Grid Auto-Save
        document.querySelectorAll('.edit-cell').forEach(cell => {
            // Prevent Enter from making new divs/lines and blur instead
            cell.addEventListener('keydown', function(e) {
                if(e.key === 'Enter') {
                    e.preventDefault();
                    cell.blur();
                }
            });

            cell.addEventListener('input', function() {
                scheduleSave();
            });
        });

        function scheduleSave() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveGrid, 1200); // Wait 1.2s after they stop typing
        }

        function saveGrid() {
            // Collect Beginning Balance (save via old route to recalculate balances optionally OR just rely on new logic)
            // But we actually just use the adjust endpoint for beginning balances.
            
            // For the Grid: Collect all rows
            const rows = document.querySelectorAll('.tx-row');
            let transactions = [];

            rows.forEach(row => {
                let isEmpty = true;
                const data = {
                    id: row.getAttribute('data-id') || '',
                    date_text: row.querySelector('.date-col')?.innerText.trim() || '',
                    particulars: row.querySelector('.particulars-col')?.innerText.trim() || '',
                    vl_earned: row.querySelector('.vl-earned-col')?.innerText.trim() || '',
                    vl_used: row.querySelector('.vl-used-col')?.innerText.trim() || '',
                    vl_balance: row.querySelector('.vl-bal-col')?.innerText.trim() || '',
                    vl_wop: row.querySelector('.vl-wop-col')?.innerText.trim() || '',
                    sl_earned: row.querySelector('.sl-earned-col')?.innerText.trim() || '',
                    sl_used: row.querySelector('.sl-used-col')?.innerText.trim() || '',
                    sl_balance: row.querySelector('.sl-bal-col')?.innerText.trim() || '',
                    sl_wop: row.querySelector('.sl-wop-col')?.innerText.trim() || '',
                    action_taken: row.querySelector('.action-col')?.innerText.trim() || '',
                };

                // Check if row actually has data typed into it
                for(let key in data) {
                    if(key !== 'id' && data[key] !== '') {
                        isEmpty = false;
                        break;
                    }
                }

                if(!isEmpty) {
                    transactions.push(data);
                }
            });

            // Send to backend
            fetch("{{ route('leave-cards.sync-transactions', $employee) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    year: '{{ $year }}',
                    transactions: transactions
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Show saved indicator briefly
                    indicator.style.opacity = '1';
                    setTimeout(() => indicator.style.opacity = '0', 2000);
                } else {
                    console.error('Failed to sync ledger grid');
                }
            })
            .catch(err => {
                console.error(err);
            });
        }
    });

    // We still keep the dedicated beginning balance logic for instantaneous updates there
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.inline-edit-input').forEach(input => {
            input.addEventListener('change', function() {
                let vl = document.getElementById('vlBeginningBalance').value;
                let sl = document.getElementById('slBeginningBalance').value;
                
                fetch("{{ route('leave-cards.adjust', $employee) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        year: '{{ $year }}',
                        vl_beginning_balance: vl || 0,
                        sl_beginning_balance: sl || 0
                    })
                })
                .then(res => res.json())
                .then(data => {});
            });
        });
    });
</script>
@endpush
@endsection
