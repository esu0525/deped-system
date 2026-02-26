<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveType;
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

    public function __construct(LeaveCardService $leaveCardService, MailService $mailService)
    {
        $this->leaveCardService = $leaveCardService;
        $this->mailService = $mailService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = LeaveApplication::with(['employee.department', 'leaveType', 'approver']);

        // Employees only see their own
        if ($user->isEmployee() && $user->employee) {
            $query->where('employee_id', $user->employee->id);
        }

        if ($request->search) {
            $query->whereHas('employee', fn($q) => $q->where('full_name', 'like', "%{$request->search}%")
            ->orWhere('employee_id', 'like', "%{$request->search}%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
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
        $leaveTypes = LeaveType::where('is_active', true)->get();

        return view('leave-applications.index', compact('applications', 'leaveTypes'));
    }

    public function create()
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $employees = $user->canManageEmployees()
            ?Employee::where('status', 'Active')->orderBy('full_name')->get()
            : collect([$user->employee])->filter();

        return view('leave-applications.create', compact('leaveTypes', 'employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'reason' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Calculate working days
        $from = Carbon::parse($request->date_from);
        $to = Carbon::parse($request->date_to);
        $numDays = $this->leaveCardService->calculateWorkingDays($from, $to);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('leave-attachments', 'public');
        }

        $application = LeaveApplication::create(array_merge($validated, [
            'num_days' => $numDays,
            'status' => 'Pending',
            'encoded_by' => auth()->id(),
        ]));

        AuditTrail::log('CREATE', 'Leave Application', "Filed leave application #{$application->application_no} for employee ID {$application->employee_id}");

        return redirect()->route('leave-applications.show', $application)->with('success', 'Leave application submitted successfully.');
    }

    public function show(LeaveApplication $leaveApplication)
    {
        $leaveApplication->load(['employee.department', 'leaveType', 'approver', 'encoder']);
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

        // Deduct credits
        $deducted = $this->leaveCardService->deductLeaveCredits($leaveApplication);

        if (!$deducted) {
            return back()->with('error', 'Insufficient leave credits. Cannot approve this application.');
        }

        $leaveApplication->update([
            'status' => 'Approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
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
