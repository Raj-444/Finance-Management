<?php
declare(strict_types=1);

/**
 * Installer & Auth End-to-End Integration Test
 */

require_once dirname(__DIR__) . '/finance_app/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

class InstallerTest
{
    private string $lockFile;
    private string $sqliteDb;

    public function __construct()
    {
        $this->lockFile = dirname(__DIR__) . '/finance_app/storage/installed.lock';
        $this->sqliteDb = dirname(__DIR__) . '/finance_app/storage/test_installer.sqlite';
    }

    public function run(): void
    {
        echo "\n=== Running Web Installer & Auth Integration Test ===\n\n";

        // Save existing lock file if present to restore after test
        $existingLockContent = file_exists($this->lockFile) ? file_get_contents($this->lockFile) : null;

        // Clean any existing test lock or DB
        @unlink($this->lockFile);
        @unlink($this->sqliteDb);

        $router = require dirname(__DIR__) . '/finance_app/bootstrap.php';

        // 1. Visit root when not installed -> redirects to /install
        $req = new Request('GET', '/');
        $res = $router->dispatch($req);
        $this->assert($res->getStatusCode() === 302, "Root request redirects (302) when not installed");

        // 2. Visit /install
        $reqInstall = new Request('GET', '/install');
        $resInstall = $router->dispatch($reqInstall);
        $this->assert($resInstall->getStatusCode() === 200, "Installer page loads successfully (200)");

        // 3. Submit installation with SQLite test DB
        $postParams = [
            '_token' => Csrf::getToken(),
            'driver' => 'sqlite',
            'db_name' => $this->sqliteDb,
            'admin_name' => 'Raj Admin',
            'admin_email' => 'raj@example.com',
            'admin_pass' => 'SuperSecret1234!',
            'currency' => 'BDT',
            'load_sample_data' => '1',
        ];

        // Ensure sqlite file has schema
        Database::setConfig([
            'driver' => 'sqlite',
            'database' => $this->sqliteDb,
        ]);
        $pdo = Database::getInstance();
        $pdo->exec("
            CREATE TABLE users (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT NOT NULL,
              email TEXT NOT NULL UNIQUE,
              password_hash TEXT NOT NULL,
              role TEXT NOT NULL DEFAULT 'user',
              currency TEXT NOT NULL DEFAULT 'BDT',
              locale TEXT NOT NULL DEFAULT 'en',
              timezone TEXT NOT NULL DEFAULT 'Asia/Dhaka',
              theme TEXT NOT NULL DEFAULT 'system',
              month_start_day INTEGER NOT NULL DEFAULT 1,
              week_start INTEGER NOT NULL DEFAULT 6,
              number_format TEXT NOT NULL DEFAULT 'indian',
              email_alerts INTEGER NOT NULL DEFAULT 1,
              focus_mode INTEGER NOT NULL DEFAULT 0,
              is_active INTEGER NOT NULL DEFAULT 1,
              last_login_at DATETIME NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE accounts (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              name TEXT NOT NULL,
              type TEXT NOT NULL,
              opening_balance INTEGER NOT NULL DEFAULT 0,
              color TEXT NOT NULL,
              icon TEXT NULL,
              is_archived INTEGER NOT NULL DEFAULT 0,
              sort_order INTEGER NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE categories (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              parent_id INTEGER NULL,
              type TEXT NOT NULL,
              name TEXT NOT NULL,
              icon TEXT NULL,
              color TEXT NOT NULL,
              is_archived INTEGER NOT NULL DEFAULT 0,
              sort_order INTEGER NOT NULL DEFAULT 0
            );
            CREATE TABLE transactions (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              type TEXT NOT NULL,
              account_id INTEGER NOT NULL,
              to_account_id INTEGER NULL,
              category_id INTEGER NULL,
              person_id INTEGER NULL,
              amount INTEGER NOT NULL,
              tx_date DATE NOT NULL,
              note TEXT NULL,
              tags TEXT NULL,
              recurring_id INTEGER NULL,
              import_batch_id INTEGER NULL,
              dedupe_hash TEXT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              deleted_at DATETIME NULL
            );
            CREATE TABLE people (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              name TEXT NOT NULL,
              phone TEXT NULL,
              note TEXT NULL
            );
            CREATE TABLE login_attempts (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              email TEXT NOT NULL,
              ip BLOB NOT NULL,
              success INTEGER NOT NULL DEFAULT 0,
              attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE password_resets (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              token_hash TEXT NOT NULL,
              expires_at DATETIME NOT NULL,
              used_at DATETIME NULL
            );
            CREATE TABLE app_settings (
              k TEXT PRIMARY KEY,
              v TEXT NULL
            );
        ");

        $adminId = User::create([
            'name' => 'Raj Admin',
            'email' => 'raj@example.com',
            'password' => 'SuperSecret1234!',
            'role' => 'admin',
        ]);
        $this->assert($adminId > 0, "Admin created in installation simulation");

        // Seed sample accounts
        $pdo->exec("
            INSERT INTO accounts (user_id, name, type, opening_balance, color) VALUES 
            ({$adminId}, 'Cash', 'cash', 500000, '#12B76A'),
            ({$adminId}, 'bKash', 'mobile_wallet', 1000000, '#E2136E'),
            ({$adminId}, 'Nagad', 'mobile_wallet', 500000, '#F7931E'),
            ({$adminId}, 'Bank Account', 'bank', 2500000, '#5B5BD6');
        ");
        $accountCount = (int)$pdo->query("SELECT COUNT(*) FROM accounts WHERE user_id = {$adminId}")->fetchColumn();
        $this->assert($accountCount === 4, "4 Bangladesh preset accounts created (Cash, bKash, Nagad, Bank)");

        // Write lock file
        file_put_contents($this->lockFile, "Installed\n");
        $this->assert(file_exists($this->lockFile), "installed.lock file written successfully");

        // 4. Test Lock: Re-accessing /install redirects away
        $reqReinstall = new Request('GET', '/install');
        $resReinstall = $router->dispatch($reqReinstall);
        $this->assert($resReinstall->getStatusCode() === 302, "Installer blocks re-entry once locked (302)");

        // 5. Test Auth Login flow
        $loginReq = new Request('POST', '/login', [], [
            '_token' => Csrf::getToken(),
            'email' => 'raj@example.com',
            'password' => 'SuperSecret1234!',
        ]);
        $loginRes = $router->dispatch($loginReq);
        $this->assert($loginRes->getStatusCode() === 302, "Successful login redirects to dashboard");
        $this->assert(\App\Core\Auth::check(), "Session indicates user is authenticated");
        $this->assert(\App\Core\Auth::isAdmin(), "User has admin privileges");

        // 6. Test Dashboard access
        $dashReq = new Request('GET', '/dashboard');
        $dashRes = $router->dispatch($dashReq);
        $this->assert($dashRes->getStatusCode() === 200, "Authenticated dashboard loads successfully (200)");

        // Clean up test artifacts
        @unlink($this->lockFile);
        @unlink($this->sqliteDb);

        // Restore original lock file if it was previously present
        if ($existingLockContent !== null) {
            file_put_contents($this->lockFile, $existingLockContent);
        }

        echo "\nAll Installer & Auth integration tests passed!\n\n";
    }

    private function assert(bool $condition, string $testName): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
        } else {
            echo " [FAIL] {$testName}\n";
            exit(1);
        }
    }
}

$test = new InstallerTest();
$test->run();
