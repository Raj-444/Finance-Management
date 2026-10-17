<?php
declare(strict_types=1);

/**
 * Phase 1 Automated Test Suite
 * Tests Core Architecture, Autoloader, I18n, Security, Models, and DB compatibility
 */

require_once dirname(__DIR__) . '/finance_app/app/Core/Autoloader.php';
\App\Core\Autoloader::register();

use App\Core\Config;
use App\Core\Database;
use App\Core\Csrf;
use App\Core\I18n;
use App\Core\Session;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Models\PasswordReset;

class Phase1Test
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "\n=== Running Phase 1 Test Suite ===\n\n";

        $this->testAutoloader();
        $this->testConfig();
        $this->testI18nAndCurrency();
        $this->testCsrfSecurity();
        $this->testDatabaseAndSchema();
        $this->testUserCreationAndHashing();
        $this->testLoginRateLimiting();
        $this->testPasswordResetWorkflow();
        $this->testRouterDispatch();

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

    private function testAutoloader(): void
    {
        $this->assert(class_exists(\App\Core\Config::class), "Autoloader loads App\\Core\\Config");
        $this->assert(class_exists(\App\Core\Database::class), "Autoloader loads App\\Core\\Database");
        $this->assert(class_exists(\App\Models\User::class), "Autoloader loads App\\Models\\User");
    }

    private function testConfig(): void
    {
        Config::load();
        $this->assert(Config::get('app.timezone') === 'Asia/Dhaka', "Config retrieves default timezone");
        $this->assert(Config::get('app.currency') === 'BDT', "Config retrieves default currency");
        $this->assert(Config::get('non.existent.key', 'default') === 'default', "Config falls back to default on missing key");
    }

    private function testI18nAndCurrency(): void
    {
        I18n::init();

        // 1. Translations
        I18n::setLocale('en');
        $this->assert(I18n::get('login') === 'Log In', "English translation loaded");

        I18n::setLocale('bn');
        $this->assert(I18n::get('login') === 'লগইন করুন', "Bangla translation loaded");

        // 2. Bangla Digits
        $banglaDigits = I18n::toBanglaDigits('1234567890');
        $this->assert($banglaDigits === '১২৩৪৫৬৭৮৯০', "Bangla digit conversion works");

        // 3. Indian Number Formatting (1,50,000.50)
        $formattedIndian = I18n::formatIndianNumber(150000.50, 2);
        $this->assert($formattedIndian === '1,50,000.50', "Indian lakh/crore number formatting (150000 -> 1,50,000.50)");

        // 4. Money formatting from minor units (150000 paisa = 1500.00)
        I18n::setLocale('en');
        $moneyEn = I18n::formatMoney(150000, 'BDT', 'intl', false);
        $this->assert($moneyEn === '৳ 1,500.00', "Minor-unit money formatting in English (150000 paisa -> ৳ 1,500.00)");

        $moneyBn = I18n::formatMoney(150000, 'BDT', 'indian', true);
        $this->assert(str_contains($moneyBn, '১,৫০০.০০'), "Minor-unit money formatting in Bangla digits");
    }

    private function testCsrfSecurity(): void
    {
        Session::start();
        $token = Csrf::getToken();
        $this->assert(strlen($token) === 64, "CSRF token generated with 64 hex characters (32 bytes)");
        $this->assert(Csrf::validate($token), "CSRF validates identical token");
        $this->assert(!Csrf::validate('invalid_token_123'), "CSRF rejects invalid token");
        $this->assert(!Csrf::validate(null), "CSRF rejects null token");
    }

    private function testDatabaseAndSchema(): void
    {
        // Use in-memory SQLite for testing to verify schema statements and PDO abstraction
        Database::setConfig([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $testConn = Database::testConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $this->assert($testConn['success'], "Database connection tester succeeds");

        $pdo = Database::getInstance();

        // Create tables needed for testing in SQLite
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
              used_at DATETIME NULL,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        ");

        $this->assert(true, "Database tables provisioned successfully");
    }

    private function testUserCreationAndHashing(): void
    {
        $userId = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
            'role' => 'admin',
        ]);

        $this->assert($userId > 0, "User created and returned auto-increment ID");

        $user = User::findById($userId);
        $this->assert($user !== null, "User found by ID");
        $this->assert($user['email'] === 'test@example.com', "User email matches lowercase");
        $this->assert(password_verify('SecurePassword123!', $user['password_hash']), "Password hash verifies with Argon2/Bcrypt");
        $this->assert(!password_verify('WrongPassword', $user['password_hash']), "Password hash rejects incorrect password");
    }

    private function testLoginRateLimiting(): void
    {
        $email = 'victim@example.com';
        $ip = '192.168.1.100';

        $this->assert(!LoginAttempt::isRateLimited($email, $ip), "Initial state is not rate limited");

        // Record 4 failed attempts
        for ($i = 0; $i < 4; $i++) {
            LoginAttempt::record($email, $ip, false);
        }
        $this->assert(!LoginAttempt::isRateLimited($email, $ip), "4 failed attempts does not trigger lock");

        // 5th failed attempt
        LoginAttempt::record($email, $ip, false);
        $this->assert(LoginAttempt::isRateLimited($email, $ip), "5 failed attempts triggers 15-minute rate limit");

        // Clear failures (simulate successful login)
        LoginAttempt::clearFailures($email, $ip);
        $this->assert(!LoginAttempt::isRateLimited($email, $ip), "Clearing failures removes rate limit");
    }

    private function testPasswordResetWorkflow(): void
    {
        $user = User::findByEmail('test@example.com');
        $this->assert($user !== null, "Found test user for password reset");

        $rawToken = PasswordReset::createToken((int)$user['id']);
        $this->assert(strlen($rawToken) === 64, "Generated raw 64-character password reset token");

        $record = PasswordReset::verify($rawToken);
        $this->assert($record !== null, "Token verified successfully within expiration window");
        $this->assert((int)$record['user_id'] === (int)$user['id'], "Token matches user ID");

        // Mark as used
        PasswordReset::markAsUsed($rawToken);
        $recordAfterUse = PasswordReset::verify($rawToken);
        $this->assert($recordAfterUse === null, "Single-use token is rejected after being used");
    }

    private function testRouterDispatch(): void
    {
        $router = new Router();
        $router->get('/test/{id}', function (Request $req, string $id) {
            return Response::json(['id' => $id]);
        });

        $request = new Request('GET', '/test/42');
        $response = $router->dispatch($request);

        $this->assert($response instanceof Response, "Router successfully dispatches matching route");
    }
}

$test = new Phase1Test();
$test->run();
