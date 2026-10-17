<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE id = ? LIMIT 1", [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch("SELECT * FROM users WHERE email = ? LIMIT 1", [trim(strtolower($email))]);
    }

    public static function create(array $data): int
    {
        $passwordHash = password_hash(
            $data['password'],
            defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT,
            ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
        );

        $sql = "INSERT INTO users (
            name, email, password_hash, role, currency, locale, timezone, 
            theme, month_start_day, week_start, number_format, email_alerts, focus_mode, is_active
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?
        )";

        Database::query($sql, [
            trim($data['name']),
            trim(strtolower($data['email'])),
            $passwordHash,
            $data['role'] ?? 'user',
            $data['currency'] ?? 'BDT',
            $data['locale'] ?? 'en',
            $data['timezone'] ?? 'Asia/Dhaka',
            $data['theme'] ?? 'system',
            (int)($data['month_start_day'] ?? 1),
            (int)($data['week_start'] ?? 6), // 6 = Saturday (typical in BD)
            $data['number_format'] ?? 'indian',
            isset($data['email_alerts']) ? (int)$data['email_alerts'] : 1,
            isset($data['focus_mode']) ? (int)$data['focus_mode'] : 0,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return (int) Database::lastInsertId();
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash(
            $newPassword,
            defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT
        );

        return Database::execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $userId]) > 0;
    }

    public static function updateLastLogin(int $userId): void
    {
        Database::query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$userId]);
    }

    public static function countAll(): int
    {
        $row = Database::fetch("SELECT COUNT(*) as count FROM users");
        return (int) ($row['count'] ?? 0);
    }

    public static function delete(int $userId): bool
    {
        return Database::execute("DELETE FROM users WHERE id = ?", [$userId]) > 0;
    }
}
