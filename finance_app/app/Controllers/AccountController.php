<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\Account;

class AccountController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $accounts = Account::getAllForUser($userId);

        return View::render('accounts.index', [
            'title' => 'Accounts & Wallets',
            'accounts' => $accounts,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();
        $name = trim((string)$request->input('name', ''));
        $type = $request->input('type', 'cash');
        $color = $request->input('color', '#5B5BD6');
        $icon = $request->input('icon', 'wallet');
        $openingBalance = (int) round((float)$request->input('opening_balance', 0) * 100);

        if (empty($name)) {
            Session::setFlash('error', 'Account name is required.');
            return Response::redirect(url('/accounts'));
        }

        try {
            Account::create($userId, [
                'name' => $name,
                'type' => $type,
                'color' => $color,
                'icon' => $icon,
                'opening_balance' => $openingBalance,
            ]);

            Session::setFlash('success', 'Account created successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to create account: ' . $e->getMessage());
        }

        return Response::redirect(url('/accounts'));
    }

    public function archive(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $accId = (int) $id;

        Account::archive($accId, $userId);
        Session::setFlash('success', 'Account archived.');
        return Response::redirect(url('/accounts'));
    }
}
