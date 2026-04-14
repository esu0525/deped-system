<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'employee') {
            return redirect()->route('employee.dashboard');
        }
        $employeeQuery = Employee::query();
        $applicationQuery = LeaveApplication::query();
        $auditQuery = AuditTrail::query();

        // Filter by user assignment (National/City)
        if (!in_array($user->role, ['admin', 'super_admin']) && $user->assign && strtolower($user->assign) !== 'all') {
            $employeeQuery->where('category', $user->assign);
            $applicationQuery->whereHas('employee', function($q) use ($user) {
                $q->where('category', $user->assign);
            });
            // We could also filter audits but audits are usually global for traceability
        }

        $totalEmployees = $employeeQuery->count();
        $totalApplications = $applicationQuery->count();
        $pendingCount = (clone $applicationQuery)->where("status", "Pending")->count();
        
        $recentActivity = AuditTrail::latest()->take(10)->get();

        $monthlySummary = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format("M");
            
            $days = (clone $applicationQuery)->where("status", "Approved")
                ->whereMonth("date_from", $date->month)
                ->whereYear("date_from", $date->year)
                ->sum("num_days");

            $monthlySummary[] = [
                "month" => $monthName,
                "days" => (float)$days ?? 0
            ];
        }

        return view("dashboard.dashboard", compact(
            "totalEmployees",
            "totalApplications",
            "pendingCount",
            "recentActivity",
            "monthlySummary"
        ));
    }
}

