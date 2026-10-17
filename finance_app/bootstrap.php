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

// Preferences Routes
$router->post('/settings/locale', [\App\Controllers\SettingsController::class, 'setLocale'], ['installed']);
$router->post('/settings/theme', [\App\Controllers\SettingsController::class, 'setTheme'], ['installed', 'no_csrf']);

return $router;
