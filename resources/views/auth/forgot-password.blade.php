<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LCMS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f1f5f9;">
    <div class="card glass" style="width: 420px; padding: 40px;">
        <h3 style="font-weight: 800; color: var(--primary); margin-bottom: 10px;">Forgot Password</h3>
        <p style="color: var(--secondary); margin-bottom: 25px;">Enter your email to receive a reset link.</p>

        @if(session('success'))
            <div style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px; border-radius: 8px; margin-bottom: 15px;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div style="background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px;">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                @error('email') <small style="color: var(--danger);">{{ $message }}</small> @enderror
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Send Reset Link</button>
            <a href="{{ route('login') }}" style="display: block; text-align: center; margin-top: 15px; color: var(--primary); font-weight: 600;">Back to Login</a>
        </form>
    </div>
</body>
</html>
