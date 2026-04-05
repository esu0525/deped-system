@extends('layouts.app')

@section('header_title', 'System Settings')

@section('content')
<div class="animate-fade">
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf

        <!-- General Settings -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-cog" style="color: var(--primary);"></i>
                </div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">General Settings</h4>
                    <small style="color: var(--secondary);">Basic system configuration</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Division Office Name</label>
                    <input type="text" name="settings[division_office_name]" class="form-control" value="{{ $settings->get('division_office_name')->value ?? 'SCHOOLS DIVISION OFFICE-QUEZON CITY' }}" placeholder="e.g. SCHOOLS DIVISION OFFICE-QUEZON CITY">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Appears on top of Leave Card printouts</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Division Office Address</label>
                    <input type="text" name="settings[division_office_address]" class="form-control" value="{{ $settings->get('division_office_address')->value ?? 'Nueva Ecija St., Bago Bantay, Quezon City' }}" placeholder="e.g. Nueva Ecija St., Bago Bantay, Quezon City">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Address shown below the office name on Leave Cards</small>
                </div>
                <div class="form-group">
                    <label class="form-label">System Name</label>
                    <input type="text" name="settings[system_name]" class="form-control" value="{{ $settings->get('system_name')->value ?? 'DepEd Leave Card System' }}" placeholder="e.g. DepEd Leave Card System">
                </div>
                <div class="form-group">
                    <label class="form-label">Organization Name</label>
                    <input type="text" name="settings[organization_name]" class="form-control" value="{{ $settings->get('organization_name')->value ?? 'Department of Education' }}" placeholder="e.g. Department of Education">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <input type="email" name="settings[contact_email]" class="form-control" value="{{ $settings->get('contact_email')->value ?? '' }}" placeholder="admin@deped.gov.ph">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <input type="text" name="settings[contact_phone]" class="form-control" value="{{ $settings->get('contact_phone')->value ?? '' }}" placeholder="(02) 8632-1361">
                </div>
            </div>
        </div>

        <!-- Leave Management Settings -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #ecfdf5, #d1fae5); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-check" style="color: var(--success);"></i>
                </div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">Leave Management</h4>
                    <small style="color: var(--secondary);">Configure leave credits and policies</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">Monthly VL Credits</label>
                    <input type="number" step="0.001" name="settings[monthly_vl_credits]" class="form-control" value="{{ $settings->get('monthly_vl_credits')->value ?? '1.250' }}" placeholder="1.250">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Credits earned per month for Vacation Leave</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Monthly SL Credits</label>
                    <input type="number" step="0.001" name="settings[monthly_sl_credits]" class="form-control" value="{{ $settings->get('monthly_sl_credits')->value ?? '1.250' }}" placeholder="1.250">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Credits earned per month for Sick Leave</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Forced Leave Per Year</label>
                    <input type="number" step="0.001" name="settings[forced_leave_per_year]" class="form-control" value="{{ $settings->get('forced_leave_per_year')->value ?? '5.000' }}" placeholder="5.000">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Mandatory forced leave days per year</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Max VL Carry Over</label>
                    <input type="number" step="0.001" name="settings[max_vl_carry_over]" class="form-control" value="{{ $settings->get('max_vl_carry_over')->value ?? '300.000' }}" placeholder="300.000">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Maximum VL balance that can carry over to next year</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Max SL Carry Over</label>
                    <input type="number" step="0.001" name="settings[max_sl_carry_over]" class="form-control" value="{{ $settings->get('max_sl_carry_over')->value ?? '300.000' }}" placeholder="300.000">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Maximum SL balance that can carry over to next year</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Special Privilege Leave</label>
                    <input type="number" step="0.001" name="settings[special_privilege_leave]" class="form-control" value="{{ $settings->get('special_privilege_leave')->value ?? '3.000' }}" placeholder="3.000">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">SPL days granted per year</small>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="card" style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, #fef2f2, #fecaca); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shield-halved" style="color: var(--danger);"></i>
                </div>
                <div>
                    <h4 style="font-weight: 700; margin: 0;">Security & Authentication</h4>
                    <small style="color: var(--secondary);">OTP, login, and security settings</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label class="form-label">OTP Expiry (Minutes)</label>
                    <input type="number" name="settings[otp_expiry_minutes]" class="form-control" value="{{ $settings->get('otp_expiry_minutes')->value ?? '5' }}" placeholder="5">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">How long OTP codes remain valid</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Max Login Attempts</label>
                    <input type="number" name="settings[max_login_attempts]" class="form-control" value="{{ $settings->get('max_login_attempts')->value ?? '5' }}" placeholder="5">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Before account is temporarily locked</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Lockout Duration (Minutes)</label>
                    <input type="number" name="settings[lockout_duration]" class="form-control" value="{{ $settings->get('lockout_duration')->value ?? '15' }}" placeholder="15">
                    <small style="color: var(--secondary); margin-top: 4px; display: block;">Account lockout time after failed attempts</small>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 32px;">
                <i class="fas fa-save"></i> Save All Settings
            </button>
        </div>
    </form>
</div>
@endsection
