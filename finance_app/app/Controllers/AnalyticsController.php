<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AnalyticsService;

class AnalyticsController
{
    public function index(Request $request): Response
    {
        $range = $request->query('range', '30d');

        switch ($range) {
            case '7d':
                $startDate = date('Y-m-d', strtotime('-7 days'));
                $endDate = date('Y-m-d');
                break;
            case 'month':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
            case 'custom':
                $startDate = $request->query('start_date') ?: date('Y-m-01');
                $endDate = $request->query('end_date') ?: date('Y-m-d');
                break;
            case '30d':
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
                $endDate = date('Y-m-d');
                break;
        }

        return View::render('analytics.index', [
            'title' => 'Analytics & Trends',
            'range' => $range,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ], 'app');
    }

    public function data(Request $request): Response
    {
        $userId = (int) Auth::id();
        $startDate = $request->query('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $request->query('end_date') ?: date('Y-m-d');

        $categoryBreakdown = AnalyticsService::getCategoryBreakdown($userId, $startDate, $endDate, 'expense');
        $incomeBreakdown = AnalyticsService::getCategoryBreakdown($userId, $startDate, $endDate, 'income');
        $dailyTrend = AnalyticsService::getDailyTrend($userId, $startDate, $endDate);
        $monthlyComparison = AnalyticsService::getMonthlyComparison($userId, 6);

        // Convert amounts from minor units (paisa) to major units for chart displays
        foreach ($categoryBreakdown as &$c) {
            $c['total_major'] = $c['total'] / 100.0;
        }
        foreach ($incomeBreakdown as &$c) {
            $c['total_major'] = $c['total'] / 100.0;
        }
        foreach ($dailyTrend as &$d) {
            $d['expense_major'] = $d['expense_total'] / 100.0;
            $d['income_major'] = $d['income_total'] / 100.0;
        }
        foreach ($monthlyComparison as &$m) {
            $m['income_major'] = $m['income'] / 100.0;
            $m['expense_major'] = $m['expense'] / 100.0;
        }

        return Response::json([
            'ok' => true,
            'data' => [
                'categories' => $categoryBreakdown,
                'incomes' => $incomeBreakdown,
                'daily_trend' => $dailyTrend,
                'monthly_comparison' => $monthlyComparison,
            ]
        ]);
    }
}
