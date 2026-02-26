@extends('layouts.app')

@section('header_title', 'Application for Leave')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Form Section -->
        <div class="card glass animate-fade">
            <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-file-signature text-primary"></i> Leave Application Details</h4>
            
            <form action="{{ route('leave-applications.store') }}" method="POST" enctype="multipart/form-data">
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
                    <label class="form-label">Type of Leave</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                        @foreach($leaveTypes as $type)
                        @php
                            /** @var \App\Models\LeaveType $type */
                            $typeId = $type->id;
                            $typeName = $type->name;
                            $typeCode = $type->code;
                        @endphp
                        <label class="glass leave-type-label" style="display: flex; align-items: center; gap: 12px; padding: 15px; border-radius: 12px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s;">
                            <input type="radio" name="leave_type_id" value="{{ $typeId }}" style="width: 20px; height: 20px;" {{ old('leave_type_id') == $typeId ? 'checked' : '' }} required>
                            <div>
                                <p style="font-weight: 700; margin: 0;">{{ $typeName }}</p>
                                <small style="opacity: 0.6;">{{ $typeCode }}</small>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="{{ old('date_from', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="{{ old('date_to', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Details of Leave (Reason)</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="e.g., Personal health checkup, Family vacation, etc." required>{{ old('reason') }}</textarea>
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
            <div class="card glass-dark animate-fade" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <h5 style="color: white; font-weight: 700; margin-bottom: 20px;"><i class="fas fa-info-circle text-info"></i> Leave Reminders</h5>
                <ul style="list-style: none; font-size: 0.85rem; padding: 0;">
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>VL must be filed 5 days in advance.</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>SL must be filed upon return to work.</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Medical certificate is required for SL > 3 days.</span>
                    </li>
                    <li style="margin-bottom: 15px; display: flex; gap: 10px;">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Forced leave (5 days) must be consumed within the year.</span>
                    </li>
                </ul>
            </div>

            <div class="card glass animate-fade" style="margin-top: 24px;" id="calc-preview">
                <h5 style="font-weight: 700; margin-bottom: 20px;">Computation Preview</h5>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Number of Days:</span>
                    <span id="days-count" style="font-weight: 800; color: var(--primary);">1 day</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <span>Type:</span>
                    <span id="type-preview" style="font-weight: 800;">Not Selected</span>
                </div>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <p style="font-size: 0.75rem; color: var(--secondary); font-style: italic;">
                    Credits will be deducted upon approval of HR administrator.
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const fromInput = document.getElementById('date_from');
    const toInput = document.getElementById('date_to');
    const daysDisplay = document.getElementById('days-count');
    const fileInput = document.getElementById('attachment');
    const fileNameDisplay = document.getElementById('file-name');

    function calculateDays() {
        if (!fromInput.value || !toInput.value) return;
        
        const start = new Date(fromInput.value);
        const end = new Date(toInput.value);
        
        if (end < start) {
            daysDisplay.innerText = "Invalid Dates";
            daysDisplay.style.color = "var(--danger)";
            return;
        }

        // Simulating working days calculation (excluding weekends)
        let count = 0;
        let cur = new Date(start);
        while (cur <= end) {
            const day = cur.getDay();
            if (day !== 0 && day !== 6) count++;
            cur.setDate(cur.getDate() + 1);
        }
        
        daysDisplay.innerText = count + (count === 1 ? " working day" : " working days");
        daysDisplay.style.color = "var(--primary)";
    }

    fromInput.addEventListener('change', calculateDays);
    toInput.addEventListener('change', calculateDays);
    
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            fileNameDisplay.innerText = "Selected: " + e.target.files[0].name;
        }
    });

    // Update type preview
    document.querySelectorAll('input[name="leave_type_id"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const label = e.target.closest('label').querySelector('p').innerText;
            document.getElementById('type-preview').innerText = label;
            
            // Highlight selected
            document.querySelectorAll('label').forEach(l => l.style.borderColor = 'transparent');
            e.target.closest('label').style.borderColor = 'var(--primary)';
        });
    });

    if (fromInput.value && toInput.value) calculateDays();
</script>
@endpush
@endsection
