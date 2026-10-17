<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $defaultConfig = [
            'app' => [
                'name' => 'Personal Finance Manager',
                'env' => 'production',
                'debug' => false,
                'url' => '',
                'timezone' => 'Asia/Dhaka',
                'locale' => 'en',
                'currency' => 'BDT',
            ],
            'db' => [
                'driver' => 'mysql', // mysql or sqlite
                'host' => 'localhost',
                'port' => 3306,
                'database' => '',
                'username' => '',
                'password' => '',
                'charset' => 'utf8mb4',
            ],
            'session' => [
                'lifetime' => 120, // minutes
                'path' => dirname(__DIR__, 2) . '/storage/sessions',
                'cookie' => 'pfm_session',
            ],
            'mail' => [
                'driver' => 'smtp',
                'host' => '',
                'port' => 587,
                'username' => '',
                'password' => '',
                'encryption' => 'tls', // tls or ssl
                'from_address' => '',
                'from_name' => 'Personal Finance Manager',
            ],
            'security' => [
                'argon2_memory' => 65536,
                'argon2_time' => 4,
                'argon2_threads' => 1,
            ],
        ];

        $configFile = dirname(__DIR__, 2) . '/config/config.php';
        if (file_exists($configFile)) {
            $userConfig = require $configFile;
            if (is_array($userConfig)) {
                self::$items = array_replace_recursive($defaultConfig, $userConfig);
            } else {
                self::$items = $defaultConfig;
            }
        } else {
            self::$items = $defaultConfig;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();

        $segments = explode('.', $key);
        $current = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public static function set(string $key, mixed $value): void
    {
        self::load();
        $segments = explode('.', $key);
        $current = &self::$items;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    public static function all(): array
    {
        self::load();
        return self::$items;
    }

    public static function isInstalled(): bool
    {
        $lockFile = dirname(__DIR__, 2) . '/storage/installed.lock';
        return file_exists($lockFile);
    }

    public static function save(array $newConfig): bool
    {
        $configFile = dirname(__DIR__, 2) . '/config/config.php';
        $content = "<?php\n\n// Auto-generated configuration file by Web Installer\nreturn " . var_export($newConfig, true) . ";\n";
        
        $saved = (bool) file_put_contents($configFile, $content, LOCK_EX);
        if ($saved) {
            self::$items = $newConfig;
            self::$loaded = true;
        }
        return $saved;
    }
}
