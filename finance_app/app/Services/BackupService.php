<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\User;

class BackupService
{
    /**
     * Export complete user ledger as a structured JSON backup
     */
    public static function export(int $userId): array
    {
        $user = User::findById($userId);

        $accounts = Database::fetchAll("SELECT * FROM accounts WHERE user_id = ?", [$userId]);
        $categories = Database::fetchAll("SELECT * FROM categories WHERE user_id = ?", [$userId]);
        $people = Database::fetchAll("SELECT * FROM people WHERE user_id = ?", [$userId]);
        $transactions = Database::fetchAll("SELECT * FROM transactions WHERE user_id = ? AND deleted_at IS NULL", [$userId]);
        $budgets = Database::fetchAll("SELECT * FROM budgets WHERE user_id = ?", [$userId]);
        $recurringRules = Database::fetchAll("SELECT * FROM recurring_rules WHERE user_id = ?", [$userId]);
        $debts = Database::fetchAll("SELECT * FROM debts WHERE user_id = ?", [$userId]);
        $debtPayments = Database::fetchAll("SELECT * FROM debt_payments WHERE user_id = ?", [$userId]);
        $savingsGoals = Database::fetchAll("SELECT * FROM savings_goals WHERE user_id = ?", [$userId]);
        $goalContributions = Database::fetchAll("SELECT * FROM goal_contributions WHERE user_id = ?", [$userId]);

        return [
            'meta' => [
                'app' => 'Personal Finance Manager',
                'version' => '1.0.0',
                'exported_at' => date('c'),
                'user_email' => $user['email'] ?? '',
            ],
            'user_settings' => [
                'currency' => $user['currency'] ?? 'BDT',
                'locale' => $user['locale'] ?? 'en',
                'theme' => $user['theme'] ?? 'system',
                'month_start_day' => (int)($user['month_start_day'] ?? 1),
                'week_start' => (int)($user['week_start'] ?? 6),
                'number_format' => $user['number_format'] ?? 'indian',
                'focus_mode' => (int)($user['focus_mode'] ?? 0),
            ],
            'accounts' => $accounts,
            'categories' => $categories,
            'people' => $people,
            'transactions' => $transactions,
            'budgets' => $budgets,
            'recurring_rules' => $recurringRules,
            'debts' => $debts,
            'debt_payments' => $debtPayments,
            'savings_goals' => $savingsGoals,
            'goal_contributions' => $goalContributions,
        ];
    }

