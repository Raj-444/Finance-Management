<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\I18n;

class DashboardController
{
    public function index(Request $request): Response
    {
        $user = Auth::user();

        return View::render('dashboard.index', [
            'title' => I18n::get('dashboard'),
            'user' => $user,
        ], 'app');
    }
}
