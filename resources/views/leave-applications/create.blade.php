@extends('layouts.app')

@section('header_title', 'Application for Leave')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Form Section -->
        <div class="card glass animate-fade">
            <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-file-signature text-primary"></i> Leave Application Details</h4>
            
            <form action="{{ route('leave-applications.store') }}" method="POST" id="leaveForm">
                @csrf
                
                @if(auth()->user()->canManageEmployees())
                <div class="form-group">
                    <label class="form-label">Employee <span style="color: var(--danger);">*</span></label>
                    <div style="position: relative;" id="employeeSearchWrapper">
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;"></i>
                            <input type="text" id="employeeSearch" class="form-control" placeholder="Search employee by name or ID..." autocomplete="off" style="padding-left: 40px;">
                        </div>
                        <input type="hidden" name="employee_id" id="employee_id" value="{{ old('employee_id') }}" required>
                        <div id="employeeDropdown" class="employee-dropdown" style="display: none;">
                            @foreach($employees as $emp)
                                <div class="employee-option" data-id="{{ $emp->id }}" data-name="{{ $emp->full_name }}" data-empid="{{ $emp->employee_id }}" data-position="{{ $emp->position }}" data-dept="{{ $emp->department->name ?? 'N/A' }}">
                                    <div>
                                        <div style="font-weight: 600; font-size: 0.88rem;">{{ $emp->full_name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--secondary);">{{ $emp->employee_id }} · {{ $emp->position ?? 'N/A' }} · {{ $emp->department->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Employee Balance Card (shown after employee is selected) -->
                <div id="employeeBalanceCard" style="display: none; margin-bottom: 20px;">
                    <div class="balance-card">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">

                            <div>
                                <div id="empInfoName" style="font-weight: 700; font-size: 0.95rem;"></div>
                                <div id="empInfoDetails" style="font-size: 0.78rem; color: var(--secondary);"></div>
                            </div>
                            <button type="button" onclick="clearEmployee()" style="margin-left: auto; background: none; border: none; color: var(--secondary); cursor: pointer; font-size: 0.85rem; padding: 4px 8px; border-radius: 6px; transition: all 0.2s;" title="Change employee">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="balance-box vl-box">
                                <div class="balance-label"><i class="fas fa-umbrella-beach"></i> Vacation Leave</div>
                                <div class="balance-value" id="vlBalance">—</div>
                                <div class="balance-sub">Total Earned: <span id="vlTotalEarned">—</span></div>
                            </div>
                            <div class="balance-box sl-box">
                                <div class="balance-label"><i class="fas fa-briefcase-medical"></i> Sick Leave</div>
                                <div class="balance-value" id="slBalance">—</div>
                                <div class="balance-sub">Total Earned: <span id="slTotalEarned">—</span></div>
                            </div>
                        </div>
                        <div id="noLeaveCardWarning" style="display: none; margin-top: 12px; padding: 10px 14px; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 10px; font-size: 0.8rem; color: #92400e;">
                            <i class="fas fa-exclamation-triangle"></i> No leave card found for this year. Balance starts at 0.
                        </div>
                    </div>
                </div>
                @else
                <input type="hidden" name="employee_id" value="{{ auth()->user()->employee->id }}">
                <div class="form-group">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                </div>
                @endif

                <input type="hidden" name="date_filed" value="{{ old('date_filed', date('Y-m-d')) }}">

                <!-- Inclusive Dates Section -->
                <div class="form-group">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <label class="form-label" style="margin-bottom: 0;">6.C Inclusive Dates of Application</label>
                        <button type="button" class="btn btn-success" id="addEntryBtn" style="padding: 6px 14px; font-size: 0.82rem;">
                            <i class="fas fa-plus"></i> Add Entry
                        </button>
                    </div>
                    
                    <div id="dateEntries">
                        <!-- Default first entry -->
                        <div class="date-entry" data-index="0">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                                <span class="entry-label">#1</span>
                                <span style="flex: 1;"></span>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.78rem;">Type of Leave</label>
                                    <select name="entries[0][leave_type_id]" class="form-control entry-type" required>
                                        <option value="">— Select —</option>
                                        @foreach($leaveTypes as $type)
                                            <option value="{{ $type->id }}" data-code="{{ $type->code }}">{{ $type->name }} ({{ $type->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.78rem;">Inclusive Dates</label>
                                    <input type="text" name="entries[0][inclusive_dates]" class="form-control entry-dates" placeholder="e.g. May 1, 2, 5-6" required>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="font-size: 0.78rem;">No. of Days</label>
                                    <input type="number" name="entries[0][num_days]" class="form-control entry-days" step="0.5" min="0.5" placeholder="0" required style="width: 90px;">
                                </div>
                            </div>
                            <div class="others-specify" style="display: none; margin-top: 8px;">
                                <input type="text" name="entries[0][other_type]" class="form-control" placeholder="Specify other leave type..." style="font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                </div>




                <!-- Commutation (Hidden default) -->
                <input type="hidden" name="commutation" value="not_requested">

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="padding: 14px 40px;">Submit Application <i class="fas fa-paper-plane"></i></button>
                    <a href="{{ route('leave-applications.index') }}" class="btn btn-secondary" style="padding: 14px 20px;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Info Section (Right Sidebar) -->
        <div>
            <!-- 7.A Certification of Leave Credits (Live Preview) -->
            <div class="card glass animate-fade" id="certificationCard">
                <h5 style="font-weight: 700; margin-bottom: 4px;"><i class="fas fa-certificate text-primary"></i> 7.A Certification of Leave Credits</h5>
                <p style="font-size: 0.75rem; color: var(--secondary); margin-bottom: 16px;">As of {{ date('F d, Y') }}</p>
                
                <table class="cert-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Vacation Leave</th>
                            <th>Sick Leave</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="row-label"><em>Total Earned</em></td>
                            <td id="certVlEarned" class="num-cell">—</td>
                            <td id="certSlEarned" class="num-cell">—</td>
                        </tr>
                        <tr class="less-row">
                            <td class="row-label"><em>Less this application</em></td>
                            <td id="certVlLess" class="num-cell">0.000</td>
                            <td id="certSlLess" class="num-cell">0.000</td>
                        </tr>
                        <tr class="balance-row">
                            <td class="row-label"><strong>Balance</strong></td>
                            <td id="certVlBalance" class="num-cell balance-num">—</td>
                            <td id="certSlBalance" class="num-cell balance-num">—</td>
                        </tr>
                    </tbody>
                </table>

                <div id="certNotice" style="margin-top: 12px; padding: 10px; background: #f0f9ff; border-radius: 8px; font-size: 0.78rem; color: #1e40af; border: 1px solid #bfdbfe;">
                    <i class="fas fa-info-circle"></i> Select an employee to see leave credit certification.
                </div>
            </div>

            <!-- Computation Preview -->
            <div class="card glass animate-fade" style="margin-top: 20px;" id="calc-preview">
                <h5 style="font-weight: 700; margin-bottom: 16px;"><i class="fas fa-calculator text-primary"></i> Computation Preview</h5>
                <div id="entries-summary"></div>
                <div style="display: flex; justify-content: space-between; margin-top: 12px; padding-top: 12px; border-top: 2px solid var(--primary);">
                    <span style="font-weight: 800;">Total Days:</span>
                    <span id="total-days" style="font-weight: 800; color: var(--primary); font-size: 1.1rem;">0 days</span>
                </div>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <p style="font-size: 0.75rem; color: var(--secondary); font-style: italic;">
                    Credits will be deducted upon approval of HR administrator.
                </p>
            </div>

            <!-- Leave Reminders -->
            <div class="card animate-fade" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #bfdbfe; margin-top: 20px;">
                <h5 style="color: var(--primary); font-weight: 700; margin-bottom: 20px;"><i class="fas fa-info-circle"></i> Leave Reminders</h5>
                <ul style="list-style: none; font-size: 0.85rem; padding: 0; margin: 0;">
                    <li style="margin-bottom: 14px; display: flex; gap: 10px; align-items: flex-start; color: var(--dark);">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 3px; flex-shrink: 0;"></i>
                        <span>VL must be filed <strong>5 days in advance</strong>.</span>
                    </li>
                    <li style="margin-bottom: 14px; display: flex; gap: 10px; align-items: flex-start; color: var(--dark);">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 3px; flex-shrink: 0;"></i>
                        <span>SL must be filed <strong>upon return to work</strong>.</span>
                    </li>
                    <li style="margin-bottom: 14px; display: flex; gap: 10px; align-items: flex-start; color: var(--dark);">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 3px; flex-shrink: 0;"></i>
                        <span>Medical certificate is required for <strong>SL &gt; 3 days</strong>.</span>
                    </li>
                    <li style="display: flex; gap: 10px; align-items: flex-start; color: var(--dark);">
                        <i class="fas fa-check-circle" style="color: var(--success); margin-top: 3px; flex-shrink: 0;"></i>
                        <span>Forced leave <strong>(5 days)</strong> must be consumed within the year.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Employee Search Dropdown */
    .employee-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 100;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        margin-top: 4px;
    }
    .employee-option {
        padding: 10px 16px;
        cursor: pointer;
        transition: all 0.15s;
        border-bottom: 1px solid #f8fafc;
    }
    .employee-option:last-child { border-bottom: none; }
    .employee-option:hover, .employee-option.active {
        background: #eff6ff;
    }
    .employee-option:first-child { border-radius: 12px 12px 0 0; }
    .employee-option:last-child { border-radius: 0 0 12px 12px; }

    /* Balance Card */
    .balance-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
    }
    .balance-box {
        padding: 16px;
        border-radius: 12px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .vl-box {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border: 1px solid #bfdbfe;
    }
    .sl-box {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 1px solid #bbf7d0;
    }
    .balance-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--secondary);
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }
    .vl-box .balance-label { color: #2563eb; }
    .sl-box .balance-label { color: #16a34a; }
    .balance-value {
        font-size: 1.6rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 4px;
    }
    .vl-box .balance-value { color: #1d4ed8; }
    .sl-box .balance-value { color: #15803d; }
    .balance-sub {
        font-size: 0.72rem;
        color: var(--secondary);
    }

    /* Date Entries */
    .date-entry {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }
    .date-entry:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
    }
    .entry-label {
        background: var(--primary);
        color: white;
        padding: 2px 10px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 700;
    }
    .remove-entry-btn {
        background: none;
        border: none;
        color: var(--danger);
        cursor: pointer;
        font-size: 0.85rem;
        padding: 4px 8px;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .remove-entry-btn:hover {
        background: #fef2f2;
    }
    .entry-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        font-size: 0.82rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .entry-summary-row:last-child {
        border-bottom: none;
    }

    /* 6.B Details of Leave styles */
    .details-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
    }
    .detail-block {
        padding-bottom: 12px;
        margin-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .detail-block:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .detail-heading {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }
    .detail-check {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        color: var(--dark);
    }
    .detail-check input[type="checkbox"],
    .detail-check input[type="radio"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
        flex-shrink: 0;
    }
    .detail-check span {
        white-space: nowrap;
    }
    .detail-line {
        flex: 1;
        border: none;
        border-bottom: 1px solid #94a3b8;
        background: transparent;
        padding: 2px 6px;
        font-size: 0.85rem;
        outline: none;
        min-width: 80px;
        transition: border-color 0.2s;
    }
    .detail-line:focus {
        border-bottom-color: var(--primary);
        border-bottom-width: 2px;
    }

    /* 7.A Certification Table */
    .cert-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .cert-table thead th {
        background: #f1f5f9;
        padding: 8px 12px;
        font-weight: 700;
        text-align: center;
        border: 1px solid #e2e8f0;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .cert-table thead th:first-child {
        text-align: left;
        border-left: none;
    }
    .cert-table thead th:last-child {
        border-right: none;
    }
    .cert-table td {
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
    }
    .cert-table td:first-child { border-left: none; }
    .cert-table td:last-child { border-right: none; }
    .row-label {
        font-size: 0.8rem;
        color: var(--dark);
    }
    .num-cell {
        text-align: center;
        font-weight: 600;
        font-family: monospace;
        font-size: 0.88rem;
    }
    .less-row {
        background: #fff7ed;
    }
    .less-row td {
        color: #c2410c;
    }
    .balance-row {
        background: #f0f9ff;
    }
    .balance-num {
        font-weight: 800 !important;
        color: var(--primary) !important;
        font-size: 0.95rem !important;
    }

    /* Insufficient warning */
    .balance-value.insufficient {
        color: #dc2626 !important;
    }
    .cert-insufficient {
        color: #dc2626 !important;
        font-weight: 800 !important;
    }
</style>
@endpush

@push('scripts')
<script>
    let entryIndex = 1;
    const entriesContainer = document.getElementById('dateEntries');
    const addBtn = document.getElementById('addEntryBtn');

    // Employee balance data
    let employeeBalance = null;

    // Leave type options HTML
    const leaveTypeOptions = `<option value="">— Select —</option>` +
        @json($leaveTypes->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name]))
        .map(t => `<option value="${t.id}" data-code="${t.code}">${t.name} (${t.code})</option>`)
        .join('');

    // ═══════════════════════════════════════════════════════
    // Employee Search & Selection
    // ═══════════════════════════════════════════════════════
    @if(auth()->user()->canManageEmployees())
    const searchInput = document.getElementById('employeeSearch');
    const dropdown = document.getElementById('employeeDropdown');
    const employeeIdInput = document.getElementById('employee_id');
    const balanceCard = document.getElementById('employeeBalanceCard');
    let activeIndex = -1;

    searchInput.addEventListener('focus', () => {
        filterEmployees();
        dropdown.style.display = 'block';
    });

    searchInput.addEventListener('input', () => {
        filterEmployees();
        dropdown.style.display = 'block';
        activeIndex = -1;
    });

    // Keyboard navigation
    searchInput.addEventListener('keydown', (e) => {
        const options = dropdown.querySelectorAll('.employee-option:not([style*="display: none"])');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, options.length - 1);
            highlightOption(options);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            highlightOption(options);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && options[activeIndex]) {
                selectEmployee(options[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });

    function highlightOption(options) {
        options.forEach(o => o.classList.remove('active'));
        if (activeIndex >= 0 && options[activeIndex]) {
            options[activeIndex].classList.add('active');
            options[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    }

    function filterEmployees() {
        const q = searchInput.value.toLowerCase().trim();
        dropdown.querySelectorAll('.employee-option').forEach(opt => {
            const name = opt.dataset.name.toLowerCase();
            const empid = opt.dataset.empid.toLowerCase();
            const position = (opt.dataset.position || '').toLowerCase();
            const dept = (opt.dataset.dept || '').toLowerCase();
            const match = !q || name.includes(q) || empid.includes(q) || position.includes(q) || dept.includes(q);
            opt.style.display = match ? '' : 'none';
        });
    }

    // Click to select
    dropdown.addEventListener('click', (e) => {
        const option = e.target.closest('.employee-option');
        if (option) selectEmployee(option);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#employeeSearchWrapper')) {
            dropdown.style.display = 'none';
        }
    });

    function selectEmployee(option) {
        const id = option.dataset.id;
        const name = option.dataset.name;
        const empid = option.dataset.empid;
        const position = option.dataset.position;
        const dept = option.dataset.dept;

        employeeIdInput.value = id;
        searchInput.value = name;
        searchInput.style.display = 'none';
        dropdown.style.display = 'none';

        // Show balance card
        balanceCard.style.display = 'block';
        document.getElementById('empInfoName').textContent = name;
        document.getElementById('empInfoDetails').textContent = `${empid} · ${position || 'N/A'} · ${dept}`;

        // Fetch balance via AJAX
        fetchEmployeeBalance(id);
    }

    function clearEmployee() {
        employeeIdInput.value = '';
        searchInput.value = '';
        searchInput.style.display = '';
        balanceCard.style.display = 'none';
        employeeBalance = null;
        resetCertification();
        searchInput.focus();
    }

    function fetchEmployeeBalance(employeeId) {
        fetch(`/api/employee/${employeeId}/leave-balance`)
            .then(r => r.json())
            .then(data => {
                employeeBalance = data;

                document.getElementById('vlBalance').textContent = parseFloat(data.vl_balance);
                document.getElementById('slBalance').textContent = parseFloat(data.sl_balance);
                document.getElementById('vlTotalEarned').textContent = parseFloat(data.vl_total_earned);
                document.getElementById('slTotalEarned').textContent = parseFloat(data.sl_total_earned);

                document.getElementById('noLeaveCardWarning').style.display = data.has_leave_card ? 'none' : 'block';

                // Update certification table
                updateCertification();
            })
            .catch(err => {
                console.error('Failed to fetch balance:', err);
            });
    }
    @endif

    function resetCertification() {
        document.getElementById('certVlEarned').textContent = '—';
        document.getElementById('certSlEarned').textContent = '—';
        document.getElementById('certVlLess').textContent = '0.000';
        document.getElementById('certSlLess').textContent = '0.000';
        document.getElementById('certVlBalance').textContent = '—';
        document.getElementById('certSlBalance').textContent = '—';
        document.getElementById('certNotice').style.display = 'block';

        // Remove insufficient warnings
        document.getElementById('certVlBalance').classList.remove('cert-insufficient');
        document.getElementById('certSlBalance').classList.remove('cert-insufficient');
    }

    // ═══════════════════════════════════════════════════════
    // 7.A Certification Auto-Calculation
    // ═══════════════════════════════════════════════════════
    function updateCertification() {
        if (!employeeBalance) return;

        const vlTotalEarned = parseFloat(employeeBalance.vl_total_earned);
        const slTotalEarned = parseFloat(employeeBalance.sl_total_earned);
        const vlCurrentBalance = parseFloat(employeeBalance.vl_balance);
        const slCurrentBalance = parseFloat(employeeBalance.sl_balance);

        // Calculate VL/SL days from entries
        let vlDays = 0;
        let slDays = 0;

        document.querySelectorAll('.date-entry').forEach(entry => {
            const typeSelect = entry.querySelector('.entry-type');
            const daysInput = entry.querySelector('.entry-days');
            const selected = typeSelect.options[typeSelect.selectedIndex];
            const days = parseFloat(daysInput.value) || 0;

            if (selected && selected.dataset) {
                if (selected.dataset.code === 'VL' || selected.dataset.code === 'FL') vlDays += days;
                else if (selected.dataset.code === 'SL') slDays += days;
            }
        });

        const vlNewBalance = vlCurrentBalance - vlDays;
        const slNewBalance = slCurrentBalance - slDays;

        document.getElementById('certVlEarned').textContent = vlTotalEarned;
        document.getElementById('certSlEarned').textContent = slTotalEarned;
        document.getElementById('certVlLess').textContent = vlDays;
        document.getElementById('certSlLess').textContent = slDays;
        document.getElementById('certVlBalance').textContent = vlNewBalance;
        document.getElementById('certSlBalance').textContent = slNewBalance;
        document.getElementById('certNotice').style.display = 'none';

        // Highlight insufficient balance
        const vlBalEl = document.getElementById('certVlBalance');
        const slBalEl = document.getElementById('certSlBalance');
        vlBalEl.classList.toggle('cert-insufficient', vlNewBalance < 0);
        slBalEl.classList.toggle('cert-insufficient', slNewBalance < 0);

        // Also update balance card values
        const vlBalBox = document.getElementById('vlBalance');
        const slBalBox = document.getElementById('slBalance');
        if (vlBalBox) vlBalBox.classList.toggle('insufficient', vlNewBalance < 0);
        if (slBalBox) slBalBox.classList.toggle('insufficient', slNewBalance < 0);
    }

    // ═══════════════════════════════════════════════════════
    // Entry Management
    // ═══════════════════════════════════════════════════════
    addBtn.addEventListener('click', () => {
        const i = entryIndex++;
        const entry = document.createElement('div');
        entry.className = 'date-entry animate-fade';
        entry.dataset.index = i;
        entry.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                <span class="entry-label">#${i + 1}</span>
                <span style="flex: 1;"></span>
                <button type="button" class="remove-entry-btn" onclick="removeEntry(this)">
                    <i class="fas fa-trash-alt"></i> Remove
                </button>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.78rem;">Type of Leave</label>
                    <select name="entries[${i}][leave_type_id]" class="form-control entry-type" required>
                        ${leaveTypeOptions}
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.78rem;">Inclusive Dates</label>
                    <input type="text" name="entries[${i}][inclusive_dates]" class="form-control entry-dates" placeholder="e.g. May 1, 2, 5-6" required>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.78rem;">No. of Days</label>
                    <input type="number" name="entries[${i}][num_days]" class="form-control entry-days" step="0.5" min="0.5" placeholder="0" required style="width: 90px;">
                </div>
            </div>
            <div class="others-specify" style="display: none; margin-top: 8px;">
                <input type="text" name="entries[${i}][other_type]" class="form-control" placeholder="Specify other leave type..." style="font-size: 0.85rem;">
            </div>
        `;
        entriesContainer.appendChild(entry);
        bindEntryEvents(entry);
        renumberEntries();
    });

    function removeEntry(btn) {
        const entry = btn.closest('.date-entry');
        entry.style.opacity = '0';
        entry.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            entry.remove();
            renumberEntries();
            updatePreview();
            updateCertification();
        }, 200);
    }

    function renumberEntries() {
        document.querySelectorAll('.date-entry').forEach((entry, idx) => {
            entry.querySelector('.entry-label').textContent = `#${idx + 1}`;
        });
    }

    function bindEntryEvents(entry) {
        const typeSelect = entry.querySelector('.entry-type');
        const daysInput = entry.querySelector('.entry-days');
        const datesInput = entry.querySelector('.entry-dates');
        const othersDiv = entry.querySelector('.others-specify');

        typeSelect.addEventListener('change', () => {
            const selected = typeSelect.options[typeSelect.selectedIndex];
            if (selected.dataset.code === 'OTH') {
                othersDiv.style.display = 'block';
                othersDiv.querySelector('input').required = true;
            } else {
                othersDiv.style.display = 'none';
                othersDiv.querySelector('input').required = false;
                othersDiv.querySelector('input').value = '';
            }
            updatePreview();
            updateCertification();
        });

        daysInput.addEventListener('input', () => {
            updatePreview();
            updateCertification();
        });
        datesInput.addEventListener('input', updatePreview);
    }

    // ═══════════════════════════════════════════════════════
    // Computation Preview
    // ═══════════════════════════════════════════════════════
    function updatePreview() {
        const summaryDiv = document.getElementById('entries-summary');
        const totalDisplay = document.getElementById('total-days');
        let totalDays = 0;
        let html = '';

        document.querySelectorAll('.date-entry').forEach((entry) => {
            const typeSelect = entry.querySelector('.entry-type');
            const daysInput = entry.querySelector('.entry-days');
            const datesInput = entry.querySelector('.entry-dates');

            const selected = typeSelect.options[typeSelect.selectedIndex];
            const typeName = selected.value ? selected.text : 'Not Selected';
            const days = parseFloat(daysInput.value) || 0;
            const datesText = datesInput.value || 'No dates';
            totalDays += days;

            html += `<div class="entry-summary-row">
                <div style="flex: 1; min-width: 0;">
                    <strong style="font-size: 0.8rem;">${typeName}</strong>
                    <div style="font-size: 0.72rem; color: var(--secondary); margin-top: 2px;">${datesText}</div>
                </div>
                <span style="font-weight: 700; color: var(--primary); white-space: nowrap; margin-left: 12px;">${days} ${days === 1 ? 'day' : 'days'}</span>
            </div>`;
        });

        summaryDiv.innerHTML = html || '<p style="color: var(--secondary); font-size: 0.82rem;">No entries yet.</p>';
        totalDisplay.textContent = totalDays + (totalDays === 1 ? ' day' : ' days');
    }

    // Initialize
    bindEntryEvents(document.querySelector('.date-entry'));
    updatePreview();
</script>
@endpush
@endsection
