<div class="modal-body animate-fade" x-data="{ category: '{{ $defaultCategory === 'employee' ? 'National' : $defaultCategory }}' }">
    <div style="margin-bottom: 25px; text-align: center;">
        <h3 style="font-weight: 800; color: var(--primary); margin: 0;">Add New <span x-text="['National', 'City'].includes(category) ? 'Employee' : 'Coordinator'"></span></h3>
        <p style="color: var(--secondary); font-size: 0.85rem; margin-top: 5px;">Fill out the information below to create a new profile.</p>
    </div>
    <form id="createEmployeeForm" onsubmit="submitForm(event)">
        @csrf
        <input type="hidden" name="category" :value="category">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Category <span style="color: var(--danger);">*</span></label>
                <select name="category_select" class="form-control" x-model="category" :required="category !== 'hrntp'">
                    <option value="National">National</option>
                    <option value="City">City</option>
                    <option value="hrntp">System Coordinator</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Reference ID (Auto-generated)</label>
                <input type="text" name="employee_id" class="form-control" placeholder="Leave blank to auto-generate">
            </div>
            <div class="form-group">
                <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                <input type="text" name="full_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Role / Position</label>
                <input type="text" name="position" class="form-control" placeholder="e.g. School Principal I">
            </div>

            <!-- HRNTP Access Field -->
            <div class="form-group" x-show="category === 'hrntp'">
                <label class="form-label">Access</label>
                <select name="access" class="form-control">
                    <option value="">Select Access</option>
                    <option value="Head Teacher">Head Teacher</option>
                    <option value="School Principal">School Principal</option>
                    <option value="Administrative Officer">Administrative Officer</option>
                    <option value="Administrative Aide">Administrative Aide</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Librarian">Librarian</option>
                </select>
            </div>

            <!-- Employee specific fields -->
            <div class="form-group" x-show="['National', 'City'].includes(category)">
                <label class="form-label">Office/Department</label>
                <input type="text" name="department_name" class="form-control" placeholder="Type office/department name">
            </div>
            <div class="form-group" x-show="['National', 'City'].includes(category)">
                <label class="form-label">Employment Status</label>
                <select name="employment_status" class="form-control">
                    <option value="Permanent">Permanent</option>
                    <option value="Temporary">Temporary</option>
                    <option value="Casual">Casual</option>
                    <option value="Contractual">Contractual</option>
                    <option value="Job Order">Job Order</option>
                </select>
            </div>
            <div class="form-group" x-show="['National', 'City'].includes(category)">
                <label class="form-label">Initial VL Balance (Days)</label>
                <input type="number" step="any" name="vl_balance" class="form-control" value="0" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>
            <div class="form-group" x-show="['National', 'City'].includes(category)">
                <label class="form-label">Initial SL Balance (Days)</label>
                <input type="number" step="any" name="sl_balance" class="form-control" value="0" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>

            <!-- HRNTP Login fields -->
            <div class="form-group" x-show="category === 'hrntp'">
                <label class="form-label">Email <span style="color: var(--danger);">*</span></label>
                <input type="email" name="email" class="form-control" :required="category === 'hrntp'">
            </div>
            <div class="form-group" x-show="category === 'hrntp'">
                <label class="form-label">Password <span style="color: var(--danger);">*</span></label>
                <input type="password" name="password" class="form-control" :required="category === 'hrntp'">
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn" 
                :style="category === 'hrntp' ? 'background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border: none; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.25);' : ''"
                style="padding-left: 30px; padding-right: 30px;">
                <i class="fas fa-save"></i> Create <span x-text="['National', 'City'].includes(category) ? 'Employee' : 'Coordinator'"></span>
            </button>
        </div>
    </form>

    <script>
        function submitForm(event) {
            event.preventDefault();
            const form = event.target;
            const btn = document.getElementById('submitBtn');
            const originalBtnHtml = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch("{{ route('employees.store') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    if (res.status === 422) {
                        // Validation errors
                        const errors = Object.values(data.errors).flat().join('\n');
                        throw new Error(errors);
                    }
                    throw new Error(data.message || 'Server error occurred.');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Submission Failed',
                    text: err.message || 'Something went wrong.'
                });
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            });
        }
    </script>

    {{-- Auto-Account Info for Employees --}}
    <div x-show="['National', 'City'].includes(category)" style="margin-top: 20px; padding: 16px 20px; background: rgba(16, 185, 129, 0.1); border-radius: 10px; border: 1px solid var(--border-color);">
        <h5 style="font-weight: 700; font-size: 0.85rem; color: var(--success); margin: 0 0 6px;">
            <i class="fas fa-magic" style="margin-right: 6px;"></i> Auto-Generated Login Account
        </h5>
        <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0; line-height: 1.6;">
            A login account will be <strong>automatically created</strong> when you add this employee.<br>
            <strong>Email:</strong> <code style="background: rgba(255, 255, 255, 0.1); padding: 1px 6px; border-radius: 3px; color: var(--primary);">lastname.firstname@deped.gov.ph</code><br>
            <strong>Password:</strong> <code style="background: rgba(255, 255, 255, 0.1); padding: 1px 6px; border-radius: 3px; color: var(--warning);">#[First2Letters]d3P3d</code>
        </p>
    </div>

    <style>
        .active-select { background: #2563eb; color: white; border-color: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .inactive-select { background: white; color: #64748b; border-color: #e2e8f0; }
        .inactive-select:hover { border-color: #cbd5e1; background: #f1f5f9; }
    </style>
</div>
