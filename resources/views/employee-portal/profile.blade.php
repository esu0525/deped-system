@extends('layouts.app')

@section('header_title', 'My Profile')

@section('content')
<div class="animate-fade">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <!-- Profile Info Card -->
        <div class="card glass animate-fade">
            <div style="text-align: center; margin-bottom: 24px;">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 2.5rem; font-weight: 800;">
                    {{ mb_substr($employee->full_name, 0, 1) }}
                </div>
                <h4 style="font-weight: 800; margin: 0; color: var(--dark);">{{ $employee->full_name }}</h4>
                <p style="color: var(--secondary); font-weight: 600; font-size: 0.9rem; margin: 4px 0 12px;">{{ $employee->position ?? 'Employee' }}</p>
                <span class="badge badge-success">{{ $employee->status }}</span>
            </div>

            <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0;">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--secondary); margin: 0;">Employee ID</p>
                        <p style="font-weight: 700; margin: 0; color: var(--dark);">{{ $employee->employee_id }}</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--secondary); margin: 0;">School/Department</p>
                        <p style="font-weight: 700; margin: 0; color: var(--dark);">{{ $employee->department->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #fef2f2; display: flex; align-items: center; justify-content: center; color: var(--danger);">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div>
                        <p style="font-size: 0.75rem; color: var(--secondary); margin: 0;">Email Address</p>
                        <p style="font-weight: 700; margin: 0; color: var(--dark);">{{ $user->email }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Change Password Card -->
        <div class="card glass animate-fade">
            <h5 style="font-weight: 800; margin: 0 0 24px; color: var(--dark);">
                <i class="fas fa-key text-primary" style="margin-right: 8px;"></i> Security Settings
            </h5>

            @if(session('success'))
                <div style="background: #ecfdf5; border-left: 4px solid #10b981; padding: 16px; border-radius: 12px; margin-bottom: 24px; color: #065f46; font-weight: 600;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div style="background: #fef2f2; border-left: 4px solid var(--danger); padding: 16px; border-radius: 12px; margin-bottom: 24px; color: var(--danger); font-size: 0.9rem; font-weight: 600;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employee.profile.password') }}" method="POST">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 16px; top: 16px; color: #94a3b8;"></i>
                            <input type="password" id="current_pw" name="current_password" class="form-control" style="padding-left: 45px; padding-right: 45px;" required>
                            <button type="button" onclick="togglePassword('current_pw')" style="position: absolute; right: 16px; top: 16px; border: 0; background: 0; color: #94a3b8; cursor: pointer;">
                                <i class="fas fa-eye" id="eye_current_pw"></i>
                            </button>
                        </div>
                        <small style="color: var(--secondary); display: block; margin-top: 6px;">Required to verify identity</small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div style="position: relative;">
                                <i class="fas fa-key" style="position: absolute; left: 16px; top: 16px; color: #94a3b8;"></i>
                                <input type="password" id="new_pw" name="password" class="form-control" style="padding-left: 45px; padding-right: 45px;" required>
                                <button type="button" onclick="togglePassword('new_pw')" style="position: absolute; right: 16px; top: 16px; border: 0; background: 0; color: #94a3b8; cursor: pointer;">
                                    <i class="fas fa-eye" id="eye_new_pw"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <div style="position: relative;">
                                <i class="fas fa-check-double" style="position: absolute; left: 16px; top: 16px; color: #94a3b8;"></i>
                                <input type="password" id="confirm_pw" name="password_confirmation" class="form-control" style="padding-left: 45px; padding-right: 45px;" required>
                                <button type="button" onclick="togglePassword('confirm_pw')" style="position: absolute; right: 16px; top: 16px; border: 0; background: 0; color: #94a3b8; cursor: pointer;">
                                    <i class="fas fa-eye" id="eye_confirm_pw"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 0.85rem; color: var(--secondary);">
                        <p style="font-weight: 700; margin: 0 0 8px; color: var(--dark);">Password Requirements:</p>
                        <ul style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 4px;">
                            <li>Minimum 8 characters</li>
                            <li>Must match confirmation</li>
                            <li>Include symbols or numbers for better security</li>
                        </ul>
                    </div>

                    <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">
                            <i class="fas fa-save" style="margin-right: 8px;"></i> Update Password
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function togglePassword(id) {
        const input = document.getElementById(id);
        const icon = document.getElementById('eye_' + id);
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endsection
