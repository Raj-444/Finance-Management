<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Account
{
    public static function getAllForUser(int $userId, bool $includeArchived = false): array
    {
        $archiveClause = $includeArchived ? "" : "AND a.is_archived = 0";

        $sql = "
            SELECT a.*,
              (
                a.opening_balance 
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'income' AND deleted_at IS NULL), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'expense' AND deleted_at IS NULL), 0)
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE to_account_id = a.id AND type = 'transfer' AND deleted_at IS NULL), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'transfer' AND deleted_at IS NULL), 0)
              ) AS current_balance
            FROM accounts a
            WHERE a.user_id = ? {$archiveClause}
            ORDER BY a.sort_order ASC, a.id ASC
        ";

        return Database::fetchAll($sql, [$userId]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT a.*,
              (
                a.opening_balance 
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'income' AND deleted_at IS NULL), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'expense' AND deleted_at IS NULL), 0)
                + COALESCE((SELECT SUM(amount) FROM transactions WHERE to_account_id = a.id AND type = 'transfer' AND deleted_at IS NULL), 0)
                - COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id AND type = 'transfer' AND deleted_at IS NULL), 0)
              ) AS current_balance
            FROM accounts a
            WHERE a.id = ? AND a.user_id = ?
            LIMIT 1
        ";

        return Database::fetch($sql, [$id, $userId]);
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "INSERT INTO accounts (user_id, name, type, opening_balance, color, icon, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        Database::query($sql, [
            $userId,
            trim($data['name']),
            $data['type'] ?? 'cash',
            (int)($data['opening_balance'] ?? 0),
            $data['color'] ?? '#5B5BD6',
            $data['icon'] ?? 'wallet',
            (int)($data['sort_order'] ?? 0),
        ]);

        return (int) Database::lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $sql = "UPDATE accounts SET name = ?, type = ?, color = ?, icon = ? WHERE id = ? AND user_id = ?";
        return Database::execute($sql, [
            trim($data['name']),
            $data['type'] ?? 'cash',
            $data['color'] ?? '#5B5BD6',
            $data['icon'] ?? 'wallet',
            $id,
            $userId,
        ]) > 0;
    }

    public static function archive(int $id, int $userId): bool
    {
        return Database::execute("UPDATE accounts SET is_archived = 1 WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function getTotalBalance(int $userId): int
    {
        $accounts = self::getAllForUser($userId);
        $total = 0;
        foreach ($accounts as $acc) {
            $total += (int) $acc['current_balance'];
        }
        return $total;
    }
}
