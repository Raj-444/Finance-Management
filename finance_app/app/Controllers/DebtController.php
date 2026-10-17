<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Person;

class DebtController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $status = $request->query('status', 'open');

        $debts = Debt::getAllForUser($userId, $status);
        $summary = Debt::getSummary($userId);
        $people = Person::getAllForUser($userId);

        return View::render('debts.index', [
            'title' => 'Lend & Borrow Tracker',
            'debts' => $debts,
            'summary' => $summary,
            'people' => $people,
            'status' => $status,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();

        $amountRaw = (float) $request->input('principal', 0);
        $principal = (int) round($amountRaw * 100);

        if ($principal <= 0) {
            Session::setFlash('error', 'Please enter a valid loan amount.');
            return Response::redirect(url('/debts'));
        }

        $personId = !empty($request->input('person_id')) ? (int)$request->input('person_id') : null;
        $newPersonName = trim((string)$request->input('new_person_name', ''));

        if (empty($personId) && !empty($newPersonName)) {
            $personId = Person::findOrCreateByName($userId, $newPersonName);
        }

        if (empty($personId)) {
            Session::setFlash('error', 'Please select or add a person/contact.');
            return Response::redirect(url('/debts'));
        }

        $direction = $request->input('direction', 'lent'); // 'lent' or 'borrowed'

        try {
            Debt::create($userId, [
                'person_id' => $personId,
                'direction' => $direction,
                'principal' => $principal,
                'opened_on' => $request->input('opened_on') ?: date('Y-m-d'),
                'due_on' => $request->input('due_on'),
                'note' => $request->input('note', ''),
            ]);

            Session::setFlash('success', 'Debt record created successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to save record: ' . $e->getMessage());
        }

        return Response::redirect(url('/debts'));
    }

    public function addPayment(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $debtId = (int) $id;

        $amountRaw = (float) $request->input('amount', 0);
        $amount = (int) round($amountRaw * 100);

        if ($amount <= 0) {
            Session::setFlash('error', 'Please enter a valid payment amount.');
            return Response::redirect(url('/debts'));
        }

        try {
            DebtPayment::record($debtId, $userId, $amount, $request->input('paid_on'), (string)$request->input('note', ''));
            Session::setFlash('success', 'Payment recorded.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to record payment: ' . $e->getMessage());
        }

        return Response::redirect(url('/debts'));
    }

    public function toggleStatus(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $debtId = (int) $id;

        $debt = Debt::findById($debtId, $userId);
        if ($debt) {
            $newStatus = ($debt['status'] === 'open') ? 'settled' : 'open';
            Debt::updateStatus($debtId, $userId, $newStatus);
            Session::setFlash('success', "Record marked as {$newStatus}.");
        }

        return Response::redirect(url('/debts'));
    }

    public function delete(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        Debt::delete((int)$id, $userId);
        Session::setFlash('success', 'Debt record deleted.');
        return Response::redirect(url('/debts'));
    }
}
