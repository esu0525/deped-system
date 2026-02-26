@extends('layouts.app')

@section('header_title', 'Application for Leave')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Form Section -->
        <div class="card glass animate-fade">
            <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-file-signature text-primary"></i> Leave Application Details</h4>
            
            <form action="{{ route('leave-applications.store') }}" method="POST" enctype="multipart/form-data" id="leaveForm">
                @csrf
                
                @if(auth()->user()->canManageEmployees())
                <div class="form-group">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" id="employee_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }} ({{ $emp->employee_id }})</option>
                        @endforeach
                    </select>
                </div>
                @else
                <input type="hidden" name="employee_id" value="{{ auth()->user()->employee->id }}">
                <div class="form-group">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->name }}" readonly>
                </div>
                @endif

                <div class="form-group">
                    <label class="form-label">Date of Filing</label>
                    <input type="date" name="date_filed" class="form-control" value="{{ old('date_filed', date('Y-m-d')) }}" required style="max-width: 250px;">
                </div>

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

                <!-- 6.B Details of Leave -->
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">6.B Details of Leave</label>
                    
                    <div class="details-section">
                        <!-- Vacation / Special Privilege Leave -->
                        <div class="detail-block">
                            <p class="detail-heading"><em>In case of Vacation/Special Privilege Leave:</em></p>
                            <div style="display: flex; flex-direction: column; gap: 8px; padding-left: 8px;">
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_within_ph" value="1" {{ old('detail_within_ph') ? 'checked' : '' }}>
                                    <span>Within the Philippines</span>
                                    <input type="text" name="detail_within_ph_specify" class="detail-line" placeholder="specify..." value="{{ old('detail_within_ph_specify') }}">
                                </label>
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_abroad" value="1" {{ old('detail_abroad') ? 'checked' : '' }}>
                                    <span>Abroad (Specify)</span>
                                    <input type="text" name="detail_abroad_specify" class="detail-line" placeholder="specify country..." value="{{ old('detail_abroad_specify') }}">
                                </label>
                            </div>
                        </div>

                        <!-- Sick Leave -->
                        <div class="detail-block">
                            <p class="detail-heading"><em>In case of Sick Leave:</em></p>
                            <div style="display: flex; flex-direction: column; gap: 8px; padding-left: 8px;">
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_in_hospital" value="1" {{ old('detail_in_hospital') ? 'checked' : '' }}>
                                    <span>In Hospital (Specify Illness)</span>
                                    <input type="text" name="detail_in_hospital_specify" class="detail-line" placeholder="specify illness..." value="{{ old('detail_in_hospital_specify') }}">
                                </label>
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_out_patient" value="1" {{ old('detail_out_patient') ? 'checked' : '' }}>
                                    <span>Out Patient (Specify Illness)</span>
                                    <input type="text" name="detail_out_patient_specify" class="detail-line" placeholder="specify illness..." value="{{ old('detail_out_patient_specify') }}">
                                </label>
                            </div>
                        </div>

                        <!-- Special Leave Benefits for Women -->
                        <div class="detail-block">
                            <p class="detail-heading"><em>In case of Special Leave Benefits for Women:</em></p>
                            <div style="padding-left: 8px;">
                                <label class="detail-check">
                                    <span>(Specify Illness)</span>
                                    <input type="text" name="detail_women_specify" class="detail-line" placeholder="specify illness..." value="{{ old('detail_women_specify') }}" style="flex: 1;">
                                </label>
                            </div>
                        </div>

                        <!-- Study Leave -->
                        <div class="detail-block">
                            <p class="detail-heading"><em>In case of Study Leave:</em></p>
                            <div style="display: flex; flex-direction: column; gap: 8px; padding-left: 8px;">
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_masters" value="1" {{ old('detail_masters') ? 'checked' : '' }}>
                                    <span>Completion of Master's Degree</span>
                                </label>
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_bar_review" value="1" {{ old('detail_bar_review') ? 'checked' : '' }}>
                                    <span>BAR/Board Examination Review</span>
                                </label>
                            </div>
                        </div>

                        <!-- Other Purpose -->
                        <div class="detail-block">
                            <p class="detail-heading"><em>Other purpose:</em></p>
                            <div style="display: flex; flex-direction: column; gap: 8px; padding-left: 8px;">
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_monetization" value="1" {{ old('detail_monetization') ? 'checked' : '' }}>
                                    <span>Monetization of Leave Credits</span>
                                </label>
                                <label class="detail-check">
                                    <input type="checkbox" name="detail_terminal" value="1" {{ old('detail_terminal') ? 'checked' : '' }}>
                                    <span>Terminal Leave</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Reason / Remarks -->
                <div class="form-group">
                    <label class="form-label">Additional Remarks (Optional)</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Any additional notes or remarks...">{{ old('reason') }}</textarea>
                </div>

                <!-- 6.D Commutation -->
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.95rem; font-weight: 700;">6.D Commutation</label>
                    <div class="details-section">
                        <div style="display: flex; flex-direction: column; gap: 10px; padding-left: 8px;">
                            <label class="detail-check">
                                <input type="radio" name="commutation" value="not_requested" {{ old('commutation', 'not_requested') == 'not_requested' ? 'checked' : '' }}>
                                <span>Not Requested</span>
                            </label>
                            <label class="detail-check">
                                <input type="radio" name="commutation" value="requested" {{ old('commutation') == 'requested' ? 'checked' : '' }}>
                                <span>Requested</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Attachment (Optional)</label>
                    <div style="border: 2px dashed #cbd5e1; padding: 30px; text-align: center; border-radius: 16px; transition: all 0.2s;" id="drop-zone">
                        <i class="fas fa-cloud-upload-alt fa-3x" style="color: #94a3b8; margin-bottom: 15px;"></i>
                        <p style="color: #64748b; margin-bottom: 15px;">Drag & drop files here or click to browse</p>
                        <input type="file" name="attachment" id="attachment" style="display: none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('attachment').click()">Select File</button>
                        <p id="file-name" style="margin-top: 15px; font-weight: 600; font-size: 0.8rem; color: var(--primary);"></p>
                    </div>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn btn-primary" style="padding: 14px 40px;">Submit Application <i class="fas fa-paper-plane"></i></button>
                    <a href="{{ route('leave-applications.index') }}" class="btn btn-secondary" style="padding: 14px 20px;">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Info Section -->
        <div>
            <div class="card animate-fade" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); border: 1px solid #bfdbfe;">
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

            <div class="card glass animate-fade" style="margin-top: 24px;" id="calc-preview">
                <h5 style="font-weight: 700; margin-bottom: 16px;">Computation Preview</h5>
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
        </div>
    </div>
