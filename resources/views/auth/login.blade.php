<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DepEd Leave Card Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="background: white;">
    <div class="login-split">
        <!-- Left Side: Branding -->
        <div class="login-left">
            <div style="z-index: 2; text-align: center;">
                <div style="width: 140px; height: 140px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; overflow: hidden;">
                    <img src="{{ asset('images/logo.jpg') }}" alt="DepEd Logo" style="width: 100%; height: 100%; object-fit: cover; transform: scale(1.1);">
                </div>
                <h1 style="font-size: 3rem; font-weight: 800; margin-bottom: 20px; line-height: 1.1;">DepEd Leave Card<br>Management System</h1>
                <p style="font-size: 1.1rem; opacity: 0.9; max-width: 500px; margin: 0 auto; line-height: 1.6;">
                    Providing automated, efficient, and secure leave management services for non-teaching personnel of the Department of Education.
                </p>
            </div>
            
            <!-- Decorations -->
            <div style="position: absolute; width: 500px; height: 500px; border: 60px solid rgba(255,255,255,0.05); border-radius: 50%; top: -100px; right: -100px;"></div>
            <div style="position: absolute; width: 300px; height: 300px; border: 30px solid rgba(255,255,255,0.05); border-radius: 50%; bottom: -50px; left: -50px;"></div>
        </div>

        <!-- Right Side: Form -->
        <div class="login-right animate-fade">
            <div style="max-width: 400px; margin: 0 auto; width: 100%;">
                <div style="margin-bottom: 40px;">
                    <h2 style="font-size: 2rem; font-weight: 800; color: var(--primary); margin-bottom: 10px;">Welcome Back</h2>
                    <p style="color: var(--secondary); font-weight: 500;">Please log in to your account to continue.</p>
                </div>

                @if($errors->any())
                    <div style="background: #fef2f2; border-left: 4px solid var(--danger); padding: 16px; border-radius: 12px; margin-bottom: 30px;">
                        <ul style="margin: 0; padding-left: 20px; color: var(--danger); font-size: 0.9rem; font-weight: 600;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Email or Username</label>
                        <div style="position: relative;">
                            <i class="fas fa-user" style="position: absolute; left: 18px; top: 18px; color: #94a3b8;"></i>
                            <input type="text" name="email" class="form-control" style="padding-left: 45px;" placeholder="Username" required autofocus>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="position: relative;">
                            <i class="fas fa-lock" style="position: absolute; left: 18px; top: 18px; color: #94a3b8;"></i>
                            <input type="password" id="passwordInput" name="password" class="form-control" style="padding-left: 45px; padding-right: 45px;" placeholder="••••••••" required>
                            <button type="button" onclick="togglePassword()" style="position: absolute; right: 18px; top: 18px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0;">
                                <i id="toggleIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <script>
                        function togglePassword() {
                            const passwordInput = document.getElementById('passwordInput');
                            const toggleIcon = document.getElementById('toggleIcon');
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                toggleIcon.classList.remove('fa-eye');
                                toggleIcon.classList.add('fa-eye-slash');
                            } else {
                                passwordInput.type = 'password';
                                toggleIcon.classList.remove('fa-eye-slash');
                                toggleIcon.classList.add('fa-eye');
                            }
                        }
                    </script>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                        <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                            <input type="checkbox" name="remember" style="width: 18px; height: 18px; accent-color: var(--primary);"> Remember me
                        </label>
                        <a href="{{ route('password.request') }}" style="color: var(--primary); font-size: 0.9rem; text-decoration: none; font-weight: 700;">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 56px; font-size: 1.1rem;">
                        Sign In to Personnel Portal <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div style="margin-top: 50px; text-align: center;">
                    <p style="color: #94a3b8; font-size: 0.85rem; font-weight: 500;">
                        Official System of the Department of Education<br>
                        Republic of the Philippines
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
