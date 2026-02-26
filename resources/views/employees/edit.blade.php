@extends('layouts.app')

@section('header_title', 'Edit Employee')

@section('content')
<div class="animate-fade">
    <div class="card glass">
        <h4 style="font-weight: 700; margin-bottom: 25px;"><i class="fas fa-user-edit text-primary"></i> Edit {{ $employee->full_name }}</h4>

        <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Employee ID <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $employee->employee_id) }}" required>
                    @error('employee_id') <small style="color: var(--danger);">{{ $message }}</small> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $employee->full_name) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select Gender</option>
                        <option value="Male" {{ old('gender', $employee->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $employee->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Position</label>
                    <input type="text" name="position" class="form-control" value="{{ old('position', $employee->position) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label">Date Hired</label>
                    <input type="date" name="date_hired" class="form-control" value="{{ old('date_hired', $employee->date_hired?->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number', $employee->contact_number) }}">
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
                    <label class="form-label">Link to User Account</label>
                    <select name="user_id" class="form-control">
                        <option value="">None</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $employee->user_id) == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $employee->address) }}</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Profile Picture</label>
                <input type="file" name="profile_picture" class="form-control" accept="image/*">
                @if($employee->profile_picture)
                    <small style="color: var(--secondary); margin-top: 5px; display: block;">Current photo uploaded</small>
                @endif
            </div>

            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Employee</button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
