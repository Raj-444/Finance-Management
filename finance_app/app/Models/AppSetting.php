<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AppSetting
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function loadAll(): void
    {
        if (self::$loaded) {
            return;
        }

        try {
            $rows = Database::fetchAll("SELECT k, v FROM app_settings");
            foreach ($rows as $row) {
                self::$cache[$row['k']] = $row['v'];
            }
            self::$loaded = true;
        } catch (\Throwable $e) {
            // Table might not exist yet before installation
            self::$cache = [];
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        self::loadAll();
        self::$cache[$key] = $value;

        $sql = "INSERT INTO app_settings (k, v) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE v = VALUES(v)";
        try {
            Database::query($sql, [$key, $value]);
        } catch (\Throwable $e) {
            // SQLite upsert syntax fallback
            $sqliteSql = "INSERT INTO app_settings (k, v) VALUES (?, ?) 
                          ON CONFLICT(k) DO UPDATE SET v = excluded.v";
            Database::query($sqliteSql, [$key, $value]);
        }
    }

    public static function isRegistrationOpen(): bool
    {
        return self::get('registration_open', '1') === '1';
    }
}
