<div class="page-container" x-data="{ modalOpen: false, deleteModal: false, deleteId: null, deleteName: '' }">
  <!-- Page Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 class="page-title" style="margin: 0 0 4px 0;">
        Smart Budgets & Forecast
      </h1>
      <p class="page-subtitle" style="margin: 0;">
        Active Period: <span style="font-weight: 700; color: #FFFFFF;"><?= e($periodStart) ?> &mdash; <?= e($periodEnd) ?></span>
        &bull; Day <span style="font-weight: 700; color: #FF45B5;"><?= e($elapsedDays) ?></span> of <?= e($daysInPeriod) ?> (<?= round(($elapsedDays / max(1, $daysInPeriod)) * 100) ?>% elapsed)
      </p>
    </div>

    <button type="button" class="btn btn-primary" @click="modalOpen = true" style="display: flex; align-items: center; gap: 8px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>Set New Budget</span>
    </button>
  </div>

  <!-- Budgets List -->
  <?php if (empty($budgets)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
        </svg>
      </div>
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 8px;">No Budgets Set For This Cycle</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); max-width: 420px; margin: 0 auto 20px;">
        Take control of your spending. Set an overall monthly budget or per-category limit, and let our run-rate engine project your month-end spend.
      </p>
      <button type="button" class="btn btn-primary" @click="modalOpen = true">Set Monthly Budget</button>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
      <?php foreach ($budgets as $b): ?>
        <?php
          $level = $b['level'];
          $badgeBg = 'var(--color-success-light)';
          $badgeColor = 'var(--color-success)';
          $badgeText = 'On Track';
          $barColor = 'var(--color-success)';

          if ($level === 'warn') {
              $badgeBg = 'var(--color-warning-light)';
              $badgeColor = 'var(--color-warning)';
              $badgeText = 'Near Limit (' . $b['warn_percent'] . '%)';
              $barColor = 'var(--color-warning)';
          } elseif ($level === 'forecast_exceeded') {
              $badgeBg = '#FFF4ED';
              $badgeColor = '#FB6514';
              $badgeText = 'Projected Over';
              $barColor = '#FB6514';
          } elseif ($level === 'exceeded') {
              $badgeBg = 'var(--color-danger-light)';
              $badgeColor = 'var(--color-danger)';
              $badgeText = 'Exceeded';
              $barColor = 'var(--color-danger)';
          }
        ?>
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <!-- Header -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: <?= e($b['category_color'] ?? '#5B5BD6') ?>15; color: <?= e($b['category_color'] ?? '#5B5BD6') ?>; display: flex; align-items: center; justify-content: center;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                  </svg>
                </div>
                <div>
                  <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0;">
                    <?= e($b['category_name']) ?>
                  </h3>
                  <span style="font-size: 0.75rem; color: var(--ink-muted);">
                    <?= $b['category_id'] ? 'Category Budget' : 'Overall Monthly Budget' ?>
                  </span>
                </div>
              </div>

              <span style="padding: 4px 8px; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>;">
                <?= $badgeText ?>
              </span>
            </div>

            <!-- Spend vs Budget Numbers -->
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <div>
                <span style="font-size: 1.25rem; font-weight: 800; color: var(--ink-primary);">
                  <?= format_money((int)$b['spent_amount']) ?>
                </span>
                <span style="font-size: 0.8125rem; color: var(--ink-muted);"> spent</span>
              </div>
              <div style="font-size: 0.875rem; color: var(--ink-muted);">
                of <span style="font-weight: 700; color: var(--ink-primary);"><?= format_money((int)$b['budget_amount']) ?></span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div style="width: 100%; height: 8px; background: var(--surface-bg); border-radius: 4px; overflow: hidden; margin-bottom: 14px; position: relative;">
              <div style="height: 100%; width: <?= min(100, $b['spent_percent']) ?>%; background: <?= $barColor ?>; border-radius: 4px; transition: width 0.4s ease;"></div>
            </div>

            <!-- Run Rate & Forecast Box -->
            <div style="background: var(--surface-bg); border-radius: var(--radius-md); padding: 12px; margin-bottom: 16px;">
              <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; margin-bottom: 6px;">
                <span style="color: var(--ink-muted);">Month-End Forecast:</span>
                <span style="font-weight: 700; color: <?= ($b['forecast_over'] ? 'var(--color-danger)' : 'var(--ink-primary)') ?>;">
                  <?= format_money((int)$b['forecast_total']) ?> (<?= round($b['forecast_percent']) ?>%)
                </span>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 0.8125rem;">
                <span style="color: var(--ink-muted);">Remaining Budget:</span>
                <span style="font-weight: 700; color: <?= ($b['remaining_amount'] < 0 ? 'var(--color-danger)' : 'var(--color-success)') ?>;">
                  <?= format_money(abs((int)$b['remaining_amount'])) ?> <?= ($b['remaining_amount'] < 0 ? 'over' : 'left') ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Card Actions -->
          <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 4px;">
            <button type="button" class="btn btn-ghost" @click="deleteId = <?= $b['id'] ?>; deleteName = '<?= addslashes($b['category_name']) ?>'; deleteModal = true;" style="color: var(--color-danger); font-size: 0.8125rem; padding: 4px 8px;">
              Remove Budget
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Modal: Set Budget -->
  <div x-show="modalOpen" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="modalOpen = false" style="width: 100%; max-width: 440px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">Set Monthly Budget</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="modalOpen = false">&times;</button>
      </div>

      <form method="POST" action="/budgets">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="category_id">Scope / Category</label>
          <select name="category_id" id="category_id" class="form-input">
            <option value="overall">Overall Total Spending</option>
            <optgroup label="Or Specific Category">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="amount">Budget Limit (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="amount" id="amount" class="form-input" placeholder="e.g. 25000" required>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="warn_percent">Warning Alert Threshold (% of budget)</label>
          <select name="warn_percent" id="warn_percent" class="form-input">
            <option value="70">70% spent</option>
            <option value="80" selected>80% spent (Recommended)</option>
            <option value="90">90% spent</option>
          </select>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="modalOpen = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Budget</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Confirmation -->
  <div x-show="deleteModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="deleteModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Remove Budget?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to remove the budget for <strong x-text="deleteName"></strong>? Past transactions will remain intact.
      </p>

      <form :action="'/budgets/' + deleteId + '/delete'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="deleteModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Remove</button>
      </form>
    </div>
  </div>
</div>
