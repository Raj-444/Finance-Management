<?php
declare(strict_types=1);

/**
 * Phase 3 Automated Test Suite
 * Tests Budgets, Forecast Engine, Debt Tracker, Savings Goals, Recurring Rules, and Notifications
 */

require_once dirname(__DIR__) . '/finance_app/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/finance_app/app/Helpers/helpers.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\SavingsGoal;
use App\Models\GoalContribution;
use App\Models\RecurringRule;
use App\Models\Notification;
use App\Services\BudgetForecastService;
use App\Services\AnalyticsService;

class Phase3Test
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "\n=== Running Phase 3 Test Suite ===\n\n";

        $dbPath = dirname(__DIR__) . '/finance_app/storage/database.sqlite';
        Database::setConfig([
            'driver' => 'sqlite',
            'database' => $dbPath,
        ]);

        $this->testBudgetForecastAndAlerts();
        $this->testDebtTrackerAndRepayments();
        $this->testSavingsGoalsAndContributions();
        $this->testRecurringRulesAndAdvance();
        $this->testNotificationService();
        $this->testAnalyticsService();

        echo "\n----------------------------------------\n";
        echo "Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "----------------------------------------\n\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName}\n";
            $this->failed++;
        }
    }

    private function testBudgetForecastAndAlerts(): void
    {
        echo "--- Testing Budgets & Forecast Engine ---\n";

        // Create test user
        $testEmail = 'phase3_budget_' . time() . '@test.com';
        $userId = User::create([
            'email' => $testEmail,
            'password' => 'secret123',
            'name' => 'Budget Tester',
            'currency' => 'BDT',
            'month_start_day' => 1,
        ]);

        $accountId = Account::create($userId, [
            'name' => 'Budget Test Account',
            'type' => 'bank',
            'opening_balance' => 5000000,
        ]);

        $catId = Category::create($userId, [
            'name' => 'Groceries Test',
            'type' => 'expense',
            'color' => '#12B76A',
        ]);

        [$periodStart, $periodEnd, $daysInPeriod, $elapsedDays] = BudgetForecastService::calculatePeriodDates(1);

        // 1. Set budget of 10,000 BDT (1,000,000 paisa)
        $budgetId = Budget::createOrUpdate($userId, [
            'category_id' => $catId,
            'period_start' => $periodStart,
            'amount' => 1000000, // 10,000 BDT
            'warn_percent' => 80,
        ]);

        $this->assert($budgetId > 0, "Budget created successfully");

        // 2. Spend 8,500 BDT (850,000 paisa) -> 85% spent (should trigger warn)
        Transaction::create($userId, [
            'type' => 'expense',
            'account_id' => $accountId,
            'category_id' => $catId,
            'amount' => 850000,
            'tx_date' => date('Y-m-d'),
            'note' => 'Big Grocery run',
        ]);

        $analyses = BudgetForecastService::evaluateAllForUser($userId);
        $this->assert(!empty($analyses), "Budget analysis evaluated");
        $analysis = $analyses[0];

        $this->assert($analysis['spent_amount'] === 850000, "Spent amount correctly tracks 8,500 BDT");
        $this->assert($analysis['spent_percent'] === 85.0, "Spent percentage calculated as 85.0%");
        $this->assert(in_array($analysis['level'], ['warn', 'forecast_exceeded']), "Budget triggered warning or forecast warning");

        // 3. Deduplication check: re-running should not duplicate notification
        $unreadCount1 = Notification::getUnreadCount($userId);
        BudgetForecastService::evaluateAllForUser($userId);
        $unreadCount2 = Notification::getUnreadCount($userId);
        $this->assert($unreadCount1 === $unreadCount2, "Notification deduplication prevents duplicate alerts ({$unreadCount1} == {$unreadCount2})");

        // 4. Overspend to 11,000 BDT (exceeded)
        Transaction::create($userId, [
            'type' => 'expense',
            'account_id' => $accountId,
            'category_id' => $catId,
            'amount' => 250000, // +2,500 BDT -> total 11,000 BDT
            'tx_date' => date('Y-m-d'),
            'note' => 'More groceries',
        ]);

        $analysesAfter = BudgetForecastService::evaluateAllForUser($userId);
        $this->assert($analysesAfter[0]['level'] === 'exceeded', "Budget level advanced to 'exceeded'");
        $this->assert($analysesAfter[0]['is_over_budget'] === true, "is_over_budget flag set to true");
        $this->assert($analysesAfter[0]['remaining_amount'] === -100000, "Negative remaining amount indicates 1,000 BDT deficit");
    }

    private function testDebtTrackerAndRepayments(): void
    {
        echo "--- Testing Debt / Loan Tracker & Settlements ---\n";

        $userId = User::create([
            'email' => 'phase3_debt_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Debt Tester',
        ]);

        $personId = \App\Models\Person::findOrCreateByName($userId, 'Arif Khan');

        // 1. Create lent record: 5,000 BDT (500,000 paisa)
        $debtId = Debt::create($userId, [
            'person_id' => $personId,
            'direction' => 'lent',
            'principal' => 500000,
            'opened_on' => date('Y-m-d'),
            'due_on' => date('Y-m-d', strtotime('+30 days')),
            'note' => 'Emergency loan for phone repair',
        ]);

        $this->assert($debtId > 0, "Debt record created with ID {$debtId}");

        $debt = Debt::findById($debtId, $userId);
        $this->assert($debt['balance'] === 500000, "Initial debt balance is 5,000 BDT");
        $this->assert($debt['status'] === 'open', "Debt initial status is open");

        // 2. Record partial payment of 2,000 BDT (200,000 paisa)
        DebtPayment::record($debtId, $userId, 200000, date('Y-m-d'), 'First installment via bKash');

        $debtAfterPay1 = Debt::findById($debtId, $userId);
        $this->assert($debtAfterPay1['total_paid'] === 200000, "Total paid updated to 2,000 BDT");
        $this->assert($debtAfterPay1['balance'] === 300000, "Remaining balance updated to 3,000 BDT");
        $this->assert($debtAfterPay1['status'] === 'open', "Debt remains open after partial payment");

        // 3. Record remaining payment of 3,000 BDT (300,000 paisa) -> should auto-settle
        DebtPayment::record($debtId, $userId, 300000, date('Y-m-d'), 'Final installment');

        $debtSettled = Debt::findById($debtId, $userId);
        $this->assert($debtSettled['balance'] === 0, "Balance reached 0 BDT");
        $this->assert($debtSettled['status'] === 'settled', "Debt auto-settled to 'settled' status");

        // 4. Summary check
        $summary = Debt::getSummary($userId);
        $this->assert($summary['total_lent_open'] === 0, "Open lent summary reflects zero remaining open debt");
    }

    private function testSavingsGoalsAndContributions(): void
    {
        echo "--- Testing Savings Goals & Progress Tracking ---\n";

        $userId = User::create([
            'email' => 'phase3_goal_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Goal Tester',
        ]);

        // 1. Create Goal "MacBook Fund", target 100,000 BDT (10,000,000 paisa)
        $goalId = SavingsGoal::create($userId, [
            'name' => 'MacBook Fund',
            'target_amount' => 10000000,
            'target_date' => date('Y-m-d', strtotime('+6 months')),
            'color' => '#5B5BD6',
        ]);

        $this->assert($goalId > 0, "Savings goal created with ID {$goalId}");

        $goal = SavingsGoal::findById($goalId, $userId);
        $this->assert((float)$goal['percent'] == 0.0, "Initial savings percent is 0%");
        $this->assert($goal['remaining'] === 10000000, "Remaining amount is full 100,000 BDT");

        // 2. Deposit 40,000 BDT (4,000,000 paisa)
        GoalContribution::record($goalId, $userId, 4000000, date('Y-m-d'), 'Bonus deposit');

        $goalAfterDep1 = SavingsGoal::findById($goalId, $userId);
        $this->assert($goalAfterDep1['current_saved'] === 4000000, "Current saved is 40,000 BDT");
        $this->assert((float)$goalAfterDep1['percent'] == 40.0, "Progress reached 40.0%");
        $this->assert($goalAfterDep1['status'] === 'active', "Goal status remains active");

        // 3. Deposit remaining 60,000 BDT (6,000,000 paisa) -> should auto-achieve
        GoalContribution::record($goalId, $userId, 6000000, date('Y-m-d'), 'Final savings target');

        $goalAchieved = SavingsGoal::findById($goalId, $userId);
        $this->assert((float)$goalAchieved['percent'] == 100.0, "Progress reached 100.0%");
        $this->assert($goalAchieved['status'] === 'achieved', "Goal status automatically updated to 'achieved'");
    }

    private function testRecurringRulesAndAdvance(): void
    {
        echo "--- Testing Recurring Rules & Auto-Advance ---\n";

        $userId = User::create([
            'email' => 'phase3_recur_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Recurring Tester',
        ]);

        $accountId = Account::create($userId, ['name' => 'Recur Account', 'type' => 'bank']);
        $catId = Category::create($userId, ['name' => 'Utilities', 'type' => 'expense']);

        // 1. Create monthly rule
        $ruleId = RecurringRule::create($userId, [
            'type' => 'expense',
            'account_id' => $accountId,
            'category_id' => $catId,
            'amount' => 150000, // 1,500 BDT
            'note' => 'Internet Bill',
            'frequency' => 'monthly',
            'interval_n' => 1,
            'next_run' => '2026-10-10',
            'auto_post' => 1,
            'is_active' => 1,
        ]);

        $this->assert($ruleId > 0, "Recurring rule created with ID {$ruleId}");

        // 2. Advance Next Run
        $nextDate = RecurringRule::advanceNextRun($ruleId, '2026-10-10', 'monthly', 1);
        $this->assert($nextDate === '2026-11-10', "Monthly advance shifted next_run from 2026-10-10 to 2026-11-10");

        // 3. Test upcoming recurring calculation for forecast
        $upcoming = RecurringRule::getUpcomingRecurringExpenseAmount($userId, '2026-11-01', '2026-11-30', $catId);
        $this->assert($upcoming === 150000, "Upcoming recurring expense in November accurately includes 1,500 BDT");

        // 4. Toggle active
        RecurringRule::toggleActive($ruleId, $userId);
        $rule = RecurringRule::findById($ruleId, $userId);
        $this->assert((int)$rule['is_active'] === 0, "Recurring rule successfully paused/toggled inactive");
    }

    private function testNotificationService(): void
    {
        echo "--- Testing Notifications Engine ---\n";

        $userId = User::create([
            'email' => 'phase3_notif_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Notif Tester',
        ]);

        // Create alert
        $notifId = Notification::createIfNotExists(
            $userId,
            'system_info',
            null,
            'Budget Warning',
            'You have spent 85% of your food budget.',
            '/budgets'
        );

        $this->assert($notifId !== null && $notifId > 0, "Notification record created");
        $this->assert(Notification::getUnreadCount($userId) === 1, "Unread count is 1");

        // Mark single as read
        Notification::markAsRead($notifId, $userId);
        $this->assert(Notification::getUnreadCount($userId) === 0, "Unread count is 0 after markAsRead");
    }

    private function testAnalyticsService(): void
    {
        echo "--- Testing Analytics Aggregation Service ---\n";

        $userId = User::create([
            'email' => 'phase3_analytics_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Analytics Tester',
        ]);

        $accountId = Account::create($userId, ['name' => 'Main Bank', 'type' => 'bank']);
        $foodCat = Category::create($userId, ['name' => 'Food & Dining', 'type' => 'expense']);
        $salaryCat = Category::create($userId, ['name' => 'Salary', 'type' => 'income']);

        $today = date('Y-m-d');
        // Add income: 50,000 BDT
        Transaction::create($userId, [
            'type' => 'income',
            'account_id' => $accountId,
            'category_id' => $salaryCat,
            'amount' => 5000000,
            'tx_date' => $today,
            'note' => 'Monthly Salary',
        ]);

        // Add expense: 5,000 BDT
        Transaction::create($userId, [
            'type' => 'expense',
            'account_id' => $accountId,
            'category_id' => $foodCat,
            'amount' => 500000,
            'tx_date' => $today,
            'note' => 'Restaurant dinner',
        ]);

        // 1. Category Breakdown
        $breakdown = AnalyticsService::getCategoryBreakdown($userId, date('Y-m-01'), date('Y-m-t'), 'expense');
        $this->assert(count($breakdown) === 1, "Category breakdown returns 1 category");
        $this->assert((int)$breakdown[0]['total'] === 500000, "Category total is 5,000 BDT (500,000 paisa)");

        // 2. Daily Trend
        $trend = AnalyticsService::getDailyTrend($userId, date('Y-m-01'), date('Y-m-t'));
        $todayEntry = null;
        foreach ($trend as $t) {
            if ($t['day'] === $today) {
                $todayEntry = $t;
                break;
            }
        }
        $this->assert($todayEntry !== null, "Daily trend contains entry for today");
        $this->assert((int)$todayEntry['income_total'] === 5000000, "Daily trend reflects today's 50,000 BDT income");
        $this->assert((int)$todayEntry['expense_total'] === 500000, "Daily trend reflects today's 5,000 BDT expense");

        // 3. Monthly Comparison
        $monthly = AnalyticsService::getMonthlyComparison($userId, 3);
        $this->assert(!empty($monthly), "Monthly comparison returns records for last 3 months");
    }
}

// Run test
(new Phase3Test())->run();
