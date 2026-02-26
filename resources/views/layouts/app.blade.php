<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'DepEd Leave Card System') }}</title>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body>
    <div class="app-container">
        {{-- Sidebar Navigation --}}
        @include('layouts.navigation')

        <!-- Main Content -->
        <main class="main-content">
            <header class="header animate-fade">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Hamburger Toggle -->
                    <button class="sidebar-hamburger" id="sidebarToggle" title="Toggle Menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Breadcrumb -->
                    <div>
                        <h3 style="font-weight: 700; color: var(--dark); font-size: 1.1rem; margin: 0;">@yield('header_title', 'Dashboard')</h3>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Breadcrumb Path -->
                    <span style="font-size: 0.8rem; color: var(--secondary); font-weight: 500;">
                        Home <span style="margin: 0 4px;">/</span> <strong style="color: var(--dark);">@yield('header_title', 'Dashboard')</strong>
                    </span>
                    <div style="height: 30px; width: 1px; background: #e2e8f0;"></div>
                    <!-- Notifications -->
                    <button class="header-icon-btn" title="Notifications">
                        <i class="fas fa-envelope"></i>
                    </button>
                    <div style="height: 30px; width: 1px; background: #e2e8f0;"></div>
                    <!-- User -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div style="line-height: 1.2;">
                            <p style="font-weight: 700; font-size: 0.85rem; margin: 0; color: var(--dark);">{{ auth()->user()->name }}</p>
                            <span style="font-size: 0.7rem; color: var(--secondary); font-weight: 500;">{{ auth()->user()->role_display }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST" style="margin-left: 8px;">
                            @csrf
                            <button type="submit" class="header-icon-btn" title="Logout" style="color: var(--danger);">
                                <i class="fas fa-power-off"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            @if(session('success'))
                <div class="card animate-fade" style="background: #ecfdf5; border-left: 5px solid var(--success); color: #065f46; padding: 18px; margin-bottom: 24px;">
                    <i class="fas fa-check-circle" style="margin-right: 10px;"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="card animate-fade" style="background: #fef2f2; border-left: 5px solid var(--danger); color: #991b1b; padding: 18px; margin-bottom: 24px;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 10px;"></i> {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Sidebar Toggle -->
    <script>
        (function() {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');

            // Restore saved state
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }

            toggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
