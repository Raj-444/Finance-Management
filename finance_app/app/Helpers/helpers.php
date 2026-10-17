<?php
declare(strict_types=1);

/**
 * Global Helper Functions for Personal Finance Manager
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\I18n;
use App\Core\Session;
use App\Core\Auth;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return I18n::get($key, $replace);
    }
}

if (!function_exists('money')) {
    function money(int|float $paisa, string $currency = 'BDT'): string
    {
        return I18n::formatMoney($paisa, $currency);
    }
}

if (!function_exists('format_money')) {
    function format_money(int|float $paisa, string $currency = 'BDT'): string
    {
        return money($paisa, $currency);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        return Csrf::meta();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $trimmed = ltrim($path, '/');
        $publicPath = dirname(__DIR__, 2) . '/public_html/' . $trimmed;
        $version = file_exists($publicPath) ? filemtime($publicPath) : 1;
        
        $base = Config::get('app.url', '');
        return rtrim($base, '/') . '/assets/' . $trimmed . '?v=' . $version;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = Config::get('app.url', '');
        return rtrim((string)$base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        $oldInputs = Session::get('_old_input', []);
        return $oldInputs[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $type): ?string
    {
        return Session::getFlash($type);
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $type): bool
    {
        return Session::hasFlash($type);
    }
}

if (!function_exists('all_flashes')) {
    function all_flashes(): array
    {
        return Session::getAllFlashes();
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return Auth::user();
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return Auth::check();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::isAdmin();
    }
}
