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
    @php
        // Updated to match official form provided in images
        $FRONT_DATA_ROWS = 10; // Total enterable rows on front (including balance)
        $BACK_DATA_ROWS = 14;  // Total enterable rows on back
        
        $transactionsArray = $transactions->toArray();
        $pages = [];
        
        // 1. CARD 1 FRONT (9 transactions + 1 Beginning Balance = 10 rows)
        $pages[] = [
            'type' => 'front',
            'is_first' => true,
            'items' => array_slice($transactionsArray, 0, $FRONT_DATA_ROWS - 1)
        ];
        
        // 2. CARD 1 BACK (14 rows)
        $remaining = array_slice($transactionsArray, $FRONT_DATA_ROWS - 1);
        $pages[] = [
            'type' => 'back',
            'is_first' => false,
            'items' => array_slice($remaining, 0, $BACK_DATA_ROWS)
        ];
        $remaining = array_slice($remaining, $BACK_DATA_ROWS);
        
        // 3. CONTINUATION CARDS (Back capacity used for both sides if continuing)
        while (count($remaining) > 0) {
            $pages[] = [
                'type' => 'front',
                'is_first' => false,
                'items' => array_slice($remaining, 0, $BACK_DATA_ROWS)
            ];
            $remaining = array_slice($remaining, $BACK_DATA_ROWS);
            
            $pages[] = [
                'type' => 'back',
                'is_first' => false,
                'items' => array_slice($remaining, 0, $BACK_DATA_ROWS)
            ];
            $remaining = array_slice($remaining, $BACK_DATA_ROWS);
        }
    @endphp

    @foreach($pages as $pageIndex => $page)
    <!-- Card Side {{ $pageIndex + 1 }}: {{ strtoupper($page['type']) }} -->
    <div class="card leave-card-form {{ $page['type'] === 'front' ? 'front-page-side' : 'back-page-side' }} print-page">
        @if($page['type'] === 'front' && $page['is_first'])
            <!-- Official Leave Card Header (Only on Card 1 Front) -->
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 0.9rem; font-weight: 700; margin: 0; text-transform: uppercase;">{{ \App\Models\SystemSetting::get('division_office_name', 'SCHOOLS DIVISION OFFICE-QUEZON CITY') }}</p>
                <p style="font-size: 0.8rem; color: var(--dark); margin: 3px 0 14px;">{{ \App\Models\SystemSetting::get('division_office_address', 'Nueva Ecija St., Bago Bantay, Quezon City') }}</p>
                <h3 style="font-weight: 800; font-size: 1rem; text-decoration: underline; text-transform: uppercase;">Leave Card Non-Teaching Personnel</h3>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px 40px; margin-bottom: 20px; font-size: 0.85rem;">
                <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1.5px solid #000; padding-bottom: 2px;">
                    <span style="font-weight: 700; white-space: nowrap;">Name:</span>
                    <span style="flex: 1;">{{ $employee->full_name }}</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1.5px solid #000; padding-bottom: 2px;">
                    <span style="font-weight: 700; white-space: nowrap;">Designation:</span>
                    <span style="flex: 1;">{{ $employee->position ?? '—' }}</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1.5px solid #000; padding-bottom: 2px;">
                    <span style="font-weight: 700; white-space: nowrap;">Station:</span>
                    <span style="flex: 1;">{{ $employee->department->name ?? '—' }}</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: baseline; border-bottom: 1.5px solid #000; padding-bottom: 2px;">
                    <span style="font-weight: 700; white-space: nowrap;">Status:</span>
                    <span style="flex: 1;">{{ $employee->employment_status ?? 'Permanent' }}</span>
                </div>
            </div>
        @elseif($pageIndex > 0)
             <div style="text-align: center; margin-bottom: 8px;" class="no-print">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary); text-transform: uppercase;">
                    <i class="fas fa-rotate"></i> Continuation - Card {{ ceil(($pageIndex + 1)/2) }} {{ $page['type'] === 'front' ? 'Front' : 'Back' }}
                </span>
            </div>
        @endif

        <div style="overflow-x: auto;">
            <table class="leave-card-table">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 100px;">PERIOD</th>
                        <th rowspan="2" style="width: 160px;">PARTICULARS</th>
                        <th colspan="4" class="group-header vl-header">Vacation Leave</th>
                        <th colspan="4" class="group-header sl-header">Sick Leave</th>
                        <th rowspan="2" style="width: 100px;">Date & Action<br>Taken on<br>APPL. For Leave</th>
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
                    @if($page['type'] === 'front' && $page['is_first'])
                        <tr class="beginning-row">
                            <td class="date-col" style="font-weight: 700; font-size: 0.68rem; line-height: 1;">BAL. AS OF: 12/31/{{ $leaveCard->year - 1 }}</td>
                            <td class="particulars-col"></td>
                            <td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px;">
                                <span class="no-print">
                                    <input type="number" id="vlBeginningBalance" value="{{ $leaveCard->vl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                                </span>
                                <span class="print-only" style="font-weight: 700;">{{ number_format($leaveCard->vl_beginning_balance, 3) }}</span>
                            </td>
                            <td></td><td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px;">
                                <span class="no-print">
                                    <input type="number" id="slBeginningBalance" value="{{ $leaveCard->sl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                                </span>
                                <span class="print-only" style="font-weight: 700;">{{ number_format($leaveCard->sl_beginning_balance, 3) }}</span>
                            </td>
                            <td></td><td></td>
                        </tr>
                    @endif

                    @foreach($page['items'] as $item)
                        @php
                            $isLess = strpos(strtoupper($item['period'] ?? ''), 'LESS') !== false;
                            $isWop = strpos(strtoupper($item['period'] ?? ''), '(WOP)') !== false;
                            $textColor = $isLess ? 'color: #dc2626;' : 'color: #000;';
                            if ($isWop) $textColor = 'color: #7c2d12;'; // Brownish for WOP
                        @endphp
                        <tr class="tx-row" data-id="{{ $item['id'] ?? '' }}">
                            <td class="edit-cell date-col" style="{{ $textColor }} font-weight: 600;" contenteditable="true">{{ $item['period'] ?? '' }}</td>
                            <td class="edit-cell particulars-col" style="font-size: 0.85rem; {{ $textColor }} font-weight: 600;" contenteditable="true">{{ $item['remarks'] ?? '' }}</td>
                            <td class="num-cell edit-cell vl-earned-col" style="{{ $textColor }}">{{ (float)($item['vl_earned'] ?? '') ?: '' }}</td>
                            <td class="num-cell edit-cell vl-used-col" style="{{ $textColor }}">{{ (float)($item['vl_used'] ?? '') ?: '' }}</td>
                            <td class="bal-cell edit-cell vl-bal-col" style="{{ $textColor }}">
                                {{ $isWop ? '-' : (isset($item['vl_balance_after']) ? number_format($item['vl_balance_after'], 3) : '') }}
                            </td>
                            <td class="num-cell edit-cell vl-wop-col" style="{{ $textColor }}">
                                @if((float)($item['vl_wop'] ?? 0) > 0)
                                    w/o {{ (float)$item['vl_wop'] }}
                                @endif
                            </td>
                            <td class="num-cell edit-cell sl-earned-col" style="{{ $textColor }}">{{ (float)($item['sl_earned'] ?? '') ?: '' }}</td>
                            <td class="num-cell edit-cell sl-used-col" style="{{ $textColor }}">{{ (float)($item['sl_used'] ?? '') ?: '' }}</td>
                            <td class="bal-cell edit-cell sl-bal-col" style="{{ $textColor }}">
                                {{ $isWop ? '-' : (isset($item['sl_balance_after']) ? number_format($item['sl_balance_after'], 3) : '') }}
                            </td>
                            <td class="num-cell edit-cell sl-wop-col" style="{{ $textColor }}">
                                @if((float)($item['sl_wop'] ?? 0) > 0)
                                    w/o {{ (float)$item['sl_wop'] }}
                                @endif
                            </td>
                            <td class="edit-cell action-col" style="{{ $textColor }} font-size: 0.65rem; line-height: 1; text-align: center;">{{ $item['action_taken'] ?? '' }}</td>
                        </tr>
                    @endforeach

                    {{-- Fill empty rows to maintain card height --}}
                    @php
                        $maxRows = ($page['type'] === 'front' && $page['is_first']) ? $FRONT_DATA_ROWS - 1 : $BACK_DATA_ROWS;
                        $emptyRowsNeeded = $maxRows - count($page['items']);
                    @endphp
                    @for($i = 0; $i < $emptyRowsNeeded; $i++)
                        <tr class="tx-row empty-row">
                            <td class="edit-cell date-col" contenteditable="true"></td>
                            <td class="edit-cell particulars-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="bal-cell edit-cell" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="bal-cell edit-cell" contenteditable="true"></td>
                            <td class="num-cell edit-cell" contenteditable="true"></td>
                            <td class="edit-cell action-col" contenteditable="true"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    @endforeach

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
       PRINT STYLES — 1/2 Index Card (landscape 8"x5" half = ~8"x5")
       ═══════════════════════════════════════════════════════════ */
    @page {
        size: 8.5in 5.5in landscape;
        margin: 5mm;
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
        }

        .no-print,
        .sidebar,
        .header,
        .save-indicator {
            display: none !important;
        }

        .print-page {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 2mm 3mm !important;
            margin: 0 !important;
            page-break-after: always !important;
            height: 5.4in !important;
            width: 8.4in !important;
            display: block !important;
        }

        /* Readable header text */
        .leave-card-form > div:first-child p {
            font-size: 8pt !important;
            margin: 0 !important;
        }
        .leave-card-form > div:first-child h3 {
            font-size: 9pt !important;
            margin: 2px 0 8px !important;
        }

        /* Table sizing for physical card - Back to readable size */
        .leave-card-table {
            font-size: 7.5pt !important;
            table-layout: auto !important; /* Allow columns to breathe */
            width: 100% !important;
        }

        .leave-card-table th,
        .leave-card-table td {
            border: 1.2px solid #000 !important;
            padding: 3px 4px !important;
            line-height: 1.1 !important;
        }
        
        /* Fixed row height to fill the card */
        .leave-card-table tbody tr {
            height: 30px !important;
        }

        .leave-card-table thead th {
            background: #f0f0f0 !important;
            font-size: 6.5pt !important;
        }

        .leave-card-table .sub-header {
            font-size: 5.5pt !important;
        }

        .leave-card-table .bal-cell {
            font-size: 7pt !important;
            font-weight: 700 !important;
        }

        .print-only {
            display: block !important;
        }
        
        .no-print {
            display: none !important;
        }

        .leave-card-table .date-col {
            white-space: nowrap !important;
        }
    }

    .print-only {
        display: none;
    }

    /* Screen view separation */
    .print-page {
        margin-bottom: 40px;
        position: relative;
    }
    
    .back-page-side::before {
        content: "BACK SIDE";
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 0.7rem;
        font-weight: 800;
        color: var(--secondary);
        letter-spacing: 2px;
    }

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
