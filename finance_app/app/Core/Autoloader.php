<?php
declare(strict_types=1);

namespace App\Core;

class Autoloader
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        // Include composer vendor autoloader if available
        $vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        // PSR-4 App autoloader fallback
        spl_autoload_register(function (string $class) {
            $prefix = 'App\\';
            $baseDir = dirname(__DIR__) . '/';

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });

        self::$registered = true;
    }
}
