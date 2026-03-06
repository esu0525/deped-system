<div class="modal-body animate-fade">
    <form id="editEmployeeForm" action="{{ route('employees.update', $employee) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Reference ID <span style="color: var(--danger);">*</span></label>
                <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $employee->employee_id) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $employee->full_name) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control" value="{{ old('position', $employee->position) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Office/Department</label>
                <input type="text" name="department_name" class="form-control" value="{{ old('department_name', $employee->department?->name) }}" placeholder="Type office/department name">
            </div>
            <div class="form-group">
                <label class="form-label">Employment Status</label>
                <select name="employment_status" class="form-control">
                    @foreach(['Permanent', 'Temporary', 'Casual', 'Contractual', 'Job Order'] as $status)
                        <option value="{{ $status }}" {{ old('employment_status', $employee->employment_status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    @foreach(['Active', 'Inactive', 'Resigned', 'Retired'] as $status)
                        <option value="{{ $status }}" {{ old('status', $employee->status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Initial VL Balance (Days)</label>
                <input type="number" step="any" name="vl_balance" class="form-control" value="{{ (float)($employee->currentLeaveCard?->vl_balance ?? 0) }}" placeholder="0">
            </div>
            <div class="form-group">
                <label class="form-label">Initial SL Balance (Days)</label>
                <input type="number" step="any" name="sl_balance" class="form-control" value="{{ (float)($employee->currentLeaveCard?->sl_balance ?? 0) }}" placeholder="0">
            </div>

        </div>

        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal(); openViewModal('{{ route('employees.show', $employee) }}')">Cancel</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 30px; padding-right: 30px;"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>
</div>
