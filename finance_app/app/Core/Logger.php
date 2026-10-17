<?php
declare(strict_types=1);

namespace App\Core;

class Logger
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';

    public static function log(string $level, string $message, array $context = []): void
    {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app.log';

        // Check for rotation (5 MB)
        if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
            @rename($logFile, $logDir . '/app-' . date('Y-m-d-His') . '.log');
        }

        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf("[%s] [%s] [User:%s] [IP:%s] %s%s\n", $date, strtoupper($level), $userId, $ip, $message, $contextStr);

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function exception(\Throwable $e, string $customMessage = ''): void
    {
        $message = ($customMessage ? $customMessage . ': ' : '') . $e->getMessage();
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => array_slice($e->getTrace(), 0, 5),
        ];
        self::log(self::ERROR, $message, $context);
    }
}
