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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:4',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $rules['role'] = 'required|in:admin,coordinator,ojt';
            $rules['access'] = 'nullable|array';
            $rules['access.*'] = 'string|max:255';
        }

        $validated = $request->validate($rules);

        try {
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
                $data['role'] = $validated['role'];
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

        $rawPositions = Employee::select('position', 'category')->whereNotNull('position')->distinct()->get();
        $positions = [];
        foreach ($rawPositions as $rp) {
            $pos = trim(preg_replace('/\s+(I|II|III|IV|V|VI|VII|VIII|IX|X)$/i', '', $rp->position));
            $cat = in_array($rp->category, ['National', 'City']) ? $rp->category : 'National';
            $positions[] = "{$pos} ({$cat})";
        }
        $defaultPositions = [
            'Assistant School Principal (National)', 'Assistant School Principal (City)',
            'Head Teacher (National)', 'Head Teacher (City)',
            'School Principal (National)', 'School Principal (City)',
            'Administrative Assistant (National)', 'Administrative Assistant (City)',
            'Administrative Aide (National)', 'Administrative Aide (City)',
            'Administrative Officer (National)', 'Administrative Officer (City)',
            'Nurse (National)', 'Nurse (City)',
            'Security Guard (National)', 'Security Guard (City)',
            'Watchman (National)', 'Watchman (City)'
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
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('role', 'like', $search);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        
        $rawPositions = Employee::select('position', 'category')->whereNotNull('position')->distinct()->get();
        $positions = [];
        foreach ($rawPositions as $rp) {
            $pos = trim(preg_replace('/\s+(I|II|III|IV|V|VI|VII|VIII|IX|X)$/i', '', $rp->position));
            $cat = in_array($rp->category, ['National', 'City']) ? $rp->category : 'National';
            $positions[] = "{$pos} ({$cat})";
        }
        $defaultPositions = [
            'Assistant School Principal (National)', 'Assistant School Principal (City)',
            'Head Teacher (National)', 'Head Teacher (City)',
            'School Principal (National)', 'School Principal (City)',
            'Administrative Assistant (National)', 'Administrative Assistant (City)',
            'Administrative Aide (National)', 'Administrative Aide (City)',
            'Administrative Officer (National)', 'Administrative Officer (City)',
            'Nurse (National)', 'Nurse (City)',
            'Security Guard (National)', 'Security Guard (City)',
            'Watchman (National)', 'Watchman (City)'
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4',
            'access' => 'nullable|array',
            'access.*' => 'string|max:255',
        ];

        if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            $rules['role'] = 'required|in:admin,coordinator,ojt';
        } else {
            $rules['role'] = 'required|in:coordinator,ojt';
        }

        $request->validate($rules);

        try {
            DB::transaction(function () use ($request) {
                $accessToGrant = $request->access ?: [];
                if (!in_array(auth()->user()->role, ['admin', 'super_admin'])) {
                    $myAccess = auth()->user()->access ? explode(', ', auth()->user()->access) : [];
                    $accessToGrant = array_intersect($accessToGrant, $myAccess);
                }

                User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
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
