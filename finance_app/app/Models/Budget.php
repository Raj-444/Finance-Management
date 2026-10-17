<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Budget
{
    public static function getAllForPeriod(int $userId, string $periodStart): array
    {
        $sql = "
            SELECT b.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
            FROM budgets b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.user_id = ? AND b.period_start = ?
            ORDER BY b.category_id IS NOT NULL, c.name ASC
        ";

        return Database::fetchAll($sql, [$userId, $periodStart]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT b.*, c.name AS category_name, c.color AS category_color, c.icon AS category_icon
            FROM budgets b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.id = ? AND b.user_id = ?
            LIMIT 1
        ";

        return Database::fetch($sql, [$id, $userId]);
    }

    public static function getOverallBudget(int $userId, string $periodStart): ?array
    {
        $sql = "SELECT * FROM budgets WHERE user_id = ? AND period_start = ? AND category_id IS NULL LIMIT 1";
        return Database::fetch($sql, [$userId, $periodStart]);
    }

    public static function createOrUpdate(int $userId, array $data): int
    {
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $periodStart = $data['period_start'];
        $amount = (int) $data['amount'];
        $warnPercent = (int) ($data['warn_percent'] ?? 80);

        // Check if existing budget exists for this period & category
        if ($categoryId === null) {
            $existing = Database::fetch(
                "SELECT id FROM budgets WHERE user_id = ? AND period_start = ? AND category_id IS NULL LIMIT 1",
                [$userId, $periodStart]
            );
        } else {
            $existing = Database::fetch(
                "SELECT id FROM budgets WHERE user_id = ? AND period_start = ? AND category_id = ? LIMIT 1",
                [$userId, $periodStart, $categoryId]
            );
        }

        if ($existing) {
            $id = (int) $existing['id'];
            Database::execute(
                "UPDATE budgets SET amount = ?, warn_percent = ? WHERE id = ? AND user_id = ?",
                [$amount, $warnPercent, $id, $userId]
            );
            return $id;
        }

        $sql = "INSERT INTO budgets (user_id, category_id, period_start, amount, warn_percent) VALUES (?, ?, ?, ?, ?)";
        Database::query($sql, [$userId, $categoryId, $periodStart, $amount, $warnPercent]);
        return (int) Database::lastInsertId();
    }

    public static function delete(int $id, int $userId): bool
    {
        return Database::execute("DELETE FROM budgets WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }
}
