@extends('layouts.app')

@section('header_title', 'Employee Leave Card')

@section('content')
<div class="animate-fade">
    <!-- Actions Bar (hidden on print) -->
    <div class="header-container no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('leave-cards.index') }}" class="btn btn-secondary" style="padding: 10px 20px;">
                <i class="fas fa-arrow-left"></i> Back to Ledger
            </a>
        <div class="tab-pill-container no-print" style="display: flex; background: #f1f5f9; padding: 5px; border-radius: 12px; gap: 5px; margin-left: 20px;">
            <a href="{{ route('leave-cards.show', [$employee->id, 'year' => $year, 'tab' => 'form6']) }}" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: {{ $tab !== 'cto' ? '#1e293b' : '#64748b' }}; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; {{ $tab !== 'cto' ? 'background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);' : '' }} transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-lines"></i> FORM 6 (VL/SL)
            </a>
            <a href="{{ route('leave-cards.show', [$employee->id, 'year' => $year, 'tab' => 'cto']) }}" 
               class="tab-pill-item" 
               style="padding: 10px 20px; text-decoration: none; color: {{ $tab === 'cto' ? '#1e293b' : '#64748b' }}; font-weight: 800; font-size: 0.75rem; letter-spacing: 0.05em; border-radius: 10px; {{ $tab === 'cto' ? 'background: #fff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);' : '' }} transition: all 0.2s; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-certificate"></i> CTO CARD
            </a>
        </div>
    </div>
    
    <div style="display: flex; gap: 10px; align-items: center;">
        <form action="{{ route('leave-cards.show', $employee->id) }}" method="GET" id="yearForm" style="display: flex; align-items: center; gap: 10px;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <label for="yearSelect" style="font-weight: 700; color: #475569; font-size: 0.85rem;">Select Year:</label>
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
        // Official 8x5 card slots
        $FRONT_DATA_ROWS = 14; 
        $BACK_DATA_ROWS = 20;  
        
        $transactionsArray = $transactions->toArray();
        
        // Filter transactions based on tab
        if ($tab === 'cto') {
            $transactionsArray = array_values(array_filter($transactionsArray, function($t) {
                return (isset($t['period']) && stripos($t['period'], 'CTO') !== false) || 
                       (isset($t['transaction_type']) && $t['transaction_type'] === 'earned' && stripos($t['period'], 'CTO') !== false);
            }));
        } else {
            $transactionsArray = array_values(array_filter($transactionsArray, function($t) {
                return stripos($t['period'] ?? '', 'CTO') === false;
            }));
        }

        $pages = [];
        $tempRemaining = $transactionsArray;
        
        // Ensure at least one page is rendered even if there are no transactions
        if (empty($tempRemaining)) {
            $pages[] = [
                'type' => 'front',
                'show_header' => true,
                'is_very_first' => true,
                'items' => [],
                'slots_used' => 0
            ];
        } else {
            while (count($tempRemaining) > 0) {
            $pageCount = count($pages);
            $isFront = ($pageCount % 2 === 0);
            $maxRows = $isFront ? $FRONT_DATA_ROWS : $BACK_DATA_ROWS;
            $maxSlots = $maxRows - 1; // 1 for the balance/carried row
            
            $itemsForPage = [];
            $slotsUsed = 0;
            
            while (count($tempRemaining) > 0) {
                $it = $tempRemaining[0];
                $s = 1; // Base 1 slot
                $p = $it['period'] ?? '';
                $r = $it['remarks'] ?? '';
                $isC = stripos($p, 'CTO') !== false;
                $isL = strpos(strtoupper($p), 'LESS') !== false;
                
                // Estimate slots. CTO spans 7 cols, so it can handle ~100 chars per line
                if ($isC) {
                    $s = max($s, ceil(strlen($p) / 100));
                } else if ($isL) {
                    $s = max($s, ceil(strlen($p . ' ' . $r) / 40));
                } else {
                    $s = max($s, ceil(strlen($p) / 18));
                    $s = max($s, ceil(strlen($r) / 20));
                }
                
                // Estimate slots for WOP Reasons (Tight 10px line-height allows ~20 chars per slot)
                $vReason = trim($it['vl_wop_reason'] ?? '');
                if ($vReason) $s = max($s, ceil((10 + strlen($vReason)) / 20));
                $sReason = trim($it['sl_wop_reason'] ?? '');
                if ($sReason) $s = max($s, ceil((10 + strlen($sReason)) / 20));

                // If adding this item exceeds max slots, move to next page
                if ($slotsUsed + $s > $maxSlots && !empty($itemsForPage)) {
                    break;
                }
                
                $itemsForPage[] = array_shift($tempRemaining);
                $slotsUsed += $s;
                if ($slotsUsed >= $maxSlots) break;
            }
            
            $pages[] = [
                'type' => $isFront ? 'front' : 'back',
                'show_header' => ($pageCount === 0 || $isFront),
                'is_very_first' => ($pageCount === 0),
                'items' => $itemsForPage,
                'slots_used' => $slotsUsed
            ];
        }
        }
        
        // Ensure final back page for pairs
        if (count($pages) % 2 !== 0) {
            $pages[] = [
                'type' => 'back',
                'show_header' => false,
                'is_very_first' => false,
                'items' => [],
                'slots_used' => 0
            ];
        }
        
        $cardPairs = array_chunk($pages, 2);
    @endphp



    @foreach($cardPairs as $pairIndex => $pair)
    <div class="card-pair-group" id="cardPairGroup_{{ $pairIndex }}" style="{{ $pairIndex > 0 ? 'display: none;' : '' }}">
        @foreach($pair as $pageIndexOffset => $page)
        @php $pageIndex = ($pairIndex * 2) + $pageIndexOffset; @endphp
        <!-- Card Side {{ $pageIndex + 1 }}: {{ strtoupper($page['type']) }} -->
        <div class="card leave-card-form {{ $page['type'] === 'front' ? 'front-page-side' : 'back-page-side' }} print-page">
        @if($pageIndex > 0)
             <div style="text-align: center; margin-bottom: 8px;" class="no-print">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--secondary); text-transform: uppercase;">
                    <i class="fas fa-rotate"></i> Continuation - Card {{ ceil(($pageIndex + 1)/2) }} {{ $page['type'] === 'front' ? 'Front' : 'Back' }}
                </span>
            </div>
        @endif

        @if($page['show_header'])
            <!-- Official Leave Card Header -->
            <div style="text-align: center; margin-bottom: 20px;">
                <p style="font-size: 0.9rem; font-weight: 700; margin: 0; text-transform: uppercase;">{{ \App\Models\SystemSetting::get('division_office_name', 'SCHOOLS DIVISION OFFICE-QUEZON CITY') }}</p>
                <p style="font-size: 0.8rem; color: var(--dark); margin: 3px 0 14px;">{{ \App\Models\SystemSetting::get('division_office_address', 'Nueva Ecija St., Bago Bantay, Quezon City') }}</p>
                <h3 style="font-weight: 800; font-size: 1rem; text-decoration: underline; text-transform: uppercase;">{{ $tab === 'cto' ? 'Compensatory Time Off (CTO) Card' : 'Leave Card' }} Non-Teaching Personnel</h3>
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
        @endif

        <div style="overflow-x: auto;">
            <table class="leave-card-table">
                <colgroup>
                    <col style="width: 13%;">
                    <col style="width: 16%;">
                    <col style="width: 6.5%;">
                    <col style="width: 6%;">
                    <col style="width: 8.5%;">
                    <col style="width: 7%;">
                    <col style="width: 6.5%;">
                    <col style="width: 6%;">
                    <col style="width: 8.5%;">
                    <col style="width: 7%;">
                    <col style="width: 15%;">
                </colgroup>
                <thead>
                    <tr>
                        <th rowspan="2">PERIOD</th>
                        <th rowspan="2">PARTICULARS</th>
                        @if($tab === 'cto')
                            <th colspan="8" class="group-header cto-header" style="background: #f0fdf4; color: #166534;">Compensatory Time Off (CTO)</th>
                        @else
                            <th colspan="4" class="group-header vl-header">Vacation Leave</th>
                            <th colspan="4" class="group-header sl-header">Sick Leave</th>
                        @endif
                        <th rowspan="2">Date & Action<br>Taken on<br>APPL. For Leave</th>
                    </tr>
                    <tr>
                        @if($tab === 'cto')
                            <th colspan="5" class="sub-header">TITLE</th>
                            <th class="sub-header">EARNED</th>
                            <th class="sub-header">USED</th>
                            <th class="sub-header">BAL.</th>
                        @else
                            <th class="sub-header">EARNED</th>
                            <th class="sub-header">ABS.<br>UND.<br>W/P.</th>
                            <th class="sub-header">BAL.</th>
                            <th class="sub-header">ABS.<br>UND.<br>WOP.</th>
                            <th class="sub-header">EARNED</th>
                            <th class="sub-header">ABS.<br>UND.<br>W/P.</th>
                            <th class="sub-header">BAL.</th>
                            <th class="sub-header">ABS.<br>UND.<br>WOP.</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @if($page['is_very_first'] && $tab !== 'cto')
                        <tr style="height: 19px; background: #fffbeb;">
                            <td colspan="2" class="date-col bleed-cell" style="font-weight: 700; font-size: 0.68rem; line-height: 1.2; vertical-align: top; position: relative; padding: 0;">
                                <div class="bleed-content" style="padding: 2px 4px 1px 10px; white-space: nowrap !important; word-break: normal !important; overflow: visible !important;">BAL. AS OF: 12/31/{{ $leaveCard->year - 1 }}</div>
                            </td>
                            <td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px; vertical-align: middle;">
                                <span class="no-print">
                                    <input type="number" id="vlBeginningBalance" value="{{ $leaveCard->vl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                                </span>
                                <span class="print-only" style="font-weight: 700;">{{ number_format($leaveCard->vl_beginning_balance, 5) }}</span>
                            </td>
                            <td></td><td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px; vertical-align: middle;">
                                <span class="no-print">
                                    <input type="number" id="slBeginningBalance" value="{{ $leaveCard->sl_beginning_balance + 0 }}" step="any" class="inline-edit-input">
                                </span>
                                <span class="print-only" style="font-weight: 700;">{{ number_format($leaveCard->sl_beginning_balance, 5) }}</span>
                            </td>
                            <td></td><td></td>
                        </tr>
                    @elseif(!$page['is_very_first'] && $tab !== 'cto')
                        @php
                            // Calculate cumulative item index to find previous balance
                            $cumulativeIndex = 0;
                            for ($prev = 0; $prev < $pageIndex; $prev++) {
                                $cumulativeIndex += count($pages[$prev]['items']);
                            }
                            
                            // Get the last item BEFORE current page items
                            $lastTransIdx = $cumulativeIndex - 1;
                            $lastTrans = ($lastTransIdx >= 0 && isset($transactionsArray[$lastTransIdx])) ? $transactionsArray[$lastTransIdx] : null;
                            
                            $carriedDate = '—';
                            if ($lastTrans) {
                                if (!empty($lastTrans['transaction_date'])) {
                                    $carriedDate = \Carbon\Carbon::parse($lastTrans['transaction_date'])->format('m/d/y');
                                } else {
                                    $carriedDate = 'Cont.';
                                }
                            } else {
                                $carriedDate = '12/31/'.($leaveCard->year - 1);
                            }

                            // Get running balances from the most recent transaction that had activity
                            $carriedVl = $leaveCard->vl_beginning_balance;
                            for ($i = $lastTransIdx; $i >= 0; $i--) {
                                if (isset($transactionsArray[$i]['vl_balance_after'])) {
                                    $carriedVl = $transactionsArray[$i]['vl_balance_after'];
                                    break;
                                }
                            }
                            
                            $carriedSl = $leaveCard->sl_beginning_balance;
                            for ($i = $lastTransIdx; $i >= 0; $i--) {
                                if (isset($transactionsArray[$i]['sl_balance_after'])) {
                                    $carriedSl = $transactionsArray[$i]['sl_balance_after'];
                                    break;
                                }
                            }
                        @endphp
                        <tr class="carried-row tx-row-print" style="height: 19px; background: #fafafa;">
                            <td colspan="2" class="date-col bleed-cell" style="font-weight: 700; font-size: 0.68rem; vertical-align: top; position: relative; padding: 0;">
                                <div class="bleed-content" style="padding: 2px 4px 1px 10px;">{{ $carriedDate }} {{ $pageIndex > 0 ? 'CARRIED OVER' : '' }}</div>
                            </td>
                            <td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px; font-weight: 700; vertical-align: middle;">
                                {{ number_format($carriedVl, 5) }}
                            </td>
                            <td></td><td></td><td></td>
                            <td class="bal-cell" style="padding: 1.5px 2px; font-weight: 700; vertical-align: middle;">
                                {{ number_format($carriedSl, 5) }}
                            </td>
                            <td></td><td></td>
                        </tr>
                    @endif

                    @foreach($page['items'] as $item)
                        @php
                            $periodText = $item['period'] ?? '';
                            $remarksText = $item['remarks'] ?? '';
                            $isLess = strpos(strtoupper($periodText), 'LESS') !== false;
                            // Suppress remarks for Monetization rows
                            if (stripos($periodText, 'Monetization') !== false) {
                                $remarksText = '';
                            }
                            $textColorValue = !empty($item['text_color']) ? $item['text_color'] : ($isLess ? '#dc2626' : '#000000');
                            $textColor = 'color: ' . $textColorValue . ';';
                            // Shrink font size if string is long
                            $periodFontSize = strlen($periodText) > 20 ? 'font-size: 0.55rem;' : 'font-size: 0.65rem;';
                            $remarksFontSize = strlen($remarksText) > 15 ? 'font-size: 0.55rem;' : 'font-size: 0.65rem;';
                            
                            $vwR = $item['vl_wop_reason'] ?? '';
                            $swR = $item['sl_wop_reason'] ?? '';
                                $isCtoRow = stripos($periodText, 'CTO') !== false;
                                $itSlots = 1;
                                if ($isCtoRow) {
                                    $itSlots = max($itSlots, ceil(strlen($periodText) / 100));
                                } else if ($isLess) {
                                    $itSlots = max($itSlots, ceil(strlen($periodText . ' ' . $remarksText) / 40));
                                } else {
                                    $itSlots = max($itSlots, ceil(strlen($periodText) / 18));
                                    $itSlots = max($itSlots, ceil(strlen($remarksText) / 20));
                                }
                                if ($vwR) $itSlots = max($itSlots, ceil((10 + strlen($vwR)) / 20));
                                if ($swR) $itSlots = max($itSlots, ceil((10 + strlen($swR)) / 20));
                                
                                // Each slot is exactly 19px to match a full row size
                                $rowHeight = 19 * $itSlots;
                        @endphp
                        <tr class="tx-row tx-row-print" data-id="{{ $item['id'] ?? '' }}" style="height: {{ $rowHeight }}px; {{ $textColor }}">
                            @if($isCtoRow)
                                {{-- 7 Columns (Period -> Title) with bleeding text --}}
                                <td class="edit-cell date-col" style="font-weight: 600; vertical-align: top; position: relative; padding: 0;" contenteditable="true">
                                    <div style="position: absolute; left: 10px; top: 0; width: calc(488%); z-index: 10; padding: 2px 4px 1px 0; line-height: 19px; min-height: 19px; white-space: normal; pointer-events: none; overflow: visible;">
                                        {{ preg_replace('/\s\d+(\.\d+)?\sday(s)?$/i', '', $periodText) }}
                                    </div>
                                </td>
                                <td></td> {{-- Particulars --}}
                                <td></td> {{-- Title 1 --}}
                                <td></td> {{-- Title 2 --}}
                                <td></td> {{-- Title 3 --}}
                                <td></td> {{-- Title 4 --}}
                                <td></td> {{-- Title 5 --}}
                                
                                <td class="num-cell">{{ ($item['cto_earned'] ?? 0) > 0 ? rtrim(rtrim(number_format($item['cto_earned'], 3), '0'), '.') : '' }}</td>
                                <td class="num-cell">{{ ($item['cto_used'] ?? 0) > 0 ? rtrim(rtrim(number_format($item['cto_used'], 3), '0'), '.') : '' }}</td>
                                <td class="bal-cell" style="font-weight: 700;">{{ rtrim(rtrim(number_format($item['cto_balance_after'] ?? 0, 3), '0'), '.') }}</td>
                                <td class="date-taken-col edit-cell" style="font-size: 0.65rem; vertical-align: middle; text-align: center;" contenteditable="true">{{ $item['action_taken'] ?? '' }}</td>
                            @elseif($isLess)
                                <td colspan="2" class="edit-cell date-col bleed-cell" style="font-weight: 600; vertical-align: top; position: relative; padding: 0;" contenteditable="true">
                                    <div class="bleed-content" style="padding: 2px 4px 1px 10px; line-height: 19px; min-height: 19px;">
                                        {{ $periodText }} &nbsp; <span style="font-size: 0.9em; font-weight: 500;">{{ $remarksText }}</span>
                                    </div>
                                </td>
                            @else
                                <td class="edit-cell date-col" style="{{ $periodFontSize }} font-weight: 600; white-space: normal; word-wrap: break-word; vertical-align: top; line-height: 19px; padding-top: 0px;" contenteditable="true">{{ $periodText }}</td>
                                <td class="edit-cell particulars-col" style="{{ $remarksFontSize }} font-weight: 600; white-space: normal; word-wrap: break-word; vertical-align: top; line-height: 19px; padding-top: 0px;" contenteditable="true">{{ $remarksText }}</td>
                            @endif

                            @if(!$isCtoRow)
                                <td class="num-cell edit-cell vl-earned-col">{{ ($item['vl_earned'] ?? 0) > 0 ? rtrim(rtrim(number_format((float)$item['vl_earned'], 6), '0'), '.') : '' }}</td>
                                <td class="num-cell edit-cell vl-used-col">{{ ($item['vl_used'] ?? 0) > 0 ? rtrim(rtrim(number_format((float)$item['vl_used'], 6), '0'), '.') : '' }}</td>
                                <td class="bal-cell edit-cell vl-bal-col">
                                    @php $isMonetRow = stripos($periodText, 'Monetization') !== false; @endphp
                                    {{ ($item['vl_wop'] ?? 0) > 0 && !$isMonetRow ? '-' : (isset($item['vl_balance_after']) ? rtrim(rtrim(number_format($item['vl_balance_after'], 6), '0'), '.') : '-') }}
                                </td>
                                <td class="num-cell edit-cell vl-wop-col" style="white-space: normal; word-wrap: break-word; font-size: 0.65rem; padding: 1px 2px; line-height: 10px !important; vertical-align: middle !important;">
                                    @if((float)($item['vl_wop'] ?? 0) > 0)
                                        {{ (float)$item['vl_wop'] }} <span style="font-size: 0.52rem; font-weight: 700; text-transform: uppercase; color: inherit; display: block; line-height: 9px !important; margin-top: 1px;">{{ $item['vl_wop_reason'] ?? '' }}</span>
                                    @endif
                                </td>
                                <td class="num-cell edit-cell sl-earned-col">{{ ($item['sl_earned'] ?? 0) > 0 ? rtrim(rtrim(number_format((float)$item['sl_earned'], 6), '0'), '.') : '' }}</td>
                                <td class="num-cell edit-cell sl-used-col">{{ ($item['sl_used'] ?? 0) > 0 ? rtrim(rtrim(number_format((float)$item['sl_used'], 6), '0'), '.') : '' }}</td>
                                <td class="bal-cell edit-cell sl-bal-col">
                                    {{ ($item['sl_wop'] ?? 0) > 0 && !$isMonetRow ? '-' : (isset($item['sl_balance_after']) ? rtrim(rtrim(number_format($item['sl_balance_after'], 6), '0'), '.') : '-') }}
                                </td>
                                <td class="num-cell edit-cell sl-wop-col" style="white-space: normal; word-wrap: break-word; font-size: 0.65rem; padding: 1px 2px; line-height: 10px !important; vertical-align: middle !important;">
                                    @if((float)($item['sl_wop'] ?? 0) > 0)
                                        @if($isMonetRow)
                                            <span style="font-size: 0.6rem; font-weight: 700;">= {{ rtrim(rtrim(number_format((float)$item['sl_wop'], 6), '0'), '.') }}</span>
                                        @else
                                            {{ (float)$item['sl_wop'] }} <span style="font-size: 0.52rem; font-weight: 700; text-transform: uppercase; color: inherit; display: block; line-height: 9px !important; margin-top: 1px;">{{ $item['sl_wop_reason'] ?? '' }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td class="edit-cell action-col" style="font-size: 0.65rem; line-height: 1; text-align: center;">{{ $item['action_taken'] ?? '' }}</td>
                            @endif
                        </tr>
                    @endforeach

                    {{-- Fill empty rows to maintain card height --}}
                    @php
                        // Empty slots needed is max - slots used
                        $maxRows = $page['show_header'] ? $FRONT_DATA_ROWS - 1 : $BACK_DATA_ROWS - 1;
                        $emptyRowsNeeded = $maxRows - $page['slots_used'];
                        if ($emptyRowsNeeded < 0) $emptyRowsNeeded = 0;
                    @endphp
                    @for($i = 0; $i < $emptyRowsNeeded; $i++)
                        <tr class="tx-row empty-row tx-row-print" style="height: 19px;">
                            <td class="edit-cell date-col" contenteditable="true"></td>
                            <td class="edit-cell particulars-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell vl-earned-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell vl-used-col" contenteditable="true"></td>
                            <td class="bal-cell edit-cell vl-bal-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell vl-wop-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell sl-earned-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell sl-used-col" contenteditable="true"></td>
                            <td class="bal-cell edit-cell sl-bal-col" contenteditable="true"></td>
                            <td class="num-cell edit-cell sl-wop-col" contenteditable="true"></td>
                            <td class="edit-cell action-col" contenteditable="true"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
        @endforeach
    </div>
    @endforeach

    @if(count($cardPairs) > 1)
    <div class="no-print" style="margin-top: 25px; margin-bottom: 25px; background: #f8fafc; padding: 15px 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <div style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.8px;">
            SHOWING PAGE <span id="currentCardNum">1</span> OF {{ count($cardPairs) }}
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" id="prevCardBtn" class="btn-pagination disabled" onclick="prevCard()" disabled style="border: none;">
                <i class="fas fa-chevron-left" style="font-size: 0.7rem;"></i> Previous
            </button>
            <button type="button" id="nextCardBtn" class="btn-pagination active" onclick="nextCard()" style="border: none;">
                Next <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
            </button>
        </div>
    </div>
    @endif

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
        white-space: normal; 
        word-wrap: break-word;
        outline: none;
    }

    .bleed-cell::after {
        content: '';
        position: absolute;
        left: 44.82%; /* Precision: 13 / (13 + 16) */
        top: 0;
        bottom: 0;
        width: 1.5px;
        background: #334155;
        z-index: 1;
    }

    /* CTO Bleed (Spans 7 columns: Period + Particulars + 5 title cols) */
    .bleed-cto-cell::after,
    .bleed-cto-cell::before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 1.5px;
        background: #334155;
        z-index: 1;
    }
    /* Border between Period (13%) and Particulars (16%) of the 63.5% group */
    .bleed-cto-cell::before {
        left: calc((13 / 63.5) * 100%); 
    }
    /* Border between Particulars (16%) and Title Area (34.5%) */
    .bleed-cto-cell::after {
        left: calc(((13 + 16) / 63.5) * 100%);
    }

    .bleed-content {
        position: relative;
        z-index: 10;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-all;
        overflow-wrap: anywhere;
    }

    .leave-card-table .particulars-col {
        text-align: left;
        padding-left: 5px;
        white-space: normal;
        word-wrap: break-word;
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

    .leave-card-table .date-col {
        font-family: 'Outfit', sans-serif;
        font-size: 0.65rem;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-all;
        text-align: left;
        padding-left: 10px;
        position: relative;
    }

    .leave-card-table .particulars-col {
        font-size: 0.65rem;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-all;
        text-align: left;
        padding-left: 8px;
    }

    /* Standard row background */
    .tx-row {
        background: transparent;
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
       PRINT STYLES — 1/2 Index Card (8"x5" / 20.32cm x 12.7cm)
       ═══════════════════════════════════════════════════════════ */
    @media print {
        @page {
            margin: 0;
        }

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

        /* Each side (Front and Back) is its own separate 8x5 page */
        .print-page {
            box-shadow: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 5mm 0 0 !important;
            margin: 0 !important;
            page-break-after: always !important;
            page-break-inside: avoid !important;
            width: 100% !important;
            box-sizing: border-box !important;
            display: block !important;
        }

        /* Tighten top info section for Front Page */
        .leave-card-form > div:first-child p:first-child {
            font-size: 0.75rem !important;
            margin: 0 !important;
        }
        .leave-card-form > div:first-child p:nth-child(2) {
            font-size: 0.65rem !important;
            margin: 0 !important;
        }
        .leave-card-form > div:first-child h3 {
            font-size: 0.85rem !important;
            margin: 4px 0 10px !important;
        }

        /* Ensure CTO title overlap works in print */
        .leave-card-table td {
            overflow: visible !important;
            position: relative !important;
        }

        .leave-card-table .date-col div {
            position: absolute !important;
            width: calc(488%) !important;
            overflow: visible !important;
            z-index: 100 !important;
            display: block !important;
            pointer-events: none;
            background: transparent !important;
        }

        .leave-card-table {
            table-layout: fixed !important;
            width: 100% !important;
        }

        /* Tighten the 2-column Name/Status section */
        .leave-card-form div[style*="display: grid"] {
            margin-bottom: 8px !important;
            gap: 2px 20px !important;
            font-size: 0.75rem !important;
        }
        
        .leave-card-form div[style*="border-bottom"] {
            padding-bottom: 1px !important;
        }

        /* Force table borders to remain black and 1px for precision */
        .leave-card-table th,
        .leave-card-table td {
            border: 1px solid #000 !important;
            padding: 0 4px !important;
            box-sizing: border-box !important;
        }

        .leave-card-table thead th {
            height: 19px !important;
            font-size: 0.6rem !important;
            line-height: 1 !important;
            padding: 0 2px !important;
        }

        .leave-card-table thead th br {
            line-height: 8px !important;
        }

        .leave-card-table {
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .leave-card-table thead {
            display: table-header-group !important;
        }

        .leave-card-table thead tr {
            height: 19px !important;
        }

        .leave-card-table tbody td {
            font-size: 0.7rem !important;
            padding: 0 4px !important;
            vertical-align: middle !important;
            overflow: hidden !important;
            line-height: 19px !important;
            height: 19px !important;
            border: 1px solid #000 !important;
            box-sizing: border-box !important;
        }

        .tx-row-print {
            height: 19px; /* This is the minimal slot height for data rows */
        }

        .beginning-row.tx-row-print {
            height: 19px !important;
        }

        .empty-row.tx-row-print {
            height: 19px !important;
        }
        
        .print-only {
            display: block !important;
        }
        
        .no-print {
            display: none !important;
        }

        .leave-card-table .date-col,
        .leave-card-table .particulars-col,
        .bleed-content {
            white-space: normal !important;
            word-wrap: break-word !important;
            word-break: break-all !important;
            overflow-wrap: anywhere !important;
        }

        .bleed-cell::after {
            background: #000 !important;
            width: 1px !important;
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
    let currentCardPair = 0;
    const totalCardPairs = {{ isset($cardPairs) ? count($cardPairs) : 0 }};

    function showCardPair(index) {
        document.querySelectorAll('.card-pair-group').forEach((el, idx) => {
            el.style.display = (idx === index) ? 'block' : 'none';
        });
        
        const currentNumEl = document.getElementById('currentCardNum');
        if(currentNumEl) currentNumEl.textContent = index + 1;
        
        const prevBtn = document.getElementById('prevCardBtn');
        const nextBtn = document.getElementById('nextCardBtn');
        
        if(prevBtn) {
            prevBtn.disabled = (index === 0);
            if (index === 0) prevBtn.classList.add('disabled');
            else prevBtn.classList.remove('disabled');
        }
        
        if(nextBtn) {
            nextBtn.disabled = (index === totalCardPairs - 1);
            if (index === totalCardPairs - 1) {
                nextBtn.classList.remove('active');
                nextBtn.classList.add('disabled');
            } else {
                nextBtn.classList.add('active');
                nextBtn.classList.remove('disabled');
            }
        }
    }

    function prevCard() {
        if (currentCardPair > 0) {
            currentCardPair--;
            showCardPair(currentCardPair);
            document.querySelector('.card-pair-group').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function nextCard() {
        if (currentCardPair < totalCardPairs - 1) {
            currentCardPair++;
            showCardPair(currentCardPair);
            document.querySelector('.card-pair-group').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Debounce timer for auto-save
        let saveTimeout;
        const indicator = document.getElementById('saveIndicator');

        // ═══════════════════════════════════════════════════════
        // Color Picker Context Menu (Right-click on editable cells)
        // ═══════════════════════════════════════════════════════
        const colorMenu = document.createElement('div');
        colorMenu.id = 'colorContextMenu';
        colorMenu.innerHTML = `
            <div class="color-menu-title">Text Color</div>
            <div class="color-option" data-color="#000000">
                <span class="color-dot" style="background: #000;"></span> Black
            </div>
            <div class="color-option" data-color="#dc2626">
                <span class="color-dot" style="background: #dc2626;"></span> Red
            </div>
            <div style="border-top: 1px solid #e2e8f0; margin: 4px 0;"></div>
            <div class="color-scroll-area">
                <div class="color-option" data-color="#2563eb">
                    <span class="color-dot" style="background: #2563eb;"></span> Blue
                </div>
                <div class="color-option" data-color="#16a34a">
                    <span class="color-dot" style="background: #16a34a;"></span> Green
                </div>
                <div class="color-option" data-color="#ea580c">
                    <span class="color-dot" style="background: #ea580c;"></span> Orange
                </div>
                <div class="color-option" data-color="#9333ea">
                    <span class="color-dot" style="background: #9333ea;"></span> Purple
                </div>
                <div class="color-option" data-color="#92400e">
                    <span class="color-dot" style="background: #92400e;"></span> Brown
                </div>
                <div class="color-option" data-color="#0d9488">
                    <span class="color-dot" style="background: #0d9488;"></span> Teal
                </div>
                <div class="color-option" data-color="#0369a1">
                    <span class="color-dot" style="background: #0369a1;"></span> Dark Blue
                </div>
                <div class="color-option" data-color="#be185d">
                    <span class="color-dot" style="background: #be185d;"></span> Pink
                </div>
                <div class="color-option" data-color="#ca8a04">
                    <span class="color-dot" style="background: #ca8a04;"></span> Yellow
                </div>
                <div class="color-option" data-color="#166534">
                    <span class="color-dot" style="background: #166534;"></span> Dark Green
                </div>
                <div class="color-option" data-color="#7c3aed">
                    <span class="color-dot" style="background: #7c3aed;"></span> Violet
                </div>
                <div class="color-option" data-color="#e11d48">
                    <span class="color-dot" style="background: #e11d48;"></span> Rose
                </div>
                <div class="color-option" data-color="#4338ca">
                    <span class="color-dot" style="background: #4338ca;"></span> Indigo
                </div>
                <div class="color-option" data-color="#0891b2">
                    <span class="color-dot" style="background: #0891b2;"></span> Cyan
                </div>
                <div class="color-option" data-color="#65a30d">
                    <span class="color-dot" style="background: #65a30d;"></span> Lime
                </div>
                <div class="color-option" data-color="#c026d3">
                    <span class="color-dot" style="background: #c026d3;"></span> Magenta
                </div>
                <div class="color-option" data-color="#475569">
                    <span class="color-dot" style="background: #475569;"></span> Gray
                </div>
                <div class="color-option" data-color="#78350f">
                    <span class="color-dot" style="background: #78350f;"></span> Amber
                </div>
                <div class="color-option" data-color="#b45309">
                    <span class="color-dot" style="background: #b45309;"></span> Gold
                </div>
                <div class="color-option" data-color="#1e3a5f">
                    <span class="color-dot" style="background: #1e3a5f;"></span> Navy
                </div>
            </div>
        `;
        colorMenu.style.cssText = `
            display: none; position: fixed; z-index: 9999;
            background: white; border: 1px solid #e2e8f0; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15); padding: 6px 0;
            min-width: 140px; font-family: inherit;
        `;
        document.body.appendChild(colorMenu);

        // Add styles for menu
        const menuStyle = document.createElement('style');
        menuStyle.textContent = `
            .color-menu-title { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 14px 6px; }
            .color-option { display: flex; align-items: center; gap: 8px; padding: 7px 14px; font-size: 0.82rem; cursor: pointer; transition: background 0.15s; font-weight: 600; }
            .color-option:hover { background: #f1f5f9; }
            .color-dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid #e2e8f0; flex-shrink: 0; }
            .color-scroll-area { max-height: 200px; overflow-y: auto; }
        `;
        document.head.appendChild(menuStyle);

        let targetRow = null;

        // Right-click on any editable cell
        document.addEventListener('contextmenu', function(e) {
            const cell = e.target.closest('.edit-cell');
            if (!cell) return;

            e.preventDefault();
            targetRow = cell.closest('tr');

            colorMenu.style.display = 'block';
            colorMenu.style.left = e.clientX + 'px';
            colorMenu.style.top = e.clientY + 'px';

            // Keep menu within viewport
            const rect = colorMenu.getBoundingClientRect();
            if (rect.right > window.innerWidth) colorMenu.style.left = (window.innerWidth - rect.width - 8) + 'px';
            if (rect.bottom > window.innerHeight) colorMenu.style.top = (window.innerHeight - rect.height - 8) + 'px';
        });

        // Select a color
        colorMenu.addEventListener('click', function(e) {
            const opt = e.target.closest('.color-option');
            if (!opt || !targetRow) return;

            const color = opt.dataset.color;
            targetRow.style.color = color;

            colorMenu.style.display = 'none';
            targetRow = null;
            scheduleSave();
        });

        // Close menu when clicking elsewhere
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#colorContextMenu')) {
                colorMenu.style.display = 'none';
            }
        });

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
                    text_color: row.style.color || '',
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
