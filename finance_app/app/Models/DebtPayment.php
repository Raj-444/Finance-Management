<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DebtPayment
{
    public static function getForDebt(int $debtId, int $userId): array
    {
        $sql = "SELECT * FROM debt_payments WHERE debt_id = ? AND user_id = ? ORDER BY paid_on DESC, id DESC";
        return Database::fetchAll($sql, [$debtId, $userId]);
    }

    public static function record(int $debtId, int $userId, int $amount, ?string $paidOn = null, string $note = ''): int
    {
        $sql = "INSERT INTO debt_payments (debt_id, user_id, amount, paid_on, note) VALUES (?, ?, ?, ?, ?)";
        Database::query($sql, [
            $debtId,
            $userId,
            $amount,
            $paidOn ?: date('Y-m-d'),
            trim($note),
        ]);

        $paymentId = (int) Database::lastInsertId();

        // Check if debt is fully repaid, and auto-settle
        $debt = Debt::findById($debtId, $userId);
        if ($debt && $debt['remaining'] <= 0) {
            Debt::updateStatus($debtId, $userId, 'settled');
        }

        return $paymentId;
    }
}
