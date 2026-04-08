<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditTrail;
use App\Models\Employee;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function update(Request $request, User $user)
    {
        if (!in_array(auth()->user()->role, ['admin', 'super_admin']) && auth()->id() !== $user->id && $user->created_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'email' => 'required|email',
            'password' => 'nullable|string|min:4',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $rules['role'] = 'required|in:admin,coordinator,ojt';
            $rules['assign'] = 'nullable|string|in:National,City';
            $rules['access'] = 'nullable|array';
            $rules['access.*'] = 'string|max:255';
        }

        $validated = $request->validate($rules);

        if (!empty($validated['email'])) {
            $hashedEmail = hash_hmac('sha256', strtolower($validated['email']), config('app.key'));
            $existingUser = User::where('email_searchable', $hashedEmail)->where('id', '!=', $user->id)->first();
            if ($existingUser) {
                return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => ['email' => ['The email has already been taken.']]], 422);
            }
        }

        try {
            $data = [
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'suffix' => $request->suffix,
                'email' => $validated['email'],
                'email_searchable' => hash_hmac('sha256', strtolower($validated['email']), config('app.key')),
            ];

            if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
                $data['role'] = $validated['role'];
                $data['assign'] = $request->assign;
                $data['access'] = !empty($validated['access']) ? implode(', ', $validated['access']) : null;
            }

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            $user->update($data);

            AuditTrail::log('UPDATE', 'User Management', "Updated account details for: {$user->name}");

            return response()->json(['success' => true, 'message' => 'Account updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    public function show(User $user)
    {
        if (!in_array(auth()->user()->role, ['admin', 'super_admin']) && auth()->id() !== $user->id && $user->created_by !== auth()->id()) {
            abort(403, 'Unauthorized access to account profile.');
        }

        $dates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $dates->push(\Carbon\Carbon::now()->subDays($i)->format('Y-m-d'));
        }

        $logins = \App\Models\AuditTrail::where('user_id', $user->id)
            ->where('action', 'LOGIN')
            ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(6)->startOfDay())
            ->get()
            ->groupBy(function($val) {
                return \Carbon\Carbon::parse($val->created_at)->format('Y-m-d');
            });

        $loginLabels = [];
        $loginCounts = [];

        foreach ($dates as $date) {
            $loginLabels[] = \Carbon\Carbon::parse($date)->format('M d');
            $loginCounts[] = isset($logins[$date]) ? $logins[$date]->count() : 0;
        }

        $loginData = [
            'labels' => $loginLabels,
            'data' => $loginCounts
        ];

        // Audit logs for this specific user
        $activities = \App\Models\AuditTrail::where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();

        $rawPositions = Employee::select('position')->whereNotNull('position')->distinct()->pluck('position');
        $positions = $rawPositions->map(function($p) {
            return trim(preg_replace('/\s*\(.*?\)\s*$/', '', $p));
        })->unique()->toArray();

        $defaultPositions = [
            'Accountant', 'Administrative Aide', 'Administrative Assistant', 'Administrative Officer',
            'Assistant School Principal', 'Assistant Schools Division Superintendent', 'Attorney',
            'Chief Education Supervisor', 'Clerk', 'Communications Equipment Operator', 'Cook',
            'Dental Aide', 'Dentist', 'Driver', 'Education Program Specialist', 'Education Program Supervisor',
            'Engineer', 'Guidance Coordinator', 'Guidance Counselor', 'Head Teacher',
            'Health Education and Promotion Officer', 'Houseparent', 'Information Technology Officer',
            'Legal Assistant', 'Librarian', 'Medical Officer', 'Nurse', 'Nurse Maid', 'Planning Officer',
            'Project Development Officer', 'Public Schools District Supervisor', 'Registrar',
            'School Librarian', 'School Principal', 'Schools Division Superintendent', 'Security Guard',
            'Senior Education Program Specialist', 'Supply Officer', 'Watchman'
        ];
        $rolesList = collect(array_merge($defaultPositions, $positions))->unique()->sort()->values()->toArray();

        return view('users.show', compact('user', 'loginData', 'activities', 'rolesList'));
    }

    public function index(Request $request)
    {
        // Filter out employees and main admin
        $query = User::whereNotIn('role', ['employee'])->where('email', '!=', 'admin@deped.gov.ph');

        if (!in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $query->where(function($q) {
                $q->where('id', auth()->id())
                  ->orWhere('created_by', auth()->id());
            });
        }

        if ($request->search) {
            $search = $request->search;
            $searchLike = '%' . $search . '%';
            $hashedEmail = hash_hmac('sha256', strtolower($search), config('app.key'));

            $query->where(function($q) use ($searchLike, $hashedEmail) {
                $q->where('first_name', 'like', $searchLike)
                  ->orWhere('last_name', 'like', $searchLike)
                  ->orWhere('email_searchable', $hashedEmail)
                  ->orWhere('role', 'like', $searchLike);
            });
        }

        $users = $query->orderBy('first_name')->paginate(15)->withQueryString();
        
        $rawPositions = Employee::select('position')->whereNotNull('position')->distinct()->pluck('position');
        $positions = $rawPositions->map(function($p) {
            return trim(preg_replace('/\s*\(.*?\)\s*$/', '', $p));
        })->unique()->toArray();

        $defaultPositions = [
            'Accountant', 'Administrative Aide', 'Administrative Assistant', 'Administrative Officer',
            'Assistant School Principal', 'Assistant Schools Division Superintendent', 'Attorney',
            'Chief Education Supervisor', 'Clerk', 'Communications Equipment Operator', 'Cook',
            'Dental Aide', 'Dentist', 'Driver', 'Education Program Specialist', 'Education Program Supervisor',
            'Engineer', 'Guidance Coordinator', 'Guidance Counselor', 'Head Teacher',
            'Health Education and Promotion Officer', 'Houseparent', 'Information Technology Officer',
            'Legal Assistant', 'Librarian', 'Medical Officer', 'Nurse', 'Nurse Maid', 'Planning Officer',
            'Project Development Officer', 'Public Schools District Supervisor', 'Registrar',
            'School Librarian', 'School Principal', 'Schools Division Superintendent', 'Security Guard',
            'Senior Education Program Specialist', 'Supply Officer', 'Watchman'
        ];
        $rolesList = collect(array_merge($defaultPositions, $positions))->unique()->sort()->values()->toArray();
        
        if (!in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $myAccess = auth()->user()->access ? explode(', ', auth()->user()->access) : [];
            $rolesList = collect($rolesList)->filter(function($role) use ($myAccess) {
                return in_array($role, $myAccess);
            })->values()->toArray();
        }
        
        if ($request->ajax()) {
            return view('users.partials.user-table-rows', compact('users', 'rolesList'));
        }

        return view('users.index', compact('users', 'rolesList'));
    }

    public function store(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:4',
            'assign' => 'nullable|string|in:National,City',
            'access' => 'nullable|array',
            'access.*' => 'string|max:255',
        ];

        if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $rules['role'] = 'required|in:admin,coordinator,ojt';
        } else {
            $rules['role'] = 'required|in:coordinator,ojt';
        }

        $request->validate($rules);

        if (!empty($request->email)) {
            $hashedEmail = hash_hmac('sha256', strtolower($request->email), config('app.key'));
            if (User::where('email_searchable', $hashedEmail)->exists()) {
                return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => ['email' => ['The email has already been taken.']]], 422);
            }
        }

        try {
            DB::transaction(function () use ($request) {
                $accessToGrant = $request->access ?: [];
                if (!in_array(auth()->user()->role, ['admin', 'super_admin'])) {
                    $myAccess = auth()->user()->access ? explode(', ', auth()->user()->access) : [];
                    $accessToGrant = array_intersect($accessToGrant, $myAccess);
                }

                User::create([
                    'first_name' => $request->first_name,
                    'middle_name' => $request->middle_name,
                    'last_name' => $request->last_name,
                    'suffix' => $request->suffix,
                    'email' => $request->email,
                    'email_searchable' => hash_hmac('sha256', strtolower($request->email), config('app.key')),
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'assign' => $request->assign,
                    'access' => !empty($accessToGrant) ? implode(', ', $accessToGrant) : null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
            });

            AuditTrail::log('CREATE', 'User Management', "Created system user account: {$request->name}");

            return response()->json(['success' => true, 'message' => 'User account created successfully in users table.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(User $user)
    {
        if (!in_array(auth()->user()->role, ['admin', 'super_admin']) && $user->created_by !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 403);
        }

        try {
            $name = $user->name;
            $user->delete();

            AuditTrail::log('DELETE', 'User Management', "Deleted user account: {$name}");

            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }
}
