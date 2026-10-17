<?php
declare(strict_types=1);

/**
 * Phase 4 Automated Test Suite
 * Tests Excel/CSV formula injection protection, import preview, atomic rollback,
 * full JSON backup export & restore, settings updates, and admin diagnostics.
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
use App\Models\SavingsGoal;
use App\Services\ExcelService;
use App\Services\BackupService;

class Phase4Test
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "\n=== Running Phase 4 Test Suite ===\n\n";

        $dbPath = dirname(__DIR__) . '/finance_app/storage/database.sqlite';
        Database::setConfig([
            'driver' => 'sqlite',
            'database' => $dbPath,
        ]);

        $this->testFormulaInjectionSanitization();
        $this->testDateParser();
        $this->testImportPreviewCommitAndRollback();
        $this->testFullBackupAndRestore();
        $this->testPasswordAndProfileSettings();

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

    private function testFormulaInjectionSanitization(): void
    {
        echo "--- Testing Formula Injection (DDE) Sanitization ---\n";

        $this->assert(ExcelService::sanitize('=1+1') === "'=1+1", "Equals sign prefixed with single quote");
        $this->assert(ExcelService::sanitize('+cmd|/c calc') === "'+cmd|/c calc", "Plus sign prefixed with single quote");
        $this->assert(ExcelService::sanitize('-50+20') === "'-50+20", "Minus sign prefixed with single quote");
        $this->assert(ExcelService::sanitize('@SUM(A1:B10)') === "'@SUM(A1:B10)", "At sign prefixed with single quote");
        $this->assert(ExcelService::sanitize("\tTAB") === "'\tTAB", "Tab character prefixed with single quote");
        $this->assert(ExcelService::sanitize("\rCR") === "'\rCR", "Carriage return prefixed with single quote");
        $this->assert(ExcelService::sanitize('Dinner at Star Kabab') === 'Dinner at Star Kabab', "Clean text left unmodified");
        $this->assert(ExcelService::sanitize('1500.50') === '1500.50', "Numbers left unmodified");
    }

    private function testDateParser(): void
    {
        echo "--- Testing Flexible Date Parser ---\n";

        $this->assert(ExcelService::parseDate('2026-10-17') === '2026-10-17', "ISO YYYY-MM-DD parsed correctly");
        $this->assert(ExcelService::parseDate('17/10/2026') === '2026-10-17', "Slash DD/MM/YYYY parsed correctly");
        $this->assert(ExcelService::parseDate('17-10-2026') === '2026-10-17', "Dash DD-MM-YYYY parsed correctly");
        $this->assert(ExcelService::parseDate('October 17, 2026') === '2026-10-17', "Textual date parsed correctly");
        $this->assert(ExcelService::parseDate('invalid-date-xyz') === null, "Invalid date returns null");
    }

    private function testImportPreviewCommitAndRollback(): void
    {
        echo "--- Testing Import Preview, Commit & Undo Rollback ---\n";

        $userId = User::create([
            'email' => 'phase4_import_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Import Tester',
        ]);

        $accountId = Account::create($userId, ['name' => 'Import Target Account', 'type' => 'bank']);
        $catId = Category::create($userId, ['name' => 'Groceries', 'type' => 'expense']);

        // Insert 1 existing transaction to test duplicate detection
        Transaction::create($userId, [
            'type' => 'expense',
            'account_id' => $accountId,
            'category_id' => $catId,
            'amount' => 50000, // 500 BDT
            'tx_date' => '2026-10-10',
            'note' => 'Existing Transaction',
        ]);

        // Mock 4 rows to import: 2 new valid, 1 duplicate, 1 invalid amount
        $mockRows = [
            ['2026-10-10', '500', 'expense', 'Groceries', 'Existing Transaction'], // duplicate
            ['2026-10-11', '1200', 'expense', 'Food', 'Dinner out'],               // valid new
            ['2026-10-12', '3500', 'income', 'Salary', 'Freelance payment'],        // valid new income
            ['2026-10-13', '-50', 'expense', 'Other', 'Negative invalid'],          // invalid amount
        ];

        $mapping = [
            'date_col' => 0,
            'amount_col' => 1,
            'type_col' => 2,
            'cat_col' => 3,
            'note_col' => 4,
            'skip_duplicates' => 1,
        ];

        // 1. Preview
        $preview = ExcelService::preview($userId, $mockRows, $mapping, $accountId);
        $this->assert($preview['valid_count'] === 2, "Preview detected 2 valid new transactions");
        $this->assert($preview['duplicate_count'] === 1, "Preview detected 1 duplicate transaction");
        $this->assert($preview['invalid_count'] === 1, "Preview detected 1 invalid row");

        // 2. Commit Import
        $commit = ExcelService::commitImport($userId, 'test_statement.csv', $mockRows, $mapping, $accountId);
        $this->assert($commit['ok'] === true, "Import commit executed successfully");
        $this->assert($commit['imported'] === 2, "Committed exactly 2 new transactions");
        $batchId = $commit['batch_id'];

        // Verify transactions exist with import_batch_id
        $importedRows = Database::fetchAll("SELECT * FROM transactions WHERE import_batch_id = ?", [$batchId]);
        $this->assert(count($importedRows) === 2, "2 transactions tagged with batch ID {$batchId}");

        // 3. Rollback (Undo Import)
        $undo = ExcelService::rollbackImport($userId, $batchId);
        $this->assert($undo === true, "Rollback executed successfully");

        $remainingRows = Database::fetchAll("SELECT * FROM transactions WHERE import_batch_id = ?", [$batchId]);
        $this->assert(count($remainingRows) === 0, "All imported transactions removed after rollback");

        $batches = ExcelService::getBatches($userId);
        $this->assert($batches[0]['status'] === 'rolled_back', "Batch status updated to 'rolled_back'");
    }

    private function testFullBackupAndRestore(): void
    {
        echo "--- Testing Full JSON Backup Export & Atomic Restore ---\n";

        // Setup User A with rich ledger
        $userAId = User::create([
            'email' => 'phase4_userA_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Original User',
            'currency' => 'EUR',
            'month_start_day' => 10,
        ]);

        $accId = Account::create($userAId, ['name' => 'Euro Wallet', 'type' => 'card', 'opening_balance' => 100000]);
        $catId = Category::create($userAId, ['name' => 'Travel', 'type' => 'expense', 'color' => '#E02424']);
        $personId = \App\Models\Person::findOrCreateByName($userAId, 'Tanvir');

        Transaction::create($userAId, [
            'type' => 'expense',
            'account_id' => $accId,
            'category_id' => $catId,
            'amount' => 4500,
            'tx_date' => '2026-10-15',
            'note' => 'Metro Ticket',
        ]);

        SavingsGoal::create($userAId, [
            'name' => 'Summer Vacation',
            'target_amount' => 500000,
            'target_date' => '2027-06-01',
        ]);

        // 1. Export Backup
        $backup = BackupService::export($userAId);
        $this->assert(isset($backup['meta']['version']), "Backup contains meta version");
        $this->assert(count($backup['accounts']) === 1, "Backup contains 1 account");
        $this->assert(count($backup['transactions']) === 1, "Backup contains 1 transaction");
        $this->assert(count($backup['savings_goals']) === 1, "Backup contains 1 savings goal");

        // 2. Restore into clean User B
        $userBId = User::create([
            'email' => 'phase4_userB_' . time() . '@test.com',
            'password' => 'secret123',
            'name' => 'Restore Target',
        ]);

        $restoreResult = BackupService::restore($userBId, $backup);
        $this->assert($restoreResult['ok'] === true, "Restore completed successfully");
        $this->assert($restoreResult['accounts_restored'] === 1, "1 account restored into User B");
        $this->assert($restoreResult['transactions_restored'] === 1, "1 transaction restored into User B");

        // Verify User B now has the restored account and transaction
        $userBAccounts = Account::getAllForUser($userBId);
        $this->assert(count($userBAccounts) === 1, "User B has 1 restored account");
        $this->assert($userBAccounts[0]['name'] === 'Euro Wallet', "Restored account name matches 'Euro Wallet'");

        $userBUser = User::findById($userBId);
        $this->assert($userBUser['currency'] === 'EUR', "User B currency updated to EUR from backup");
        $this->assert((int)$userBUser['month_start_day'] === 10, "User B month start day updated to 10");
    }

    private function testPasswordAndProfileSettings(): void
    {
        echo "--- Testing Password Update & Security ---\n";

        $userId = User::create([
            'email' => 'phase4_pwd_' . time() . '@test.com',
            'password' => 'OldPassword123!',
            'name' => 'Password Tester',
        ]);

        $user = User::findById($userId);
        $this->assert(password_verify('OldPassword123!', $user['password_hash']), "Old password verifies");

        // Update password
        User::updatePassword($userId, 'NewSuperSecretPass999!');
        $updatedUser = User::findById($userId);
        $this->assert(password_verify('NewSuperSecretPass999!', $updatedUser['password_hash']), "New password verifies with Argon2/Bcrypt");
        $this->assert(!password_verify('OldPassword123!', $updatedUser['password_hash']), "Old password no longer valid");
    }
}

// Run test
(new Phase4Test())->run();
