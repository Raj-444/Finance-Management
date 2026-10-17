<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use App\Models\Person;

class TransactionController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();

        $filters = [
            'type' => $request->query('type'),
            'account_id' => $request->query('account_id'),
            'category_id' => $request->query('category_id'),
            'person_id' => $request->query('person_id'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'search' => $request->query('search'),
        ];

        $page = (int) $request->query('page', 1);
        $paginated = Transaction::getPaginated($userId, $filters, $page, 20);

        $accounts = Account::getAllForUser($userId);
        $categories = Category::getAllForUser($userId);
        $people = Person::getAllForUser($userId);

        return View::render('transactions.index', [
            'title' => 'Transactions',
            'transactions' => $paginated['data'],
            'pagination' => $paginated,
            'filters' => $filters,
            'accounts' => $accounts,
            'categories' => $categories,
            'people' => $people,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();

        $amountRaw = $request->input('amount');
        // Convert to paisa minor units (e.g. 150.50 -> 15050)
        $amountInPaisa = (int) round((float)$amountRaw * 100);

        if ($amountInPaisa <= 0) {
            if ($request->wantsJson()) {
                return Response::json(['ok' => false, 'error' => 'Please enter a valid amount greater than zero.'], 422);
            }
            Session::setFlash('error', 'Please enter a valid amount.');
            return Response::redirect(url('/transactions'));
        }

        $type = $request->input('type', 'expense');
        $accountId = (int) $request->input('account_id');
        $toAccountId = ($type === 'transfer') ? (int)$request->input('to_account_id') : null;
        $categoryId = !empty($request->input('category_id')) ? (int)$request->input('category_id') : null;
        $personId = !empty($request->input('person_id')) ? (int)$request->input('person_id') : null;
        $txDate = $request->input('tx_date') ?: date('Y-m-d');
        $note = (string) $request->input('note', '');
        $tags = (string) $request->input('tags', '');

        // Handle quick-added new person name if provided
        $newPersonName = trim((string)$request->input('new_person_name', ''));
        if (!empty($newPersonName)) {
            $personId = Person::findOrCreateByName($userId, $newPersonName);
        }

        try {
            $txId = Transaction::create($userId, [
                'type' => $type,
                'account_id' => $accountId,
                'to_account_id' => $toAccountId,
                'category_id' => $categoryId,
                'person_id' => $personId,
                'amount' => $amountInPaisa,
                'tx_date' => $txDate,
                'note' => $note,
                'tags' => $tags,
            ]);

            // Save last used account in session for smart defaults
            Session::set('last_used_account_id', $accountId);

            if ($request->wantsJson()) {
                return Response::json([
                    'ok' => true,
                    'message' => 'Transaction saved successfully.',
                    'id' => $txId,
                ]);
            }

            Session::setFlash('success', 'Transaction recorded successfully.');
            return Response::redirect(url('/dashboard'));
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return Response::json(['ok' => false, 'error' => $e->getMessage()], 500);
            }
            Session::setFlash('error', 'Failed to record transaction: ' . $e->getMessage());
            return Response::redirect(url('/transactions'));
        }
    }

    public function delete(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $txId = (int) $id;

        $deleted = Transaction::softDelete($txId, $userId);

        if ($request->wantsJson()) {
            return Response::json(['ok' => $deleted, 'id' => $txId]);
        }

        if ($deleted) {
            Session::setFlash('success', 'Transaction removed.');
        } else {
            Session::setFlash('error', 'Unable to delete transaction.');
        }

        return Response::redirect(url('/transactions'));
    }

    public function restore(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $txId = (int) $id;

        $restored = Transaction::restore($txId, $userId);

        if ($request->wantsJson()) {
            return Response::json(['ok' => $restored, 'id' => $txId]);
        }

        if ($restored) {
            Session::setFlash('success', 'Transaction restored.');
        }

        return Response::redirect(url('/transactions'));
    }
}
