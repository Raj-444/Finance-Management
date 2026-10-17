<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Core\Database;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Person;

class DashboardController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $monthStartDay = (int) ($user['month_start_day'] ?? 1);
        $summary = Transaction::getDashboardSummary($userId, $monthStartDay);
        $accounts = Account::getAllForUser($userId);
        $recentTransactions = Transaction::getRecent($userId, 10);
        $categories = Category::getAllForUser($userId);
        $people = Person::getAllForUser($userId);
        $cashflowHistory = \App\Services\AnalyticsService::getMonthlyComparison($userId, 6);

        $focusMode = (bool) Session::get('focus_mode', (bool)($user['focus_mode'] ?? false));

        return View::render('dashboard.index', [
            'title' => 'Dashboard',
            'user' => $user,
            'summary' => $summary,
            'accounts' => $accounts,
            'recentTransactions' => $recentTransactions,
            'categories' => $categories,
            'people' => $people,
            'cashflowHistory' => $cashflowHistory,
            'focusMode' => $focusMode,
            'lastUsedAccountId' => Session::get('last_used_account_id', $accounts[0]['id'] ?? 0),
        ], 'app');
    }

    public function toggleFocusMode(Request $request): Response
    {
        $userId = (int) Auth::id();
        $current = (bool) Session::get('focus_mode', false);
        $new = !$current;

        Session::set('focus_mode', $new);
        Database::query("UPDATE users SET focus_mode = ? WHERE id = ?", [$new ? 1 : 0, $userId]);

        if ($request->wantsJson()) {
            return Response::json(['ok' => true, 'focus_mode' => $new]);
        }

        return Response::redirect(url('/dashboard'));
    }
}
