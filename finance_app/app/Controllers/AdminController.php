<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

class AdminController
{
    public function index(Request $request): Response
    {
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Administrator access required.');
            return Response::redirect(url('/dashboard'));
        }

        $users = Database::fetchAll("SELECT * FROM users ORDER BY id ASC");

        // System Diagnostic Metrics
        $systemInfo = [
            'php_version' => PHP_VERSION,
            'server_os' => PHP_OS,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI / Internal',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'pdo_drivers' => \PDO::getAvailableDrivers(),
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'pdo_sqlite' => extension_loaded('pdo_sqlite'),
                'mbstring' => extension_loaded('mbstring'),
                'json' => extension_loaded('json'),
                'curl' => extension_loaded('curl'),
                'fileinfo' => extension_loaded('fileinfo'),
                'openssl' => extension_loaded('openssl'),
                'zlib' => extension_loaded('zlib'),
            ],
        ];

        // Check last cron execution from storage/logs/app.log
        $lastCron = 'Never detected';
        $logFile = dirname(__DIR__, 2) . '/storage/logs/app.log';
        if (file_exists($logFile)) {
            $lines = file($logFile);
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                if (str_contains($lines[$i], 'Cron runner started') || str_contains($lines[$i], 'Cron runner finished')) {
                    $lastCron = trim(substr($lines[$i], 1, 19));
                    break;
                }
            }
        }

        return View::render('admin.index', [
            'title' => 'Admin Panel & Diagnostics',
            'users' => $users,
            'system' => $systemInfo,
            'lastCron' => $lastCron,
        ], 'app');
    }

    public function toggleRole(Request $request, string $id): Response
    {
        if (!Auth::isAdmin()) {
            return Response::redirect(url('/dashboard'));
        }

        $userId = (int)$id;
        $currentUser = Auth::id();

        if ($userId === $currentUser) {
            Session::setFlash('error', 'You cannot alter your own admin privileges.');
            return Response::redirect(url('/admin'));
        }

        $user = User::findById($userId);
        if ($user) {
            $newRole = ($user['role'] === 'admin') ? 'user' : 'admin';
            Database::query("UPDATE users SET role = ? WHERE id = ?", [$newRole, $userId]);
            Session::setFlash('success', "Updated user role to {$newRole}.");
        }

        return Response::redirect(url('/admin'));
    }

    public function toggleActive(Request $request, string $id): Response
    {
        if (!Auth::isAdmin()) {
            return Response::redirect(url('/dashboard'));
        }

        $userId = (int)$id;
        $currentUser = Auth::id();

        if ($userId === $currentUser) {
            Session::setFlash('error', 'You cannot suspend your own account.');
            return Response::redirect(url('/admin'));
        }

        $user = User::findById($userId);
        if ($user) {
            $newActive = ((int)$user['is_active'] === 1) ? 0 : 1;
            Database::query("UPDATE users SET is_active = ? WHERE id = ?", [$newActive, $userId]);
            $statusText = ($newActive === 1) ? 'activated' : 'suspended';
            Session::setFlash('success', "User account has been {$statusText}.");
        }

        return Response::redirect(url('/admin'));
    }

    public function deleteUser(Request $request, string $id): Response
    {
        if (!Auth::isAdmin()) {
            return Response::redirect(url('/dashboard'));
        }

        $userId = (int)$id;
        $currentUser = Auth::id();

        if ($userId === $currentUser) {
            Session::setFlash('error', 'You cannot delete your own account.');
            return Response::redirect(url('/admin'));
        }

        Database::query("DELETE FROM users WHERE id = ?", [$userId]);
        Session::setFlash('success', 'User account and all related data have been removed.');
        return Response::redirect(url('/admin'));
    }
}
