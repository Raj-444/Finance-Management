<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class GoalContribution
{
    public static function getForGoal(int $goalId, int $userId): array
    {
        $sql = "SELECT * FROM goal_contributions WHERE goal_id = ? AND user_id = ? ORDER BY contributed_on DESC, id DESC";
        return Database::fetchAll($sql, [$goalId, $userId]);
    }

    public static function record(int $goalId, int $userId, int $amount, ?string $contributedOn = null, string $note = ''): int
    {
        $sql = "INSERT INTO goal_contributions (goal_id, user_id, amount, contributed_on, note) VALUES (?, ?, ?, ?, ?)";
        Database::query($sql, [
            $goalId,
            $userId,
            $amount,
            $contributedOn ?: date('Y-m-d'),
            trim($note),
        ]);

        $id = (int) Database::lastInsertId();

        // Check if goal is achieved
        $goal = SavingsGoal::findById($goalId, $userId);
        if ($goal && $goal['remaining'] <= 0 && $goal['status'] === 'active') {
            SavingsGoal::updateStatus($goalId, $userId, 'achieved');
        }

        return $id;
    }
}
