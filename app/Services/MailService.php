<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Send an email using PHPMailer
     */
    public function send($to, $subject, $message)
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = env('MAIL_PORT', 587);

            // Recipients
            $mail->setFrom(env('MAIL_FROM_ADDRESS', 'noreply@deped.gov.ph'), env('MAIL_FROM_NAME', 'DepEd Leave Card System'));
            $mail->addAddress($to);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            $mail->send();
            Log::info("Email sent to: $to");
            return true;
        }
        catch (Exception $e) {
            Log::error("Mail Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send OTP Verification
     */
    public function sendOtp($user, $otp)
    {
        $subject = "Security Verification - DepEd Leave Card System";
        $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                <h2 style='color: #0038a8;'>OTP Verification</h2>
                <p>Hello <strong>{$user->name}</strong>,</p>
                <p>You are trying to log in to the DepEd Leave Card Management System. Use the code below to verify your identity:</p>
                <div style='background: #f1f5f9; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0;'>
                    <span style='font-size: 2.5rem; font-weight: 800; letter-spacing: 10px; color: #0038a8;'>{$otp}</span>
                </div>
                <p style='color: #64748b; font-size: 0.85rem;'>This code will expire in " . config('app.otp_expiry', 5) . " minutes. If you did not request this, please ignore this email.</p>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='font-size: 0.75rem; color: #94a3b8; text-align: center;'>&copy; 2025 Department of Education Personnel Portal</p>
            </div>
        ";

        return $this->send($user->email, $subject, $message);
    }

    /**
     * Send Password Reset Link
     */
    public function sendPasswordReset($email, $name, $resetLink)
    {
        $subject = "Password Reset - DepEd Leave Card System";
        $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                <h2 style='color: #0038a8;'>Password Reset Request</h2>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>We received a request to reset your password for the DepEd Leave Card Management System.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$resetLink}' style='background: #0038a8; color: white; padding: 14px 30px; text-decoration: none; border-radius: 8px; font-weight: 700;'>Reset Password</a>
                </div>
                <p style='color: #64748b; font-size: 0.85rem;'>This link will expire in 60 minutes. If you did not request a password reset, please ignore this email.</p>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='font-size: 0.75rem; color: #94a3b8; text-align: center;'>&copy; 2025 Department of Education Personnel Portal</p>
            </div>
        ";

        return $this->send($email, $subject, $message);
    }

    /**
     * Send Leave Application Status Notification
     */
    public function sendLeaveNotification($email, $name, $status, $leaveType, $dateFrom, $dateTo, $remarks = '')
    {
        $statusColor = match ($status) {
            'Approved' => '#16a34a',
            'Rejected' => '#dc2626',
            default => '#f59e0b',
        };

        $subject = "Leave Application {$status} - DepEd Leave Card System";
        $message = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                <h2 style='color: #0038a8;'>Leave Application Update</h2>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>Your leave application has been <span style='color: {$statusColor}; font-weight: 700;'>{$status}</span>.</p>
                <div style='background: #f1f5f9; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Leave Type:</strong> {$leaveType}</p>
                    <p><strong>Date:</strong> {$dateFrom} to {$dateTo}</p>
                    <p><strong>Status:</strong> <span style='color: {$statusColor}; font-weight: 700;'>{$status}</span></p>
                    " . ($remarks ? "<p><strong>Remarks:</strong> {$remarks}</p>" : "") . "
                </div>
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='font-size: 0.75rem; color: #94a3b8; text-align: center;'>&copy; 2025 Department of Education Personnel Portal</p>
            </div>
        ";

        return $this->send($email, $subject, $message);
    }
}