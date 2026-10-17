<?php
declare(strict_types=1);

// Prevent direct access
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// 1. Register Autoloader & Helpers
require_once __DIR__ . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/app/Helpers/helpers.php';

// 2. Set Error and Exception Handlers
error_reporting(E_ALL);
ini_set('display_errors', '0');

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (\Throwable $e) {
    \App\Core\Logger::exception($e, 'Uncaught Exception');

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'An unexpected internal error occurred.']);
        exit;
    }

    try {
        echo \App\Core\View::render('errors.500', ['error' => $e->getMessage()], 'auth')->send();
    } catch (\Throwable) {
        echo "<div style='font-family: sans-serif; padding: 40px; text-align: center;'>";
        echo "<h2>Application Error</h2><p>An unexpected error occurred. Please try again later or check the logs.</p>";
        echo "</div>";
    }
    exit;
});

// 3. Load Configuration
\App\Core\Config::load();

// Set default timezone
$tz = \App\Core\Config::get('app.timezone', 'Asia/Dhaka');
date_default_timezone_set($tz);

// 4. Start Session & I18n
\App\Core\Session::start();
\App\Core\I18n::init();

// 5. Define Routes
$router = new \App\Core\Router();

// Installer Routes
$router->get('/install', [\App\Controllers\InstallController::class, 'index']);
$router->post('/install', [\App\Controllers\InstallController::class, 'submit']);
$router->post('/install/test-db', [\App\Controllers\InstallController::class, 'testDb'], ['no_csrf']);
$router->get('/install/complete', [\App\Controllers\InstallController::class, 'complete']);

// Authentication Routes
$router->get('/login', [\App\Controllers\AuthController::class, 'showLogin'], ['installed', 'guest']);
$router->post('/login', [\App\Controllers\AuthController::class, 'login'], ['installed', 'guest']);
$router->get('/register', [\App\Controllers\AuthController::class, 'showRegister'], ['installed', 'guest']);
$router->post('/register', [\App\Controllers\AuthController::class, 'register'], ['installed', 'guest']);
$router->post('/logout', [\App\Controllers\AuthController::class, 'logout'], ['installed', 'auth']);
$router->get('/forgot-password', [\App\Controllers\AuthController::class, 'showForgotPassword'], ['installed', 'guest']);
$router->post('/forgot-password', [\App\Controllers\AuthController::class, 'sendResetLink'], ['installed', 'guest']);
$router->get('/reset-password', [\App\Controllers\AuthController::class, 'showResetPassword'], ['installed', 'guest']);
$router->post('/reset-password', [\App\Controllers\AuthController::class, 'resetPassword'], ['installed', 'guest']);

// App Core Routes
$router->get('/', fn() => \App\Core\Response::redirect(url('/dashboard')), ['installed']);
$router->get('/dashboard', [\App\Controllers\DashboardController::class, 'index'], ['installed', 'auth']);
$router->post('/dashboard/focus-mode', [\App\Controllers\DashboardController::class, 'toggleFocusMode'], ['installed', 'auth', 'no_csrf']);

// Transactions Routes
$router->get('/transactions', [\App\Controllers\TransactionController::class, 'index'], ['installed', 'auth']);
$router->post('/transactions', [\App\Controllers\TransactionController::class, 'store'], ['installed', 'auth']);
$router->post('/transactions/{id}/delete', [\App\Controllers\TransactionController::class, 'delete'], ['installed', 'auth']);
$router->post('/transactions/{id}/restore', [\App\Controllers\TransactionController::class, 'restore'], ['installed', 'auth']);

// Accounts Routes
$router->get('/accounts', [\App\Controllers\AccountController::class, 'index'], ['installed', 'auth']);
$router->post('/accounts', [\App\Controllers\AccountController::class, 'store'], ['installed', 'auth']);
$router->post('/accounts/{id}/archive', [\App\Controllers\AccountController::class, 'archive'], ['installed', 'auth']);

// Categories Routes
$router->get('/categories', [\App\Controllers\CategoryController::class, 'index'], ['installed', 'auth']);
$router->post('/categories', [\App\Controllers\CategoryController::class, 'store'], ['installed', 'auth']);
$router->post('/categories/{id}/archive', [\App\Controllers\CategoryController::class, 'archive'], ['installed', 'auth']);

// Budgets Routes
$router->get('/budgets', [\App\Controllers\BudgetController::class, 'index'], ['installed', 'auth']);
$router->post('/budgets', [\App\Controllers\BudgetController::class, 'store'], ['installed', 'auth']);
$router->post('/budgets/{id}/delete', [\App\Controllers\BudgetController::class, 'delete'], ['installed', 'auth']);

// Analytics Routes
$router->get('/analytics', [\App\Controllers\AnalyticsController::class, 'index'], ['installed', 'auth']);
$router->get('/analytics/data', [\App\Controllers\AnalyticsController::class, 'data'], ['installed', 'auth']);

