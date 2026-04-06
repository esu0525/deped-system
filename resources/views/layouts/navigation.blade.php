{{-- Sidebar Navigation --}}
<aside class="sidebar" id="sidebar">
    {{-- Logo --}}
    <div class="sidebar-logo">
        <img src="{{ asset('images/logo.jpg') }}" alt="LCMS Logo">
        <span class="sidebar-brand" style="font-size: 1rem; line-height: 1.1; color: #ffffff !important;">Leave Card <br>Management System</span>
    </div>

    @if(auth()->user()->isEmployee())
        {{-- ═══════════════ EMPLOYEE NAVIGATION ═══════════════ --}}
        <div class="sidebar-section-label">PORTAL</div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('employee.dashboard') }}"
                    class="nav-link {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}"
                    data-tooltip="Dashboard">
                    <i class="fas fa-th-large"></i> <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('employee.leave-card') }}"
                    class="nav-link {{ request()->routeIs('employee.leave-card') ? 'active' : '' }}"
                    data-tooltip="Leave Card">
                    <i class="fas fa-address-card"></i> <span class="nav-text">Leave Card</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('employee.profile') }}"
                    class="nav-link {{ request()->routeIs('employee.profile') ? 'active' : '' }}" data-tooltip="My Profile">
                    <i class="fas fa-user-circle"></i> <span class="nav-text">Profile</span>
                </a>
            </li>
        </ul>
    @else
        {{-- ═══════════════ ADMIN / HR / ENCODER NAVIGATION ═══════════════ --}}
        <div class="sidebar-section-label">MAIN MENU</div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    data-tooltip="Dashboard">
                    <i class="fas fa-th-large"></i> <span class="nav-text">Dashboard</span>
                </a>
            </li>

            @if(auth()->user()->hasRole(['admin', 'super_admin']))
                <li class="nav-item">
                    <a href="{{ route('employees.index') }}"
                        class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" data-tooltip="Employees">
                        <i class="fas fa-users-rectangle"></i> <span class="nav-text">Employee Management</span>
                    </a>
                </li>
            @endif

            @if(auth()->user()->hasRole(['admin', 'super_admin', 'coordinator', 'ojt']))
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"
                        data-tooltip="Accounts">
                        <i class="fas fa-shield-halved"></i> <span class="nav-text">Account Management</span>
                    </a>
                </li>
            @endif
        </ul>

        <div class="sidebar-section-label">OPERATIONS</div>

        <ul class="nav-menu">
            <li class="nav-item">
                <a href="{{ route('leave-applications.index') }}"
                    class="nav-link {{ request()->routeIs('leave-applications.*') ? 'active' : '' }}"
                    data-tooltip="Applications">
                    <i class="fas fa-file-signature"></i> <span class="nav-text">Leave Applications</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('leave-cards.index') }}"
                    class="nav-link {{ request()->routeIs('leave-cards.*') ? 'active' : '' }}" data-tooltip="Leave Ledger">
                    <i class="fas fa-address-card"></i> <span class="nav-text">Leave Ledger</span>
                </a>
            </li>


            @if(auth()->user()->hasRole(['admin', 'super_admin']))
                <li class="nav-item">
                    <a href="{{ route('settings.index') }}"
                        class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" data-tooltip="Settings">
                        <i class="fas fa-gear"></i> <span class="nav-text">System Settings</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="{{ route('audit-trail.index') }}"
                        class="nav-link {{ request()->routeIs('audit-trail.*') ? 'active' : '' }}" data-tooltip="Audit Trails">
                        <i class="fas fa-clock-rotate-left"></i> <span class="nav-text">Audit Trails</span>
                    </a>
                </li>
            @endif
        </ul>
    @endif
</aside>