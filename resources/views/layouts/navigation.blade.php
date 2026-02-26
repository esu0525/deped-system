{{-- Sidebar Navigation --}}
<aside class="sidebar" id="sidebar">
    {{-- Logo --}}
    <div class="sidebar-logo">
        <img src="{{ asset('images/logo.jpg') }}" alt="DepEd Logo">
        <span class="sidebar-brand">DepEd</span>
    </div>

    {{-- Section: Main Menu --}}
    <div class="sidebar-section-label">MAIN MENU</div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                <i class="fas fa-th-large"></i> <span class="nav-text">Dashboard</span>
            </a>
        </li>

        @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
        <li class="nav-item">
            <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-tooltip="Employees">
                <i class="fas fa-users"></i> <span class="nav-text">Employee Management</span>
            </a>
        </li>
        @endif
    </ul>

    {{-- Section: Operations --}}
    <div class="sidebar-section-label">OPERATIONS</div>

    <ul class="nav-menu">
        <li class="nav-item">
            <a href="{{ route('leave-applications.index') }}" class="nav-link {{ request()->routeIs('leave-applications.*') ? 'active' : '' }}" data-tooltip="Applications">
                <i class="fas fa-file-signature"></i> <span class="nav-text">Leave Applications</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('leave-cards.index') }}" class="nav-link {{ request()->routeIs('leave-cards.*') ? 'active' : '' }}" data-tooltip="Leave Ledger">
                <i class="fas fa-address-card"></i> <span class="nav-text">Leave Ledger</span>
            </a>
        </li>

        @if(auth()->user()->hasRole(['super_admin', 'hr_admin']))
        <li class="nav-item">
            <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}" data-tooltip="AI Detection">
                <i class="fas fa-shield-halved"></i> <span class="nav-text">AI Detection</span>
                @php $unreviewedCount = \App\Models\AiDetectionLog::where('is_reviewed', false)->where('risk_level', 'High')->count(); @endphp
                @if($unreviewedCount > 0)
                    <span class="nav-badge">{{ $unreviewedCount }}</span>
                @endif
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-tooltip="Reports">
                <i class="fas fa-chart-bar"></i> <span class="nav-text">Reports</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('import.index') }}" class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}" data-tooltip="Data Migration">
                <i class="fas fa-cloud-arrow-up"></i> <span class="nav-text">Data Migration</span>
            </a>
        </li>
        @endif

        @if(auth()->user()->hasRole('super_admin'))
        <li class="nav-item">
            <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" data-tooltip="Settings">
                <i class="fas fa-gear"></i> <span class="nav-text">System Settings</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="{{ route('audit-trail.index') }}" class="nav-link {{ request()->routeIs('audit-trail.*') ? 'active' : '' }}" data-tooltip="Audit Trails">
                <i class="fas fa-clock-rotate-left"></i> <span class="nav-text">Audit Trails</span>
            </a>
        </li>
        @endif
    </ul>
</aside>
