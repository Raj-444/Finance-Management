<div class="page-container" x-data="{
  createModal: false,
  depositModal: false,
  activeGoal: null,
  deleteModal: false,
  deleteId: null,
  deleteName: ''
}">
  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--ink-primary); margin: 0 0 4px 0;">
        Savings Goals
      </h1>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin: 0;">
        Plan for milestones, emergency funds, and major purchases with progressive visual targets.
      </p>
    </div>

    <button type="button" class="btn btn-primary" @click="createModal = true" style="display: flex; align-items: center; gap: 8px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>Create Goal</span>
    </button>
  </div>

  <!-- Overall Progress Banner -->
  <div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(91,91,214,0.06), rgba(18,183,106,0.06)); border-color: rgba(91,91,214,0.15);">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
      <div>
        <span style="font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-primary);">Overall Savings Progress</span>
        <div style="font-size: 1.75rem; font-weight: 800; color: var(--ink-primary); margin-top: 2px;">
          <?= format_money((int)$summary['total_saved']) ?>
          <span style="font-size: 1rem; font-weight: 500; color: var(--ink-muted);">of <?= format_money((int)$summary['total_target']) ?> target</span>
        </div>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 1.75rem; font-weight: 800; color: var(--color-success);"><?= $summary['overall_percent'] ?>%</span>
        <div style="font-size: 0.75rem; color: var(--ink-muted);"><?= $summary['active_count'] ?> Active Goals</div>
      </div>
    </div>

    <!-- Wide Progress Bar -->
    <div style="width: 100%; height: 10px; background: var(--surface-card); border-radius: 5px; overflow: hidden;">
      <div style="height: 100%; width: <?= $summary['overall_percent'] ?>%; background: linear-gradient(90deg, var(--color-primary), var(--color-success)); border-radius: 5px; transition: width 0.4s ease;"></div>
    </div>
  </div>

  <!-- Goals Grid -->
  <?php if (empty($goals)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
        </svg>
      </div>
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 8px;">No Savings Goals Created</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); max-width: 420px; margin: 0 auto 20px;">
        Setting concrete targets helps build consistent financial resilience. Create your first goal today!
      </p>
      <button type="button" class="btn btn-primary" @click="createModal = true">Set Savings Goal</button>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
      <?php foreach ($goals as $g): ?>
        <?php
          $isAchieved = ($g['status'] === 'achieved');
          $isPaused = ($g['status'] === 'paused');
          $pct = (float)$g['percent'];
          $target = (int)$g['target_amount'];
          $saved = (int)$g['current_saved'];
          $remaining = (int)$g['remaining'];
          $goalColor = !empty($g['color']) ? $g['color'] : '#12B76A';
        ?>
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; border-top: 4px solid <?= e($goalColor) ?>;">
          <div>
            <!-- Header -->
            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px;">
              <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: <?= e($goalColor) ?>18; color: <?= e($goalColor) ?>; display: flex; align-items: center; justify-content: center;">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                  </svg>
                </div>
                <div>
                  <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0;">
                    <?= e($g['name']) ?>
                  </h3>
                  <?php if (!empty($g['target_date'])): ?>
                    <span style="font-size: 0.75rem; color: var(--ink-muted);">Target: <?= e($g['target_date']) ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <span style="font-size: 0.75rem; font-weight: 700; padding: 4px 8px; border-radius: var(--radius-full); background: <?= $isAchieved ? 'var(--color-success-light)' : ($isPaused ? 'var(--surface-bg)' : 'var(--color-primary-light)') ?>; color: <?= $isAchieved ? 'var(--color-success)' : ($isPaused ? 'var(--ink-muted)' : 'var(--color-primary)') ?>;">
                <?= $isAchieved ? 'Completed 🎉' : ucfirst(e($g['status'])) ?>
              </span>
            </div>

            <!-- Numbers -->
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <div>
                <span style="font-size: 1.25rem; font-weight: 800; color: var(--ink-primary);"><?= format_money($saved) ?></span>
                <span style="font-size: 0.8125rem; color: var(--ink-muted);"> saved</span>
              </div>
              <div style="font-size: 0.875rem; font-weight: 700; color: <?= e($goalColor) ?>;">
                <?= $pct ?>%
              </div>
            </div>

            <!-- Progress Bar -->
            <div style="width: 100%; height: 8px; background: var(--surface-bg); border-radius: 4px; overflow: hidden; margin-bottom: 12px;">
              <div style="height: 100%; width: <?= $pct ?>%; background: <?= e($goalColor) ?>; border-radius: 4px; transition: width 0.4s ease;"></div>
            </div>

            <!-- Remaining Box -->
            <div style="background: var(--surface-bg); border-radius: var(--radius-md); padding: 10px 12px; margin-bottom: 12px; display: flex; justify-content: space-between; font-size: 0.8125rem;">
              <span style="color: var(--ink-muted);">Remaining to reach target:</span>
              <span style="font-weight: 700; color: var(--ink-primary);"><?= format_money($remaining) ?></span>
            </div>
          </div>

          <!-- Actions -->
          <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 6px; flex-wrap: wrap; gap: 8px;">
            <?php if (!$isAchieved): ?>
              <button type="button" class="btn btn-ghost" @click="activeGoal = <?= htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8') ?>; depositModal = true;" style="font-size: 0.8125rem; padding: 4px 8px; color: var(--color-success); font-weight: 700;">
                + Add Deposit
              </button>
            <?php else: ?>
              <span style="font-size: 0.8125rem; color: var(--color-success); font-weight: 600;">Goal Accomplished!</span>
            <?php endif; ?>

            <div style="display: flex; gap: 6px; margin-left: auto;">
              <form action="/goals/<?= $g['id'] ?>/toggle" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost" style="font-size: 0.75rem; padding: 4px 6px;">
                  <?= $isPaused ? 'Resume' : 'Pause' ?>
                </button>
              </form>

              <button type="button" class="btn btn-ghost" @click="deleteId = <?= $g['id'] ?>; deleteName = '<?= addslashes($g['name']) ?>'; deleteModal = true;" style="font-size: 0.75rem; padding: 4px 6px; color: var(--color-danger);">
                Delete
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Modal: Create Goal -->
  <div x-show="createModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="createModal = false" style="width: 100%; max-width: 440px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">New Savings Goal</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="createModal = false">&times;</button>
      </div>

      <form method="POST" action="/goals">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="name">Goal Name</label>
          <input type="text" name="name" id="name" class="form-input" placeholder="e.g. Emergency Fund, Laptop, Eid Travel" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="target_amount">Target Amount (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="target_amount" id="target_amount" class="form-input" placeholder="e.g. 50000" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="target_date">Target Date (Optional)</label>
          <input type="date" name="target_date" id="target_date" class="form-input">
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label">Theme Color</label>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="color" name="color" value="#12B76A" style="width: 42px; height: 38px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; background: transparent; padding: 2px;">
            <span style="font-size: 0.8125rem; color: var(--ink-muted);">Choose a visual accent for this goal</span>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="createModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Goal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Add Deposit -->
  <div x-show="depositModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="depositModal = false" style="width: 100%; max-width: 420px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">Add Savings Deposit</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="depositModal = false">&times;</button>
      </div>

      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 16px;">
        Adding funds toward <strong x-text="activeGoal ? activeGoal.name : ''"></strong>.
      </p>

      <form :action="'/goals/' + (activeGoal ? activeGoal.id : 0) + '/contribute'" method="POST">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="dep_amount">Deposit Amount (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="amount" id="dep_amount" class="form-input" placeholder="e.g. 5000" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="contributed_on">Date</label>
          <input type="date" name="contributed_on" id="contributed_on" class="form-input" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="dep_note">Note</label>
          <input type="text" name="note" id="dep_note" class="form-input" placeholder="e.g. Monthly salary savings, bonus">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="depositModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Deposit</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Confirmation -->
  <div x-show="deleteModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="deleteModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Delete Goal?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to delete <strong x-text="deleteName"></strong>? Deposit logs for this goal will be removed.
      </p>

      <form :action="'/goals/' + deleteId + '/delete'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="deleteModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Delete</button>
      </form>
    </div>
  </div>
</div>
