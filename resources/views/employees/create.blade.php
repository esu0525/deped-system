@extends('layouts.app')

@section('header_title', 'Add New Employee')

@section('content')
<div class="animate-fade">
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-user-plus text-primary"></i> New Employee Profile</h4>

        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Reference ID (Auto-generated)</label>
                    <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" placeholder="Leave blank to auto-generate">
                    @error('employee_id') <small style="color: var(--danger);">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Office/Department</label>
                    <input type="text" name="department_name" class="form-control" value="{{ old('department_name') }}" placeholder="Type office/department name">
                </div>
                <div class="form-group">
                    <label class="form-label">Employment Status</label>
                    <select name="employment_status" class="form-control">
                        <option value="Permanent" {{ old('employment_status') == 'Permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="Temporary" {{ old('employment_status') == 'Temporary' ? 'selected' : '' }}>Temporary</option>
                        <option value="Casual" {{ old('employment_status') == 'Casual' ? 'selected' : '' }}>Casual</option>
                        <option value="Contractual" {{ old('employment_status') == 'Contractual' ? 'selected' : '' }}>Contractual</option>
                        <option value="Job Order" {{ old('employment_status') == 'Job Order' ? 'selected' : '' }}>Job Order</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Initial VL Balance (Days)</label>
                    <input type="number" step="any" name="vl_balance" class="form-control" value="{{ old('vl_balance') }}" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Initial SL Balance (Days)</label>
                    <input type="number" step="any" name="sl_balance" class="form-control" value="{{ old('sl_balance') }}" placeholder="0">
                </div>
            </div>

            <!-- Profile picture removed -->

            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
