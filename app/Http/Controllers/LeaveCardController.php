<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveCard;
use Illuminate\Http\Request;

class LeaveCardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'leaveCards'])->where('status', 'Active');

        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where('full_name', 'like', $search)
                ->orWhere('employee_id', 'like', $search);
        }

        $employees = $query->orderBy('full_name')->paginate(15)->withQueryString();

        return view('leave-cards.index', compact('employees'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $employee->load(['department', 'leaveCards', 'leaveTransactions' => function ($q) {
            $q->with('leaveType')->orderBy('transaction_date', 'desc');
        }]);

        $currentLeaveCard = $employee->leaveCards->where('year', now()->year)->first();

        return view('leave-cards.show', compact('employee', 'currentLeaveCard'));
    }
}
