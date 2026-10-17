<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class LoginAttempt
{
    public static function record(string $email, string $ip, bool $success): void
    {
        $binaryIp = @inet_pton($ip);
        if ($binaryIp === false) {
            $binaryIp = inet_pton('127.0.0.1');
        }

        $now = date('Y-m-d H:i:s');
        Database::query(
            "INSERT INTO login_attempts (email, ip, success, attempted_at) VALUES (?, ?, ?, ?)",
            [trim(strtolower($email)), $binaryIp, $success ? 1 : 0, $now]
        );
    }

    public static function isRateLimited(string $email, string $ip): bool
    {
        $binaryIp = @inet_pton($ip);
        if ($binaryIp === false) {
            $binaryIp = inet_pton('127.0.0.1');
        }

        $email = trim(strtolower($email));
        $cutoff = date('Y-m-d H:i:s', time() - (15 * 60));

        // 5 failures per 15 minutes per email OR per IP
        $sql = "SELECT COUNT(*) as failures FROM login_attempts 
                WHERE (email = ? OR ip = ?) 
                  AND success = 0 
                  AND attempted_at >= ?";

        $row = Database::fetch($sql, [$email, $binaryIp, $cutoff]);
        return ((int)($row['failures'] ?? 0)) >= 5;
    }

    public static function clearFailures(string $email, string $ip): void
    {
        $binaryIp = @inet_pton($ip);
        if ($binaryIp === false) {
            $binaryIp = inet_pton('127.0.0.1');
        }

        $email = trim(strtolower($email));

        Database::query(
            "DELETE FROM login_attempts WHERE email = ? OR ip = ?",
            [$email, $binaryIp]
        );
    }
}
