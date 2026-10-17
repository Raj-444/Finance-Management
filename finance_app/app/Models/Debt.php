<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Debt
{
    public static function getAllForUser(int $userId, ?string $status = null): array
    {
        $statusClause = ($status !== null) ? "AND d.status = ?" : "";
        $params = [$userId];
        if ($status !== null) {
            $params[] = $status;
        }

        $sql = "
            SELECT d.*, 
                   p.name AS person_name, p.phone AS person_phone,
                   COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = d.id), 0) AS total_paid
            FROM debts d
            JOIN people p ON p.id = d.person_id
            WHERE d.user_id = ? {$statusClause}
            ORDER BY d.status ASC, d.opened_on DESC
        ";

        $rows = Database::fetchAll($sql, $params);
        foreach ($rows as &$r) {
            $r['remaining'] = max(0, (int)$r['principal'] - (int)$r['total_paid']);
            $r['balance'] = $r['remaining'];
        }

        return $rows;
    }

    public static function findById(int $id, int $userId): ?array
    {
        $sql = "
            SELECT d.*, 
                   p.name AS person_name, p.phone AS person_phone,
                   COALESCE((SELECT SUM(amount) FROM debt_payments WHERE debt_id = d.id), 0) AS total_paid
            FROM debts d
            JOIN people p ON p.id = d.person_id
            WHERE d.id = ? AND d.user_id = ?
            LIMIT 1
        ";

        $r = Database::fetch($sql, [$id, $userId]);
        if ($r) {
            $r['remaining'] = max(0, (int)$r['principal'] - (int)$r['total_paid']);
            $r['balance'] = $r['remaining'];
        }
        return $r;
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "
            INSERT INTO debts (user_id, person_id, direction, principal, opened_on, due_on, note, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'open')
        ";

        Database::query($sql, [
            $userId,
            (int)$data['person_id'],
            $data['direction'], // 'lent' or 'borrowed'
            (int)$data['principal'],
            $data['opened_on'] ?? date('Y-m-d'),
            !empty($data['due_on']) ? $data['due_on'] : null,
            trim($data['note'] ?? ''),
        ]);

        return (int) Database::lastInsertId();
    }

    public static function updateStatus(int $id, int $userId, string $status): bool
    {
        return Database::execute("UPDATE debts SET status = ? WHERE id = ? AND user_id = ?", [$status, $id, $userId]) > 0;
    }

    public static function delete(int $id, int $userId): bool
    {
        return Database::execute("DELETE FROM debts WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }

    public static function getSummary(int $userId): array
    {
        $all = self::getAllForUser($userId, 'open');
        $totalLent = 0;
        $totalBorrowed = 0;

        foreach ($all as $d) {
            if ($d['direction'] === 'lent') {
                $totalLent += $d['remaining'];
            } else {
                $totalBorrowed += $d['remaining'];
            }
        }

        return [
            'total_lent' => $totalLent,
            'total_lent_open' => $totalLent,
            'total_borrowed' => $totalBorrowed,
            'total_borrowed_open' => $totalBorrowed,
            'net_position' => $totalLent - $totalBorrowed,
            'net_balance' => $totalLent - $totalBorrowed,
        ];
    }
}
