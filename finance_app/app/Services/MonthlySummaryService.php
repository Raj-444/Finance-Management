<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\User;
use App\Models\Account;

class MonthlySummaryService
{
    /**
     * Compute financial metrics for a given user and year-month (YYYY-MM).
     * Defaults to the preceding calendar month if not specified.
     */
    public static function computeMetrics(int $userId, ?string $yearMonth = null): array
    {
        $user = User::findById($userId);
        if (!$user) {
            throw new \InvalidArgumentException("User not found.");
        }

        if (!$yearMonth) {
            $yearMonth = date('Y-m', (int)strtotime('first day of last month'));
        }

        $startDate = $yearMonth . '-01';
        $endDate = date('Y-m-t', (int)strtotime($startDate));

        // Income
        $incRow = Database::fetch(
            "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'income' AND tx_date BETWEEN ? AND ? AND deleted_at IS NULL",
            [$userId, $startDate, $endDate]
        );
        $totalIncome = (int)($incRow['total'] ?? 0);

        // Expense
        $expRow = Database::fetch(
            "SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND tx_date BETWEEN ? AND ? AND deleted_at IS NULL",
            [$userId, $startDate, $endDate]
        );
        $totalExpense = (int)($expRow['total'] ?? 0);

        $netSavings = $totalIncome - $totalExpense;
        $savingsRate = $totalIncome > 0 ? round(($netSavings / $totalIncome) * 100, 1) : 0.0;

        // Top 3 Expense Categories
        $topCategories = Database::fetchAll(
            "SELECT c.name, c.color, SUM(t.amount) as total 
             FROM transactions t
             LEFT JOIN categories c ON t.category_id = c.id
             WHERE t.user_id = ? AND t.type = 'expense' AND t.tx_date BETWEEN ? AND ? AND t.deleted_at IS NULL
             GROUP BY c.id, c.name, c.color
             ORDER BY total DESC
             LIMIT 3",
            [$userId, $startDate, $endDate]
        );

        $categoriesData = [];
        foreach ($topCategories as $cat) {
            $catTotal = (int)$cat['total'];
            $pct = $totalExpense > 0 ? round(($catTotal / $totalExpense) * 100, 1) : 0.0;
            $categoriesData[] = [
                'name' => $cat['name'] ?: 'Uncategorized',
                'color' => $cat['color'] ?: '#64748B',
                'total' => $catTotal,
                'percentage' => $pct,
            ];
        }

        // Net worth across active accounts
        $totalNetWorth = Account::getTotalBalance($userId);

        return [
            'user' => $user,
            'year_month' => $yearMonth,
            'month_label' => date('F Y', (int)strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_savings' => $netSavings,
            'savings_rate' => $savingsRate,
            'top_categories' => $categoriesData,
            'net_worth' => $totalNetWorth,
        ];
    }

    /**
     * Build responsive HTML email template
     */
    public static function buildHtml(array $metrics): string
    {
        $currency = $metrics['user']['currency'] ?? 'BDT';
        $incomeFmt = \money($metrics['total_income'], $currency);
        $expenseFmt = \money($metrics['total_expense'], $currency);
        $netPrefix = $metrics['net_savings'] >= 0 ? '+' : '';
        $netFmt = $netPrefix . \money($metrics['net_savings'], $currency);
        $netColor = $metrics['net_savings'] >= 0 ? '#12B76A' : '#F04438';
        $netWorthFmt = \money($metrics['net_worth'], $currency);
        $userName = htmlspecialchars($metrics['user']['name']);
        $monthLabel = $metrics['month_label'];

        $categoryRowsHtml = '';
        if (!empty($metrics['top_categories'])) {
            foreach ($metrics['top_categories'] as $c) {
                $cName = htmlspecialchars($c['name']);
                $cTotal = \money($c['total'], $currency);
                $cPct = $c['percentage'];
                $cColor = htmlspecialchars($c['color']);
                $categoryRowsHtml .= "
                <tr>
                    <td style='padding: 10px 0; border-bottom: 1px solid #E2E8F0; font-size: 14px;'>
                        <span style='display:inline-block; width:10px; height:10px; border-radius:50%; background-color:{$cColor}; margin-right:8px;'></span>
                        {$cName}
                    </td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #E2E8F0; text-align: right; font-weight: 600; font-size: 14px;'>{$cTotal}</td>
                    <td style='padding: 10px 0; border-bottom: 1px solid #E2E8F0; text-align: right; color: #64748B; font-size: 13px;'>{$cPct}%</td>
                </tr>";
            }
        } else {
            $categoryRowsHtml = "<tr><td colspan='3' style='padding:16px 0; text-align:center; color:#94A3B8; font-size:14px;'>No expenses recorded in this period.</td></tr>";
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Monthly Financial Summary - {$monthLabel}</title>
</head>
<body style='margin:0; padding:0; background-color:#F8FAFC; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#0F172A;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#F8FAFC; padding: 32px 16px;'>
        <tr>
            <td align='center'>
                <table width='100%' style='max-width: 600px; background-color:#FFFFFF; border-radius: 16px; overflow:hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #E2E8F0;' cellpadding='0' cellspacing='0'>
                    <!-- Header -->
                    <tr>
                        <td style='background: linear-gradient(135deg, #5B5BD6 0%, #3B38A6 100%); padding: 32px; color: #FFFFFF;'>
                            <h2 style='margin: 0 0 6px 0; font-size: 22px; font-weight: 700; letter-spacing: -0.02em;'>Personal Finance Manager</h2>
                            <p style='margin: 0; font-size: 15px; opacity: 0.9;'>Monthly Financial Statement &bull; {$monthLabel}</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style='padding: 32px;'>
                            <p style='margin: 0 0 20px 0; font-size: 15px; line-height: 1.5;'>Hello <strong>{$userName}</strong>,</p>
                            <p style='margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #475569;'>
                                Here is your high-level financial review for <strong>{$monthLabel}</strong>. Keeping consistent track of your spending is the foundation of lasting wealth.
                            </p>

                            <!-- Metric Cards Grid -->
                            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom: 28px;'>
                                <tr>
                                    <td width='48%' style='background-color: #F8FAFC; border-radius: 12px; padding: 16px; border: 1px solid #E2E8F0; vertical-align: top;'>
                                        <div style='font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #12B76A; margin-bottom: 4px;'>Total Income</div>
                                        <div style='font-size: 18px; font-weight: 700; color: #0F172A;'>{$incomeFmt}</div>
                                    </td>
                                    <td width='4%'></td>
                                    <td width='48%' style='background-color: #F8FAFC; border-radius: 12px; padding: 16px; border: 1px solid #E2E8F0; vertical-align: top;'>
                                        <div style='font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #F04438; margin-bottom: 4px;'>Total Expenses</div>
                                        <div style='font-size: 18px; font-weight: 700; color: #0F172A;'>{$expenseFmt}</div>
                                    </td>
                                </tr>
                                <tr><td height='12'></td><td></td><td></td></tr>
                                <tr>
                                    <td width='48%' style='background-color: #F8FAFC; border-radius: 12px; padding: 16px; border: 1px solid #E2E8F0; vertical-align: top;'>
                                        <div style='font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;'>Net Savings</div>
                                        <div style='font-size: 18px; font-weight: 700; color: {$netColor};'>{$netFmt}</div>
                                    </td>
                                    <td width='4%'></td>
                                    <td width='48%' style='background-color: #F8FAFC; border-radius: 12px; padding: 16px; border: 1px solid #E2E8F0; vertical-align: top;'>
                                        <div style='font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748B; margin-bottom: 4px;'>Savings Rate</div>
                                        <div style='font-size: 18px; font-weight: 700; color: #5B5BD6;'>{$metrics['savings_rate']}%</div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Top Spending Categories -->
                            <div style='margin-bottom: 28px;'>
                                <h3 style='margin: 0 0 12px 0; font-size: 16px; font-weight: 700;'>Top Spending Categories</h3>
                                <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse: collapse;'>
                                    {$categoryRowsHtml}
                                </table>
                            </div>

                            <!-- Net Worth Glance -->
                            <div style='background-color: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 12px; padding: 16px; margin-bottom: 28px; text-align: center;'>
                                <div style='font-size: 13px; color: #4338CA; font-weight: 600; margin-bottom: 4px;'>Total Current Net Worth</div>
                                <div style='font-size: 22px; font-weight: 800; color: #1E1B4B;'>{$netWorthFmt}</div>
                            </div>

                            <!-- Button CTA -->
                            <div style='text-align: center; margin-top: 32px;'>
                                <a href='" . url('/dashboard') . "' style='display: inline-block; background-color: #5B5BD6; color: #FFFFFF; text-decoration: none; padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 600; box-shadow: 0 2px 8px rgba(91,91,214,0.3);'>Open App Dashboard</a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #F8FAFC; border-top: 1px solid #E2E8F0; padding: 20px 32px; font-size: 12px; color: #94A3B8; text-align: center;'>
                            You received this automated monthly statement because email summaries are active on your account.<br>
                            To adjust notification preferences, visit Settings in your dashboard.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
        ";
    }

    /**
     * Dispatch monthly summary email to user
     */
    public static function sendSummaryForUser(int $userId, ?string $yearMonth = null): array
    {
        $metrics = self::computeMetrics($userId, $yearMonth);
        $user = $metrics['user'];

        if (empty($user['email'])) {
            return ['ok' => false, 'error' => 'User does not have a registered email address.'];
        }

        $subject = "Your {$metrics['month_label']} Financial Summary";
        $html = self::buildHtml($metrics);
        $text = "Personal Finance Manager - {$metrics['month_label']} Summary\n\n"
              . "Total Income: {$metrics['total_income']}\n"
              . "Total Expenses: {$metrics['total_expense']}\n"
              . "Net Savings: {$metrics['net_savings']} ({$metrics['savings_rate']}%)\n\n"
              . "Visit your dashboard: " . url('/dashboard');

        $sent = Mailer::send($user['email'], $user['name'], $subject, $html, $text);

        if ($sent) {
            // Record activity log for deduplication
            Database::query(
                "INSERT INTO activity_log (user_id, action, entity, entity_id) VALUES (?, ?, ?, ?)",
                [$userId, 'monthly_summary_sent', 'summary', (int)date('Ym', (int)strtotime($metrics['start_date']))]
            );
        }

        return [
            'ok' => $sent,
            'metrics' => $metrics,
            'email_sent' => $sent,
        ];
    }
}
