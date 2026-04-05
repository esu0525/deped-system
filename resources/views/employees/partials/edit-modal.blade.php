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
                <input type="number" step="any" name="vl_balance" class="form-control" value="{{ (float)($employee->currentLeaveCard?->vl_balance ?? 0) }}" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>
            <div class="form-group">
                <label class="form-label">Initial SL Balance (Days)</label>
                <input type="number" step="any" name="sl_balance" class="form-control" value="{{ (float)($employee->currentLeaveCard?->sl_balance ?? 0) }}" onfocus="if(this.value==='0') this.value='';" onblur="if(this.value==='') this.value='0';">
            </div>

        </div>

        <div style="display: flex; gap: 12px; margin-top: 30px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="closeEditModal(); openViewModal('{{ route('employees.show', $employee) }}')">Cancel</button>
            <button type="submit" class="btn btn-primary" style="padding-left: 30px; padding-right: 30px;"><i class="fas fa-save"></i> Save Changes</button>
        </div>
    </form>

    {{-- Employee Account Management --}}
    <div style="margin-top: 24px; padding: 20px; background: #f0f9ff; border-radius: 10px; border: 1px solid #bae6fd;">
        <h5 style="font-weight: 700; font-size: 0.9rem; color: #0369a1; margin: 0 0 12px;">
            <i class="fas fa-user-shield" style="margin-right: 6px;"></i> Employee Login Account
        </h5>

        @if($employee->user)
            {{-- Account exists --}}
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px; background: #ecfdf5; border-radius: 8px; border: 1px solid #a7f3d0;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #10b981; color: white; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-check"></i>
                </div>
                <div style="flex: 1;">
                    <p style="font-weight: 700; font-size: 0.88rem; margin: 0; color: #065f46;">Account Active</p>
                    <p style="font-size: 0.78rem; color: #047857; margin: 2px 0 0;">
                        <i class="fas fa-envelope" style="margin-right: 4px;"></i> {{ $employee->user->email }}
                        &bull; Role: {{ $employee->user->role_display }}
                    </p>
                </div>
            </div>

            {{-- Show default password reminder --}}
            @php
                $parsed = \App\Services\AccountGeneratorService::parseName($employee->full_name);
                $first2 = mb_substr(ucfirst(strtolower($parsed['surname'])), 0, 2);
                $defaultPw = '#' . $first2 . 'd3P3d';
            @endphp
            <div style="margin-top: 10px; padding: 10px 14px; background: #fffbeb; border-radius: 8px; border: 1px solid #fde68a; font-size: 0.78rem;">
                <i class="fas fa-key" style="color: #d97706; margin-right: 6px;"></i>
                <strong>Default Password:</strong>
                <code style="background: #fef3c7; padding: 2px 8px; border-radius: 4px; font-weight: 700; color: #92400e;">{{ $defaultPw }}</code>
                <span style="color: var(--secondary); margin-left: 4px;">(#{{ $first2 }} + d3P3d)</span>
            </div>
        @else
            {{-- No account - Auto-generate one --}}
            @php
                $credentials = \App\Services\AccountGeneratorService::generateCredentials($employee->full_name);
            @endphp
            <p style="font-size: 0.78rem; color: var(--secondary); margin: 0 0 12px;">
                This employee doesn't have a login account yet. Click below to auto-generate one.
            </p>

            <div style="padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 14px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.82rem;">
                    <div>
                        <span style="font-weight: 600; color: var(--secondary);">Auto Email:</span><br>
                        <code style="font-weight: 700; color: #1e40af;">{{ $credentials['email'] }}</code>
                    </div>
                    <div>
                        <span style="font-weight: 600; color: var(--secondary);">Auto Password:</span><br>
                        <code style="font-weight: 700; color: #92400e;">{{ $credentials['password'] }}</code>
                    </div>
                </div>
            </div>

            <form action="{{ route('employees.create-account', $employee) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-user-plus"></i> Create Account Automatically
                </button>
            </form>
        @endif
    </div>
</div>
