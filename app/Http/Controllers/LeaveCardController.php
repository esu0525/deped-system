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
        $query = Employee::with(['department', 'leaveCards', 'user'])->where('status', 'Active');

        // Filter by user assignment (National/City)
        $user = auth()->user();
        if (!in_array($user->role, ['admin', 'super_admin']) && $user->assign && strtolower($user->assign) !== 'all') {
            $query->where('category', $user->assign);
        }
        if ($user && !in_array($user->role, ['admin', 'super_admin']) && !empty($user->access)) {
            $accessList = explode(', ', $user->access);
            $query->where(function ($sq) use ($accessList) {
                foreach ($accessList as $access) {
                    preg_match('/^(.*?)(?:\s+\((National|City)\))?$/', trim($access), $matches);
                    $pos = trim($matches[1] ?? '');
                    $cat = $matches[2] ?? null;
                    
                    $sq->orWhere(function($subQ) use ($pos, $cat) {
                        $subQ->where('position', 'like', $pos . '%');
                        if ($cat) {
                            $subQ->where('category', $cat);
                        }
                    });
                }
            });
        }

        // Summary Stats (Based on RBAC universe, without search/sort filters)
        $statsQuery = clone $query;
        $stats = [
            'total' => (clone $statsQuery)->count(),
            'national' => (clone $statsQuery)->where('category', 'National')->count(),
            'city' => (clone $statsQuery)->where('category', 'City')->count(),
        ];

        if ($request->has('search') && $request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('employee_id', 'like', $search);
            });
        }

        // Sorting & Auto-Filtering
        $sort = $request->input('sort', 'name');
        if ($sort === 'National') {
            $query->where('category', 'National')->orderBy('full_name');
        } elseif ($sort === 'City') {
            $query->where('category', 'City')->orderBy('full_name');
        } else {
            $query->orderBy('full_name');
        }

        $employees = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('leave-cards.partials.leave-card-rows', compact('employees'));
        }

        return view('leave-cards.index', compact('employees', 'stats'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee)
    {
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
        $tab = $request->input('tab', 'form6');

        $query = $employee->leaveTransactions()
            ->with(['leaveType', 'encoder'])
            ->orderBy('id', 'asc');

        if ($leaveCard) {
            $query->where('leave_card_id', $leaveCard->id);
        } else {
            return view('leave-cards.show', compact('employee', 'leaveCard', 'years', 'year', 'tab'))->with('transactions', collect());
        }

        // Wellness Balance: 5 days max per year
        $wellnessUsed = \App\Models\LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'Approved')
            ->whereYear('date_filed', $year)
            ->whereHas('leaveType', function ($q) {
                $q->where('name', 'like', '%Wellness%');
            })
            ->sum('num_days');
        $wellnessBalance = max(0, 5 - $wellnessUsed);

        $transactions = $query->get();

        return view('leave-cards.show', compact('employee', 'leaveCard', 'years', 'year', 'transactions', 'tab', 'wellnessBalance', 'wellnessUsed'));
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

            $vlType = LeaveType::where('code', 'VL')->first();
            $monthEnd = now()->endOfMonth();
            if ($vlType) {
                LeaveTransaction::create([
                    'employee_id' => $employee->id,
                    'leave_card_id' => $leaveCard->id,
                    'leave_type_id' => $vlType->id,
                    'transaction_date' => $monthEnd,
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
                    'transaction_date' => $monthEnd,
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
                'action_taken' => $data['action_taken'] ?? null,
                'encoded_by' => auth()->id(),
                'text_color' => $data['text_color'] ?? null,
            ];

            // Use local vars for checks to handle empty/string cases correctly
            $vlWText = trim($data['vl_wop'] ?? '');
            $slWText = trim($data['sl_wop'] ?? '');

            // Try to extract number and reason (e.g., "1 CREDITS EXHAUSTED" or "= 111.5075")
            preg_match('/^=?\s*([\d.]+)\s*(.*)$/', $vlWText, $vlMatches);
            $vlE = isset($data['vl_earned']) && $data['vl_earned'] !== '' ? floatval($data['vl_earned']) : 0;
            $vlU = isset($data['vl_used']) && $data['vl_used'] !== '' ? floatval($data['vl_used']) : 0;
            $vlW = isset($vlMatches[1]) ? floatval($vlMatches[1]) : 0;
            $vlWR = isset($vlMatches[2]) ? trim($vlMatches[2]) : (isset($data['vl_wop_reason']) ? $data['vl_wop_reason'] : null);
            // If the text was just "=", don't treat as 0 wop
            if (trim($vlWText) === '=') $vlW = 0;

            preg_match('/^=?\s*([\d.]+)\s*(.*)$/', $slWText, $slMatches);
            $slE = isset($data['sl_earned']) && $data['sl_earned'] !== '' ? floatval($data['sl_earned']) : 0;
            $slU = isset($data['sl_used']) && $data['sl_used'] !== '' ? floatval($data['sl_used']) : 0;
            $slW = isset($slMatches[1]) ? floatval($slMatches[1]) : 0;
            $slWR = isset($slMatches[2]) ? trim($slMatches[2]) : (isset($data['sl_wop_reason']) ? $data['sl_wop_reason'] : null);
            if (trim($slWText) === '=') $slW = 0;

            $updateData['vl_earned'] = $vlE ?: null;
            $updateData['vl_used'] = $vlU ?: null;
            $updateData['vl_wop'] = $vlW ?: null;
            $updateData['vl_wop_reason'] = $vlWR ?: null;
            $updateData['sl_earned'] = $slE ?: null;
            $updateData['sl_used'] = $slU ?: null;
            $updateData['sl_wop'] = $slW ?: null;
            $updateData['sl_wop_reason'] = $slWR ?: null;

            // Handle balances: explicitly save NULL if empty, hyphen OR if the column is NOT active in this row
            $vlBalStr = trim($data['vl_balance'] ?? '');
            if (in_array($vlBalStr, ['', '-'])) {
                $updateData['vl_balance_after'] = null;
            } else if ($vlE == 0 && $vlU == 0 && $vlW == 0 && strpos(strtoupper($data['date_text'] ?? ''), 'BAL') === false) {
                 // Auto-null if no VL activity on this row (and not a "Balance" row)
                $updateData['vl_balance_after'] = null;
            } else {
                $updateData['vl_balance_after'] = floatval($vlBalStr);
            }
            
            $slBalStr = trim($data['sl_balance'] ?? '');
            if (in_array($slBalStr, ['', '-'])) {
                $updateData['sl_balance_after'] = null;
            } else if ($slE == 0 && $slU == 0 && $slW == 0 && strpos(strtoupper($data['date_text'] ?? ''), 'BAL') === false) {
                // Auto-null if no SL activity on this row (and not a "Balance" row)
                $updateData['sl_balance_after'] = null;
            } else {
                $updateData['sl_balance_after'] = floatval($slBalStr);
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

        // Recalculate using the refined model logic
        $leaveCard->recalculate();

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
