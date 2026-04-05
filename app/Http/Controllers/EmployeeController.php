<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Models\AuditTrail;
use App\Services\LeaveCardService;
use App\Services\AccountGeneratorService;
use Illuminate\Http\Request;
use App\Exports\EmployeeExport;
use Maatwebsite\Excel\Facades\Excel;
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
        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = Employee::with(['department', 'user']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhereHas('department', function ($dq) use ($search) {
                    $dq->where('name', 'like', "%{$search}%");
                }
                );
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->employment_status) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->input('export') === 'true') {
            return $this->export($query->get());
        }

        $employees = $query->orderBy('full_name')->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return view('employees.partials.employee-table-rows', compact('employees'));
        }

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('employees.employee-list', compact('employees', 'departments'));
    }

    public function export($employees)
    {
        $filename = "employees_export_" . date('Y-m-d') . ".xlsx";
        return Excel::download(new EmployeeExport($employees), $filename);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return view('employees.partials.create-modal');
        }
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'employee')->whereDoesntHave('employee')->get();
        return view('employees.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'nullable|string|unique:employees',
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department_name' => 'nullable|string|max:255',
            'employment_status' => 'nullable|in:Permanent,Temporary,Casual,Contractual,Job Order',
            'date_hired' => 'nullable|date',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive,Resigned,Retired',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'nullable|exists:users,id',
            'vl_balance' => 'nullable|numeric|min:0',
            'sl_balance' => 'nullable|numeric|min:0',
        ]);

        // Dynamically find or create the department by name
        $departmentId = null;
        if (!empty($validated['department_name'])) {
            $dept = Department::firstOrCreate(['name' => $validated['department_name']]);
            $departmentId = $dept->id;
        }
        $validated['department_id'] = $departmentId;
        unset($validated['department_name']);

        // Profile picture logic removed

        $employee = Employee::create($validated);

        // Auto-create leave card for current year and set initial balances
        $leaveCard = $this->leaveCardService->getOrCreateLeaveCard($employee);

        $vlBalance = floatval($request->input('vl_balance', 0));
        $slBalance = floatval($request->input('sl_balance', 0));

        if ($vlBalance > 0 || $slBalance > 0) {
            $leaveCard->update([
                'vl_beginning_balance' => $vlBalance,
                'vl_balance' => $vlBalance,
                'sl_beginning_balance' => $slBalance,
                'sl_balance' => $slBalance,
            ]);
        }

        // Auto-create user account for the employee
        $accountResult = AccountGeneratorService::createAccountForEmployee($employee);
        $generatedPassword = $accountResult['password'];
        $generatedEmail = $accountResult['user']->email;

        AuditTrail::log('CREATE', 'Employee Management', "Created employee profile for {$employee->full_name} (ID: {$employee->employee_id}) with account {$generatedEmail}");

        return redirect()->route('employees.index')->with('success',
            "Employee profile created successfully. Auto-generated account — Email: {$generatedEmail} | Password: {$generatedPassword}"
        );
    }

    public function show(Request $request, Employee $employee)
    {
        $employee->load(['department', 'user', 'leaveCards', 'leaveApplications.leaveType']);
        $currentLeaveCard = $employee->currentLeaveCard;

        if ($request->ajax()) {
            return view('employees.partials.show-modal', compact('employee', 'currentLeaveCard'));
        }

        return view('employees.show', compact('employee', 'currentLeaveCard'));
    }

    public function edit(Request $request, Employee $employee)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'employee')
            ->where(function ($q) use ($employee) {
            $q->whereDoesntHave('employee')->orWhereHas('employee', fn($q2) => $q2->where('id', $employee->id));
        })->get();

        if ($request->ajax()) {
            return view('employees.partials.edit-modal', compact('employee', 'departments', 'users'));
        }

        return view('employees.edit', compact('employee', 'departments', 'users'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:employees,employee_id,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'department_name' => 'nullable|string|max:255',
            'employment_status' => 'nullable|in:Permanent,Temporary,Casual,Contractual,Job Order',
            'date_hired' => 'nullable|date',
            'contact_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive,Resigned,Retired',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'nullable|exists:users,id',
            'vl_balance' => 'nullable|numeric|min:0',
            'sl_balance' => 'nullable|numeric|min:0',
        ]);

        // Dynamically find or create the department by name
        $departmentId = null;
        if (!empty($validated['department_name'])) {
            $dept = Department::firstOrCreate(['name' => $validated['department_name']]);
            $departmentId = $dept->id;
        }
        $validated['department_id'] = $departmentId;
        unset($validated['department_name']);

        $old = $employee->toArray();

        // Profile picture logic removed

        $employee->update($validated);

        // Update leave card balances
        $leaveCard = $this->leaveCardService->getOrCreateLeaveCard($employee);
        $vlBalance = floatval($request->input('vl_balance', 0));
        $slBalance = floatval($request->input('sl_balance', 0));

        $leaveCard->update([
            'vl_beginning_balance' => $vlBalance,
            'vl_balance' => $vlBalance,
            'sl_beginning_balance' => $slBalance,
            'sl_balance' => $slBalance,
        ]);

        AuditTrail::log('UPDATE', 'Employee Management', "Updated employee profile for {$employee->full_name}", $old, $employee->toArray());

        return redirect()->route('employees.index')->with('success', 'Employee profile updated successfully.');
    }

    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        $employee->delete();
        AuditTrail::log('DELETE', 'Employee Management', "Deleted employee profile for {$name}");
        return redirect()->route('employees.index')->with('success', "Employee {$name} deleted successfully.");
    }

    /**
     * Create a user account for an employee (auto-generate credentials).
     */
    public function createAccount(Request $request, Employee $employee)
    {
        if ($employee->user_id) {
            return back()->with('error', 'This employee already has an account.');
        }

        $accountResult = AccountGeneratorService::createAccountForEmployee($employee);

        AuditTrail::log(
            'CREATE',
            'Employee Management',
            "Created user account for {$employee->full_name} ({$accountResult['user']->email})"
        );

        return redirect()->route('employees.index')
            ->with('success',
            "Login account created for {$employee->full_name} — Email: {$accountResult['user']->email} | Password: {$accountResult['password']}"
        );
    }
}
