<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function getToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token || !is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function validate(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        $sessionToken = self::getToken();
        return hash_equals($sessionToken, $token);
    }

    public static function validateRequest(Request $request): bool
    {
        // GET, HEAD, OPTIONS are considered safe
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        // Try getting token from request body (_token)
        $token = $request->input('_token');

        // Or from header
        if (!$token) {
            $token = $request->header('X-CSRF-TOKEN') ?? $request->header('X-XSRF-TOKEN');
        }

        return self::validate($token);
    }

    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function meta(): string
    {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}
