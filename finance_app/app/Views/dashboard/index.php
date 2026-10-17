<div style="display: flex; flex-direction: column; gap: 24px;">

  <!-- Focus Mode Bar -->
  <div style="display: flex; align-items: center; justify-content: space-between; background-color: var(--bg-surface); padding: 12px 20px; border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
    <div style="display: flex; align-items: center; gap: 10px;">
      <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background-color: <?= $focusMode ? 'var(--color-income)' : 'var(--ink-muted)' ?>;"></span>
      <span style="font-size: 0.875rem; font-weight: 600; color: var(--ink-primary);">
        <?= e(__('focus_mode')) ?> (Zero Distraction): <?= $focusMode ? 'Enabled' : 'Disabled' ?>
      </span>
    </div>

    <form method="POST" action="/dashboard/focus-mode">
      <button type="submit" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 600;">
        <?= $focusMode ? 'Switch to Standard View' : 'Turn on Focus Mode' ?>
      </button>
    </form>
  </div>

  <!-- Hero Balance Card -->
  <div class="card-hero">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
      <div style="font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">
        <?= e(__('total_balance')) ?>
      </div>
      <div style="font-size: 0.8125rem; background: rgba(255, 255, 255, 0.2); padding: 4px 10px; border-radius: var(--radius-full); backdrop-filter: blur(4px);">
        Period: <?= date('M Y') ?>
      </div>
    </div>

    <div style="font-size: 2.75rem; font-weight: 800; line-height: 1.1; margin-bottom: 24px;" class="tabular-nums">
      <?= money($summary['total_balance'], $user['currency'] ?? 'BDT') ?>
    </div>

    <!-- Income / Expense / Net breakdown -->
    <div class="grid-3" style="background: rgba(255, 255, 255, 0.12); padding: 16px 20px; border-radius: var(--radius-lg); backdrop-filter: blur(8px);">
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;"><?= e(__('income')) ?> (<?= e(__('this_month')) ?>)</div>
        <div style="font-size: 1.25rem; font-weight: 700; color: #75FB4C;" class="tabular-nums">
          +<?= money($summary['monthly_income'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;"><?= e(__('expense')) ?> (<?= e(__('this_month')) ?>)</div>
        <div style="font-size: 1.25rem; font-weight: 700; color: #FFA299;" class="tabular-nums">
          -<?= money($summary['monthly_expense'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>
      <div>
        <div style="font-size: 0.75rem; opacity: 0.8; margin-bottom: 2px;">
          <?= e(__('net_savings')) ?>
          <?php if ($summary['saved_rate'] > 0): ?>
            <span style="font-size: 0.6875rem; background: rgba(255, 255, 255, 0.25); padding: 1px 6px; border-radius: 4px; margin-left: 4px;">
              <?= $summary['saved_rate'] ?>%
            </span>
          <?php endif; ?>
        </div>
        <div style="font-size: 1.25rem; font-weight: 700;" class="tabular-nums">
          <?= money($summary['net_savings'], $user['currency'] ?? 'BDT') ?>
        </div>
      </div>
    </div>
  </div>

  <?php if (!$focusMode): ?>
    <!-- Accounts Overview Grid -->
    <div>
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary);">Wallets & Accounts</h3>
        <a href="/accounts" style="font-size: 0.875rem; font-weight: 600; color: var(--color-primary);">Manage Accounts →</a>
      </div>

      <div class="grid-4" style="gap: 16px;">
        <?php foreach ($accounts as $acc): ?>
          <div class="card" style="margin-bottom: 0; border-top: 4px solid <?= e($acc['color']) ?>;">
            <div class="card-body" style="padding: 18px 20px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted); text-transform: uppercase;">
                  <?= e(str_replace('_', ' ', $acc['type'])) ?>
                </span>
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: <?= e($acc['color']) ?>;"></span>
              </div>
              <div style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 6px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                <?= e($acc['name']) ?>
              </div>
              <div style="font-size: 1.25rem; font-weight: 800; color: var(--ink-primary);" class="tabular-nums">
                <?= money($acc['current_balance']) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- Recent Transactions Feed -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <div style="display: flex; align-items: center; gap: 8px;">
        <h3 class="card-title"><?= e(__('recent_transactions')) ?></h3>
        <span class="badge badge-primary"><?= count($recentTransactions) ?></span>
      </div>
      <div style="display: flex; gap: 8px;">
        <button type="button" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8125rem;" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'expense'}}))">
          + Add Expense
        </button>
        <button type="button" class="btn btn-secondary" style="padding: 6px 14px; font-size: 0.8125rem;" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'income'}}))">
          + Add Income
        </button>
        <a href="/transactions" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 600;">
          <?= e(__('view_all')) ?> →
        </a>
      </div>
    </div>

    <div class="card-body" style="padding: 0;">
      <?php if (empty($recentTransactions)): ?>
        <div style="text-align: center; padding: 48px 24px;">
          <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: var(--radius-full); background-color: var(--bg-subtle); color: var(--ink-muted); margin-bottom: 16px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
          </div>
          <h4 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 8px; color: var(--ink-primary);"><?= e(__('no_transactions')) ?></h4>
          <p style="color: var(--ink-muted); font-size: 0.875rem; margin-bottom: 16px;">
            Click <strong>Quick Add</strong> or press <strong>N</strong> on your keyboard to record your first entry.
          </p>
        </div>
      <?php else: ?>
        <div style="display: flex; flex-direction: column;">
          <?php foreach ($recentTransactions as $tx): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid var(--border-color); transition: background-color 150ms ease;">
              
              <!-- Left: Category color pill, Name, Account, Date -->
              <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 42px; height: 42px; border-radius: var(--radius-md); background-color: <?= $tx['type'] === 'income' ? 'var(--color-income-light)' : ($tx['type'] === 'transfer' ? 'var(--color-primary-light)' : 'var(--color-expense-light)') ?>; color: <?= $tx['type'] === 'income' ? 'var(--color-income)' : ($tx['type'] === 'transfer' ? 'var(--color-primary)' : 'var(--color-expense)') ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                  <?php if ($tx['type'] === 'income'): ?>
                    ↓
                  <?php elseif ($tx['type'] === 'transfer'): ?>
                    ⇄
                  <?php else: ?>
                    ↑
                  <?php endif; ?>
                </div>

                <div>
                  <div style="font-weight: 600; font-size: 0.9375rem; color: var(--ink-primary); display: flex; align-items: center; gap: 8px;">
                    <span><?= e($tx['category_name'] ?: ($tx['type'] === 'transfer' ? 'Transfer' : 'Uncategorized')) ?></span>
                    <?php if (!empty($tx['note'])): ?>
                      <span style="font-weight: 400; font-size: 0.8125rem; color: var(--ink-muted);">• <?= e($tx['note']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--ink-muted); margin-top: 2px;">
                    <span><?= date('d M, Y', strtotime($tx['tx_date'])) ?></span>
                    <span>•</span>
                    <span style="font-weight: 500; color: var(--ink-secondary);"><?= e($tx['account_name']) ?></span>
                    <?php if ($tx['type'] === 'transfer' && !empty($tx['to_account_name'])): ?>
                      <span>→ <?= e($tx['to_account_name']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- Right: Amount and Actions -->
              <div style="display: flex; align-items: center; gap: 16px;">
                <div style="text-align: right;">
                  <div style="font-size: 1.0625rem; font-weight: 700; color: <?= $tx['type'] === 'income' ? 'var(--color-income)' : ($tx['type'] === 'transfer' ? 'var(--color-primary)' : 'var(--color-expense)') ?>;" class="tabular-nums">
                    <?= $tx['type'] === 'income' ? '+' : ($tx['type'] === 'transfer' ? '' : '-') ?><?= money($tx['amount']) ?>
                  </div>
                  <span style="font-size: 0.6875rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink-muted);">
                    <?= e($tx['type']) ?>
                  </span>
                </div>

                <form method="POST" action="/transactions/<?= $tx['id'] ?>/delete" onsubmit="return confirm('Are you sure you want to delete this transaction?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-ghost btn-icon" style="width: 32px; height: 32px; color: var(--ink-muted);" title="Delete">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                  </button>
                </form>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
