<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\User;
use App\Models\AppSetting;

class InstallController
{
    private array $requiredExtensions = [
        'pdo_mysql',
        'mbstring',
        'zip',
        'xml',
        'libxml',
        'openssl',
        'json',
        'fileinfo',
    ];

    public function index(Request $request): Response
    {
        if (Config::isInstalled()) {
            return Response::redirect(url('/'));
        }

        $checks = $this->runSystemChecks();
        $allPassed = $checks['all_passed'];

        return View::render('install.index', [
            'title' => 'Installation Wizard',
            'checks' => $checks,
            'allPassed' => $allPassed,
        ], 'install');
    }

    public function testDb(Request $request): Response
    {
        if (Config::isInstalled()) {
            return Response::json(['ok' => false, 'error' => 'Application is already installed.'], 403);
        }

        $config = [
            'driver' => $request->input('driver', 'mysql'),
            'host' => $request->input('host', 'localhost'),
            'port' => (int)$request->input('port', 3306),
            'database' => $request->input('database', ''),
            'username' => $request->input('username', ''),
            'password' => $request->input('password', ''),
            'charset' => 'utf8mb4',
        ];

        $result = Database::testConnection($config);
        return Response::json([
            'ok' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    public function submit(Request $request): Response
    {
        if (Config::isInstalled()) {
            return Response::redirect(url('/'));
        }

        // Validate Inputs
        $driver = $request->input('driver', 'mysql');
        $dbHost = $request->input('db_host', 'localhost');
        $dbPort = (int)$request->input('db_port', 3306);
        $dbName = $request->input('db_name', '');
        $dbUser = $request->input('db_user', '');
        $dbPass = $request->input('db_pass', '');

        $adminName = trim($request->input('admin_name', ''));
        $adminEmail = trim(strtolower($request->input('admin_email', '')));
        $adminPass = $request->input('admin_pass', '');
        $currency = $request->input('currency', 'BDT');
        $loadSampleData = (bool)$request->input('load_sample_data', false);

        if (empty($dbName) && $driver === 'mysql') {
            Session::setFlash('error', 'Database name is required.');
            return Response::redirect(url('/install'));
        }

        if (empty($adminName) || empty($adminEmail) || strlen($adminPass) < 8) {
            Session::setFlash('error', 'Please provide valid admin credentials (password at least 8 characters).');
            return Response::redirect(url('/install'));
        }

        $dbConfig = [
            'driver' => $driver,
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUser,
            'password' => $dbPass,
            'charset' => 'utf8mb4',
        ];

        // 1. Test Database
        $test = Database::testConnection($dbConfig);
        if (!$test['success']) {
            Session::setFlash('error', 'Database connection failed: ' . $test['message']);
            return Response::redirect(url('/install'));
        }

        // 2. Set Database config dynamically
        Database::setConfig($dbConfig);

        // 3. Execute Schema
        try {
            $schemaFile = dirname(__DIR__, 2) . '/database/schema.sql';
            Database::execSqlFile($schemaFile);
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Database schema execution failed: ' . $e->getMessage());
            return Response::redirect(url('/install'));
        }

        // 4. Create Administrator User
        try {
            $adminId = User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $adminPass,
                'role' => 'admin',
                'currency' => $currency,
                'locale' => 'en',
                'timezone' => 'Asia/Dhaka',
            ]);
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Admin user creation failed: ' . $e->getMessage());
            return Response::redirect(url('/install'));
        }

        // 5. Load Optional Sample Data (Bangladesh context)
        if ($loadSampleData) {
            $this->seedSampleData($adminId);
        }

        // 6. Save Configuration file
        $appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') 
                  . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $newConfig = [
            'app' => [
                'name' => 'Personal Finance Manager',
                'env' => 'production',
                'debug' => false,
                'url' => $appUrl,
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'currency' => $currency,
            ],
            'db' => $dbConfig,
            'session' => [
                'lifetime' => 120,
                'path' => dirname(__DIR__, 2) . '/storage/sessions',
                'cookie' => 'pfm_session',
            ],
            'mail' => [
                'driver' => 'smtp',
                'host' => '',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls',
                'from_address' => '',
                'from_name' => 'Personal Finance Manager',
            ],
        ];

        Config::save($newConfig);

        // 7. Write Lock file
        $lockFile = dirname(__DIR__, 2) . '/storage/installed.lock';
        file_put_contents($lockFile, 'Installed on ' . date('c') . "\n");

        Session::setFlash('success', 'Installation successful! Please log in with your admin credentials.');
        return Response::redirect(url('/install/complete'));
    }

    public function complete(Request $request): Response
    {
        $baseDir = dirname(__DIR__, 2);
        $cronCmd = "*/15 * * * * /usr/local/bin/php {$baseDir}/bin/cron.php >/dev/null 2>&1";

        return View::render('install.complete', [
            'title' => 'Installation Complete',
            'cronCmd' => $cronCmd,
        ], 'install');
    }

    private function runSystemChecks(): array
    {
        $phpVersion = PHP_VERSION;
        $phpPassed = version_compare($phpVersion, '8.2.0', '>=');

        $extResults = [];
        $allExtPassed = true;
        foreach ($this->requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $extResults[$ext] = $loaded;
            if (!$loaded) {
                $allExtPassed = false;
            }
        }

        $dirsToCheck = [
            'config' => dirname(__DIR__, 2) . '/config',
            'storage/logs' => dirname(__DIR__, 2) . '/storage/logs',
            'storage/sessions' => dirname(__DIR__, 2) . '/storage/sessions',
            'storage/cache' => dirname(__DIR__, 2) . '/storage/cache',
            'storage/backups' => dirname(__DIR__, 2) . '/storage/backups',
            'storage/tmp' => dirname(__DIR__, 2) . '/storage/tmp',
        ];

        $dirResults = [];
        $allDirPassed = true;
        foreach ($dirsToCheck as $label => $path) {
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $writable = is_writable($path);
            $dirResults[$label] = $writable;
            if (!$writable) {
                $allDirPassed = false;
            }
        }

        return [
            'php_version' => $phpVersion,
            'php_passed' => $phpPassed,
            'extensions' => $extResults,
            'ext_passed' => $allExtPassed,
            'directories' => $dirResults,
            'dir_passed' => $allDirPassed,
            'all_passed' => $phpPassed && $allExtPassed && $allDirPassed,
        ];
    }

    private function seedSampleData(int $userId): void
    {
        // Sample accounts
        $accounts = [
            ['Cash', 'cash', 500000, '#12B76A'],       // 5,000.00 BDT
            ['bKash', 'mobile_wallet', 1000000, '#E2136E'], // 10,000.00 BDT
            ['Nagad', 'mobile_wallet', 500000, '#F7931E'],  // 5,000.00 BDT
            ['Bank Account', 'bank', 2500000, '#5B5BD6'], // 25,000.00 BDT
        ];

        foreach ($accounts as $acc) {
            Database::query(
                "INSERT INTO accounts (user_id, name, type, opening_balance, color) VALUES (?, ?, ?, ?, ?)",
                [$userId, $acc[0], $acc[1], $acc[2], $acc[3]]
            );
        }

        // Sample categories
        $categories = [
            ['Food & Dining', 'expense', '#F04438', 'utensils'],
            ['Groceries', 'expense', '#F79009', 'shopping-cart'],
            ['Rent & Housing', 'expense', '#7F56D9', 'home'],
            ['Utilities & Bills', 'expense', '#2E90FA', 'bolt'],
            ['Transportation', 'expense', '#06AED4', 'car'],
            ['Health & Medical', 'expense', '#EE46BC', 'activity'],
            ['Salary', 'income', '#12B76A', 'briefcase'],
            ['Freelance / Bonus', 'income', '#32D583', 'award'],
        ];

        foreach ($categories as $cat) {
            Database::query(
                "INSERT INTO categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)",
                [$userId, $cat[0], $cat[1], $cat[2], $cat[3]]
            );
        }
    }
}
