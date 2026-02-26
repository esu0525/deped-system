<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DepEd Leave Card Management System</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        .hero-section {
            max-width: 800px;
            padding: 40px;
            animation: fadeIn 1s ease;
        }
        .logo-circle {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 3rem;
            backdrop-filter: blur(10px);
        }
        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: -2px;
            background: linear-gradient(to right, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .tagline {
            font-size: 1.2rem;
            opacity: 0.7;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .btn-large {
            padding: 18px 48px;
            font-size: 1.1rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .features {
            display: flex;
            gap: 20px;
            margin-top: 60px;
            justify-content: center;
        }
        .feature-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px 25px;
            border-radius: 12px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="logo-circle">
            🏫
        </div>
        <h1>DepEd Leave Card</h1>
        <p class="tagline">
            Automated Leave Management System for Non-Teaching Personnel.<br>
            Secure, Efficient, and Intelligence-Driven Ledgering.
        </p>

        @auth
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-large">Enter Dashboard <i class="fas fa-arrow-right"></i></a>
        @else
            <a href="{{ route('login') }}" class="btn btn-primary btn-large">Login to System <i class="fas fa-sign-in-alt"></i></a>
        @endauth

        <div class="features">
            <div class="feature-item">
                <i class="fas fa-shield-halved"></i> OTP Secured
            </div>
            <div class="feature-item">
                <i class="fas fa-robot"></i> AI Fraud Detection
            </div>
            <div class="feature-item">
                <i class="fas fa-file-excel"></i> Excel Integration
            </div>
            <div class="feature-item">
                <i class="fas fa-bolt"></i> Auto-Crediting
            </div>
        </div>
    </div>
</body>
</html>
