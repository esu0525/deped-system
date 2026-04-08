<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditTrail;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Rate limiting
        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Find user by email (using the searchable column as 'email' is encrypted)
        $hashedEmail = hash_hmac('sha256', strtolower($request->email), config('app.key'));
        $user = User::where('email_searchable', $hashedEmail)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 300);
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials. Please check your email and password.',
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
        }

        RateLimiter::clear($key);

        // Skip OTP for employees - login directly
        if ($user->role === 'employee') {
            $user->update([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();

            AuditTrail::log('LOGIN', 'Authentication', "Employee {$user->name} logged in successfully");

            return redirect()->intended(route('dashboard'));
        }

        // Generate and send OTP for administrative roles
        $otp = $user->generateOtp();
        $sent = $this->mailService->sendOtp($user, $otp);

        // Store user ID in session for OTP verification
        session(['otp_user_id' => $user->id, 'otp_sent' => true]);

        if (!$sent) {
            // If mail fails, log the OTP for development
            Log::info("OTP for {$user->email}: {$otp}");
        }

        return redirect()->route('otp.verify')->with('info', 'An OTP has been sent to your email address.');
    }

    // ─── OTP Verification ────────────────────────────────────────────────────

    public function showOtpVerify()
    {
        if (!session('otp_user_id')) {
            return redirect()->route('login');
        }
        return view('auth.otp-verify');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);

        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        // Check OTP attempts
        $maxAttempts = (int)\App\Models\SystemSetting::get('otp_max_attempts', env('OTP_MAX_ATTEMPTS', 5));
        if ($user->otp_attempts >= $maxAttempts) {
            $user->clearOtp();
            session()->forget('otp_user_id');
            return redirect()->route('login')->with('error', 'Too many OTP attempts. Please login again.');
        }

        if (!$user->isOtpValid($request->otp)) {
            $user->increment('otp_attempts');
            $remaining = $maxAttempts - $user->otp_attempts;
            return back()->with('error', "Invalid or expired OTP. {$remaining} attempts remaining.");
        }

        // OTP valid - login user
        $user->clearOtp();
        $user->update([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        Auth::login($user, true);
        session()->forget('otp_user_id');
        $request->session()->regenerate();

        AuditTrail::log('LOGIN', 'Authentication', "User {$user->name} logged in successfully");

        return redirect()->intended(route('dashboard'));
    }

    public function resendOtp(Request $request)
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('login');
        }

        $otp = $user->generateOtp();
        $sent = $this->mailService->sendOtp($user, $otp);

        if (!$sent) {
            Log::info("Resent OTP for {$user->email}: {$otp}");
        }

        return back()->with('info', 'A new OTP has been sent to your email.');
    }

    // ─── Forgot Password ─────────────────────────────────────────────────────

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $hashedEmail = hash_hmac('sha256', strtolower($request->email), config('app.key'));
        $user = User::where('email_searchable', $hashedEmail)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'The selected email is invalid.']);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetLink = route('password.reset', ['token' => $token, 'email' => $request->email]);
        $this->mailService->sendPasswordReset($user->email, $user->name, $resetLink);

        return back()->with('success', 'Password reset link has been sent to your email.');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        /** @var object|null $record */
        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return back()->with('error', 'Invalid or expired reset token.');
        }

        if (now()->diffInMinutes($record->created_at) > 60) {
            return back()->with('error', 'Reset link has expired. Please request a new one.');
        }

        $hashedEmail = hash_hmac('sha256', strtolower($request->email), config('app.key'));
        $user = User::where('email_searchable', $hashedEmail)->first();
        
        if (!$user) {
            return back()->with('error', 'User not found.');
        }

        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        AuditTrail::log('PASSWORD_RESET', 'Authentication', "Password reset for {$user->name}");

        return redirect()->route('login')->with('success', 'Password has been reset successfully. Please login.');
    }

    // ─── Logout ──────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        AuditTrail::log('LOGOUT', 'Authentication', 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    // ─── Register (Admin only) ───────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:super_admin,hr_admin,encoder,employee',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        AuditTrail::log('CREATE', 'User Management', "Created user account for {$user->name} with role {$user->role}");

        return redirect()->route('users.index')->with('success', "User account for {$user->name} created successfully.");
    }
}
