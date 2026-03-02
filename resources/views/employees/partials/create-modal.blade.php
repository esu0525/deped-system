<div class="modal-body animate-fade">
    <form id="createEmployeeForm" action="{{ route('employees.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Reference ID (Auto-generated)</label>
                <input type="text" name="employee_id" class="form-control" placeholder="Leave blank to auto-generate">
            </div>
            <div class="form-group">
                <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Position</label>
                <input type="text" name="position" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Office/Department</label>
                <input type="text" name="department_name" class="form-control" placeholder="Type office/department name">
            </div>
            <div class="form-group">
                <label class="form-label">Employment Status</label>
                <select name="employment_status" class="form-control">
                    <option value="Permanent">Permanent</option>
                    <option value="Temporary">Temporary</option>
                    <option value="Casual">Casual</option>
                    <option value="Contractual">Contractual</option>
                    <option value="Job Order">Job Order</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Initial VL Balance (Days)</label>
                <input type="number" step="any" name="vl_balance" class="form-control" placeholder="0">
            </div>
            <div class="form-group">
                <label class="form-label">Initial SL Balance (Days)</label>
                <input type="number" step="any" name="sl_balance" class="form-control" placeholder="0">
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 30px; padding-right: 30px;"><i class="fas fa-save"></i> Create Employee</button>
        </div>
    </form>
</div>
