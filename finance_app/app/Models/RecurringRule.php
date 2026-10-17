<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class RecurringRule
{
    public static function getAllForUser(int $userId): array
    {
        $sql = "
            SELECT r.*, 
                   a.name AS account_name, a.color AS account_color,
                   c.name AS category_name, c.color AS category_color, c.icon AS category_icon
            FROM recurring_rules r
            LEFT JOIN accounts a ON a.id = r.account_id
            LEFT JOIN categories c ON c.id = r.category_id
            WHERE r.user_id = ?
            ORDER BY r.is_active DESC, r.next_run ASC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        return Database::fetch("SELECT * FROM recurring_rules WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "
            INSERT INTO recurring_rules (
                user_id, type, account_id, category_id, amount, note,
                frequency, interval_n, next_run, end_date, auto_post, is_active
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ";

        Database::query($sql, [
            $userId,
            $data['type'],
            (int)$data['account_id'],
            !empty($data['category_id']) ? (int)$data['category_id'] : null,
            (int)$data['amount'],
            trim($data['note'] ?? ''),
            $data['frequency'] ?? 'monthly',
            (int)($data['interval_n'] ?? 1),
            $data['next_run'] ?? date('Y-m-d'),
            !empty($data['end_date']) ? $data['end_date'] : null,
            isset($data['auto_post']) ? (int)$data['auto_post'] : 1,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return (int) Database::lastInsertId();
    }

    public static function toggleActive(int $id, int $userId): bool
    {
        return Database::execute(
            "UPDATE recurring_rules SET is_active = (1 - is_active) WHERE id = ? AND user_id = ?",
            [$id, $userId]
        ) > 0;
    }

    public static function delete(int $id, int $userId): bool
    {
        return Database::execute("DELETE FROM recurring_rules WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function getDueRules(string $date): array
    {
        $sql = "
            SELECT r.*, u.timezone, u.currency, u.email_alerts, u.email, u.name as user_name
            FROM recurring_rules r
            JOIN users u ON u.id = r.user_id
            WHERE r.is_active = 1 
              AND r.next_run <= ? 
              AND (r.end_date IS NULL OR r.end_date >= ?)
        ";

        return Database::fetchAll($sql, [$date, $date]);
    }

    public static function advanceNextRun(int $id, string $currentNextRun, string $frequency, int $intervalN = 1): string
    {
        $date = new \DateTime($currentNextRun);
        $intervalN = max(1, $intervalN);

        switch ($frequency) {
            case 'daily':
                $date->modify("+{$intervalN} day");
                break;
            case 'weekly':
                $date->modify("+{$intervalN} week");
                break;
            case 'yearly':
                $date->modify("+{$intervalN} year");
                break;
            case 'monthly':
            default:
                $date->modify("+{$intervalN} month");
                break;
        }

        $nextDate = $date->format('Y-m-d');
        Database::query("UPDATE recurring_rules SET next_run = ? WHERE id = ?", [$nextDate, $id]);
        return $nextDate;
    }

    /**
     * Get upcoming recurring expense amount for the active month (used in forecast calculation)
     */
    public static function getUpcomingRecurringExpenseAmount(int $userId, string $periodStart, string $periodEnd, ?int $categoryId = null): int
    {
        $params = [$userId, $periodStart, $periodEnd];
        $catClause = "";

        if ($categoryId !== null) {
            $catClause = "AND category_id = ?";
            $params[] = $categoryId;
        }

        $sql = "
            SELECT COALESCE(SUM(amount), 0) AS upcoming_total
            FROM recurring_rules
            WHERE user_id = ? 
              AND type = 'expense'
              AND is_active = 1 
              AND next_run >= ? 
              AND next_run <= ?
              {$catClause}
        ";

        $row = Database::fetch($sql, $params);
        return (int)($row['upcoming_total'] ?? 0);
    }
}
