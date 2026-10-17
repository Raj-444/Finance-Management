<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\SavingsGoal;
use App\Models\GoalContribution;

class GoalController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $goals = SavingsGoal::getAllForUser($userId);

        $totalTarget = 0;
        $totalSaved = 0;
        $activeCount = 0;

        foreach ($goals as $g) {
            $totalTarget += (int)$g['target_amount'];
            $totalSaved += (int)$g['current_saved'];
            if ($g['status'] === 'active') {
                $activeCount++;
            }
        }

        $overallPercent = ($totalTarget > 0) ? min(100, round(($totalSaved / $totalTarget) * 100, 1)) : 0;

        return View::render('goals.index', [
            'title' => 'Savings Goals',
            'goals' => $goals,
            'summary' => [
                'total_target' => $totalTarget,
                'total_saved' => $totalSaved,
                'overall_percent' => $overallPercent,
                'active_count' => $activeCount,
            ],
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();

        $name = trim((string)$request->input('name', ''));
        $targetAmountRaw = (float) $request->input('target_amount', 0);
        $targetAmount = (int) round($targetAmountRaw * 100);

        if (empty($name)) {
            Session::setFlash('error', 'Please enter a goal name.');
            return Response::redirect(url('/goals'));
        }

        if ($targetAmount <= 0) {
            Session::setFlash('error', 'Please enter a valid target amount.');
            return Response::redirect(url('/goals'));
        }

        try {
            SavingsGoal::create($userId, [
                'name' => $name,
                'target_amount' => $targetAmount,
                'target_date' => $request->input('target_date'),
                'color' => $request->input('color', '#12B76A'),
                'icon' => $request->input('icon', 'target'),
            ]);

            Session::setFlash('success', 'Savings goal created successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to create goal: ' . $e->getMessage());
        }

        return Response::redirect(url('/goals'));
    }

    public function addContribution(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $goalId = (int) $id;

        $amountRaw = (float) $request->input('amount', 0);
        $amount = (int) round($amountRaw * 100);

        if ($amount <= 0) {
            Session::setFlash('error', 'Please enter a valid contribution amount.');
            return Response::redirect(url('/goals'));
        }

        try {
            GoalContribution::record(
                $goalId,
                $userId,
                $amount,
                $request->input('contributed_on') ?: date('Y-m-d'),
                (string)$request->input('note', '')
            );

            Session::setFlash('success', 'Contribution added towards your goal!');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to add contribution: ' . $e->getMessage());
        }

        return Response::redirect(url('/goals'));
    }

    public function toggleStatus(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        $goalId = (int) $id;

        $goal = SavingsGoal::findById($goalId, $userId);
        if ($goal) {
            $newStatus = ($goal['status'] === 'active') ? 'paused' : 'active';
            SavingsGoal::updateStatus($goalId, $userId, $newStatus);
            Session::setFlash('success', "Goal status updated to {$newStatus}.");
        }

        return Response::redirect(url('/goals'));
    }

    public function delete(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        SavingsGoal::delete((int)$id, $userId);
        Session::setFlash('success', 'Goal deleted.');
        return Response::redirect(url('/goals'));
    }
}
