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
                <h3 style="font-weight: 800; color: var(--primary);">@yield('header_title', 'Dashboard')</h3>
                <div style="display: flex; align-items: center; gap: 20px;">
                    <button class="btn btn-sm btn-secondary" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div style="height: 35px; width: 1px; background: #e2e8f0;"></div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 12px; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem;">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div style="line-height: 1;">
                            <p style="font-weight: 700; font-size: 0.9rem; margin: 0;">{{ auth()->user()->name }}</p>
                            <span style="font-size: 0.7rem; color: var(--secondary); font-weight: 600;">{{ auth()->user()->role_display }}</span>
                        </div>
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

