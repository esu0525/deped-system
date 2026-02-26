<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Models\AuditTrail;
use App\Services\LeaveCardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    protected LeaveCardService $leaveCardService;

    public function __construct(LeaveCardService $leaveCardService)
    {
        $this->leaveCardService = $leaveCardService;
    }

    public function index(Request $request)
    {
        $query = Employee::with(['department', 'user']);

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where('full_name', 'like', $search)
                ->orWhere('employee_id', 'like', $search)
                ->orWhere('position', 'like', $search);
        }

        if ($request->department) {
            $query->where('department_id', $request->department);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employment_status) {
            $query->where('employment_status', $request->employment_status);
        }

        $employees = $query->orderBy('full_name')->paginate(15)->withQueryString();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.employee-list', compact('employees', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'employee')->whereDoesntHave('employee')->get();
        return view('employees.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees',
            'full_name' => 'required|string|max:255',
            'gender' => 'nullable|in:Male,Female',
            'position' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'employment_status' => 'nullable|in:Permanent,Temporary,Casual,Contractual,Job Order',
            'date_hired' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive,Resigned,Retired',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('profile_picture')) {
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        $employee = Employee::create($validated);

        // Auto-create leave card for current year
        $this->leaveCardService->getOrCreateLeaveCard($employee);

        AuditTrail::log('CREATE', 'Employee Management', "Created employee profile for {$employee->full_name} (ID: {$employee->employee_id})");

        return redirect()->route('employees.show', $employee)->with('success', 'Employee profile created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['department', 'user', 'leaveCards', 'leaveApplications.leaveType', 'latestAiLog']);
        $currentLeaveCard = $employee->currentLeaveCard;
        return view('employees.show', compact('employee', 'currentLeaveCard'));
    }

    public function edit(Employee $employee)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'employee')
            ->where(function ($q) use ($employee) {
            $q->whereDoesntHave('employee')->orWhereHas('employee', fn($q2) => $q2->where('id', $employee->id));
        })->get();
        return view('employees.edit', compact('employee', 'departments', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'gender' => 'nullable|in:Male,Female',
            'position' => 'nullable|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'employment_status' => 'nullable|in:Permanent,Temporary,Casual,Contractual,Job Order',
            'date_hired' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive,Resigned,Retired',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $old = $employee->toArray();

        if ($request->hasFile('profile_picture')) {
            if ($employee->profile_picture) {
                Storage::disk('public')->delete($employee->profile_picture);
            }
            $validated['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        $employee->update($validated);

        AuditTrail::log('UPDATE', 'Employee Management', "Updated employee profile for {$employee->full_name}", $old, $employee->toArray());

        return redirect()->route('employees.show', $employee)->with('success', 'Employee profile updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        if ($employee->profile_picture) {
            Storage::disk('public')->delete($employee->profile_picture);
        }
        $employee->delete();
        AuditTrail::log('DELETE', 'Employee Management', "Deleted employee profile for {$name}");
        return redirect()->route('employees.index')->with('success', "Employee {$name} deleted successfully.");
    }
}
