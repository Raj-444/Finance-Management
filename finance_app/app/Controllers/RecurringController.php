<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\RecurringRule;
use App\Models\Account;
use App\Models\Category;

class RecurringController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();

        $rules = RecurringRule::getAllForUser($userId);
        $accounts = Account::getAllForUser($userId);
        $categories = Category::getAllForUser($userId);

        return View::render('recurring.index', [
            'title' => 'Recurring Subscriptions & Bills',
            'rules' => $rules,
            'accounts' => $accounts,
            'categories' => $categories,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();

        $amountRaw = (float) $request->input('amount', 0);
        $amount = (int) round($amountRaw * 100);

        if ($amount <= 0) {
            Session::setFlash('error', 'Please enter a valid amount.');
            return Response::redirect(url('/recurring'));
        }

        $accountId = (int) $request->input('account_id', 0);
        if ($accountId <= 0) {
            Session::setFlash('error', 'Please select an account.');
            return Response::redirect(url('/recurring'));
        }

        $type = in_array($request->input('type'), ['income', 'expense']) ? $request->input('type') : 'expense';
        $categoryId = !empty($request->input('category_id')) ? (int)$request->input('category_id') : null;
        $frequency = in_array($request->input('frequency'), ['daily', 'weekly', 'monthly', 'yearly']) ? $request->input('frequency') : 'monthly';
        $intervalN = max(1, (int)$request->input('interval_n', 1));
        $nextRun = $request->input('next_run') ?: date('Y-m-d');
        $endDate = !empty($request->input('end_date')) ? $request->input('end_date') : null;
        $note = trim((string)$request->input('note', ''));
        $autoPost = $request->input('auto_post') ? 1 : 0;

        try {
            RecurringRule::create($userId, [
                'type' => $type,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'amount' => $amount,
                'note' => $note,
                'frequency' => $frequency,
                'interval_n' => $intervalN,
                'next_run' => $nextRun,
                'end_date' => $endDate,
                'auto_post' => $autoPost,
                'is_active' => 1,
            ]);

            Session::setFlash('success', 'Recurring rule scheduled successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to save recurring rule: ' . $e->getMessage());
        }

        return Response::redirect(url('/recurring'));
    }

    public function toggleActive(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $ruleId = (int) $id;

        RecurringRule::toggleActive($ruleId, $userId);
        Session::setFlash('success', 'Recurring rule status updated.');
        return Response::redirect(url('/recurring'));
    }

    public function delete(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        RecurringRule::delete((int)$id, $userId);
        Session::setFlash('success', 'Recurring rule deleted.');
        return Response::redirect(url('/recurring'));
    }
}
