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
        if (auth()->user()->role === 'employee') {
            return redirect()->route('employee.dashboard');
        }
        $totalEmployees = Employee::count();
        $totalApplications = LeaveApplication::count();
        $pendingCount = LeaveApplication::where("status", "Pending")->count();
        
        $recentActivity = AuditTrail::latest()->take(10)->get();

        $monthlySummary = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format("M");
            
            $days = LeaveApplication::where("status", "Approved")
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

