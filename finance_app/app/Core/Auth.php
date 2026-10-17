<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;
use App\Services\Mailer;

class Auth
{
    public static function attempt(string $email, string $password, string $ip): array
    {
        $email = trim(strtolower($email));

        // Check login rate limiting (5 failures in 15 mins)
        if (LoginAttempt::isRateLimited($email, $ip)) {
            Logger::warning("Login blocked by rate limit for {$email} from {$ip}");
            return [
                'success' => false,
                'error' => I18n::get('too_many_attempts'),
            ];
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            LoginAttempt::record($email, $ip, false);
            Logger::info("Failed login attempt for {$email} from {$ip}");
            return [
                'success' => false,
                'error' => I18n::get('invalid_credentials'),
            ];
        }

        if (empty($user['is_active'])) {
            LoginAttempt::record($email, $ip, false);
            return [
                'success' => false,
                'error' => 'Your account has been deactivated. Please contact the administrator.',
            ];
        }

        // Check if password needs rehash
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        if (password_needs_rehash($user['password_hash'], $algo)) {
            User::updatePassword((int)$user['id'], $password);
        }

        // Record successful login & clear failures
        LoginAttempt::record($email, $ip, true);
        LoginAttempt::clearFailures($email, $ip);
        User::updateLastLogin((int)$user['id']);

        // Session regeneration to prevent session fixation
        Session::regenerate();

        // Store user in session (excluding password hash)
        unset($user['password_hash']);
        Session::set('user', $user);
        Session::set('user_id', (int)$user['id']);
        Session::set('role', $user['role']);
        Session::set('locale', $user['locale'] ?? 'en');

        Logger::info("User {$user['id']} ({$email}) logged in successfully from {$ip}");

        return ['success' => true];
    }

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id !== null ? (int)$id : null;
    }

    public static function isAdmin(): bool
    {
        return Session::get('role') === 'admin';
    }

    public static function logout(): void
    {
        $userId = self::id();
        if ($userId) {
            Logger::info("User {$userId} logged out");
        }
        Session::destroy();
    }

    public static function sendResetLink(string $email): bool
    {
        $user = User::findByEmail($email);
        if (!$user) {
            // Return true even if user not found to prevent user enumeration
            return true;
        }

        $rawToken = PasswordReset::createToken((int)$user['id']);
        $resetUrl = url('/reset-password?token=' . urlencode($rawToken));

        $subject = 'Password Reset Request - ' . Config::get('app.name', 'Personal Finance Manager');
        $html = "
            <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto; padding: 24px;'>
                <h2>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') . ",</p>
                <p>We received a request to reset your password. Click the link below to set a new password:</p>
                <p style='margin: 24px 0;'><a href='{$resetUrl}' style='background: #5B5BD6; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;'>Reset Password</a></p>
                <p style='color: #666; font-size: 13px;'>This link will expire in 60 minutes. If you did not make this request, you can safely ignore this email.</p>
            </div>
        ";

        return Mailer::send($user['email'], $user['name'], $subject, $html);
    }

    public static function resetPassword(string $rawToken, string $newPassword): array
    {
        $resetRecord = PasswordReset::verify($rawToken);
        if (!$resetRecord) {
            return [
                'success' => false,
                'error' => I18n::get('invalid_reset_token'),
            ];
        }

        if (strlen($newPassword) < 8) {
            return [
                'success' => false,
                'error' => 'Password must be at least 8 characters long.',
            ];
        }

        User::updatePassword((int)$resetRecord['user_id'], $newPassword);
        PasswordReset::markAsUsed($rawToken);

        Logger::info("Password reset successfully for user {$resetRecord['user_id']}");

        return ['success' => true];
    }
}
