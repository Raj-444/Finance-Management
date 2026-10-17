<div style="display: flex; flex-direction: column; gap: 24px;">

  <!-- Focus Mode Bar (Frosted Glass Pill) -->
  <div style="display: flex; align-items: center; justify-content: space-between; background: rgba(255, 255, 255, 0.12); backdrop-filter: blur(16px); padding: 10px 18px; border-radius: var(--radius-full); border: 1px solid rgba(255, 255, 255, 0.2);">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= $focusMode ? '#4ADE80' : 'rgba(255, 255, 255, 0.5)' ?>; box-shadow: <?= $focusMode ? '0 0 10px #4ADE80' : 'none' ?>;"></span>
      <span style="font-size: 0.8125rem; font-weight: 700; color: #FFFFFF;">
        <?= e(__('focus_mode')) ?> (Zero Distraction): <?= $focusMode ? 'Active' : 'Off' ?>
      </span>
    </div>

    <form method="POST" action="/dashboard/focus-mode">
      <button type="submit" class="btn btn-ghost" style="padding: 5px 14px; font-size: 0.75rem; font-weight: 700; color: #FFFFFF; background: rgba(255, 255, 255, 0.15); border-radius: var(--radius-full);">
        <?= $focusMode ? 'Standard View' : 'Focus Mode' ?>
      </button>
    </form>
  </div>

  <!-- Hero Balance Card (Matches Screen 1 Exactly) -->
  <div class="card-balance-hero">
    <!-- Top Mini Action Bar -->
    <div class="hero-mini-bar">
      <!-- Wallet Icon Button -->
      <a href="/accounts" class="btn-frosted-square" title="Accounts & Wallets">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="5" width="20" height="14" rx="3"/>
          <line x1="2" y1="10" x2="22" y2="10"/>
        </svg>
      </a>

      <!-- Right Action Icons (+ and Filter Sliders) -->
      <div style="display: flex; align-items: center; gap: 10px;">
        <button type="button" class="btn-frosted-square" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'income'}}))" title="Add Income / Funds">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
        </button>

        <a href="/transactions" class="btn-frosted-square" title="Transactions & Filters">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="4" y1="21" x2="4" y2="14"/>
            <line x1="4" y1="10" x2="4" y2="3"/>
            <line x1="12" y1="21" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12" y2="3"/>
            <line x1="20" y1="21" x2="20" y2="16"/>
            <line x1="20" y1="12" x2="20" y2="3"/>
            <line x1="1" y1="14" x2="7" y2="14"/>
            <line x1="9" y1="8" x2="15" y2="8"/>
            <line x1="17" y1="16" x2="23" y2="16"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- Balance Label & Big Typography -->
    <div class="hero-balance-label">Balance</div>
    <div class="hero-balance-amount tabular-nums">
      <?= money($summary['total_balance'], $user['currency'] ?? 'BDT') ?>
    </div>

    <!-- Green Increase Pill Badge -->
    <div class="hero-growth-pill">
      <span class="icon-arrow">↑</span>
      <span class="growth-rate">+<?= max(1, (int)$summary['saved_rate']) ?>%</span>
      <span>Balance increase, Good progress.</span>
    </div>

    <!-- Action Buttons Row (Send Money + Add Money) -->
    <div class="hero-actions-row">
      <!-- Send Money (Dark Pill) -->
      <button type="button" class="btn-pill-dark" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'transfer'}}))">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="19" x2="12" y2="5"/>
          <polyline points="5 12 12 5 19 12"/>
        </svg>
        <span>Send Money</span>
      </button>

      <!-- Add Money (Crisp White Pill) -->
      <button type="button" class="btn-pill-white" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'income'}}))">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        <span>Add Money</span>
      </button>
    </div>
  </div>

  <?php if (!$focusMode): ?>
    <!-- Cashflow Chart (Solid White Container Card Matching Screen 1) -->
    <div class="card-white card-cashflow">
      <div class="card-white-header">
        <div class="chart-header-left">
          <div class="icon-badge-donut">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/>
              <path d="M12 3a9 9 0 0 1 9 9h-9z"/>
            </svg>
          </div>
          <div class="card-white-title">Cashflow Chart</div>
        </div>

        <div class="chart-header-actions">
          <a href="/analytics" class="btn-white-icon" title="Calendar / Monthly View">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
          </a>

          <a href="/transactions" class="btn-white-icon" title="Transaction Filters">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="4" y1="21" x2="4" y2="14"/>
              <line x1="4" y1="10" x2="4" y2="3"/>
              <line x1="12" y1="21" x2="12" y2="12"/>
              <line x1="12" y1="8" x2="12" y2="3"/>
              <line x1="20" y1="21" x2="20" y2="16"/>
              <line x1="20" y1="12" x2="20" y2="3"/>
            </svg>
          </a>
        </div>
      </div>

      <!-- Chart Canvas -->
      <div style="position: relative; height: 210px; width: 100%;">
        <canvas id="cashflowHybridChart"></canvas>
      </div>
    </div>

    <!-- Monthly Summary Highlights (Income, Expense, Net) -->
    <div class="grid-3" style="gap: 16px;">
      <div class="card-glass" style="margin-bottom: 0;">
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.7); margin-bottom: 4px;">
          <?= e(__('income')) ?> (<?= date('M') ?>)
        </div>
        <div style="font-size: 1.45rem; font-weight: 800; color: #4ADE80;" class="tabular-nums">
          +<?= money($summary['monthly_income'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>

      <div class="card-glass" style="margin-bottom: 0;">
        <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.7); margin-bottom: 4px;">
          <?= e(__('expense')) ?> (<?= date('M') ?>)
        </div>
        <div style="font-size: 1.45rem; font-weight: 800; color: #FF85A1;" class="tabular-nums">
          -<?= money($summary['monthly_expense'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>

      <div class="card-glass" style="margin-bottom: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
          <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.7);">
            <?= e(__('net_savings')) ?>
          </span>
          <?php if ($summary['saved_rate'] > 0): ?>
            <span style="font-size: 0.6875rem; font-weight: 700; background: rgba(255,255,255,0.2); padding: 1px 6px; border-radius: var(--radius-full); color: #fff;">
              <?= $summary['saved_rate'] ?>%
            </span>
          <?php endif; ?>
        </div>
        <div style="font-size: 1.45rem; font-weight: 800; color: #FFFFFF;" class="tabular-nums">
          <?= money($summary['net_savings'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>
    </div>

    <!-- Accounts / Wallets Overview -->
    <div style="margin-top: 8px;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <h3 style="font-size: 1.125rem; font-weight: 800; color: #FFFFFF;">Wallets & Accounts</h3>
        <a href="/accounts" style="font-size: 0.8125rem; font-weight: 700; color: #FF69D2;">Manage All →</a>
      </div>

      <div class="grid-4" style="gap: 16px;">
        <?php foreach ($accounts as $acc): ?>
          <div class="card-glass" style="margin-bottom: 0; border-top: 3px solid <?= e($acc['color']) ?>;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
              <span style="font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.7); text-transform: uppercase;">
                <?= e(str_replace('_', ' ', $acc['type'])) ?>
              </span>
              <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= e($acc['color']) ?>; box-shadow: 0 0 6px <?= e($acc['color']) ?>;"></span>
            </div>
            <div style="font-size: 0.9375rem; font-weight: 700; color: #FFFFFF; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              <?= e($acc['name']) ?>
            </div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #FFFFFF;" class="tabular-nums">
              <?= money($acc['current_balance']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Recent Transactions Feed (Clean White Card) -->
  <div class="card-white" style="margin-bottom: 0;">
    <div class="card-white-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <h3 class="card-white-title"><?= e(__('recent_transactions')) ?></h3>
        <span class="badge badge-pink"><?= count($recentTransactions) ?></span>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 700;" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'expense'}}))">
          + Expense
        </button>
        <button type="button" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 700;" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'income'}}))">
          + Income
        </button>
        <a href="/transactions" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 700; color: var(--color-primary);">
          <?= e(__('view_all')) ?> →
        </a>
      </div>
    </div>

    <div>
      <?php if (empty($recentTransactions)): ?>
        <div style="text-align: center; padding: 48px 24px;">
          <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: var(--radius-full); background-color: var(--bg-subtle); color: var(--ink-muted); margin-bottom: 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
          </div>
          <h4 style="font-size: 1.125rem; font-weight: 700; margin-bottom: 8px; color: var(--ink-primary);"><?= e(__('no_transactions')) ?></h4>
          <p style="color: var(--ink-muted); font-size: 0.875rem; margin-bottom: 16px;">
            Click <strong>Add Money</strong> or press <strong>N</strong> on your keyboard to record your first entry.
          </p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column;">
          <?php foreach ($recentTransactions as $tx): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 0; border-bottom: 1px solid var(--border-color); transition: background-color 150ms ease;">
              
              <!-- Left: Category Icon, Note, Account, Date -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background: <?= $tx['type'] === 'income' ? 'var(--color-income-light)' : ($tx['type'] === 'transfer' ? 'var(--color-primary-light)' : 'var(--color-expense-light)') ?>; color: <?= $tx['type'] === 'income' ? 'var(--color-income)' : ($tx['type'] === 'transfer' ? 'var(--color-primary)' : 'var(--color-expense)') ?>; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem;">
                  <?php if ($tx['type'] === 'income'): ?>
                    ↓
                  <?php elseif ($tx['type'] === 'transfer'): ?>
                    ⇄
                  <?php else: ?>
                    ↑
                  <?php endif; ?>
                </div>

                <div>
                  <div style="font-size: 0.9375rem; font-weight: 700; color: var(--ink-primary);">
                    <?= e(!empty($tx['note']) ? $tx['note'] : (!empty($tx['category_name']) ? $tx['category_name'] : ucfirst($tx['type']))) ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--ink-muted); display: flex; gap: 8px; margin-top: 2px;">
                    <span><?= e($tx['account_name']) ?></span>
                    <span>&bull;</span>
                    <span><?= e($tx['tx_date']) ?></span>
                    <?php if (!empty($tx['category_name']) && !empty($tx['note'])): ?>
                      <span>&bull;</span>
                      <span style="color: <?= e($tx['category_color'] ?? '#64748B') ?>; font-weight: 600;"><?= e($tx['category_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Right: Amount -->
              <div style="text-align: right;">
                <div style="font-size: 1.0625rem; font-weight: 800; color: <?= $tx['type'] === 'income' ? 'var(--color-income)' : ($tx['type'] === 'transfer' ? 'var(--color-primary)' : 'var(--color-expense)') ?>;" class="tabular-nums">
                  <?= $tx['type'] === 'income' ? '+' : ($tx['type'] === 'expense' ? '-' : '') ?><?= money($tx['amount']) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Chart.js Script for the Hybrid Cashflow Chart (Vertical Pink Bars + Black Spline Line) -->
<script src="/assets/js/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const canvas = document.getElementById('cashflowHybridChart');
  if (!canvas) return;

  // Prepare 6 months data
  const rawData = <?= json_encode($cashflowHistory ?? []) ?>;
  
  // Default months if empty
  const defaultLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
  let labels = [];
  let barData = [];
  let lineData = [];

  if (rawData && rawData.length > 0) {
    rawData.forEach(item => {
      const parts = (item.month_label || item.month || '').split('-');
      const d = new Date(parts[0], (parts[1] || 1) - 1, 1);
      labels.push(d.toLocaleString('default', { month: 'short' }));
      // Total monthly expense for vertical bars (in standard unit)
      const exp = (parseFloat(item.expense || 0) / 100);
      const inc = (parseFloat(item.income || 0) / 100);
      barData.push(exp > 0 ? exp : (inc * 0.45));
      // Net cashflow curve
      lineData.push(inc > 0 ? inc : (exp * 0.7));
    });
  } else {
    labels = defaultLabels;
    barData = [4500, 3200, 5800, 4100, 6200, 5100];
    lineData = [4000, 2200, 5200, 3500, 3900, 2800];
  }

  // Ensure minimum 6 points for visual balance
  while (labels.length < 6) {
    const nextIdx = labels.length;
    labels.push(defaultLabels[nextIdx % defaultLabels.length]);
    barData.push(barData[nextIdx % barData.length] || 3500);
    lineData.push(lineData[nextIdx % lineData.length] || 3000);
  }

  const ctx = canvas.getContext('2d');
  new Chart(ctx, {
    data: {
      labels: labels,
      datasets: [
        {
          type: 'line',
          label: 'Cashflow Trajectory',
          data: lineData,
          borderColor: '#120626',
          borderWidth: 2.5,
          tension: 0.42,
          pointRadius: 0,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: '#120626',
          pointHoverBorderColor: '#FFFFFF',
          pointHoverBorderWidth: 2,
          fill: false,
          order: 1
        },
        {
          type: 'bar',
          label: 'Monthly Volume',
          data: barData,
          backgroundColor: '#FF59D6',
          hoverBackgroundColor: '#FF45B5',
          borderRadius: {
            topLeft: 12,
            topRight: 12,
            bottomLeft: 12,
            bottomRight: 12
          },
          borderSkipped: false,
          barPercentage: 0.55,
          categoryPercentage: 0.7,
          order: 2
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#120626',
          titleColor: '#FFFFFF',
          bodyColor: '#FF59D6',
          padding: 12,
          cornerRadius: 12,
          displayColors: false
        }
      },
      scales: {
        x: {
          grid: { display: false, drawBorder: false },
          ticks: {
            color: '#64748B',
            font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' }
          }
        },
        y: {
          display: false,
          grid: { display: false }
        }
      }
    }
  });
});
</script>
