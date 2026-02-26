<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - DepEd Leave Card System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .otp-card {
            width: 100%;
            max-width: 450px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            color: white;
            text-align: center;
        }
        .otp-input-group {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
        }
        .otp-digit {
            width: 50px;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 24px;
            font-weight: 700;
            color: white;
            text-align: center;
            outline: none;
            transition: all 0.2s;
        }
        .otp-digit:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }
    </style>
</head>
<body>
    <div class="otp-card animate-fade">
        <div style="font-size: 50px; margin-bottom: 20px;">🛡️</div>
        <h2 style="font-weight: 700; margin-bottom: 10px;">Security Verification</h2>
        <p style="opacity: 0.7; font-size: 0.9rem; margin-bottom: 30px;">
            We've sent a 6-digit verification code to your email. Please enter it below to continue.
        </p>

        @if(session('info'))
            <div style="background: rgba(59, 130, 246, 0.2); padding: 12px; border-radius: 12px; font-size: 0.85rem; margin-bottom: 20px; color: #93c5fd;">
                {{ session('info') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 12px; font-size: 0.85rem; margin-bottom: 20px; color: #fca5a5;">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('otp.verify') }}" method="POST">
            @csrf
            <div class="form-group">
                <input type="text" name="otp" id="otp-combined" maxlength="6" class="form-control" style="text-align: center; font-size: 32px; letter-spacing: 12px; font-weight: 800; background: rgba(255,255,255,0.05); color: white; border-color: rgba(255,255,255,0.1);" placeholder="000000" autocomplete="off" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 14px; margin-top: 10px;">
                Verify Code <i class="fas fa-shield-alt"></i>
            </button>
        </form>

        <div style="margin-top: 30px; font-size: 0.85rem; opacity: 0.7;">
            Didn't receive the code? 
            <form action="{{ route('otp.resend') }}" method="POST" style="display: inline;">
                @csrf
                <button type="submit" style="background: none; border: none; color: var(--primary); font-weight: 700; cursor: pointer; font-family: inherit;">Resend OTP</button>
            </form>
        </div>

        <p id="timer" style="margin-top: 15px; font-size: 0.8rem; color: var(--accent); font-weight: 600;">OTP expires in: <span id="countdown">05:00</span></p>

        <a href="{{ route('login') }}" style="display: block; margin-top: 30px; color: white; opacity: 0.5; font-size: 0.85rem; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <script>
        // Simple countdown timer
        let time = 300; // 5 minutes
        const display = document.getElementById('countdown');
        
        setInterval(() => {
            if (time <= 0) return;
            time--;
            const mins = Math.floor(time / 60);
            const secs = time % 60;
            display.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }, 1000);
    </script>
</body>
</html>