// Debts Routes
$router->get('/debts', [\App\Controllers\DebtController::class, 'index'], ['installed', 'auth']);
$router->post('/debts', [\App\Controllers\DebtController::class, 'store'], ['installed', 'auth']);
$router->post('/debts/{id}/payments', [\App\Controllers\DebtController::class, 'addPayment'], ['installed', 'auth']);
$router->post('/debts/{id}/toggle', [\App\Controllers\DebtController::class, 'toggleStatus'], ['installed', 'auth']);
$router->post('/debts/{id}/delete', [\App\Controllers\DebtController::class, 'delete'], ['installed', 'auth']);

// Goals Routes
$router->get('/goals', [\App\Controllers\GoalController::class, 'index'], ['installed', 'auth']);
$router->post('/goals', [\App\Controllers\GoalController::class, 'store'], ['installed', 'auth']);
$router->post('/goals/{id}/contribute', [\App\Controllers\GoalController::class, 'addContribution'], ['installed', 'auth']);
$router->post('/goals/{id}/toggle', [\App\Controllers\GoalController::class, 'toggleStatus'], ['installed', 'auth']);
$router->post('/goals/{id}/delete', [\App\Controllers\GoalController::class, 'delete'], ['installed', 'auth']);

// Recurring Rules Routes
$router->get('/recurring', [\App\Controllers\RecurringController::class, 'index'], ['installed', 'auth']);
$router->post('/recurring', [\App\Controllers\RecurringController::class, 'store'], ['installed', 'auth']);
$router->post('/recurring/{id}/toggle', [\App\Controllers\RecurringController::class, 'toggleActive'], ['installed', 'auth']);
$router->post('/recurring/{id}/delete', [\App\Controllers\RecurringController::class, 'delete'], ['installed', 'auth']);

// Notifications Routes
$router->get('/notifications/unread', [\App\Controllers\NotificationController::class, 'unread'], ['installed', 'auth']);
$router->post('/notifications/mark-all-read', [\App\Controllers\NotificationController::class, 'markAllRead'], ['installed', 'auth']);
$router->post('/notifications/{id}/mark-read', [\App\Controllers\NotificationController::class, 'markRead'], ['installed', 'auth']);

// Settings & Profile Routes
$router->get('/settings', [\App\Controllers\SettingsController::class, 'index'], ['installed', 'auth']);
$router->post('/settings/profile', [\App\Controllers\SettingsController::class, 'updateProfile'], ['installed', 'auth']);
$router->post('/settings/password', [\App\Controllers\SettingsController::class, 'updatePassword'], ['installed', 'auth']);
$router->get('/settings/export/csv', [\App\Controllers\SettingsController::class, 'exportCsv'], ['installed', 'auth']);
$router->get('/settings/export/xlsx', [\App\Controllers\SettingsController::class, 'exportXlsx'], ['installed', 'auth']);
$router->post('/settings/import/upload', [\App\Controllers\SettingsController::class, 'importUpload'], ['installed', 'auth']);
$router->post('/settings/import/preview', [\App\Controllers\SettingsController::class, 'importPreview'], ['installed', 'auth', 'no_csrf']);
$router->post('/settings/import/commit', [\App\Controllers\SettingsController::class, 'importCommit'], ['installed', 'auth']);
$router->post('/settings/import/{id}/undo', [\App\Controllers\SettingsController::class, 'importUndo'], ['installed', 'auth']);
$router->get('/settings/backup/download', [\App\Controllers\SettingsController::class, 'backupDownload'], ['installed', 'auth']);
$router->post('/settings/backup/restore', [\App\Controllers\SettingsController::class, 'backupRestore'], ['installed', 'auth']);
$router->post('/settings/smtp', [\App\Controllers\SettingsController::class, 'updateSmtp'], ['installed', 'auth']);
$router->post('/settings/smtp/test', [\App\Controllers\SettingsController::class, 'testSmtp'], ['installed', 'auth', 'no_csrf']);
$router->post('/settings/registration/toggle', [\App\Controllers\SettingsController::class, 'toggleRegistration'], ['installed', 'auth', 'no_csrf']);
$router->post('/settings/locale', [\App\Controllers\SettingsController::class, 'setLocale'], ['installed']);
$router->post('/settings/theme', [\App\Controllers\SettingsController::class, 'setTheme'], ['installed', 'no_csrf']);

// Admin Panel Routes
$router->get('/admin', [\App\Controllers\AdminController::class, 'index'], ['installed', 'auth']);
$router->post('/admin/users/{id}/role', [\App\Controllers\AdminController::class, 'toggleRole'], ['installed', 'auth']);
$router->post('/admin/users/{id}/active', [\App\Controllers\AdminController::class, 'toggleActive'], ['installed', 'auth']);
$router->post('/admin/users/{id}/delete', [\App\Controllers\AdminController::class, 'deleteUser'], ['installed', 'auth']);

return $router;
