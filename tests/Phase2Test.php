<?php
declare(strict_types=1);

/**
 * Phase 2 Automated Test Suite
 * Tests Accounts, Categories, Transactions, Balance Maths, Transfers, and IDOR protection
 */

require_once dirname(__DIR__) . '/finance_app/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once dirname(__DIR__) . '/finance_app/app/Helpers/helpers.php';

use App\Core\Database;
use App\Models\User;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;

class Phase2Test
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "\n=== Running Phase 2 Test Suite ===\n\n";

        // Setup in-memory SQLite database
        $dbPath = dirname(__DIR__) . '/finance_app/storage/database.sqlite';
        Database::setConfig([
            'driver' => 'sqlite',
            'database' => $dbPath,
        ]);

        $this->testBalanceMathAndTransfers();
        $this->testIdorProtection();
        $this->testPaginationAndFiltering();
        $this->testSoftDeleteAndRestore();

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

    private function testBalanceMathAndTransfers(): void
    {
        // Create test user 1
        $user1Id = User::create([
            'name' => 'Math Test User',
            'email' => 'math_test_' . uniqid() . '@example.com',
            'password' => 'TestPass123!',
        ]);

        // Create Account A: Opening balance ৳ 1,000.00 (100,000 paisa)
        $accAId = Account::create($user1Id, [
            'name' => 'Test Account A',
            'type' => 'bank',
            'opening_balance' => 100000,
        ]);

        // Create Account B: Opening balance ৳ 500.00 (50,000 paisa)
        $accBId = Account::create($user1Id, [
            'name' => 'Test Account B',
            'type' => 'cash',
            'opening_balance' => 50000,
        ]);

        // 1. Initial Balances
        $accA = Account::findById($accAId, $user1Id);
        $accB = Account::findById($accBId, $user1Id);
        $this->assert((int)$accA['current_balance'] === 100000, "Initial Account A balance is 100000");
        $this->assert((int)$accB['current_balance'] === 50000, "Initial Account B balance is 50000");

        // 2. Add Income ৳ 200.00 (20,000 paisa) to Account A
        Transaction::create($user1Id, [
            'type' => 'income',
            'account_id' => $accAId,
            'amount' => 20000,
            'tx_date' => date('Y-m-d'),
        ]);

        $accA = Account::findById($accAId, $user1Id);
        $this->assert((int)$accA['current_balance'] === 120000, "Income adds to Account A (100000 + 20000 = 120000)");

        // 3. Add Expense ৳ 300.00 (30,000 paisa) from Account A
        Transaction::create($user1Id, [
            'type' => 'expense',
            'account_id' => $accAId,
            'amount' => 30000,
            'tx_date' => date('Y-m-d'),
        ]);

        $accA = Account::findById($accAId, $user1Id);
        $this->assert((int)$accA['current_balance'] === 90000, "Expense subtracts from Account A (120000 - 30000 = 90000)");

        // 4. Transfer ৳ 400.00 (40,000 paisa) from Account A to Account B
        Transaction::create($user1Id, [
            'type' => 'transfer',
            'account_id' => $accAId,
            'to_account_id' => $accBId,
            'amount' => 40000,
            'tx_date' => date('Y-m-d'),
        ]);

        $accA = Account::findById($accAId, $user1Id);
        $accB = Account::findById($accBId, $user1Id);
        $this->assert((int)$accA['current_balance'] === 50000, "Transfer subtracts from source Account A (90000 - 40000 = 50000)");
        $this->assert((int)$accB['current_balance'] === 90000, "Transfer adds to destination Account B (50000 + 40000 = 90000)");

        // Total user net balance should be 50000 + 90000 = 140000
        $totalBal = Account::getTotalBalance($user1Id);
        $this->assert($totalBal === 140000, "Net total across accounts equals 140000");
    }

    private function testIdorProtection(): void
    {
        // User A
        $userAId = User::create([
            'name' => 'User Alice',
            'email' => 'alice_' . uniqid() . '@example.com',
            'password' => 'Pass123456!',
        ]);
        $accAId = Account::create($userAId, ['name' => 'Alice Private Wallet', 'opening_balance' => 100000]);
        $txAId = Transaction::create($userAId, [
            'type' => 'expense',
            'account_id' => $accAId,
            'amount' => 15000,
            'tx_date' => date('Y-m-d'),
            'note' => 'Alice Secret Purchase',
        ]);

        // User B
        $userBId = User::create([
            'name' => 'User Bob',
            'email' => 'bob_' . uniqid() . '@example.com',
            'password' => 'Pass123456!',
        ]);

        // User B attempts to read User A's transaction
        $stolenTx = Transaction::findById($txAId, $userBId);
        $this->assert($stolenTx === null, "IDOR: User B cannot read User A's transaction");

        // User B attempts to read User A's account
        $stolenAcc = Account::findById($accAId, $userBId);
        $this->assert($stolenAcc === null, "IDOR: User B cannot read User A's account");

        // User B attempts to delete User A's transaction
        $hackedDelete = Transaction::softDelete($txAId, $userBId);
        $this->assert(!$hackedDelete, "IDOR: User B cannot delete User A's transaction");

        // Verify transaction is still alive for Alice
        $aliceTx = Transaction::findById($txAId, $userAId);
        $this->assert($aliceTx !== null, "Alice transaction remains safe and intact");
    }

    private function testPaginationAndFiltering(): void
    {
        $userId = User::create([
            'name' => 'Filter User',
            'email' => 'filter_' . uniqid() . '@example.com',
            'password' => 'Pass123456!',
        ]);
        $accId = Account::create($userId, ['name' => 'Filter Acc', 'opening_balance' => 0]);
        $catFoodId = Category::create($userId, ['name' => 'Food Filter', 'type' => 'expense']);
        $catTravelId = Category::create($userId, ['name' => 'Travel Filter', 'type' => 'expense']);

        // Create 3 food transactions and 2 travel transactions
        for ($i = 1; $i <= 3; $i++) {
            Transaction::create($userId, [
                'type' => 'expense',
                'account_id' => $accId,
                'category_id' => $catFoodId,
                'amount' => 5000 * $i,
                'tx_date' => date('Y-m-d'),
                'note' => "Lunch item {$i}",
            ]);
        }
        for ($i = 1; $i <= 2; $i++) {
            Transaction::create($userId, [
                'type' => 'expense',
                'account_id' => $accId,
                'category_id' => $catTravelId,
                'amount' => 10000 * $i,
                'tx_date' => date('Y-m-d'),
                'note' => "Taxi ride {$i}",
            ]);
        }

        // Filter by category food
        $foodResult = Transaction::getPaginated($userId, ['category_id' => $catFoodId]);
        $this->assert($foodResult['total'] === 3, "Filter by category returns exact 3 food rows");

        // Search by keyword 'Taxi'
        $searchResult = Transaction::getPaginated($userId, ['search' => 'Taxi']);
        $this->assert($searchResult['total'] === 2, "Search by note keyword returns 2 matching rows");
    }

    private function testSoftDeleteAndRestore(): void
    {
        $userId = User::create([
            'name' => 'Undo User',
            'email' => 'undo_' . uniqid() . '@example.com',
            'password' => 'Pass123456!',
        ]);
        $accId = Account::create($userId, ['name' => 'Undo Acc', 'opening_balance' => 100000]);

        $txId = Transaction::create($userId, [
            'type' => 'expense',
            'account_id' => $accId,
            'amount' => 20000,
            'tx_date' => date('Y-m-d'),
        ]);

        // Balance before delete: 80000
        $acc = Account::findById($accId, $userId);
        $this->assert((int)$acc['current_balance'] === 80000, "Balance reflects expense before deletion");

        // Soft delete
        Transaction::softDelete($txId, $userId);
        $accAfterDelete = Account::findById($accId, $userId);
        $this->assert((int)$accAfterDelete['current_balance'] === 100000, "Soft-deleted expense does not deduct from balance");

        // Restore (Undo)
        Transaction::restore($txId, $userId);
        $accAfterRestore = Account::findById($accId, $userId);
        $this->assert((int)$accAfterRestore['current_balance'] === 80000, "Restoring transaction restores balance deduction");
    }
}

$test = new Phase2Test();
$test->run();
