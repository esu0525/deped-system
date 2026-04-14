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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        // Default filter: only show National and City (exclude HR/NTP accounts if any in employee table)
        // Adjusting this to be flexible
        $query->whereIn('category', ['National', 'City', 'employee']);
        
        // RBAC: Restricted Access Filter
        $user = auth()->user();
        
        // Filter by user assignment (National/City)
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

        if ($request->category) {
            $query->where('category', $request->category);
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

        if ($request->input('export') === 'true') {
            return $this->export($query->get());
        }

        $employees = $query->paginate(15)->withQueryString();

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
        $defaultCategory = $request->query('category', 'employee');
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('role', 'employee')->whereDoesntHave('employee')->get();

        if ($request->ajax()) {
            return view('employees.partials.create-modal', compact('defaultCategory', 'departments', 'users'));
        }
        return view('employees.create', compact('defaultCategory', 'departments', 'users'));
    }

    public function store(Request $request)
    {
        \Log::info('Store Attempt:', $request->all());

        try {
            $validated = $request->validate([
                'category' => 'required|in:employee,hrntp,National,City',
                'full_name' => 'required|string|max:255',
                'employee_id' => 'nullable|string', // Reference ID/Username
                'position' => 'nullable|string|max:255',
                'access' => 'nullable|string|max:255',
                'email' => 'required_if:category,hrntp|nullable|email',
                'password' => 'required_if:category,hrntp|nullable|string|min:4',
                // Employee specific fields
                'department_name' => 'nullable|string|max:255',
                'employment_status' => 'nullable|in:Regular,Contractual',
                'vl_balance' => 'nullable|numeric|min:0',
                'sl_balance' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            if (!empty($validated['email'])) {
                $hashedEmail = User::generateEmailHash($validated['email']);
                if (User::where('email_searchable', $hashedEmail)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation error',
                        'errors' => ['email' => ['The email has already been taken.']]
                    ], 422);
                }
            }

            return DB::transaction(function () use ($request, $validated) {
                if ($validated['category'] === 'hrntp') {
                    // --- 1. COORDINATOR PATH: Save ONLY to USERS Table ---
                    // No Employee::create() here to avoid dependency issues
                    $nameParts = explode(' ', $validated['full_name']);
                    $last = array_pop($nameParts);
                    $first = array_shift($nameParts);
                    $middle = implode(' ', $nameParts);

                    $user = User::create([
                        'first_name' => $first,
                        'middle_name' => $middle,
                        'last_name' => $last,
                        'email' => $validated['email'],
                        'email_searchable' => User::generateEmailHash($validated['email']),
                        'password' => Hash::make($validated['password']),
                        'role' => $validated['position'], // This maps to position in modal name
                        'access' => $validated['access'],
                        'is_active' => true,
                    ]);

                    AuditTrail::log('CREATE', 'System Users', "Created Coordinator User account: {$user->name}");

                    return response()->json([
                        'success' => true,
                        'message' => "Coordinator account saved successfully in Users database.",
                        'redirect' => route('employees.index', ['type' => 'hrntp'])
                    ]);
                } else {
                    // --- 2. EMPLOYEE PATH: Save to EMPLOYEES Table (and auto-generate account) ---
                    $departmentId = null;
                    if (!empty($validated['department_name'])) {
                        $dept = Department::firstOrCreate(['name' => $validated['department_name']]);
                        $departmentId = $dept->id;
                    }

                    $employee = Employee::create([
                        'employee_id' => $validated['employee_id'],
                        'full_name' => $validated['full_name'],
                        'position' => $validated['position'],
                        'category' => in_array($validated['category'], ['National', 'City']) ? $validated['category'] : 'National',
                        'employment_status' => $validated['employment_status'],
                        'department_id' => $departmentId,
                        'status' => 'Active',
                    ]);
                    
                    // Set balances and auto-account
                    $leaveCard = $this->leaveCardService->getOrCreateLeaveCard($employee);
                    $leaveCard->update([
                        'vl_beginning_balance' => $validated['vl_balance'] ?? 0,
                        'sl_beginning_balance' => $validated['sl_balance'] ?? 0,
                        'vl_balance' => $validated['vl_balance'] ?? 0,
                        'sl_balance' => $validated['sl_balance'] ?? 0,
                    ]);

                    $accountResult = AccountGeneratorService::createAccountForEmployee($employee);
                    $generatedPassword = $accountResult['password'] ?? 'Check system settings';
                    $generatedEmail = $accountResult['user']->email;

                    return response()->json([
                        'success' => true,
                        'message' => "Employee profile created. Account: {$generatedEmail} | Pass: {$generatedPassword}",
                        'redirect' => route('employees.index', ['type' => 'employee'])
                    ]);
                }
            });
        } catch (\Exception $e) {
            \Log::error('Coordinator/Employee Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Saving Failed: ' . $e->getMessage()
            ], 500);
        }
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
            'category' => 'nullable|in:National,City',
            'employee_id' => 'required|string|unique:employees,employee_id,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'access' => 'nullable|string|max:255',
            'department_name' => 'nullable|string|max:255',
            'employment_status' => 'nullable|in:Regular,Contractual',
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

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Employee updated successfully.']);
        }

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
     * Remove the specified user from storage.
     */
    public function destroyUser(User $user)
    {
        try {
            $name = $user->name;
            $user->delete();
            
            AuditTrail::log('DELETE', 'System Users', "Deleted user account: {$name}");
            
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
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
