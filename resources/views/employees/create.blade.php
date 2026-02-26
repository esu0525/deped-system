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
                    <label class="form-label">Employee ID <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" required>
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
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label">Date Hired</label>
                    <input type="date" name="date_hired" class="form-control" value="{{ old('date_hired') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Link to User Account</label>
                    <select name="user_id" class="form-control">
                        <option value="">None</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Profile Picture</label>
                <input type="file" name="profile_picture" class="form-control" accept="image/*">
            </div>

            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
