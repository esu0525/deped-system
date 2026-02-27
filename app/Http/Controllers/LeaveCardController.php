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
        $transactions = $leaveCard ? 
            $employee->leaveTransactions()
            ->with(['leaveType', 'encoder'])
            ->where('leave_card_id', $leaveCard->id)
            ->orderBy('id', 'asc')
            ->get()
            : collect();

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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

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

    /**
     * Sync the fully manual transactions grid.
     */
    public function syncTransactions(Request $request, Employee $employee)
    {
        $year = $request->input('year');
        $transactionsData = $request->input('transactions', []);

        $leaveCard = LeaveCard::firstOrCreate(
        ['employee_id' => $employee->id, 'year' => $year],
        ['vl_beginning_balance' => 0, 'sl_beginning_balance' => 0]
        );

        $existingIds = [];

        foreach ($transactionsData as $data) {
            // Ignore completely empty rows
            if (empty(trim($data['date_text'] ?? '')) && empty(trim($data['particulars'] ?? '')) && empty(trim($data['vl_earned'] ?? '')) && empty(trim($data['vl_used'] ?? ''))) {
                continue;
            }

            try {
                // If it looks like a valid date visually, try to parse it. Otherwise, use now() for sorting but keep actual string in 'period'
                $parsedStr = preg_replace('/[^0-9\/\-]/', '', $data['date_text'] ?? '');
                $parsedDate = !empty($parsedStr) ?\Carbon\Carbon::parse($parsedStr) : now();
            }
            catch (\Exception $e) {
                $parsedDate = now();
            }

            $updateData = [
                'employee_id' => $employee->id,
                'leave_card_id' => $leaveCard->id,
                'transaction_date' => $parsedDate,
                'period' => $data['date_text'] ?? null,
                'remarks' => $data['particulars'] ?? null,
                'vl_earned' => isset($data['vl_earned']) && $data['vl_earned'] !== '' ? floatval($data['vl_earned']) : null,
                'vl_used' => isset($data['vl_used']) && $data['vl_used'] !== '' ? floatval($data['vl_used']) : null,
                'vl_wop' => isset($data['vl_wop']) && $data['vl_wop'] !== '' ? floatval($data['vl_wop']) : null,
                'sl_earned' => isset($data['sl_earned']) && $data['sl_earned'] !== '' ? floatval($data['sl_earned']) : null,
                'sl_used' => isset($data['sl_used']) && $data['sl_used'] !== '' ? floatval($data['sl_used']) : null,
                'sl_wop' => isset($data['sl_wop']) && $data['sl_wop'] !== '' ? floatval($data['sl_wop']) : null,
                'action_taken' => $data['action_taken'] ?? null,
                'encoded_by' => auth()->id(),
            ];

            if (isset($data['vl_balance']) && !in_array(trim($data['vl_balance']), ['', '-'])) {
                $updateData['vl_balance_after'] = floatval($data['vl_balance']);
            }
            if (isset($data['sl_balance']) && !in_array(trim($data['sl_balance']), ['', '-'])) {
                $updateData['sl_balance_after'] = floatval($data['sl_balance']);
            }

            if (!empty($data['id'])) {
                $trans = LeaveTransaction::find($data['id']);
                if ($trans && $trans->leave_card_id == $leaveCard->id) {
                    $trans->update($updateData);
                    $existingIds[] = $trans->id;
                }
            }
            else {
                $newTrans = LeaveTransaction::create($updateData);
                $existingIds[] = $newTrans->id;
            }
        }

        // Delete any transactions that were present before but no longer exist in the payload
        LeaveTransaction::where('leave_card_id', $leaveCard->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        // Optional: Do NOT recalculate to preserve the manual balances
        // If we want system calculations, we would iterate existingIds, but user wants manual override.
        $totalVLEarned = LeaveTransaction::where('leave_card_id', $leaveCard->id)->sum('vl_earned') + LeaveTransaction::where('leave_card_id', $leaveCard->id)->where('leaveType.code', 'VL')->where('transaction_type', 'earned')->sum('days');
        $totalVLUsed = LeaveTransaction::where('leave_card_id', $leaveCard->id)->sum('vl_used') + LeaveTransaction::where('leave_card_id', $leaveCard->id)->where('leaveType.code', 'VL')->where('transaction_type', 'used')->sum('days');

        $totalSLEarned = LeaveTransaction::where('leave_card_id', $leaveCard->id)->sum('sl_earned') + LeaveTransaction::where('leave_card_id', $leaveCard->id)->where('leaveType.code', 'SL')->where('transaction_type', 'earned')->sum('days');
        $totalSLUsed = LeaveTransaction::where('leave_card_id', $leaveCard->id)->sum('sl_used') + LeaveTransaction::where('leave_card_id', $leaveCard->id)->where('leaveType.code', 'SL')->where('transaction_type', 'used')->sum('days');

        $leaveCard->update([
            'vl_earned' => $totalVLEarned,
            'vl_used' => $totalVLUsed,
            'vl_balance' => $leaveCard->vl_beginning_balance + $totalVLEarned - $totalVLUsed,
            'sl_earned' => $totalSLEarned,
            'sl_used' => $totalSLUsed,
            'sl_balance' => $leaveCard->sl_beginning_balance + $totalSLEarned - $totalSLUsed,
        ]);

        AuditTrail::create([
            'user_id' => auth()->id(),
            'action' => 'UPDATE',
            'module' => 'Leave Cards',
            'description' => "Synced manual grid for {$employee->full_name} (Year {$year})",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true]);
    }
}
