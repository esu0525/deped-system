<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditTrail;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function deactivate(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => false]);

        AuditTrail::log('DEACTIVATE', 'User Management', "Deactivated user account: {$user->username}");

        return back()->with('success', 'User deactivated successfully.');
    }

    public function activate(User $user)
    {
        $user->update(['is_active' => true]);

        AuditTrail::log('ACTIVATE', 'User Management', "Activated user account: {$user->username}");

        return back()->with('success', 'User activated successfully.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $username = $user->username;
        $user->delete();

        AuditTrail::log('DELETE', 'User Management', "Deleted user account: {$username}");

        return back()->with('success', 'User deleted successfully.');
    }
}
