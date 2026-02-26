<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveCard;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\AuditTrail;
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
    public function show(Request $request, Employee $employee)
    {
        $employee->load(['department', 'leaveCards']);

        // Available years from leave cards, default to current year
        $years = $employee->leaveCards->pluck('year')->unique()->sort()->values();
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $year = $request->input('year', now()->year);

        // Get leave card for selected year
        $leaveCard = $employee->leaveCards->where('year', $year)->first();

        // Get transactions for selected year
        $transactions = $employee->leaveTransactions()
            ->with(['leaveType', 'encoder'])
            ->whereYear('transaction_date', $year)
            ->orderBy('transaction_date', 'asc')
            ->get();

        return view('leave-cards.show', compact('employee', 'leaveCard', 'years', 'year', 'transactions'));
    }

    /**
     * Update or create leave card beginning balance.
     */
    public function adjust(Request $request, Employee $employee)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'vl_beginning_balance' => 'required|numeric|min:0',
            'sl_beginning_balance' => 'required|numeric|min:0',
            'forced_leave_balance' => 'nullable|numeric|min:0',
            'special_leave_balance' => 'nullable|numeric|min:0',
        ]);

        $year = $request->input('year');

        $leaveCard = LeaveCard::updateOrCreate(
        ['employee_id' => $employee->id, 'year' => $year],
        [
            'vl_beginning_balance' => $request->input('vl_beginning_balance'),
            'sl_beginning_balance' => $request->input('sl_beginning_balance'),
            'forced_leave_balance' => $request->input('forced_leave_balance', 0),
            'special_leave_balance' => $request->input('special_leave_balance', 0),
        ]
        );

        // Recalculate balances based on transactions
        $leaveCard->recalculate();

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'UPDATE',
            'module' => 'Leave Cards',
            'description' => "Updated leave card balance for {$employee->full_name} (Year: {$year}) — VL: {$request->vl_beginning_balance}, SL: {$request->sl_beginning_balance}",
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('leave-cards.show', ['employee' => $employee, 'year' => $year])
            ->with('success', "Leave card balance for {$employee->full_name} (Year {$year}) updated successfully.");
    }

    /**
     * Add monthly credits to all active employees.
     */
    public function addMonthlyCredits(Request $request)
    {
        $monthlyVL = \App\Models\SystemSetting::get('monthly_vl_credits', 1.250);
        $monthlySL = \App\Models\SystemSetting::get('monthly_sl_credits', 1.250);
        $year = now()->year;
        $count = 0;

        $employees = Employee::where('status', 'Active')->get();

        foreach ($employees as $employee) {
            $leaveCard = LeaveCard::firstOrCreate(
            ['employee_id' => $employee->id, 'year' => $year],
            ['vl_beginning_balance' => 0, 'sl_beginning_balance' => 0]
            );

            // Add VL credit
            $vlType = LeaveType::where('code', 'VL')->first();
            if ($vlType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $vlType->id,
                    'transaction_date' => now(),
                    'transaction_type' => 'earned',
                    'days' => $monthlyVL,
                    'vl_balance_after' => $leaveCard->vl_balance + $monthlyVL,
                    'sl_balance_after' => $leaveCard->sl_balance,
                    'remarks' => 'Monthly VL Credit (' . now()->format('F Y') . ')',
                    'encoded_by' => auth()->id(),
                ]);
            }

            // Add SL credit
            $slType = LeaveType::where('code', 'SL')->first();
            if ($slType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $slType->id,
                    'transaction_date' => now(),
                    'transaction_type' => 'earned',
                    'days' => $monthlySL,
                    'vl_balance_after' => $leaveCard->vl_balance + $monthlyVL,
                    'sl_balance_after' => $leaveCard->sl_balance + $monthlySL,
                    'remarks' => 'Monthly SL Credit (' . now()->format('F Y') . ')',
                    'encoded_by' => auth()->id(),
                ]);
            }

            $leaveCard->recalculate();
            $count++;
        }

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'CREATE',
            'module' => 'Leave Cards',
            'description' => "Added monthly credits (VL: {$monthlyVL}, SL: {$monthlySL}) to {$count} employees.",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Monthly credits added successfully to {$count} employees.");
    }
}
