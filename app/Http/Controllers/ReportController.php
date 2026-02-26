<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\LeaveApplication;
use App\Models\Department;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeesExport;
use App\Exports\LeaveCardExport;
use App\Exports\LeaveApplicationsExport;
use App\Exports\LeaveTransactionsExport;
use App\Exports\MonthlyDeptSummaryExport;
use App\Models\AuditTrail;

class ReportController extends Controller
{
    public function index()
    {
        $departments = Department::where('is_active', true)->get();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        return view('reports.report-center', compact('departments', 'leaveTypes'));
    }

    public function employeeSummary(Request $request)
    {
        $query = Employee::with(['department', 'currentLeaveCard'])
            ->where('status', 'Active');

        if ($request->department) {
            $query->where('department_id', $request->department);
        }

        $employees = $query->orderBy('full_name')->get();
        $departments = Department::where('is_active', true)->get();
        $year = $request->year ?? now()->year;

        return view('reports.employee-summary', compact('employees', 'departments', 'year'));
    }

    public function monthlyLeave(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $applications = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('status', 'Approved')
            ->whereYear('date_from', $year)
            ->whereMonth('date_from', $month);

        if ($request->department) {
            $applications->whereHas('employee', fn($q) => $q->where('department_id', $request->department));
        }

        $applications = $applications->get();
        $departments = Department::where('is_active', true)->get();

        return view('reports.monthly-leave', compact('applications', 'departments', 'month', 'year'));
    }

    public function departmentUsage(Request $request)
    {
        $year = $request->year ?? now()->year;

        $departments = Department::with(['employees' => function ($q) use ($year) {
            $q->with(['leaveCards' => fn($q2) => $q2->where('year', $year)]);
        }])->get();

        return view('reports.department-usage', compact('departments', 'year'));
    }

    public function leaveBalances(Request $request)
    {
        $year = $request->year ?? now()->year;
        $query = Employee::with(['department', 'leaveCards' => fn($q) => $q->where('year', $year)])
            ->where('status', 'Active');

        if ($request->department) {
            $query->where('department_id', $request->department);
        }

        $employees = $query->orderBy('full_name')->get();
        $departments = Department::where('is_active', true)->get();

        return view('reports.leave-balances', compact('employees', 'departments', 'year'));
    }

    // ─── Export ──────────────────────────────────────────────────────────────

    public function exportEmployees(Request $request)
    {
        AuditTrail::log('EXPORT', 'Reports', 'Exported employee list to Excel');
        return Excel::download(new EmployeesExport($request->all()), 'employees_' . date('Ymd') . '.xlsx');
    }

    public function exportLeaveCards(Request $request)
    {
        AuditTrail::log('EXPORT', 'Reports', 'Exported leave card summary to Excel');
        return Excel::download(new LeaveCardExport($request->all()), 'leave_cards_' . date('Ymd') . '.xlsx');
    }

    public function exportLeaveApplications(Request $request)
    {
        AuditTrail::log('EXPORT', 'Reports', 'Exported leave applications to Excel');
        return Excel::download(new LeaveApplicationsExport($request->all()), 'leave_applications_' . date('Ymd') . '.xlsx');
    }

    public function exportLeaveTransactions(Request $request)
    {
        AuditTrail::log('EXPORT', 'Reports', 'Exported leave transactions to Excel');
        return Excel::download(new LeaveTransactionsExport($request->all()), 'leave_transactions_' . date('Ymd') . '.xlsx');
    }

    public function exportMonthlyDeptSummary(Request $request)
    {
        AuditTrail::log('EXPORT', 'Reports', 'Exported monthly department summary to Excel');
        return Excel::download(new MonthlyDeptSummaryExport($request->all()), 'monthly_dept_summary_' . date('Ymd') . '.xlsx');
    }
}
