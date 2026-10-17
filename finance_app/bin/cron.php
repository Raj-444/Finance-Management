<?php
declare(strict_types=1);

/**
 * Scheduled Cron Job Runner for Personal Finance Manager
 * Executed via cPanel Cron Jobs every 15 minutes.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Direct web access forbidden. Cron must be executed from CLI or cPanel scheduler.\n");
}

define('ROOT_DIR', dirname(__DIR__));
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Config;
use App\Core\Logger;

if (!Config::isInstalled()) {
    echo "Personal Finance Manager is not installed yet. Skipping cron execution.\n";
    exit(0);
}

// Lock file to avoid overlapping executions
$cronLock = dirname(__DIR__) . '/storage/cron.lock';
if (file_exists($cronLock)) {
    $lockAge = time() - filemtime($cronLock);
    // If lock is older than 30 minutes, consider it stale and clear
    if ($lockAge > 1800) {
        @unlink($cronLock);
    } else {
        echo "Cron is already running (locked {$lockAge}s ago). Exiting.\n";
        exit(0);
    }
}

file_put_contents($cronLock, (string) getmypid());

$startTime = microtime(true);
Logger::info("Cron runner started");

try {
    // Phase 1: Maintenance & Log rotation checks
    echo "[" . date('Y-m-d H:i:s') . "] Starting scheduled tasks...\n";

    // 1. Rotate logs if app.log exceeds 5MB
    $logFile = dirname(__DIR__) . '/storage/logs/app.log';
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        @rename($logFile, dirname(__DIR__) . '/storage/logs/app-' . date('Ymd-His') . '.log');
        echo "Rotated application log.\n";
    }

    // 2. Clean old session files older than 7 days
    $sessionDir = dirname(__DIR__) . '/storage/sessions';
    if (is_dir($sessionDir)) {
        $files = glob($sessionDir . '/sess_*');
        $now = time();
        $cleaned = 0;
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > 7 * 86400)) {
                @unlink($file);
                $cleaned++;
            }
        }
        if ($cleaned > 0) {
            echo "Cleaned {$cleaned} stale session files.\n";
        }
    }

    $duration = round(microtime(true) - $startTime, 4);
    echo "Cron tasks completed in {$duration}s.\n";
    Logger::info("Cron runner finished in {$duration}s");
} catch (\Throwable $e) {
    Logger::exception($e, "Cron execution failed");
    echo "Error during cron execution: " . $e->getMessage() . "\n";
} finally {
    @unlink($cronLock);
}
