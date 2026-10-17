<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Core\Session;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetForecastService;

class BudgetController
{
    public function index(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        // Run budget analysis and forecast for current period
        $budgetAnalyses = BudgetForecastService::evaluateAllForUser($userId);

        $categories = Category::getAllForUser($userId, 'expense');

        [$periodStart, $periodEnd, $daysInPeriod, $elapsedDays] = BudgetForecastService::calculatePeriodDates((int)($user['month_start_day'] ?? 1));

        return View::render('budgets.index', [
            'title' => 'Smart Budgets & Forecast',
            'budgets' => $budgetAnalyses,
            'categories' => $categories,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'daysInPeriod' => $daysInPeriod,
            'elapsedDays' => $elapsedDays,
        ], 'app');
    }

    public function store(Request $request): Response
    {
        $userId = (int) Auth::id();
        $user = Auth::user();

        $amountRaw = (float) $request->input('amount', 0);
        $amountInPaisa = (int) round($amountRaw * 100);

        if ($amountInPaisa <= 0) {
            Session::setFlash('error', 'Please enter a valid budget amount.');
            return Response::redirect(url('/budgets'));
        }

        [$periodStart] = BudgetForecastService::calculatePeriodDates((int)($user['month_start_day'] ?? 1));

        $categoryId = $request->input('category_id');
        $categoryId = (!empty($categoryId) && $categoryId !== 'overall') ? (int)$categoryId : null;
        $warnPercent = (int) $request->input('warn_percent', 80);

        try {
            Budget::createOrUpdate($userId, [
                'category_id' => $categoryId,
                'period_start' => $periodStart,
                'amount' => $amountInPaisa,
                'warn_percent' => $warnPercent,
            ]);

            Session::setFlash('success', 'Budget saved successfully.');
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Failed to save budget: ' . $e->getMessage());
        }

        return Response::redirect(url('/budgets'));
    }

    public function delete(Request $request, string $id): Response
    {
        $userId = (int) Auth::id();
        Budget::delete((int)$id, $userId);
        Session::setFlash('success', 'Budget removed.');
        return Response::redirect(url('/budgets'));
    }
}
