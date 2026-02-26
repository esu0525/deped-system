{{-- Sidebar Navigation --}}
<aside class="sidebar" id="sidebar">
    <!-- Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>

    <div class="sidebar-logo">
        <img src="{{ asset('images/logo.jpg') }}" alt="DepEd Logo" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; transform: scale(1.1);">
        <div>
            <span style="display: block; font-weight: 800; color: var(--primary); font-size: 1.1rem; line-height: 1;">DepEd Personnel</span>
            <small style="font-size: 0.65rem; color: var(--secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Leave Card System</small>
        </div>
    </div>

    <nav>
        <ul class="nav-menu">
            {{-- Dashboard --}}
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dashboard">
                    <i class="fas fa-chart-pie"></i> <span class="nav-text">Dashboard</span>
                </a>
            </li>

            {{-- Employees (Admin/Encoder only) --}}
            @if(auth()->user()->hasRole(['super_admin', 'hr_admin', 'encoder']))
            <li class="nav-item">
                <a href="{{ route('employees.index') }}" class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-tooltip="Employees">
                    <i class="fas fa-users-viewfinder"></i> <span class="nav-text">Employees</span>
                </a>
            </li>
            @endif

            {{-- Leave Applications --}}
            <li class="nav-item">
                <a href="{{ route('leave-applications.index') }}" class="nav-link {{ request()->routeIs('leave-applications.*') ? 'active' : '' }}" data-tooltip="Applications">
                    <i class="fas fa-file-signature"></i> <span class="nav-text">Applications</span>
                </a>
            </li>

            {{-- Leave Ledger --}}
            <li class="nav-item">
                <a href="{{ route('leave-cards.index') }}" class="nav-link {{ request()->routeIs('leave-cards.*') ? 'active' : '' }}" data-tooltip="Leave Ledger">
                    <i class="fas fa-address-card"></i> <span class="nav-text">Leave Ledger</span>
                </a>
            </li>

            {{-- Admin-only Section --}}
            @if(auth()->user()->hasRole(['super_admin', 'hr_admin']))

            {{-- AI Detection --}}
            <li class="nav-item">
                <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}" data-tooltip="AI Detection">
                    <i class="fas fa-shield-virus"></i> <span class="nav-text">AI Detection</span>
                    @php $unreviewedCount = \App\Models\AiDetectionLog::where('is_reviewed', false)->where('risk_level', 'High')->count(); @endphp
                    @if($unreviewedCount > 0)
                        <span class="badge" style="background: var(--danger); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; margin-left: auto;">{{ $unreviewedCount }}</span>
                    @endif
                </a>
            </li>

            {{-- Reports --}}
            <li class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-tooltip="Reports">
                    <i class="fas fa-file-export"></i> <span class="nav-text">Reports</span>
                </a>
            </li>

            {{-- Data Migration --}}
            <li class="nav-item">
                <a href="{{ route('import.index') }}" class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}" data-tooltip="Data Migration">
                    <i class="fas fa-cloud-arrow-up"></i> <span class="nav-text">Data Migration</span>
                </a>
            </li>
            @endif

            {{-- Utilities Section Divider --}}
            <div class="sidebar-section-divider" style="margin: 20px 0 10px 15px; font-size: 0.65rem; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">Utilities</div>

            {{-- Super Admin only --}}
            @if(auth()->user()->hasRole('super_admin'))
            <li class="nav-item">
                <a href="{{ route('settings.index') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" data-tooltip="System Settings">
                    <i class="fas fa-gears"></i> <span class="nav-text">System Settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('audit-trail.index') }}" class="nav-link {{ request()->routeIs('audit-trail.*') ? 'active' : '' }}" data-tooltip="Audit Trails">
                    <i class="fas fa-clock-rotate-left"></i> <span class="nav-text">Audit Trails</span>
                </a>
            </li>
            @endif
        </ul>

        {{-- User Info Card --}}
        <div class="sidebar-user-info" style="margin-top: 40px; padding: 20px; background: #f8fafc; border-radius: 16px; border: 1px solid #e2e8f0;">
            <p style="font-size: 0.75rem; color: var(--secondary); font-weight: 700; margin-bottom: 5px;">LOGGED IN AS</p>
            <p style="font-size: 0.85rem; font-weight: 800; color: var(--primary);">{{ auth()->user()->name }}</p>
            <small style="font-weight: 600; opacity: 0.6;">{{ auth()->user()->role_display }}</small>
            <form action="{{ route('logout') }}" method="POST" style="margin-top: 15px;">
                @csrf
                <button type="submit" style="background: none; border: none; color: var(--danger); font-weight: 700; cursor: pointer; padding: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-power-off"></i> <span class="nav-text">Logout</span>
                </button>
            </form>
        </div>
    </nav>
</aside>
