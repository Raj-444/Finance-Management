<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Notification
{
    public static function createIfNotExists(
        int $userId,
        string $type,
        ?int $budgetId,
        string $title,
        ?string $body = null,
        ?string $link = null
    ): ?int {
        // Enforce max one alert per budget per level
        if ($budgetId !== null) {
            $existing = Database::fetch(
                "SELECT id FROM notifications WHERE budget_id = ? AND type = ? LIMIT 1",
                [$budgetId, $type]
            );
            if ($existing) {
                return null;
            }
        }

        $sql = "INSERT INTO notifications (user_id, type, budget_id, title, body, link, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $now = date('Y-m-d H:i:s');
        try {
            Database::query($sql, [$userId, $type, $budgetId, $title, $body, $link, $now]);
            return (int) Database::lastInsertId();
        } catch (\Throwable $e) {
            // In case of unique constraint violation race condition
            return null;
        }
    }

    public static function getUnreadForUser(int $userId, int $limit = 10): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL ORDER BY created_at DESC LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function getAllForUser(int $userId, int $limit = 20): array
    {
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function getUnreadCount(int $userId): int
    {
        $row = Database::fetch("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND read_at IS NULL", [$userId]);
        return (int)($row['cnt'] ?? 0);
    }

    public static function markAllAsRead(int $userId): void
    {
        $now = date('Y-m-d H:i:s');
        Database::query("UPDATE notifications SET read_at = ? WHERE user_id = ? AND read_at IS NULL", [$now, $userId]);
    }

    public static function markAsRead(int $id, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        return Database::execute("UPDATE notifications SET read_at = ? WHERE id = ? AND user_id = ?", [$now, $id, $userId]) > 0;
    }
}
