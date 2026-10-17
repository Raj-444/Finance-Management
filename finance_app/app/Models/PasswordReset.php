<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class PasswordReset
{
    /**
     * Create a new password reset token (returns raw token to email to user)
     */
    public static function createToken(int $userId): string
    {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        // Invalidate any existing unused tokens for this user
        Database::query("DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL", [$userId]);

        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 60 minutes
        $sql = "INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)";
        Database::query($sql, [$userId, $tokenHash, $expiresAt]);

        return $rawToken;
    }

    /**
     * Verify token validity
     */
    public static function verify(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT pr.*, u.email, u.name 
                FROM password_resets pr
                JOIN users u ON u.id = pr.user_id
                WHERE pr.token_hash = ? 
                  AND pr.used_at IS NULL 
                  AND pr.expires_at > ?
                LIMIT 1";

        return Database::fetch($sql, [$tokenHash, $now]);
    }

    /**
     * Mark token as used
     */
    public static function markAsUsed(string $rawToken): void
    {
        $tokenHash = hash('sha256', $rawToken);
        Database::query("UPDATE password_resets SET used_at = NOW() WHERE token_hash = ?", [$tokenHash]);
    }
}
