<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class AnalyticsService
{
    /**
     * Get spending breakdown by category for a date range
     */
    public static function getCategoryBreakdown(int $userId, string $startDate, string $endDate, string $type = 'expense'): array
    {
        $sql = "
            SELECT 
                COALESCE(c.name, 'Uncategorized') AS label,
                COALESCE(c.name, 'Uncategorized') AS category_name,
                COALESCE(c.color, '#64748B') AS color,
                COALESCE(c.color, '#64748B') AS category_color,
                COUNT(t.id) AS tx_count,
                COALESCE(SUM(t.amount), 0) AS total
            FROM transactions t
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE t.user_id = ? 
              AND t.type = ? 
              AND t.deleted_at IS NULL
              AND t.tx_date >= ? 
              AND t.tx_date <= ?
            GROUP BY c.id, c.name, c.color
            HAVING total > 0
            ORDER BY total DESC
        ";

        return Database::fetchAll($sql, [$userId, $type, $startDate, $endDate]);
    }

    /**
     * Get daily spending trend
     */
    public static function getDailyTrend(int $userId, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT 
                t.tx_date AS date_label,
                t.tx_date AS day,
                t.tx_date AS tx_date,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense_total,
                COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income_total
            FROM transactions t
            WHERE t.user_id = ? 
              AND t.deleted_at IS NULL
              AND t.tx_date >= ? 
              AND t.tx_date <= ?
            GROUP BY t.tx_date
            ORDER BY t.tx_date ASC
        ";

        return Database::fetchAll($sql, [$userId, $startDate, $endDate]);
    }

    /**
     * Get monthly income vs expense comparison for the last 6 months
     */
    public static function getMonthlyComparison(int $userId, int $months = 6): array
    {
        $startDate = date('Y-m-01', strtotime("-{$months} months"));
        $endDate = date('Y-m-t');

        // Compatible with SQLite (strftime) and MySQL (DATE_FORMAT)
        $monthExpr = "substr(t.tx_date, 1, 7)"; // 'YYYY-MM' works identically in SQLite and MySQL!

        $sql = "
            SELECT 
                {$monthExpr} AS month_label,
                {$monthExpr} AS month,
                COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS expense
            FROM transactions t
            WHERE t.user_id = ? 
              AND t.deleted_at IS NULL
              AND t.tx_date >= ? 
              AND t.tx_date <= ?
            GROUP BY {$monthExpr}
            ORDER BY {$monthExpr} ASC
        ";

        return Database::fetchAll($sql, [$userId, $startDate, $endDate]);
    }
}
