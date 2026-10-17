<div style="display: flex; flex-direction: column; gap: 20px;">

  <!-- Header & Actions -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 2px;">Transactions</h2>
      <p style="font-size: 0.875rem; color: var(--ink-muted);">
        Total <?= $pagination['total'] ?> transactions found
      </p>
    </div>

    <div style="display: flex; gap: 8px;">
      <button type="button" class="btn btn-primary" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'expense'}}))">
        + Add Expense
      </button>
      <button type="button" class="btn btn-secondary" onclick="window.dispatchEvent(new CustomEvent('open-quick-add', {detail: {type: 'income'}}))">
        + Add Income
      </button>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-body" style="padding: 16px 20px;">
      <form method="GET" action="/transactions" style="display: flex; flex-direction: column; gap: 12px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
          
          <!-- Search -->
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Search Note / Contact</label>
            <input type="text" name="search" class="form-input" placeholder="e.g. rent, groceries..." value="<?= e($filters['search'] ?? '') ?>" style="min-height: 38px; font-size: 0.875rem;">
          </div>

          <!-- Type Filter -->
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Type</label>
            <select name="type" class="form-select" style="min-height: 38px; font-size: 0.875rem;">
              <option value="">All Types</option>
              <option value="expense" <?= ($filters['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
              <option value="income" <?= ($filters['type'] ?? '') === 'income' ? 'selected' : '' ?>>Income</option>
              <option value="transfer" <?= ($filters['type'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
            </select>
          </div>

          <!-- Account Filter -->
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Account</label>
            <select name="account_id" class="form-select" style="min-height: 38px; font-size: 0.875rem;">
              <option value="">All Accounts</option>
              <?php foreach ($accounts as $acc): ?>
                <option value="<?= $acc['id'] ?>" <?= ((string)($filters['account_id'] ?? '') === (string)$acc['id']) ? 'selected' : '' ?>>
                  <?= e($acc['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Category Filter -->
          <div>
            <label class="form-label" style="font-size: 0.75rem;">Category</label>
            <select name="category_id" class="form-select" style="min-height: 38px; font-size: 0.875rem;">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ((string)($filters['category_id'] ?? '') === (string)$cat['id']) ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?> (<?= e($cat['type']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Date Range -->
          <div>
            <label class="form-label" style="font-size: 0.75rem;">From Date</label>
            <input type="date" name="date_from" class="form-input" value="<?= e($filters['date_from'] ?? '') ?>" style="min-height: 38px; font-size: 0.875rem;">
          </div>
          <div>
            <label class="form-label" style="font-size: 0.75rem;">To Date</label>
            <input type="date" name="date_to" class="form-input" value="<?= e($filters['date_to'] ?? '') ?>" style="min-height: 38px; font-size: 0.875rem;">
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px;">
          <a href="/transactions" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem;">
            Reset Filters
          </a>
          <button type="submit" class="btn btn-primary" style="padding: 6px 18px; font-size: 0.8125rem;">
            Apply Filters
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Transactions Table -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-body" style="padding: 0;">
      <?php if (empty($transactions)): ?>
        <div style="text-align: center; padding: 48px 24px;">
          <h4 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 8px; color: var(--ink-primary);">No transactions match your search</h4>
          <p style="color: var(--ink-muted); font-size: 0.875rem;">Try adjusting your filters or date range.</p>
        </div>
      <?php else: ?>
        <div style="overflow-x: auto;">
          <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
            <thead>
              <tr style="background-color: var(--bg-subtle); border-bottom: 1px solid var(--border-color); color: var(--ink-muted); font-weight: 600;">
                <th style="padding: 12px 20px;">Date</th>
                <th style="padding: 12px 16px;">Type & Category</th>
                <th style="padding: 12px 16px;">Note / Tags</th>
                <th style="padding: 12px 16px;">Account</th>
                <th style="padding: 12px 20px; text-align: right;">Amount</th>
                <th style="padding: 12px 16px; text-align: center; width: 60px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $tx): ?>
                <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 150ms ease;">
                  <td style="padding: 14px 20px; color: var(--ink-muted); white-space: nowrap;">
                    <?= date('d M, Y', strtotime($tx['tx_date'])) ?>
                  </td>

                  <td style="padding: 14px 16px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span class="badge <?= $tx['type'] === 'income' ? 'badge-income' : ($tx['type'] === 'transfer' ? 'badge-primary' : 'badge-expense') ?>">
                        <?= strtoupper($tx['type']) ?>
                      </span>
                      <span style="font-weight: 600; color: var(--ink-primary);">
                        <?= e($tx['category_name'] ?: ($tx['type'] === 'transfer' ? 'Transfer' : 'Uncategorized')) ?>
                      </span>
                    </div>
                  </td>

                  <td style="padding: 14px 16px; max-width: 260px;">
                    <div style="color: var(--ink-secondary); text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                      <?= e($tx['note'] ?: '-') ?>
                    </div>
                    <?php if (!empty($tx['tags'])): ?>
                      <div style="font-size: 0.75rem; color: var(--ink-muted); margin-top: 2px;">#<?= e($tx['tags']) ?></div>
                    <?php endif; ?>
                  </td>

                  <td style="padding: 14px 16px; white-space: nowrap;">
                    <span style="font-weight: 500; color: var(--ink-primary);"><?= e($tx['account_name']) ?></span>
                    <?php if ($tx['type'] === 'transfer' && !empty($tx['to_account_name'])): ?>
                      <span style="color: var(--ink-muted);">→ <?= e($tx['to_account_name']) ?></span>
                    <?php endif; ?>
                  </td>

                  <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: <?= $tx['type'] === 'income' ? 'var(--color-income)' : ($tx['type'] === 'transfer' ? 'var(--color-primary)' : 'var(--color-expense)') ?>;" class="tabular-nums">
                    <?= $tx['type'] === 'income' ? '+' : ($tx['type'] === 'transfer' ? '' : '-') ?><?= money($tx['amount']) ?>
                  </td>

                  <td style="padding: 14px 16px; text-align: center;">
                    <form method="POST" action="/transactions/<?= $tx['id'] ?>/delete" onsubmit="return confirm('Delete this transaction?')">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost btn-icon" style="width: 30px; height: 30px; color: var(--ink-muted);" title="Delete">
                        ✕
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($pagination['last_page'] > 1): ?>
          <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; background-color: var(--bg-subtle); border-top: 1px solid var(--border-color);">
            <div style="font-size: 0.8125rem; color: var(--ink-muted);">
              Page <?= $pagination['current_page'] ?> of <?= $pagination['last_page'] ?>
            </div>
            <div style="display: flex; gap: 6px;">
              <?php if ($pagination['current_page'] > 1): ?>
                <a href="?page=<?= $pagination['current_page'] - 1 ?>" class="btn btn-secondary" style="padding: 4px 12px; font-size: 0.8125rem;">
                  ← Previous
                </a>
              <?php endif; ?>
              <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?page=<?= $pagination['current_page'] + 1 ?>" class="btn btn-secondary" style="padding: 4px 12px; font-size: 0.8125rem;">
                  Next →
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>
