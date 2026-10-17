<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\User;
use App\Models\Budget;
use App\Models\Notification;
use App\Models\RecurringRule;

class BudgetForecastService
{
    /**
     * Compute budget forecast and evaluate alerts for a user
     */
    public static function evaluateAllForUser(int $userId): array
    {
        $user = User::findById($userId);
        if (!$user) {
            return [];
        }

        $monthStartDay = (int)($user['month_start_day'] ?? 1);
        [$periodStart, $periodEnd, $daysInPeriod, $elapsedDays] = self::calculatePeriodDates($monthStartDay);

        $budgets = Budget::getAllForPeriod($userId, $periodStart);
        $results = [];

        foreach ($budgets as $budget) {
            $analysis = self::analyzeBudget($user, $budget, $periodStart, $periodEnd, $daysInPeriod, $elapsedDays);
            $results[] = $analysis;

            // Trigger alert checks
            self::checkAndTriggerAlerts($user, $budget, $analysis);
        }

        return $results;
    }

    /**
     * Calculate period boundaries based on month_start_day
     */
    public static function calculatePeriodDates(int $monthStartDay): array
    {
        $today = new \DateTime();
        $currentDay = (int)$today->format('j');
        $currentMonth = (int)$today->format('n');
        $currentYear = (int)$today->format('Y');

        if ($currentDay >= $monthStartDay) {
            $startDate = new \DateTime(sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $monthStartDay));
            $endDate = clone $startDate;
            $endDate->modify('+1 month -1 day');
        } else {
            $startDate = new \DateTime(sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $monthStartDay));
            $startDate->modify('-1 month');
            $endDate = clone $startDate;
            $endDate->modify('+1 month -1 day');
        }

        $daysInPeriod = (int)$startDate->diff($endDate)->days + 1;
        $elapsedDays = max(1, (int)$startDate->diff($today)->days + 1);

        return [
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $daysInPeriod,
            $elapsedDays
        ];
    }

    /**
     * Analyze a single budget: spent, projected, percentage, status
     */
    public static function analyzeBudget(
        array $user,
        array $budget,
        string $periodStart,
        string $periodEnd,
        int $daysInPeriod,
        int $elapsedDays
    ): array {
        $userId = (int)$user['id'];
        $budgetId = (int)$budget['id'];
        $budgetAmount = (int)$budget['amount'];
        $warnPercent = (int)$budget['warn_percent'];
        $categoryId = !empty($budget['category_id']) ? (int)$budget['category_id'] : null;

        // Calculate actual spent so far
        $catCondition = ($categoryId !== null) ? "AND category_id = ?" : "";
        $params = [$userId, $periodStart, $periodEnd];
        if ($categoryId !== null) {
            $params[] = $categoryId;
        }

        $sql = "
            SELECT COALESCE(SUM(amount), 0) AS total_spent
            FROM transactions
            WHERE user_id = ? 
              AND type = 'expense'
              AND deleted_at IS NULL
              AND tx_date >= ?
              AND tx_date <= ?
              {$catCondition}
        ";

        $spentRow = Database::fetch($sql, $params);
        $spent = (int)($spentRow['total_spent'] ?? 0);

        // Upcoming recurring expenses
        $todayStr = date('Y-m-d');
        $upcomingRecurring = RecurringRule::getUpcomingRecurringExpenseAmount($userId, $todayStr, $periodEnd, $categoryId);

        // Run rate forecast projection
        $runRateSpend = ($spent / $elapsedDays) * $daysInPeriod;
        $projected = (int) round($runRateSpend + $upcomingRecurring);

        $spentPercent = ($budgetAmount > 0) ? round(($spent / $budgetAmount) * 100, 1) : 0;
        $projectedPercent = ($budgetAmount > 0) ? round(($projected / $budgetAmount) * 100, 1) : 0;
        $remaining = max(0, $budgetAmount - $spent);

        $status = 'normal'; // normal, warn, exceeded, forecast
        if ($spent > $budgetAmount) {
            $status = 'exceeded';
        } elseif ($spent >= ($budgetAmount * ($warnPercent / 100))) {
            $status = 'warn';
        } elseif ($projected > $budgetAmount) {
            $status = 'forecast';
        }

        return [
            'id' => $budgetId,
            'budget' => $budget,
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
            'category_name' => $budget['category_name'] ?? 'Overall Monthly Budget',
            'category_color' => $budget['category_color'] ?? '#5B5BD6',
            'amount' => $budgetAmount,
            'budget_amount' => $budgetAmount,
            'spent' => $spent,
            'spent_amount' => $spent,
            'remaining' => $remaining,
            'remaining_amount' => $budgetAmount - $spent,
            'projected' => $projected,
            'forecast_total' => $projected,
            'spent_percent' => $spentPercent,
            'projected_percent' => $projectedPercent,
            'forecast_percent' => $projectedPercent,
            'warn_percent' => $warnPercent,
            'status' => $status,
            'level' => $status,
            'is_over_budget' => ($spent > $budgetAmount),
            'forecast_over' => ($projected > $budgetAmount),
            'days_in_period' => $daysInPeriod,
            'elapsed_days' => $elapsedDays,
        ];
    }

    /**
     * Deduplicated Alert Triggering
     */
    private static function checkAndTriggerAlerts(array $user, array $budget, array $analysis): void
    {
        $userId = (int)$user['id'];
        $budgetId = (int)$budget['id'];
        $categoryName = $analysis['category_name'];
        $status = $analysis['status'];

        if ($status === 'normal') {
            return;
        }

        $title = '';
        $body = '';

        if ($status === 'exceeded') {
            $title = "Budget Exceeded: {$categoryName}";
            $body = "You have spent " . money($analysis['spent'], $user['currency']) . " exceeding your budget limit of " . money($analysis['amount'], $user['currency']) . ".";
            $notifId = Notification::createIfNotExists($userId, 'budget_exceeded', $budgetId, $title, $body, '/budgets');
            if ($notifId) {
                self::dispatchEmailAlert($user, $title, $body);
            }
        } elseif ($status === 'warn') {
            $title = "Budget Warning ({$analysis['warn_percent']}%): {$categoryName}";
            $body = "You have used {$analysis['spent_percent']}% of your budget (" . money($analysis['spent'], $user['currency']) . " of " . money($analysis['amount'], $user['currency']) . ").";
            $notifId = Notification::createIfNotExists($userId, 'budget_warn', $budgetId, $title, $body, '/budgets');
            if ($notifId) {
                self::dispatchEmailAlert($user, $title, $body);
            }
        } elseif ($status === 'forecast') {
            $title = "Spending Forecast Warning: {$categoryName}";
            $body = "At your current spending run-rate, you are projected to spend " . money($analysis['projected'], $user['currency']) . " which will exceed your budget of " . money($analysis['amount'], $user['currency']) . ".";
            $notifId = Notification::createIfNotExists($userId, 'budget_forecast', $budgetId, $title, $body, '/budgets');
            if ($notifId) {
                self::dispatchEmailAlert($user, $title, $body);
            }
        }
    }

    /**
     * Email dispatch respecting quiet hours and user settings
     */
    private static function dispatchEmailAlert(array $user, string $subject, string $message): void
    {
        if (empty($user['email_alerts'])) {
            return;
        }

        // Check quiet hours
        if (!empty($user['quiet_from']) && !empty($user['quiet_to'])) {
            $nowTime = date('H:i:s');
            $from = $user['quiet_from'];
            $to = $user['quiet_to'];

            if ($from < $to) {
                if ($nowTime >= $from && $nowTime <= $to) {
                    Logger::info("Email alert suppressed due to quiet hours for user {$user['id']}");
                    return;
                }
            } else {
                // Overnight quiet hours (e.g. 23:00 to 07:00)
                if ($nowTime >= $from || $nowTime <= $to) {
                    Logger::info("Email alert suppressed due to overnight quiet hours for user {$user['id']}");
                    return;
                }
            }
        }

        $html = "
            <div style='font-family: sans-serif; max-width: 520px; margin: 0 auto; padding: 24px; border: 1px solid #E2E8F0; border-radius: 12px;'>
                <h3 style='color: #F04438; margin-top: 0;'>{$subject}</h3>
                <p style='color: #334155; line-height: 1.6;'>{$message}</p>
                <div style='margin-top: 24px;'>
                    <a href='" . url('/budgets') . "' style='background: #5B5BD6; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600;'>View Budgets</a>
                </div>
            </div>
        ";

        Mailer::send($user['email'], $user['name'], $subject, $html);
    }
}