</div>

@push('styles')
<style>
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
    .detail-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
        flex-shrink: 0;
    }
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
</style>
@endpush

@push('scripts')
<script>
    let entryIndex = 1;
    const entriesContainer = document.getElementById('dateEntries');
    const addBtn = document.getElementById('addEntryBtn');
    const fileInput = document.getElementById('attachment');
    const fileNameDisplay = document.getElementById('file-name');

    // Leave type options HTML
    const leaveTypeOptions = `<option value="">— Select —</option>` +
        @json($leaveTypes->map(fn($t) => ['id' => $t->id, 'code' => $t->code, 'name' => $t->name]))
        .map(t => `<option value="${t.id}" data-code="${t.code}">${t.name} (${t.code})</option>`)
        .join('');

    // Add new leave entry
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

    // Remove entry
    function removeEntry(btn) {
        const entry = btn.closest('.date-entry');
        entry.style.opacity = '0';
        entry.style.transform = 'translateX(-20px)';
        setTimeout(() => {
            entry.remove();
            renumberEntries();
            updatePreview();
        }, 200);
    }

    // Renumber entries
    function renumberEntries() {
        document.querySelectorAll('.date-entry').forEach((entry, idx) => {
            entry.querySelector('.entry-label').textContent = `#${idx + 1}`;
        });
    }

    // Bind events to an entry
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
        });

        daysInput.addEventListener('input', updatePreview);
        datesInput.addEventListener('input', updatePreview);
    }

    // Update computation preview
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

    // File input
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            fileNameDisplay.innerText = "Selected: " + e.target.files[0].name;
        }
    });

    // Initialize first entry
    bindEntryEvents(document.querySelector('.date-entry'));
    updatePreview();
</script>
@endpush
@endsection
