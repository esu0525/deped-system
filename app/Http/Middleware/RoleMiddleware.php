<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated. Please contact the administrator.');
        }

        // Flatten comma-separated roles (e.g., 'super_admin,hr_admin' becomes ['super_admin', 'hr_admin'])
        $allRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $allRoles[] = trim($r);
            }
        }

        if (!empty($allRoles) && !in_array($user->role, $allRoles)) {
            abort(403, 'Unauthorized. You do not have permission to access this page.');
        }

        return $next($request);
    }
}
