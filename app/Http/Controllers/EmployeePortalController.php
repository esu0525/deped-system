<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EmployeePortalController extends Controller
{
    /**
     * Show the employee dashboard/portal.
     */
    public function dashboard()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return view('employee-portal.no-profile');
        }

        $employee->load(['department', 'leaveCards', 'user']);
        $currentLeaveCard = $employee->leaveCards->where('year', now()->year)->first();

        // Get recent leave applications
        $recentApplications = $employee->leaveApplications()
            ->with('leaveType')
            ->latest()
            ->take(5)
            ->get();

        // Get recent activity logs
        $recentLogs = AuditTrail::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('employee-portal.dashboard', compact('employee', 'currentLeaveCard', 'recentApplications', 'recentLogs'));
    }

    /**
     * Show the employee's own leave card (read-only).
     */
    public function leaveCard(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'No employee profile linked to your account.');
        }

        $employee->load(['department', 'leaveCards', 'user']);

        // Available years from leave cards, default to current year
        $years = $employee->leaveCards->pluck('year')->unique()->sort()->values();
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $year = $request->input('year', now()->year);

        // Get leave card for selected year
        $leaveCard = $employee->leaveCards->where('year', $year)->first();

        // Get transactions for selected year
        $transactions = $leaveCard
            ? $employee->leaveTransactions()
            ->with(['leaveType', 'encoder'])
            ->where('leave_card_id', $leaveCard->id)
            ->orderBy('id', 'asc')
            ->get()
            : collect();

        AuditTrail::log('VIEW', 'Employee Portal', "Employee {$user->name} viewed their leave card for year {$year}");

        return view('employee-portal.leave-card', compact('employee', 'leaveCard', 'years', 'year', 'transactions'));
    }

    public function profile()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return view('employee-portal.no-profile');
        }

        return view('employee-portal.profile', compact('user', 'employee'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditTrail::log('UPDATE', 'Employee Portal', "Employee {$user->name} changed their password");

        return back()->with('success', 'Your password has been updated successfully.');
    }
}
