<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'LCMS') }}</title>
    <script>
        // Apply theme immediately to prevent flash
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>

    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
                        <h3 style="font-weight: 700; color: var(--text-main); font-size: 1.1rem; margin: 0;">@yield('header_title', 'Dashboard')</h3>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 16px;">
                    <!-- Breadcrumb Path -->
                    <span style="font-size: 0.8rem; color: var(--secondary); font-weight: 500;">
                        Home <span style="margin: 0 4px;">/</span> <strong style="color: var(--text-main);">@yield('header_title', 'Dashboard')</strong>
                    </span>
                    <div style="height: 30px; width: 1px; background: var(--border-color);"></div>
                    
                    <!-- Theme Toggle -->
                    <button id="themeToggle" class="header-icon-btn" title="Toggle Theme">
                        <i class="fas fa-moon dark-icon"></i>
                        <i class="fas fa-sun light-icon" style="display: none;"></i>
                    </button>
                    <div style="height: 30px; width: 1px; background: var(--border-color);"></div>
                    <!-- User -->
                    <div style="display: flex; align-items: center; gap: 10px;">
                        @if(auth()->user()->avatar)
                            <div style="width: 36px; height: 36px; border-radius: 50%; overflow: hidden; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                        @else
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.9rem;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        @endif
                        <div style="line-height: 1.2;">
                            <p style="font-weight: 700; font-size: 0.85rem; margin: 0; color: var(--text-main);">{{ auth()->user()->name }}</p>
                            <span style="font-size: 0.7rem; color: var(--secondary); font-weight: 500;">{{ auth()->user()->role_display }}</span>
                        </div>
                        <div style="display: flex; gap: 4px; margin-left: 8px;">
                            @if(auth()->user()->isEmployee())
                                <a href="{{ route('employee.profile') }}" class="header-icon-btn" title="My Profile" style="color: var(--primary);">
                                    <i class="fas fa-user-circle"></i>
                                </a>
                            @endif
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="header-icon-btn" title="Logout" style="color: var(--danger);">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            @if(session('success'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: "{{ session('success') }}",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                    });
                </script>
            @endif

            @if(session('error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: "{{ session('error') }}",
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true
                        });
                    });
                </script>
            @endif

            @if($errors->any())
                <div class="card animate-fade" style="background: #fef2f2; border-left: 5px solid var(--danger); color: #991b1b; padding: 18px; margin-bottom: 24px;">
                    <h5 style="font-weight: 700; margin: 0 0 10px;">Validation Errors:</h5>
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Sidebar and Theme Toggle -->
    <script>
        (function() {
            // Sidebar Toggle
            const sidebar = document.getElementById('sidebar');
            const toggle = document.getElementById('sidebarToggle');
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);

            if (sidebar && toggle) {
                // Initial check for desktop collapse
                if (localStorage.getItem('sidebar-collapsed') === 'true' && window.innerWidth > 1024) {
                    sidebar.classList.add('collapsed');
                }

                toggle.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        sidebar.classList.toggle('open');
                        overlay.classList.toggle('active');
                    } else {
                        sidebar.classList.toggle('collapsed');
                        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
                    }
                });

                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                });
            }

            // Theme Toggle
            const themeToggle = document.getElementById('themeToggle');
            const darkIcon = themeToggle.querySelector('.dark-icon');
            const lightIcon = themeToggle.querySelector('.light-icon');

            function updateIcons() {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                if (isDark) {
                    darkIcon.style.display = 'none';
                    lightIcon.style.display = 'block';
                } else {
                    darkIcon.style.display = 'block';
                    lightIcon.style.display = 'none';
                }
            }

            updateIcons();

            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateIcons();
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
