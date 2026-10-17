<div style="display: flex; flex-direction: column; gap: 24px;">

  <!-- Hero Card -->
  <div class="card-hero">
    <div style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85; margin-bottom: 4px;">
      <?= e(__('total_balance')) ?>
    </div>
    <div style="font-size: 2.5rem; font-weight: 800; line-height: 1.1; margin-bottom: 24px;" class="tabular-nums">
      <?= money(0, $user['currency'] ?? 'BDT') ?>
    </div>

    <div class="grid-3" style="background: rgba(255, 255, 255, 0.12); padding: 16px 20px; border-radius: var(--radius-lg); backdrop-filter: blur(8px);">
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;"><?= e(__('income')) ?> (<?= e(__('this_month')) ?>)</div>
        <div style="font-size: 1.125rem; font-weight: 700; color: #75FB4C;" class="tabular-nums">+<?= money(0, $user['currency'] ?? 'BDT') ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;"><?= e(__('expense')) ?> (<?= e(__('this_month')) ?>)</div>
        <div style="font-size: 1.125rem; font-weight: 700; color: #FFA299;" class="tabular-nums">-<?= money(0, $user['currency'] ?? 'BDT') ?></div>
      </div>
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;"><?= e(__('net_savings')) ?></div>
        <div style="font-size: 1.125rem; font-weight: 700;" class="tabular-nums"><?= money(0, $user['currency'] ?? 'BDT') ?></div>
      </div>
    </div>
  </div>

  <!-- Phase 1 Welcome & Architecture Status -->
  <div class="grid-2">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">System Status (Phase 1)</h3>
        <span class="badge badge-income">Foundation Active</span>
      </div>
      <div class="card-body">
        <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.875rem;">
          <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
            <span style="color: var(--ink-secondary);">Logged In User:</span>
            <strong><?= e($user['name'] ?? 'User') ?> (<?= e($user['role'] ?? 'user') ?>)</strong>
          </div>
          <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
            <span style="color: var(--ink-secondary);">Database Driver:</span>
            <strong><?= e(\App\Core\Config::get('db.driver', 'mysql')) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; padding-bottom: 8px; border-bottom: 1px solid var(--border-color);">
            <span style="color: var(--ink-secondary);">Session Lifetime:</span>
            <strong><?= e(\App\Core\Config::get('session.lifetime', 120)) ?> min (Protected in storage/sessions)</strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: var(--ink-secondary);">CSRF Protection:</span>
            <span class="badge badge-primary">Active (Strict)</span>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Design System & UI Tokens</h3>
        <span class="badge badge-primary">Tokens Ready</span>
      </div>
      <div class="card-body">
        <p style="font-size: 0.875rem; color: var(--ink-secondary); margin-bottom: 16px;">
          Hand-crafted vanilla CSS tokens with light and dark mode, 8px grid, smooth transitions, and high contrast typography.
        </p>

        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
          <span class="badge badge-income">Income: +24%</span>
          <span class="badge badge-expense">Expense: -12%</span>
          <span class="badge badge-warning">Budget Warning: 85%</span>
          <span class="badge badge-primary">bKash Active</span>
        </div>

        <div style="margin-top: 16px; display: flex; gap: 8px;">
          <button class="btn btn-secondary" style="font-size: 0.8125rem;" onclick="window.PFM.toast('Test notification banner with undo toast capability!', 'success')">
            Test Toast
          </button>
          <button class="btn btn-ghost" style="font-size: 0.8125rem;" onclick="window.PFM.toggleTheme()">
            Switch Theme
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Activity Empty State / Placeholder -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title"><?= e(__('recent_transactions')) ?></h3>
      <span class="badge badge-warning">Phase 2 Next</span>
    </div>
    <div class="card-body" style="text-align: center; padding: 48px 24px;">
      <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: var(--radius-full); background-color: var(--bg-subtle); color: var(--ink-muted); margin-bottom: 16px;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
      </div>
      <h4 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 8px; color: var(--ink-primary);"><?= e(__('no_transactions')) ?></h4>
      <p style="color: var(--ink-muted); font-size: 0.875rem; max-width: 420px; margin: 0 auto;">
        Accounts, categories, and fast 2-tap transaction entry will be enabled in Phase 2.
      </p>
    </div>
  </div>

</div>
