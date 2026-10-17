<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Transaction
{
    public static function create(int $userId, array $data): int
    {
        $amount = (int) $data['amount'];
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transaction amount must be greater than zero.");
        }

        $type = $data['type'];
        if (!in_array($type, ['expense', 'income', 'transfer'], true)) {
            throw new \InvalidArgumentException("Invalid transaction type: {$type}");
        }

        $accountId = (int) $data['account_id'];
        $toAccountId = ($type === 'transfer' && !empty($data['to_account_id'])) ? (int)$data['to_account_id'] : null;
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $personId = !empty($data['person_id']) ? (int)$data['person_id'] : null;
        $txDate = $data['tx_date'] ?? date('Y-m-d');
        $note = trim($data['note'] ?? '');
        $tags = trim($data['tags'] ?? '');

        // Generate deduplication hash
        $dedupeHash = sha1("{$userId}_{$txDate}_{$amount}_{$accountId}_{$note}");

        $sql = "
            INSERT INTO transactions (
                user_id, type, account_id, to_account_id, category_id, person_id,
                amount, tx_date, note, tags, dedupe_hash, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $now = date('Y-m-d H:i:s');
        Database::query($sql, [
            $userId, $type, $accountId, $toAccountId, $categoryId, $personId,
            $amount, $txDate, $note, $tags, $dedupeHash, $now, $now
        ]);

        $insertedId = (int) Database::lastInsertId();

        if ($type === 'expense') {
            try {
                \App\Services\BudgetForecastService::evaluateAllForUser($userId);
            } catch (\Throwable $e) {
                // Silently log; never fail transaction insert on notification failure
                \App\Core\Logger::error("Budget evaluation failed on tx create: " . $e->getMessage());
            }
        }

        return $insertedId;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT t.*, 
                   a.name AS account_name, a.color AS account_color,
                   to_a.name AS to_account_name, to_a.color AS to_account_color,
                   c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                   p.name AS person_name
            FROM transactions t
            LEFT JOIN accounts a ON a.id = t.account_id
            LEFT JOIN accounts to_a ON to_a.id = t.to_account_id
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN people p ON p.id = t.person_id
            WHERE t.id = ? AND t.user_id = ?
            LIMIT 1
        ";

        return Database::fetch($sql, [$id, $userId]);
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $amount = (int) $data['amount'];
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Transaction amount must be greater than zero.");
        }

        $type = $data['type'];
        $accountId = (int) $data['account_id'];
        $toAccountId = ($type === 'transfer' && !empty($data['to_account_id'])) ? (int)$data['to_account_id'] : null;
        $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : null;
        $personId = !empty($data['person_id']) ? (int)$data['person_id'] : null;
        $txDate = $data['tx_date'] ?? date('Y-m-d');
        $note = trim($data['note'] ?? '');
        $tags = trim($data['tags'] ?? '');

        $sql = "
            UPDATE transactions SET 
                type = ?, account_id = ?, to_account_id = ?, category_id = ?, person_id = ?,
                amount = ?, tx_date = ?, note = ?, tags = ?, updated_at = ?
            WHERE id = ? AND user_id = ?
        ";

        $now = date('Y-m-d H:i:s');
        return Database::execute($sql, [
            $type, $accountId, $toAccountId, $categoryId, $personId,
            $amount, $txDate, $note, $tags, $now,
            $id, $userId
        ]) > 0;
    }

    public static function softDelete(int $id, int $userId): bool
    {
        $now = date('Y-m-d H:i:s');
        return Database::execute("UPDATE transactions SET deleted_at = ? WHERE id = ? AND user_id = ?", [$now, $id, $userId]) > 0;
    }

    public static function restore(int $id, int $userId): bool
    {
        return Database::execute("UPDATE transactions SET deleted_at = NULL WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function getRecent(int $userId, int $limit = 10): array
    {
        $sql = "
            SELECT t.*, 
                   a.name AS account_name, a.color AS account_color,
                   to_a.name AS to_account_name, to_a.color AS to_account_color,
                   c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                   p.name AS person_name
            FROM transactions t
            LEFT JOIN accounts a ON a.id = t.account_id
            LEFT JOIN accounts to_a ON to_a.id = t.to_account_id
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN people p ON p.id = t.person_id
            WHERE t.user_id = ? AND t.deleted_at IS NULL
            ORDER BY t.tx_date DESC, t.id DESC
            LIMIT ?
        ";

        return Database::fetchAll($sql, [$userId, $limit]);
    }

    public static function getPaginated(int $userId, array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ["t.user_id = ?", "t.deleted_at IS NULL"];
        $params = [$userId];

        if (!empty($filters['type'])) {
            $where[] = "t.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['account_id'])) {
            $where[] = "(t.account_id = ? OR t.to_account_id = ?)";
            $params[] = (int)$filters['account_id'];
            $params[] = (int)$filters['account_id'];
        }

        if (!empty($filters['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['person_id'])) {
            $where[] = "t.person_id = ?";
            $params[] = (int)$filters['person_id'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "t.tx_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "t.tx_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(t.note LIKE ? OR c.name LIKE ? OR p.name LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(" AND ", $where);

        // Count Total
        $countSql = "
            SELECT COUNT(*) AS total
            FROM transactions t
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN people p ON p.id = t.person_id
            WHERE {$whereClause}
        ";
        $totalRow = Database::fetch($countSql, $params);
        $totalRows = (int)($totalRow['total'] ?? 0);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch Rows
        $sql = "
            SELECT t.*, 
                   a.name AS account_name, a.color AS account_color,
                   to_a.name AS to_account_name, to_a.color AS to_account_color,
                   c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                   p.name AS person_name
            FROM transactions t
            LEFT JOIN accounts a ON a.id = t.account_id
            LEFT JOIN accounts to_a ON to_a.id = t.to_account_id
            LEFT JOIN categories c ON c.id = t.category_id
            LEFT JOIN people p ON p.id = t.person_id
            WHERE {$whereClause}
            ORDER BY t.tx_date DESC, t.id DESC
            LIMIT ? OFFSET ?
        ";

        $rowParams = array_merge($params, [$perPage, $offset]);
        $rows = Database::fetchAll($sql, $rowParams);

        return [
            'data' => $rows,
            'total' => $totalRows,
            'current_page' => $page,
            'last_page' => $totalPages,
            'per_page' => $perPage,
        ];
    }

    public static function getDashboardSummary(int $userId, int $monthStartDay = 1): array
    {
        $today = new \DateTime();
        $currentDay = (int)$today->format('j');
        $currentMonth = (int)$today->format('n');
        $currentYear = (int)$today->format('Y');

        if ($currentDay >= $monthStartDay) {
            $periodStartDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $monthStartDay);
            $nextMonth = $today->modify('+1 month');
            $periodEndDate = sprintf('%04d-%02d-%02d', (int)$nextMonth->format('Y'), (int)$nextMonth->format('n'), $monthStartDay - 1);
        } else {
            $prevMonth = $today->modify('-1 month');
            $periodStartDate = sprintf('%04d-%02d-%02d', (int)$prevMonth->format('Y'), (int)$prevMonth->format('n'), $monthStartDay);
            $periodEndDate = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $monthStartDay - 1);
        }

        // Monthly Income and Expense
        $sql = "
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS monthly_income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS monthly_expense
            FROM transactions
            WHERE user_id = ? AND deleted_at IS NULL AND tx_date >= ?
        ";

        $stats = Database::fetch($sql, [$userId, $periodStartDate]);
        $monthlyIncome = (int)($stats['monthly_income'] ?? 0);
        $monthlyExpense = (int)($stats['monthly_expense'] ?? 0);
        $netSavings = $monthlyIncome - $monthlyExpense;
        $savedRate = ($monthlyIncome > 0) ? round(($netSavings / $monthlyIncome) * 100, 1) : 0;

        $totalBalance = Account::getTotalBalance($userId);

        return [
            'total_balance' => $totalBalance,
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'net_savings' => $netSavings,
            'saved_rate' => $savedRate,
            'period_start' => $periodStartDate,
        ];
    }
}