    /**
     * Restore user ledger from JSON backup payload
     */
    public static function restore(int $userId, array $backup): array
    {
        if (empty($backup['meta']) || !isset($backup['transactions']) || !isset($backup['accounts'])) {
            throw new \InvalidArgumentException("Invalid backup format or missing required sections.");
        }

        // Wipe existing records for this user in foreign-key safe order
        Database::query("DELETE FROM debt_payments WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM debts WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM goal_contributions WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM savings_goals WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM recurring_rules WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM transactions WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM budgets WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM categories WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM accounts WHERE user_id = ?", [$userId]);
        Database::query("DELETE FROM people WHERE user_id = ?", [$userId]);

        // Restore User Settings
        if (!empty($backup['user_settings'])) {
            $s = $backup['user_settings'];
            Database::query(
                "UPDATE users SET currency = ?, locale = ?, theme = ?, month_start_day = ?, week_start = ?, number_format = ? WHERE id = ?",
                [
                    $s['currency'] ?? 'BDT',
                    $s['locale'] ?? 'en',
                    $s['theme'] ?? 'system',
                    (int)($s['month_start_day'] ?? 1),
                    (int)($s['week_start'] ?? 6),
                    $s['number_format'] ?? 'indian',
                    $userId
                ]
            );
        }

        // 1. Restore Accounts & map old ID to new ID
        $accountMap = [];
        foreach ($backup['accounts'] as $a) {
            $sql = "INSERT INTO accounts (user_id, name, type, opening_balance, color, icon, is_archived, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            Database::query($sql, [
                $userId,
                $a['name'],
                $a['type'] ?? 'cash',
                (int)($a['opening_balance'] ?? 0),
                $a['color'] ?? '#5B5BD6',
                $a['icon'] ?? null,
                (int)($a['is_archived'] ?? 0),
                (int)($a['sort_order'] ?? 0),
            ]);
            $newId = (int)Database::lastInsertId();
            $accountMap[(int)$a['id']] = $newId;
        }

        // 2. Restore Categories (parents first, then children)
        $categoryMap = [];
        $categories = $backup['categories'];
        usort($categories, fn($a, $b) => ($a['parent_id'] === null ? -1 : 1));

        foreach ($categories as $c) {
            $parentId = !empty($c['parent_id']) ? ($categoryMap[(int)$c['parent_id']] ?? null) : null;
            $sql = "INSERT INTO categories (user_id, parent_id, type, name, icon, color, is_archived, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            Database::query($sql, [
                $userId,
                $parentId,
                $c['type'],
                $c['name'],
                $c['icon'] ?? null,
                $c['color'] ?? '#64748B',
                (int)($c['is_archived'] ?? 0),
                (int)($c['sort_order'] ?? 0),
            ]);
            $newId = (int)Database::lastInsertId();
            $categoryMap[(int)$c['id']] = $newId;
        }

        // 3. Restore People
        $peopleMap = [];
        if (!empty($backup['people'])) {
            foreach ($backup['people'] as $p) {
                Database::query("INSERT INTO people (user_id, name, phone, note) VALUES (?, ?, ?, ?)", [
                    $userId, $p['name'], $p['phone'] ?? null, $p['note'] ?? null
                ]);
                $peopleMap[(int)$p['id']] = (int)Database::lastInsertId();
            }
        }

        // 4. Restore Transactions
        $txCount = 0;
        foreach ($backup['transactions'] as $t) {
            $accId = $accountMap[(int)$t['account_id']] ?? null;
            if (!$accId) continue;

            $toAccId = !empty($t['to_account_id']) ? ($accountMap[(int)$t['to_account_id']] ?? null) : null;
            $catId = !empty($t['category_id']) ? ($categoryMap[(int)$t['category_id']] ?? null) : null;
            $personId = !empty($t['person_id']) ? ($peopleMap[(int)$t['person_id']] ?? null) : null;

            $sql = "
                INSERT INTO transactions (
                    user_id, type, account_id, to_account_id, category_id, person_id,
                    amount, tx_date, note, tags, dedupe_hash, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?
                )
            ";

            Database::query($sql, [
                $userId,
                $t['type'],
                $accId,
                $toAccId,
                $catId,
                $personId,
                (int)$t['amount'],
                $t['tx_date'],
                $t['note'] ?? '',
                $t['tags'] ?? '',
                $t['dedupe_hash'] ?? null,
                $t['created_at'] ?? date('Y-m-d H:i:s'),
                $t['updated_at'] ?? date('Y-m-d H:i:s'),
            ]);
            $txCount++;
        }

        // 5. Restore Budgets
        if (!empty($backup['budgets'])) {
            foreach ($backup['budgets'] as $b) {
                $catId = !empty($b['category_id']) ? ($categoryMap[(int)$b['category_id']] ?? null) : null;
                Database::query(
                    "INSERT INTO budgets (user_id, category_id, period_start, amount, warn_percent) VALUES (?, ?, ?, ?, ?)",
                    [$userId, $catId, $b['period_start'], (int)$b['amount'], (int)($b['warn_percent'] ?? 80)]
                );
            }
        }

        // 6. Restore Goals & Contributions
        $goalMap = [];
        if (!empty($backup['savings_goals'])) {
            foreach ($backup['savings_goals'] as $g) {
                Database::query(
                    "INSERT INTO savings_goals (user_id, name, target_amount, target_date, color, icon, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$userId, $g['name'], (int)$g['target_amount'], $g['target_date'] ?? null, $g['color'] ?? '#12B76A', $g['icon'] ?? null, $g['status'] ?? 'active']
                );
                $goalMap[(int)$g['id']] = (int)Database::lastInsertId();
            }

            if (!empty($backup['goal_contributions'])) {
                foreach ($backup['goal_contributions'] as $gc) {
                    $newGoalId = $goalMap[(int)$gc['goal_id']] ?? null;
                    if ($newGoalId) {
                        Database::query(
                            "INSERT INTO goal_contributions (goal_id, user_id, amount, contributed_on, note) VALUES (?, ?, ?, ?, ?)",
                            [$newGoalId, $userId, (int)$gc['amount'], $gc['contributed_on'], $gc['note'] ?? '']
                        );
                    }
                }
            }
        }

        // 7. Restore Debts & Payments
        $debtMap = [];
        if (!empty($backup['debts'])) {
            foreach ($backup['debts'] as $d) {
                $personId = !empty($d['person_id']) ? ($peopleMap[(int)$d['person_id']] ?? null) : null;
                if (!$personId && !empty($peopleMap)) {
                    $personId = reset($peopleMap);
                }
                if ($personId) {
                    Database::query(
                        "INSERT INTO debts (user_id, person_id, direction, principal, opened_on, due_on, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$userId, $personId, $d['direction'], (int)$d['principal'], $d['opened_on'], $d['due_on'] ?? null, $d['note'] ?? '', $d['status'] ?? 'open']
                    );
                    $debtMap[(int)$d['id']] = (int)Database::lastInsertId();
                }
            }

            if (!empty($backup['debt_payments'])) {
                foreach ($backup['debt_payments'] as $dp) {
                    $newDebtId = $debtMap[(int)$dp['debt_id']] ?? null;
                    if ($newDebtId) {
                        Database::query(
                            "INSERT INTO debt_payments (debt_id, user_id, amount, paid_on, note) VALUES (?, ?, ?, ?, ?)",
                            [$newDebtId, $userId, (int)$dp['amount'], $dp['paid_on'], $dp['note'] ?? '']
                        );
                    }
                }
            }
        }

        // 8. Restore Recurring Rules
        if (!empty($backup['recurring_rules'])) {
            foreach ($backup['recurring_rules'] as $r) {
                $accId = $accountMap[(int)$r['account_id']] ?? null;
                $catId = !empty($r['category_id']) ? ($categoryMap[(int)$r['category_id']] ?? null) : null;
                if ($accId) {
                    Database::query(
                        "INSERT INTO recurring_rules (user_id, type, account_id, category_id, amount, note, frequency, interval_n, next_run, end_date, auto_post, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $userId, $r['type'], $accId, $catId, (int)$r['amount'], $r['note'] ?? '',
                            $r['frequency'] ?? 'monthly', (int)($r['interval_n'] ?? 1), $r['next_run'],
                            $r['end_date'] ?? null, (int)($r['auto_post'] ?? 1), (int)($r['is_active'] ?? 1)
                        ]
                    );
                }
            }
        }

        return [
            'ok' => true,
            'accounts_restored' => count($accountMap),
            'categories_restored' => count($categoryMap),
            'transactions_restored' => $txCount,
        ];
    }
}
