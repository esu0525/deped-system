<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DepEd Leave Card System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f1f5f9;">
    <div class="card glass" style="width: 420px; padding: 40px;">
        <h3 style="font-weight: 800; color: var(--primary); margin-bottom: 25px;">Reset Password</h3>

        @if(session('error'))
            <div style="background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 15px;">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" required>
                @error('password') <small style="color: var(--danger);">{{ $message }}</small> @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">Reset Password</button>
        </form>
    </div>
</body>
</html>
