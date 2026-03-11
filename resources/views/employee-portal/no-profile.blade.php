@extends('layouts.app')

@section('header_title', 'My Dashboard')

@section('content')
<div class="animate-fade" style="display: flex; align-items: center; justify-content: center; min-height: 60vh;">
    <div class="card glass" style="text-align: center; padding: 60px 40px; max-width: 500px;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #fbbf24, #f59e0b); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem; color: white;">
            <i class="fas fa-user-slash"></i>
        </div>
        <h3 style="font-weight: 800; color: var(--dark); margin: 0 0 10px;">No Employee Profile Linked</h3>
        <p style="color: var(--secondary); font-size: 0.92rem; line-height: 1.6; margin: 0 0 24px;">
            Your account is not yet linked to an employee profile. Please contact the HR Administrator to link your account so you can view your leave card and other information.
        </p>
        <div style="padding: 16px; background: #fffbeb; border-radius: 10px; border: 1px solid #fde68a;">
            <p style="font-size: 0.82rem; font-weight: 600; color: #92400e; margin: 0;">
                <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                Contact your HR Admin or System Administrator for assistance.
            </p>
        </div>
    </div>
</div>
@endsection
