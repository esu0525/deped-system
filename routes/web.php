<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveCardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditTrailController;

// ─── Authentication ───────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/otp/verify', [AuthController::class, 'showOtpVerify'])->name('otp.verify');
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');

Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
// Corrected to use AuthController for these as well if they are handled there
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// ─── Protected Routes (Logged in & OTP Verified) ──────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ─── Employee Portal (Employee role only) ─────────────────────────────
    Route::middleware(['role:employee'])->prefix('my')->group(function () {
        Route::get('/dashboard', [EmployeePortalController::class, 'dashboard'])->name('employee.dashboard');
        Route::get('/leave-card', [EmployeePortalController::class, 'leaveCard'])->name('employee.leave-card');
        Route::get('/profile', [EmployeePortalController::class, 'profile'])->name('employee.profile');
        Route::post('/profile/password', [EmployeePortalController::class, 'updatePassword'])->name('employee.profile.password');
    });

    // ─── Employee Management (Admin only) ───────────────────────────
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::resource('employees', EmployeeController::class);
        Route::post('/employees/{employee}/create-account', [EmployeeController::class, 'createAccount'])->name('employees.create-account');
    });

    // ─── Account Management ───────────────────────────────────────────
    Route::middleware(['role:admin,super_admin,coordinator,ojt'])->group(function () {
        Route::resource('users', UserController::class);
    });

    // Employees can view their own profile
    Route::get('/profile', [EmployeeController::class, 'show'])->name('profile');

    // ─── Leave Applications (Admin/Coordinator/OJT) ──────────────────────────
    Route::middleware(['role:admin,super_admin,coordinator,ojt'])->group(function () {
        Route::resource('leave-applications', LeaveApplicationController::class);
        Route::post('/leave-applications/bulk-approve', [LeaveApplicationController::class, 'bulkApprove'])->name('leave-applications.bulk-approve');
        Route::post('/leave-applications/{leave_application}/approve', [LeaveApplicationController::class, 'approve'])->name('leave-applications.approve');
        Route::post('/leave-applications/{leave_application}/reject', [LeaveApplicationController::class, 'reject'])->name('leave-applications.reject');
    });

    Route::get('/api/employee/{employee}/leave-balance', [LeaveApplicationController::class, 'getEmployeeBalance'])->name('api.employee.leave-balance');
    Route::get('/api/employee/{employee}/cto-balances', [LeaveApplicationController::class, 'getEmployeeCtoBalances'])->name('api.employee.cto-balances');

    // ─── Leave Cards & Automation ─────────────────────────────────────────
    Route::get('/leave-cards', [LeaveCardController::class, 'index'])->name('leave-cards.index');
    Route::get('/leave-cards/{employee}', [LeaveCardController::class, 'show'])->name('leave-cards.show');

    Route::middleware(['role:admin,super_admin,coordinator,ojt'])->group(function () {
        Route::post('/leave-cards/{employee}/adjust', [LeaveCardController::class, 'adjust'])->name('leave-cards.adjust');
        Route::post('/leave-cards/{employee}/sync-transactions', [LeaveCardController::class, 'syncTransactions'])->name('leave-cards.sync-transactions');
        Route::post('/leave-cards/monthly-credit', [LeaveCardController::class, 'addMonthlyCredits'])->name('leave-cards.monthly-credits');
    });


    // ─── Import ───────────────────────────────────────────────────────────
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::post('/import/employees', [ImportController::class, 'importEmployees'])->name('import.employees');
    });

    // ─── User Management & Settings (Admin only) ──────────────────────────────────────────────────
    Route::middleware(['role:admin,super_admin'])->group(function () {
        Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // System Settings
        Route::get('/settings', function () {
            $settings = \App\Models\SystemSetting::all()->keyBy('key');
            return view('settings.index', compact('settings'));
        })->name('settings.index');

        Route::post('/settings', function (\Illuminate\Http\Request $request) {
            $settingsData = $request->input('settings', []);
            foreach ($settingsData as $key => $value) {
                \App\Models\SystemSetting::set($key, $value);
            }
            \App\Models\AuditTrail::create([
                'user_id' => auth()->id(),
                'action' => 'UPDATE',
                'module' => 'System Settings',
                'description' => 'Updated system settings: ' . implode(', ', array_keys($settingsData)),
                'ip_address' => $request->ip(),
            ]);
            return back()->with('success', 'System settings updated successfully.');
        })->name('settings.update');

        // Audit Trails
        Route::get('/audit-trails', [AuditTrailController::class, 'index'])->name('audit-trail.index');
    });
});
