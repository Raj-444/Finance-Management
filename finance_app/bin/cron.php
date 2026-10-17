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
    echo "[" . date('Y-m-d H:i:s') . "] Starting scheduled tasks...\n";

    // 1. Process Due Recurring Rules
    $today = date('Y-m-d');
    $dueRules = \App\Models\RecurringRule::getDueRules($today);
    $processedRules = 0;

    foreach ($dueRules as $rule) {
        $userId = (int) $rule['user_id'];
        $ruleId = (int) $rule['id'];

        if ((int)$rule['auto_post'] === 1) {
            try {
                \App\Models\Transaction::create($userId, [
                    'type' => $rule['type'],
                    'account_id' => $rule['account_id'],
                    'category_id' => $rule['category_id'],
                    'amount' => $rule['amount'],
                    'tx_date' => $rule['next_run'],
                    'note' => (!empty($rule['note']) ? $rule['note'] . ' ' : '') . '(Recurring Auto-Post)',
                    'tags' => 'recurring',
                ]);
            } catch (\Throwable $ex) {
                Logger::error("Failed to auto-post recurring rule #{$ruleId}: " . $ex->getMessage());
            }
        }

        // Advance next run date
        \App\Models\RecurringRule::advanceNextRun(
            $ruleId,
            $rule['next_run'],
            $rule['frequency'],
            (int)($rule['interval_n'] ?? 1)
        );
        $processedRules++;
    }

    if ($processedRules > 0) {
        echo "Processed {$processedRules} due recurring rules.\n";
    }

    // 2. Evaluate Budgets and trigger forecast/warning alerts
    $users = \App\Core\Database::fetchAll("SELECT id FROM users");
    $evalUsers = 0;
    foreach ($users as $u) {
        try {
            \App\Services\BudgetForecastService::evaluateAllForUser((int)$u['id']);
            $evalUsers++;
        } catch (\Throwable $ex) {
            Logger::error("Budget evaluation failed for user #{$u['id']}: " . $ex->getMessage());
        }
    }
    if ($evalUsers > 0) {
        echo "Evaluated budget alerts for {$evalUsers} users.\n";
    }

    // 3. Process Monthly Summary Emails on the 1st of each month
    if ((int)date('j') === 1) {
        $lastMonthKey = (int)date('Ym', (int)strtotime('first day of last month'));
        $optedUsers = \App\Core\Database::fetchAll("SELECT id, email FROM users WHERE email_alerts = 1 AND is_active = 1");
        $sentSummaries = 0;
        foreach ($optedUsers as $ou) {
            $uId = (int)$ou['id'];
            $alreadySent = \App\Core\Database::fetch(
                "SELECT id FROM activity_log WHERE user_id = ? AND action = 'monthly_summary_sent' AND entity_id = ? LIMIT 1",
                [$uId, $lastMonthKey]
            );
            if (!$alreadySent) {
                try {
                    $res = \App\Services\MonthlySummaryService::sendSummaryForUser($uId);
                    if ($res['ok']) {
                        $sentSummaries++;
                    }
                } catch (\Throwable $mex) {
                    Logger::error("Monthly summary failed for user #{$uId}: " . $mex->getMessage());
                }
            }
        }
        if ($sentSummaries > 0) {
            echo "Dispatched {$sentSummaries} monthly financial summaries.\n";
        }
    }

    // 4. Rotate logs if app.log exceeds 5MB
    $logFile = dirname(__DIR__) . '/storage/logs/app.log';
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        @rename($logFile, dirname(__DIR__) . '/storage/logs/app-' . date('Ymd-His') . '.log');
        echo "Rotated application log.\n";
    }

    // 4. Clean old session files older than 7 days
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
