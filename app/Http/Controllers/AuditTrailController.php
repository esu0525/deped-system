<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    /**
     * Display a listing of the audit trails.
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = AuditTrail::with('user')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                }
                );
            });
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('audit-trail.partials.table-rows', compact('logs'));
        }

        $excludeModules = ['Reports', 'Import'];
        $modules = AuditTrail::distinct()
            ->pluck('module')
            ->filter(fn($m) => !empty($m) && !in_array($m, $excludeModules))
            ->sort();
        $actions = AuditTrail::distinct()->pluck('action')->filter()->sort();

        return view('audit-trail.index', compact('logs', 'modules', 'actions'));
    }

    private function authorizeAdmin()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }
    }
}
