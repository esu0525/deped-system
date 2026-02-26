<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - DepEd Leave Card Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .otp-input-wrapper {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }
        .otp-digit {
            width: 52px;
            height: 62px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            text-align: center;
            outline: none;
            transition: all 0.2s ease;
            font-family: 'Outfit', sans-serif;
        }
        .otp-digit:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 56, 168, 0.1);
            transform: translateY(-2px);
        }
        .otp-digit.filled {
            border-color: var(--primary);
            background: #eff6ff;
        }
        .timer-ring {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--warning);
        }
        .timer-ring.expired {
            background: #fef2f2;
            border-color: #fecaca;
            color: var(--danger);
        }
        .resend-link {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            font-size: 0.9rem;
            text-decoration: underline;
            transition: opacity 0.2s;
        }
        .resend-link:hover {
            opacity: 0.7;
        }
    </style>
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

        <!-- Right Side: OTP Form -->
        <div class="login-right animate-fade">
            <div style="max-width: 420px; margin: 0 auto; width: 100%; text-align: center;">

                <!-- Shield Icon -->
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                    <i class="fas fa-shield-halved" style="font-size: 1.8rem; color: var(--primary);"></i>
                </div>

                <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--dark); margin-bottom: 8px;">Security Verification</h2>
                <p style="color: var(--secondary); font-weight: 500; font-size: 0.92rem; line-height: 1.6; margin-bottom: 8px;">
                    We've sent a 6-digit verification code to your email.
                </p>
                <p style="color: var(--secondary); font-weight: 500; font-size: 0.85rem; margin-bottom: 30px;">
                    Please enter it below to continue.
                </p>

                @if(session('info'))
                    <div style="background: #eff6ff; border-left: 4px solid var(--primary); padding: 14px 18px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px; color: var(--primary); font-weight: 600; text-align: left;">
                        <i class="fas fa-info-circle" style="margin-right: 8px;"></i> {{ session('info') }}
                    </div>
                @endif

                @if(session('error'))
                    <div style="background: #fef2f2; border-left: 4px solid var(--danger); padding: 14px 18px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 20px; color: var(--danger); font-weight: 600; text-align: left;">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i> {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('otp.verify') }}" method="POST" id="otpForm">
                    @csrf
                    <input type="hidden" name="otp" id="otp-hidden">

                    <!-- 6 Individual OTP Inputs -->
                    <div class="otp-input-wrapper">
                        <input type="text" class="otp-digit" maxlength="1" data-index="0" inputmode="numeric" autocomplete="off" autofocus>
                        <input type="text" class="otp-digit" maxlength="1" data-index="1" inputmode="numeric" autocomplete="off">
                        <input type="text" class="otp-digit" maxlength="1" data-index="2" inputmode="numeric" autocomplete="off">
                        <input type="text" class="otp-digit" maxlength="1" data-index="3" inputmode="numeric" autocomplete="off">
                        <input type="text" class="otp-digit" maxlength="1" data-index="4" inputmode="numeric" autocomplete="off">
                        <input type="text" class="otp-digit" maxlength="1" data-index="5" inputmode="numeric" autocomplete="off">
                    </div>

                    <!-- Timer -->
                    <div style="margin-bottom: 24px;">
                        <span class="timer-ring" id="timerRing">
                            <i class="fas fa-clock"></i>
                            <span id="countdown">05:00</span>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 52px; font-size: 1rem; border-radius: 14px;">
                        <i class="fas fa-shield-halved"></i> Verify & Continue
                    </button>
                </form>

                <!-- Resend OTP -->
                <div style="margin-top: 28px;">
                    <p style="color: var(--secondary); font-size: 0.88rem; font-weight: 500;">
                        Didn't receive the code?
                        <form action="{{ route('otp.resend') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="resend-link">Resend OTP</button>
                        </form>
                    </p>
                </div>

                <!-- Back to Login -->
                <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; color: var(--secondary); font-size: 0.85rem; text-decoration: none; font-weight: 600; transition: color 0.2s;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>

                <!-- Footer -->
                <div style="margin-top: 40px;">
                    <p style="color: #94a3b8; font-size: 0.8rem; font-weight: 500;">
                        Official System of the Department of Education<br>
                        Republic of the Philippines
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ─── OTP Input Logic ────────────────────────────────────────────
        const digits = document.querySelectorAll('.otp-digit');
        const hiddenInput = document.getElementById('otp-hidden');
        const form = document.getElementById('otpForm');

        function updateHiddenInput() {
            let otp = '';
            digits.forEach(d => otp += d.value);
            hiddenInput.value = otp;
        }

        digits.forEach((digit, index) => {
            digit.addEventListener('input', (e) => {
                const val = e.target.value.replace(/\D/g, '');
                e.target.value = val.charAt(0) || '';

                if (val && index < 5) {
                    digits[index + 1].focus();
                }

                e.target.classList.toggle('filled', !!val);
                updateHiddenInput();

                // Auto submit when all 6 digits filled
                if (hiddenInput.value.length === 6) {
                    form.submit();
                }
            });

            digit.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !digit.value && index > 0) {
                    digits[index - 1].focus();
                    digits[index - 1].value = '';
                    digits[index - 1].classList.remove('filled');
                }
            });

            // Handle paste
            digit.addEventListener('paste', (e) => {
                e.preventDefault();
                const pasteData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
                pasteData.split('').forEach((char, i) => {
                    if (digits[i]) {
                        digits[i].value = char;
                        digits[i].classList.add('filled');
                    }
                });
                updateHiddenInput();
                if (pasteData.length >= 6) {
                    digits[5].focus();
                    form.submit();
                } else {
                    digits[Math.min(pasteData.length, 5)].focus();
                }
            });
        });

        // ─── Countdown Timer ────────────────────────────────────────────
        let time = 300;
        const display = document.getElementById('countdown');
        const timerRing = document.getElementById('timerRing');

        const timer = setInterval(() => {
            if (time <= 0) {
                clearInterval(timer);
                display.innerText = 'Expired';
                timerRing.classList.add('expired');
                return;
            }
            time--;
            const mins = Math.floor(time / 60);
            const secs = time % 60;
            display.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

            if (time <= 60) {
                timerRing.classList.add('expired');
            }
        }, 1000);
    </script>
</body>
</html>
