<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\I18n;

class SettingsController
{
    public function setLocale(Request $request): Response
    {
        $locale = $request->input('locale', 'en');
        if (in_array($locale, ['en', 'bn'], true)) {
            I18n::setLocale($locale);
            if (Auth::check()) {
                Database::query("UPDATE users SET locale = ? WHERE id = ?", [$locale, Auth::id()]);
            }
        }

        $referer = $request->header('Referer') ?? url('/dashboard');
        return Response::redirect($referer);
    }

    public function setTheme(Request $request): Response
    {
        $theme = $request->input('theme', 'system');
        if (in_array($theme, ['light', 'dark', 'system'], true)) {
            Session::set('theme', $theme);
            if (Auth::check()) {
                Database::query("UPDATE users SET theme = ? WHERE id = ?", [$theme, Auth::id()]);
            }
        }

        return Response::json(['ok' => true, 'theme' => $theme]);
    }
}
