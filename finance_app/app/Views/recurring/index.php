<div class="page-container" x-data="{
  modalOpen: false,
  deleteModal: false,
  deleteId: null,
  deleteNote: '',
  type: 'expense'
}">
  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 class="page-title" style="margin: 0 0 4px 0;">
        Recurring Subscriptions & Bills
      </h1>
      <p class="page-subtitle" style="margin: 0;">
        Automate regular monthly bills (WiFi, house rent, utilities) and salary income.
      </p>
    </div>

    <button type="button" class="btn btn-primary" @click="modalOpen = true" style="display: flex; align-items: center; gap: 8px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>Schedule Recurring Rule</span>
    </button>
  </div>

  <!-- Rules List -->
  <?php if (empty($rules)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
        </svg>
      </div>
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 8px;">No Recurring Rules Scheduled</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); max-width: 420px; margin: 0 auto 20px;">
        Never miss rent or subscription payments. Add rules and let the cron daemon automatically post them and factor them into your monthly forecast.
      </p>
      <button type="button" class="btn btn-primary" @click="modalOpen = true">Schedule First Rule</button>
    </div>
  <?php else: ?>
    <div class="card" style="padding: 0; overflow: hidden;">
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Type / Description</th>
              <th>Category</th>
              <th>Account</th>
              <th>Cadence</th>
              <th>Next Run</th>
              <th style="text-align: right;">Amount</th>
              <th style="text-align: center;">Auto-Post</th>
              <th style="text-align: center;">Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rules as $r): ?>
              <?php
                $isExpense = ($r['type'] === 'expense');
                $isActive = ((int)$r['is_active'] === 1);
                $isAuto = ((int)$r['auto_post'] === 1);
              ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; background: <?= $isExpense ? 'var(--color-danger-light)' : 'var(--color-success-light)' ?>; color: <?= $isExpense ? 'var(--color-danger)' : 'var(--color-success)' ?>;">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <?php if ($isExpense): ?>
                          <line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>
                        <?php else: ?>
                          <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
                        <?php endif; ?>
                      </svg>
                    </div>
                    <div>
                      <span style="font-weight: 600; color: var(--ink-primary);"><?= e(!empty($r['note']) ? $r['note'] : ucfirst($r['type'])) ?></span>
                    </div>
                  </div>
                </td>

                <td>
                  <?php if (!empty($r['category_name'])): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border-radius: var(--radius-full); font-size: 0.75rem; background: <?= e($r['category_color'] ?? '#5B5BD6') ?>15; color: <?= e($r['category_color'] ?? '#5B5BD6') ?>; font-weight: 600;">
                      <?= e($r['category_name']) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: var(--ink-muted); font-size: 0.8125rem;">&mdash;</span>
                  <?php endif; ?>
                </td>

                <td>
                  <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-primary);"><?= e($r['account_name'] ?? 'Account') ?></span>
                </td>

                <td>
                  <span style="font-size: 0.8125rem; color: var(--ink-muted); text-transform: capitalize;">
                    <?= ((int)$r['interval_n'] > 1) ? 'Every ' . $r['interval_n'] . ' ' . $r['frequency'] . 's' : ucfirst($r['frequency']) ?>
                  </span>
                </td>

                <td>
                  <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-primary);"><?= e($r['next_run']) ?></span>
                </td>

                <td style="text-align: right; font-weight: 700; color: <?= $isExpense ? 'var(--color-danger)' : 'var(--color-success)' ?>;">
                  <?= $isExpense ? '-' : '+' ?><?= format_money((int)$r['amount']) ?>
                </td>

                <td style="text-align: center;">
                  <span style="font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: var(--radius-full); background: <?= $isAuto ? 'var(--color-success-light)' : 'var(--surface-bg)' ?>; color: <?= $isAuto ? 'var(--color-success)' : 'var(--ink-muted)' ?>;">
                    <?= $isAuto ? 'Auto-Post' : 'Reminder' ?>
                  </span>
                </td>

                <td style="text-align: center;">
                  <form action="/recurring/<?= $r['id'] ?>/toggle" method="POST" style="display: inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-ghost" style="padding: 2px 8px; font-size: 0.75rem; font-weight: 600; border-radius: var(--radius-full); background: <?= $isActive ? 'var(--color-primary-light)' : 'var(--surface-bg)' ?>; color: <?= $isActive ? 'var(--color-primary)' : 'var(--ink-muted)' ?>;">
                      <?= $isActive ? 'Active' : 'Paused' ?>
                    </button>
                  </form>
                </td>

                <td style="text-align: right;">
                  <button type="button" class="btn btn-ghost" @click="deleteId = <?= $r['id'] ?>; deleteNote = '<?= addslashes($r['note'] ?: ucfirst($r['type'])) ?>'; deleteModal = true;" style="color: var(--color-danger); font-size: 0.8125rem; padding: 4px 6px;">
                    Delete
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- Modal: Schedule Recurring Rule -->
  <div x-show="modalOpen" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="modalOpen = false" style="width: 100%; max-width: 480px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">Schedule Recurring Rule</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="modalOpen = false">&times;</button>
      </div>

      <form method="POST" action="/recurring">
        <?= csrf_field() ?>

        <!-- Type Selector -->
        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label">Rule Type</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer;">
              <input type="radio" name="type" value="expense" x-model="type" checked>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--color-danger);">Expense / Bill</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer;">
              <input type="radio" name="type" value="income" x-model="type">
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--color-success);">Income / Salary</span>
            </label>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="amount">Amount (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="amount" id="amount" class="form-input" placeholder="e.g. 1500" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label" for="account_id">Account</label>
            <select name="account_id" id="account_id" class="form-input" required>
              <?php foreach ($accounts as $acc): ?>
                <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-input">
              <option value="">None / Uncategorized</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label" for="frequency">Cadence / Frequency</label>
            <select name="frequency" id="frequency" class="form-input">
              <option value="monthly" selected>Monthly (Once a month)</option>
              <option value="weekly">Weekly</option>
              <option value="daily">Daily</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="interval_n">Every N</label>
            <input type="number" min="1" max="12" name="interval_n" id="interval_n" class="form-input" value="1">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="next_run">First / Next Execution Date</label>
          <input type="date" name="next_run" id="next_run" class="form-input" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="note">Description / Note</label>
          <input type="text" name="note" id="note" class="form-input" placeholder="e.g. WiFi Bill, Gym Membership, House Rent" required>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
            <input type="checkbox" name="auto_post" value="1" checked style="width: 18px; height: 18px;">
            <span style="font-size: 0.875rem; color: var(--ink-primary); font-weight: 500;">
              Automatically post transaction when due via scheduled cron
            </span>
          </label>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="modalOpen = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Schedule Rule</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Confirmation -->
  <div x-show="deleteModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="deleteModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Delete Recurring Rule?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to stop and delete recurring rule <strong x-text="deleteNote"></strong>? Past transactions posted by this rule will not be deleted.
      </p>

      <form :action="'/recurring/' + deleteId + '/delete'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="deleteModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Delete</button>
      </form>
    </div>
  </div>
</div>
