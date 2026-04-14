<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\LeaveCard;
use App\Models\Employee;
use App\Models\AuditTrail;
use App\Services\LeaveCardService;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LeaveApplicationController extends Controller
{
    protected LeaveCardService $leaveCardService;
    protected MailService $mailService;

    public function __construct(
        LeaveCardService $leaveCardService,
        MailService $mailService
        )
    {
        $this->leaveCardService = $leaveCardService;
        $this->mailService = $mailService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = LeaveApplication::with(['employee.department', 'employee.user', 'leaveType', 'approver']);
        
        // Filter by user assignment (National/City)
        if (!in_array($user->role, ['admin', 'super_admin']) && $user->assign && strtolower($user->assign) !== 'all') {
            $query->whereHas('employee', function($q) use ($user) {
                $q->where('category', $user->assign);
            });
        }

        // Employees only see their own
        if ($user->role === 'employee' && $user->employee) {
            $query->where('employee_id', $user->employee->id);
        } elseif ($user->role === 'ojt') {
            // OJTs only see what they encoded themselves
            $query->where('encoded_by', $user->id);
        } elseif (!in_array($user->role, ['admin', 'super_admin']) && !empty($user->access)) {
            $accessList = explode(', ', $user->access);
            $query->whereHas('employee', function ($q) use ($accessList) {
                $q->where(function ($sq) use ($accessList) {
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
            });
        }

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        } elseif (!$request->has('status')) {
            $query->where('status', 'Pending');
        }

        if ($request->leave_type) {
            $query->where('leave_type_id', $request->leave_type);
        }

        if ($request->date_from) {
            $query->where('date_from', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date_to', '<=', $request->date_to);
        }

        $applications = $query->latest()->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('leave-applications.partials.table-rows', compact('applications'));
        }

        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        // Stats for summary cards
        $statsQuery = LeaveApplication::query();
        if ($user->isEmployee() && $user->employee) {
            $statsQuery->where('employee_id', $user->employee->id);
        } elseif (!in_array($user->role, ['admin', 'super_admin']) && !empty($user->access)) {
            $accessList = explode(', ', $user->access);
            $statsQuery->whereHas('employee', function ($q) use ($accessList) {
                $q->where(function ($sq) use ($accessList) {
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
            });
        }

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'pending' => (clone $statsQuery)->where('status', 'Pending')->count(),
            'approved' => (clone $statsQuery)->where('status', 'Approved')->count(),
            'rejected' => (clone $statsQuery)->where('status', 'Rejected')->count(),
        ];

        return view('leave-applications.index', compact('applications', 'leaveTypes', 'stats'));
    }

    public function create()
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $employeesQuery = Employee::where('status', 'Active');

        // Filter by user assignment (National/City)
        if (!in_array($user->role, ['admin', 'super_admin']) && $user->assign && strtolower($user->assign) !== 'all') {
            $employeesQuery->where('category', $user->assign);
        }

        if (!in_array($user->role, ['admin', 'super_admin']) && !empty($user->access)) {
            $accessList = explode(', ', $user->access);
            $employeesQuery->where(function ($sq) use ($accessList) {
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
        $employees = $user->canManageEmployees()
            ? $employeesQuery->orderBy('full_name')->get()
            : collect([$user->employee])->filter();

        return view('leave-applications.create', compact('leaveTypes', 'employees'));
    }

    /**
     * API: Get employee leave balance for AJAX.
     */
    public function getEmployeeBalance(Employee $employee)
    {
        $year = now()->year;
        $leaveCard = LeaveCard::where('employee_id', $employee->id)
            ->where('year', $year)
            ->first();

        if (!$leaveCard) {
            return response()->json([
                'employee' => [
                    'full_name' => $employee->full_name,
                    'employee_id' => $employee->employee_id,
                    'position' => $employee->position,
                    'department' => $employee->department->name ?? 'N/A',
                ],
                'year' => $year,
                'vl_total_earned' => 0,
                'vl_balance' => 0,
                'sl_total_earned' => 0,
                'sl_balance' => 0,
                'has_leave_card' => false,
            ]);
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

        return response()->json([
            'employee' => [
                'full_name' => $employee->full_name,
                'employee_id' => $employee->employee_id,
                'position' => $employee->position,
                'department' => $employee->department->name ?? 'N/A',
            ],
            'year' => $year,
            'vl_total_earned' => floatval($leaveCard->vl_beginning_balance) + floatval($leaveCard->vl_earned),
            'vl_balance' => floatval($leaveCard->vl_balance),
            'sl_total_earned' => floatval($leaveCard->sl_beginning_balance) + floatval($leaveCard->sl_earned),
            'sl_balance' => floatval($leaveCard->sl_balance),
            'wellness_balance' => floatval($wellnessBalance),
            'wellness_used' => floatval($wellnessUsed),
            'has_leave_card' => true,
        ]);
    }

    /**
     * API: Get employee active CTO balances.
     */
    public function getEmployeeCtoBalances(Employee $employee)
    {
        return response()->json($employee->ctoBalances());
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'entries' => 'required|array|min:1',
            'entries.*.leave_type_name' => 'required|string|max:255',
            'entries.*.inclusive_dates' => 'nullable|string|max:255',
            'entries.*.num_days' => 'required|numeric|min:0',
            'entries.*.cto_earned_days' => 'nullable|numeric|min:0|max:999',
            'reason' => 'nullable|string|max:1000',
            'commutation' => 'nullable|string',
        ]);

        $entries = $request->input('entries');
        $totalDays = 0;

        // Process entries and find/create leave types
        $processedEntries = [];
        foreach ($entries as $index => $entry) {
            $name = trim($entry['leave_type_name']);
            $leaveType = LeaveType::where('name', $name)->first();
            
            if (!$leaveType) {
                // Create new leave type if not exists
                $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
                // Ensure code is unique-ish
                if (LeaveType::where('code', $code)->exists()) {
                    $code .= rand(1, 9);
                }
                
                $leaveType = LeaveType::create([
                    'name' => $name,
                    'code' => $code,
                    'is_active' => true,
                    'is_earnable' => false,
                ]);
            }
            
            $entry['leave_type_id'] = $leaveType->id;

            // ── Wellness Leave: enforce max 5 earned credits ──
            if (stripos($name, 'wellness') !== false) {
                $earnedCredits = isset($entry['cto_earned_days']) && $entry['cto_earned_days'] !== ''
                    ? floatval($entry['cto_earned_days'])
                    : 5;
                // Cap at 5 (maximum allowed); default to 5 if zero/unset
                $entry['cto_earned_days'] = min(max($earnedCredits, 0), 5) ?: 5;

                // Also reject if no. of days exceeds 5
                if (floatval($entry['num_days']) > 5) {
                    return back()->withInput()->withErrors([
                        "entries.{$index}.num_days" => 'Wellness Leave cannot exceed 5 days.',
                    ]);
                }
            }

            $processedEntries[$index] = $entry;
            $totalDays += floatval($entry['num_days']);
        }
 
        // Use the first entry's leave type as the main type
        $firstEntry = $processedEntries[array_key_first($processedEntries)];

        $application = LeaveApplication::create([
            'employee_id' => $request->employee_id,
            'leave_type_id' => $firstEntry['leave_type_id'],
            'other_leave_type' => $firstEntry['other_type'] ?? null,
            'date_filed' => now(),
            'date_from' => now(),
            'date_to' => now(),
            'num_days' => $totalDays,
            'reason' => $request->reason,
            // 6.B Details of Leave
            'leave_location' => $request->leave_location,
            'leave_location_detail' => $request->leave_location_detail,
            'sick_leave_type' => $request->sick_leave_type,
            'sick_leave_detail' => $request->sick_leave_detail,
            'women_leave_detail' => $request->women_leave_detail,
            'study_leave_type' => $request->study_leave_type,
            'other_leave_detail' => $request->other_leave_detail,
            // 6.D Commutation
            'commutation' => $request->commutation,
            'status' => 'Pending',
            'encoded_by' => auth()->id(),
        ]);

        // Save each entry as a detail record
        foreach ($processedEntries as $entry) {
            $application->details()->create([
                'leave_type_id' => $entry['leave_type_id'],
                'inclusive_dates' => $entry['inclusive_dates'] ?? '',
                'other_type' => $entry['other_type'] ?? null,
                'cto_title' => $entry['cto_title'] ?? null,
                'date_from' => now(),
                'date_to' => now(),
                'num_days' => $entry['num_days'],
                'cto_earned_days' => $entry['cto_earned_days'] ?? null,
                'is_with_pay' => ($entry['is_with_pay'] ?? '1') === '1',
                'lwop_reason' => $entry['lwop_reason'] ?? null,
            ]);
        }

        AuditTrail::log('CREATE', 'Leave Application', "Filed leave application #{$application->application_no} for employee ID {$application->employee_id} ({$totalDays} days, " . count($entries) . " entries)");

        return redirect()->route('leave-applications.index')->with('success', 'Leave application submitted successfully.');
    }

    public function show(LeaveApplication $leaveApplication, Request $request)
    {
        $leaveApplication->load(['employee.department', 'employee.user', 'leaveType', 'approver', 'encoder', 'details.leaveType']);

        if ($request->ajax()) {
            return view('leave-applications.partials.show-modal', compact('leaveApplication'));
        }

        return view('leave-applications.show', compact('leaveApplication'));
    }

    public function edit(LeaveApplication $leaveApplication)
    {
        if ($leaveApplication->status !== 'Pending') {
            return back()->with('error', 'Only pending applications can be edited.');
        }

        $leaveTypes = LeaveType::where('is_active', true)->get();
        $employees = Employee::where('status', 'Active')->orderBy('full_name')->get();
        return view('leave-applications.edit', compact('leaveApplication', 'leaveTypes', 'employees'));
    }

    public function update(Request $request, LeaveApplication $leaveApplication)
    {
        if ($leaveApplication->status !== 'Pending') {
            return back()->with('error', 'Only pending applications can be edited.');
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'reason' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $from = Carbon::parse($request->date_from);
        $to = Carbon::parse($request->date_to);
        $numDays = $this->leaveCardService->calculateWorkingDays($from, $to);

        if ($request->hasFile('attachment')) {
            if ($leaveApplication->attachment) {
                Storage::disk('public')->delete($leaveApplication->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $leaveApplication->update(array_merge($validated, ['num_days' => $numDays]));

        AuditTrail::log('UPDATE', 'Leave Application', "Updated leave application #{$leaveApplication->application_no}");

        return redirect()->route('leave-applications.show', $leaveApplication)->with('success', 'Leave application updated.');
    }

    public function approve(Request $request, LeaveApplication $leaveApplication)
    {
        if (!auth()->user()->canApproveLeave()) {
            abort(403);
        }

        if ($leaveApplication->status !== 'Pending') {
            return back()->with('error', 'Application is not in pending status.');
        }

        $request->validate(['remarks' => 'nullable|string|max:500']);

        // Get leave card before deduction for certification
        $employee = $leaveApplication->employee;
        $leaveCardBefore = $this->leaveCardService->getOrCreateLeaveCard($employee);

        // Capture balance BEFORE deduction
        $vlCurrentBalance = floatval($leaveCardBefore->vl_balance);
        $slCurrentBalance = floatval($leaveCardBefore->sl_balance);

        // Deduct credits
        $deducted = $this->leaveCardService->deductLeaveCredits($leaveApplication, $request->remarks);

        if (!$deducted) {
            return back()->with('error', 'Insufficient leave credits. Cannot approve this application.');
        }

        // Get leave card after deduction
        $leaveCardAfter = $leaveCardBefore->fresh();

        // Calculate VL/SL days for this application from actual details
        $vlDays = 0;
        $slDays = 0;
        $isMonetization = false;

        foreach ($leaveApplication->details as $detail) {
            $type = $detail->leaveType;
            if (stripos($type->name, '50% Monetization') !== false) {
                $isMonetization = true;
                break;
            }
        }

        if ($isMonetization) {
            $vlDays = $vlCurrentBalance / 2;
            $slDays = $slCurrentBalance / 2;
        } else {
            foreach ($leaveApplication->details as $detail) {
                $type = $detail->leaveType;
                if ($type->code === 'VL' || $type->code === 'FL')
                    $vlDays += floatval($detail->num_days);
                elseif ($type->code === 'SL')
                    $slDays += floatval($detail->num_days);
            }
        }

        $leaveApplication->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
            // 7.A Certification of Leave Credits
            'cert_vl_total_earned' => $vlCurrentBalance,
            'cert_vl_less_this' => $vlDays,
            'cert_vl_balance' => floatval($leaveCardAfter->vl_balance),
            'cert_sl_total_earned' => $slCurrentBalance,
            'cert_sl_less_this' => $slDays,
            'cert_sl_balance' => floatval($leaveCardAfter->sl_balance),
        ]);

        // Send email notification
        if ($leaveApplication->employee->email) {
            $this->mailService->sendLeaveNotification(
                $leaveApplication->employee->email,
                $leaveApplication->employee->full_name,
                'Approved',
                $leaveApplication->leaveType->name,
                $leaveApplication->date_from->format('M d, Y'),
                $leaveApplication->date_to->format('M d, Y'),
                $request->remarks ?? ''
            );
        }

        // Sync to external system
        \App\Services\SyncService::syncLeaveApplication($leaveApplication);

        AuditTrail::log('APPROVE', 'Leave Application', "Approved leave application #{$leaveApplication->application_no}");

        return back()->with('success', 'Leave application approved and credits deducted.');
    }

    public function reject(Request $request, LeaveApplication $leaveApplication)
    {
        if (!auth()->user()->canApproveLeave()) {
            abort(403);
        }

        if ($leaveApplication->status !== 'Pending') {
            return back()->with('error', 'Application is not in pending status.');
        }

        $request->validate(['remarks' => 'required|string|max:500']);

        $leaveApplication->update([
            'status' => 'Rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);

        if ($leaveApplication->employee->email) {
            $this->mailService->sendLeaveNotification(
                $leaveApplication->employee->email,
                $leaveApplication->employee->full_name,
                'Rejected',
                $leaveApplication->leaveType->name,
                $leaveApplication->date_from->format('M d, Y'),
                $leaveApplication->date_to->format('M d, Y'),
                $request->remarks
            );
        }

        AuditTrail::log('REJECT', 'Leave Application', "Rejected leave application #{$leaveApplication->application_no}: {$request->remarks}");

        return back()->with('success', 'Leave application rejected.');
    }

    public function bulkApprove(Request $request)
    {
        if (!auth()->user()->canApproveLeave()) {
            abort(403);
        }

        $request->validate([
            'application_ids' => 'required|array',
            'application_ids.*' => 'exists:leave_applications,id'
        ]);

        $applications = LeaveApplication::with(['employee', 'employee.user', 'details.leaveType'])
            ->whereIn('id', $request->application_ids)
            ->where('status', 'Pending')
            ->get();

        $successCount = 0;
        $failedCount = 0;

        $reasons = [];
        /** @var \App\Models\LeaveApplication $leaveApplication */
        foreach ($applications as $leaveApplication) {
            try {
                // Duplicate standard approve logic to maintain proper certification records
                $employee = $leaveApplication->employee;
                $leaveCardBefore = $this->leaveCardService->getOrCreateLeaveCard($employee);

                $vlCurrentBalance = floatval($leaveCardBefore->vl_balance);
                $slCurrentBalance = floatval($leaveCardBefore->sl_balance);

                if ($this->leaveCardService->deductLeaveCredits($leaveApplication)) {
                    $leaveCardAfter = $leaveCardBefore->fresh();

                    $vlDays = 0;
                    $slDays = 0;
                    foreach ($leaveApplication->details as $detail) {
                        $type = $detail->leaveType;
                        if (!$type) continue;
                        
                        if ($type->code === 'VL' || $type->code === 'FL')
                            $vlDays += floatval($detail->num_days);
                        elseif ($type->code === 'SL')
                            $slDays += floatval($detail->num_days);
                    }

                    $leaveApplication->update([
                        'status' => 'Approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'remarks' => 'Bulk Approved',
                        'cert_vl_total_earned' => $vlCurrentBalance,
                        'cert_vl_less_this' => $vlDays,
                        'cert_vl_balance' => floatval($leaveCardAfter->vl_balance),
                        'cert_sl_total_earned' => $slCurrentBalance,
                        'cert_sl_less_this' => $slDays,
                        'cert_sl_balance' => floatval($leaveCardAfter->sl_balance),
                    ]);

                    if ($employee->email) {
                        $this->mailService->sendLeaveNotification(
                            $employee->email,
                            $employee->full_name,
                            'Approved',
                            $leaveApplication->leaveType->name,
                            $leaveApplication->date_from->format('M d, Y'),
                            $leaveApplication->date_to->format('M d, Y'),
                            'Bulk Approved'
                        );
                    }
                    
                    // Sync to external system
                    \App\Services\SyncService::syncLeaveApplication($leaveApplication);
                    
                    AuditTrail::log('APPROVE', 'Leave Application', "Bulk approved leave application #{$leaveApplication->application_no}");
                    $successCount++;
                } else {
                    $failedCount++;
                    $reasons[] = "{$leaveApplication->application_no} (Insufficient Balance)";
                }
            } catch (\Exception $e) {
                $failedCount++;
                $reasons[] = "{$leaveApplication->application_no} (Error: " . $e->getMessage() . ")";
            }
        }

        $message = "Successfully approved {$successCount} applications.";
        if ($failedCount > 0) {
            $message .= " Failed: " . implode(', ', $reasons);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('leave-applications.index', ['status' => 'Pending'])->with('success', $message);
    }

    public function destroy(LeaveApplication $leaveApplication)
    {
        if (!auth()->user()->canDeleteFinalized() && $leaveApplication->status !== 'Pending') {
            return back()->with('error', 'You cannot delete a finalized application.');
        }

        if ($leaveApplication->status === 'Approved') {
            $this->leaveCardService->restoreLeaveCredits($leaveApplication);
        }

        if ($leaveApplication->attachment) {
            Storage::disk('public')->delete($leaveApplication->attachment);
        }

        $no = $leaveApplication->application_no;
        $leaveApplication->delete();

        AuditTrail::log('DELETE', 'Leave Application', "Deleted leave application #{$no}");

        return redirect()->route('leave-applications.index')->with('success', 'Leave application deleted.');
    }
}
