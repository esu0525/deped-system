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
                <input type="number" step="any" name="vl_balance" class="form-control" value="0" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>
            <div class="form-group">
                <label class="form-label">Initial SL Balance (Days)</label>
                <input type="number" step="any" name="sl_balance" class="form-control" value="0" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 30px; padding-right: 30px;"><i class="fas fa-save"></i> Create Employee</button>
        </div>
    </form>

    {{-- Auto-Account Info --}}
    <div style="margin-top: 20px; padding: 16px 20px; background: #ecfdf5; border-radius: 10px; border: 1px solid #a7f3d0;">
        <h5 style="font-weight: 700; font-size: 0.85rem; color: #065f46; margin: 0 0 6px;">
            <i class="fas fa-magic" style="margin-right: 6px;"></i> Auto-Generated Login Account
        </h5>
        <p style="font-size: 0.78rem; color: #047857; margin: 0; line-height: 1.6;">
            A login account will be <strong>automatically created</strong> when you add this employee.<br>
            <strong>Email:</strong> <code style="background: #d1fae5; padding: 1px 6px; border-radius: 3px;">lastname.firstname@deped.gov.ph</code><br>
            <strong>Password:</strong> <code style="background: #d1fae5; padding: 1px 6px; border-radius: 3px;">#[First2Letters]d3P3d</code>
            <span style="color: #065f46;">(e.g. Conanan → <strong>#Cod3P3d</strong>)</span>
        </p>
    </div>
</div>
