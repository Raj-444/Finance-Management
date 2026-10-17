<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SavingsGoal
{
    public static function getAllForUser(int $userId): array
    {
        $sql = "
            SELECT g.*, 
                   COALESCE((SELECT SUM(amount) FROM goal_contributions WHERE goal_id = g.id), 0) AS current_saved
            FROM savings_goals g
            WHERE g.user_id = ? AND g.status != 'archived'
            ORDER BY g.status ASC, g.target_date ASC
        ";

        $rows = Database::fetchAll($sql, [$userId]);
        foreach ($rows as &$r) {
            $target = (int)$r['target_amount'];
            $saved = (int)$r['current_saved'];
            $r['percent'] = ($target > 0) ? min(100, round(($saved / $target) * 100, 1)) : 0;
            $r['remaining'] = max(0, $target - $saved);
        }

        return $rows;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT g.*, 
                   COALESCE((SELECT SUM(amount) FROM goal_contributions WHERE goal_id = g.id), 0) AS current_saved
            FROM savings_goals g
            WHERE g.id = ? AND g.user_id = ?
            LIMIT 1
        ";

        $r = Database::fetch($sql, [$id, $userId]);
        if ($r) {
            $target = (int)$r['target_amount'];
            $saved = (int)$r['current_saved'];
            $r['percent'] = ($target > 0) ? min(100, round(($saved / $target) * 100, 1)) : 0;
            $r['remaining'] = max(0, $target - $saved);
        }
        return $r;
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "INSERT INTO savings_goals (user_id, name, target_amount, target_date, color, icon, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')";
        Database::query($sql, [
            $userId,
            trim($data['name']),
            (int)$data['target_amount'],
            !empty($data['target_date']) ? $data['target_date'] : null,
            $data['color'] ?? '#12B76A',
            $data['icon'] ?? 'target',
        ]);

        return (int) Database::lastInsertId();
    }

    public static function updateStatus(int $id, int $userId, string $status): bool
    {
        return Database::execute("UPDATE savings_goals SET status = ? WHERE id = ? AND user_id = ?", [$status, $id, $userId]) > 0;
    }

    public static function delete(int $id, int $userId): bool
    {
        return Database::execute("DELETE FROM savings_goals WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }
}
