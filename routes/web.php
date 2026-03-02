<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveCardController;
use App\Http\Controllers\AiDetectionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ImportController;

// ─── Authentication ───────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
Route::post('/login', [AuthController::class , 'login']);
Route::post('/logout', [AuthController::class , 'logout'])->name('logout');

Route::get('/otp/verify', [AuthController::class , 'showOtpVerify'])->name('otp.verify');
Route::post('/otp/verify', [AuthController::class , 'verifyOtp']);
Route::post('/otp/resend', [AuthController::class , 'resendOtp'])->name('otp.resend');

Route::get('/forgot-password', [AuthController::class , 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class , 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class , 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class , 'resetPassword'])->name('password.update');

// ─── Protected Routes (Logged in & OTP Verified) ──────────────────────────
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

    // ─── Employee Management (Admin/HR/Encoder) ───────────────────────────
    Route::middleware(['role:super_admin,hr_admin,encoder'])->group(function () {
            Route::resource('employees', EmployeeController::class);
        }
        );

        // Employees can view their own profile
        Route::get('/profile', [EmployeeController::class , 'show'])->name('profile');

        // ─── Leave Applications ───────────────────────────────────────────────
        Route::resource('leave-applications', LeaveApplicationController::class);
        Route::post('/leave-applications/{leave_application}/approve', [LeaveApplicationController::class , 'approve'])->name('leave-applications.approve');
        Route::post('/leave-applications/{leave_application}/reject', [LeaveApplicationController::class , 'reject'])->name('leave-applications.reject');
        Route::get('/api/employee/{employee}/leave-balance', [LeaveApplicationController::class , 'getEmployeeBalance'])->name('api.employee.leave-balance');

        // ─── Leave Cards & Automation ─────────────────────────────────────────
        Route::get('/leave-cards', [LeaveCardController::class , 'index'])->name('leave-cards.index');
        Route::get('/leave-cards/{employee}', [LeaveCardController::class , 'show'])->name('leave-cards.show');

        Route::middleware(['role:super_admin,hr_admin,encoder'])->group(function () {
            Route::post('/leave-cards/{employee}/adjust', [LeaveCardController::class , 'adjust'])->name('leave-cards.adjust');
            Route::post('/leave-cards/{employee}/sync-transactions', [LeaveCardController::class , 'syncTransactions'])->name('leave-cards.sync-transactions');
            Route::post('/leave-cards/monthly-credit', [LeaveCardController::class , 'addMonthlyCredits'])->name('leave-cards.monthly-credits');
        }
        );

        // ─── Reports ──────────────────────────────────────────────────────────
        Route::group(['prefix' => 'reports', 'as' => 'reports.', 'middleware' => ['role:super_admin,hr_admin']], function () {
            Route::get('/', [ReportController::class , 'index'])->name('index');
            Route::get('/employee-summary', [ReportController::class , 'employeeSummary'])->name('employee-summary');
            Route::get('/monthly-leave', [ReportController::class , 'monthlyLeave'])->name('monthly-leave');
            Route::get('/department-usage', [ReportController::class , 'departmentUsage'])->name('department-usage');
            Route::get('/leave-balances', [ReportController::class , 'leaveBalances'])->name('leave-balances');

            // Exports
            Route::get('/export/employees', [ReportController::class , 'exportEmployees'])->name('export.employees');
            Route::get('/export/leave-cards', [ReportController::class , 'exportLeaveCards'])->name('export.leave-cards');
            Route::get('/export/leave-applications', [ReportController::class , 'exportLeaveApplications'])->name('export.leave-applications');
            Route::get('/export/leave-transactions', [ReportController::class , 'exportLeaveTransactions'])->name('export.leave-transactions');
            Route::get('/export/monthly-summary', [ReportController::class , 'exportMonthlyDeptSummary'])->name('export.monthly-summary');
        }
        );

        // ─── Import ───────────────────────────────────────────────────────────
        Route::group(['prefix' => 'import', 'as' => 'import.', 'middleware' => ['role:super_admin,hr_admin']], function () {
            Route::get('/', [ImportController::class , 'index'])->name('index');
            Route::get('/template/{type}', [ImportController::class , 'downloadTemplate'])->name('template');
            Route::post('/employees', [ImportController::class , 'importEmployees'])->name('employees');
            Route::post('/leave-cards', [ImportController::class , 'importLeaveCards'])->name('leave-cards');
            Route::post('/transactions', [ImportController::class , 'importLeaveTransactions'])->name('transactions');
        }
        );

        // ─── AI Detection ─────────────────────────────────────────────────────
        Route::group(['prefix' => 'ai-detection', 'as' => 'ai.', 'middleware' => ['role:super_admin,hr_admin']], function () {
            Route::get('/', [AiDetectionController::class , 'index'])->name('index');
            Route::get('/{ai_detection_log}', [AiDetectionController::class , 'show'])->name('show');
            Route::post('/analyze/{employee}', [AiDetectionController::class , 'analyze'])->name('analyze');
            Route::post('/analyze-all', [AiDetectionController::class , 'analyzeAll'])->name('analyze-all');
            Route::post('/review/{ai_detection_log}', [AiDetectionController::class , 'markReviewed'])->name('review');
        }
        );

        Route::middleware(['role:super_admin'])->group(function () {
            Route::get('/settings', function () {
                    $settings = \App\Models\SystemSetting::all()->keyBy('key');
                    return view('settings.index', compact('settings'));
                }
                )->name('settings.index');

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
                }
                )->name('settings.update');
            }
            );

            Route::middleware(['role:super_admin'])->get('/audit-trails', [App\Http\Controllers\AuditTrailController::class , 'index'])->name('audit-trail.index');
        });
