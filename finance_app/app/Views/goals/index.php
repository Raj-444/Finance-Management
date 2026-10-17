<div class="page-container" x-data="{
  createModal: false,
  depositModal: false,
  activeGoal: null,
  deleteModal: false,
  deleteId: null,
  deleteName: '',
  expandedGoalId: null
}">

  <!-- Page Header (Matches Screen 2: 'My Goals') -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px;">
    <h1 style="font-size: 1.75rem; font-weight: 800; color: #FFFFFF; letter-spacing: -0.02em; margin: 0;">
      My Goals
    </h1>

    <button type="button" class="btn btn-primary" @click="createModal = true" style="display: flex; align-items: center; gap: 8px; padding: 10px 22px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>+ Add Goal</span>
    </button>
  </div>

  <?php if (empty($goals)): ?>
    <!-- Empty State with Mockup Preview -->
    <div class="goal-featured-card" style="text-align: center; padding: 40px 24px;">
      <div class="goal-featured-header" style="justify-content: center; margin-bottom: 12px;">
        <div class="goal-featured-title">Tesla X Model (Sample Goal)</div>
      </div>

      <!-- Preview Semi-Circle Arc Gauge -->
      <div class="arc-gauge-container">
        <svg class="arc-gauge-svg" viewBox="0 0 260 145">
          <!-- Background Arc Track -->
          <path d="M 25 130 A 105 105 0 0 1 235 130" fill="none" stroke="rgba(255, 69, 181, 0.12)" stroke-width="18" stroke-linecap="round"/>
          <!-- Value Arc (e.g. 68% progress) -->
          <path d="M 25 130 A 105 105 0 0 1 205 60" fill="none" stroke="#FF45B5" stroke-width="18" stroke-linecap="round"/>
        </svg>

        <div class="arc-gauge-center">
          <div class="arc-target-label">Target</div>
          <div class="arc-target-amount tabular-nums">$110 456</div>
          <div class="arc-balance-label">Balance: $75,110 (68%)</div>
        </div>
      </div>

      <p style="font-size: 0.9375rem; color: #64748B; max-width: 440px; margin: 12px auto 20px;">
        Track major milestones, dream purchases, or your emergency fund with visual targets and deposit records.
      </p>

      <button type="button" class="btn btn-primary" @click="createModal = true">
        Create Your First Goal Now
      </button>
    </div>

  <?php else: ?>
    <?php 
      // Top Featured Goal (first active goal)
      $featured = $goals[0];
      $featuredPct = min(100, max(0, (float)$featured['percent']));
      // Calculate arc dashoffset (semicircle circumference is PI * radius = 3.14159 * 105 ~= 330)
      $arcRadius = 105;
      $arcLength = 330;
      $strokeDash = round(($featuredPct / 100) * $arcLength);
    ?>

    <!-- Top Featured Goal Card (Pure White with Semi-Circle Arc Gauge) -->
    <div class="goal-featured-card">
      <div class="goal-featured-header">
        <div class="goal-featured-title"><?= e($featured['name']) ?></div>
        <button type="button" class="goal-featured-chevron" @click="activeGoal = <?= htmlspecialchars(json_encode($featured), ENT_QUOTES, 'UTF-8') ?>; depositModal = true;" title="Contribute">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="18 15 12 9 6 15"/>
          </svg>
        </button>
      </div>

      <!-- Semi-Circle Arc Gauge Visual -->
      <div class="arc-gauge-container">
        <svg class="arc-gauge-svg" viewBox="0 0 260 145">
          <!-- Background Arc Track -->
          <path d="M 25 130 A 105 105 0 0 1 235 130" 
                fill="none" 
                stroke="rgba(255, 69, 181, 0.12)" 
                stroke-width="18" 
                stroke-linecap="round"/>
          
          <!-- Dynamic Value Arc -->
          <path d="M 25 130 A 105 105 0 0 1 235 130" 
                fill="none" 
                stroke="url(#arcGradient)" 
                stroke-width="18" 
                stroke-linecap="round"
                stroke-dasharray="<?= $arcLength ?>"
                stroke-dashoffset="<?= max(0, $arcLength - $strokeDash) ?>"
                style="transition: stroke-dashoffset 0.8s ease;"/>

          <defs>
            <linearGradient id="arcGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#FF59D6" />
              <stop offset="100%" stop-color="#F43F5E" />
            </linearGradient>
          </defs>
        </svg>

        <div class="arc-gauge-center">
          <div class="arc-target-label">Target</div>
          <div class="arc-target-amount tabular-nums">
            <?= money($featured['target_amount']) ?>
          </div>
          <div class="arc-balance-label">
            Balance: <strong style="color: #120626;"><?= money($featured['current_saved']) ?></strong> (<?= $featuredPct ?>%)
          </div>
        </div>
      </div>

      <!-- Action Pill for Featured Goal -->
      <div style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 8px;">
        <button type="button" class="btn btn-pill-dark" @click="activeGoal = <?= htmlspecialchars(json_encode($featured), ENT_QUOTES, 'UTF-8') ?>; depositModal = true;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span>+ Add Deposit</span>
        </button>

        <?php if (!empty($featured['target_date'])): ?>
          <span style="font-size: 0.8125rem; color: #64748B; font-weight: 600;">
            Due: <?= e($featured['target_date']) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sub-Goals List (Translucent Frosted Violet Cards Matching Screen 2) -->
    <div style="margin-top: 10px;">
      <?php foreach ($goals as $index => $g): ?>
        <?php
          // If only 1 goal exists, we can still list it or other goals
          $pct = (float)$g['percent'];
          $isCompleted = ($g['status'] === 'achieved');
        ?>
        <div class="subgoal-card" x-data="{ expanded: false }">
          <div class="subgoal-header">
            <div class="subgoal-title"><?= e($g['name']) ?></div>
            <button type="button" class="subgoal-chevron" @click="expanded = !expanded" title="Toggle details">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :style="expanded ? 'transform: rotate(180deg)' : ''">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </button>
          </div>

          <!-- Progress Capsule Row (Matches Screenshot 15%, 65%, 40%) -->
          <div class="subgoal-progress-row">
            <div class="subgoal-percent-text"><?= (int)$pct ?>%</div>
            <div class="subgoal-progress-capsule">
              <div class="subgoal-progress-fill" style="width: <?= min(100, $pct) ?>%;"></div>
            </div>
          </div>

          <!-- Expandable Details & Actions -->
          <div x-show="expanded" x-transition style="margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.18);">
            <div style="display: flex; justify-content: space-between; font-size: 0.8125rem; color: rgba(255,255,255,0.85); margin-bottom: 12px;">
              <span>Saved: <strong><?= money($g['current_saved']) ?></strong></span>
              <span>Target: <strong><?= money($g['target_amount']) ?></strong></span>
              <span>Remaining: <strong><?= money($g['remaining']) ?></strong></span>
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
              <button type="button" class="btn" style="background: #FFFFFF; color: #120626; font-size: 0.8125rem; padding: 6px 14px;" @click="activeGoal = <?= htmlspecialchars(json_encode($g), ENT_QUOTES, 'UTF-8') ?>; depositModal = true;">
                + Add Deposit
              </button>

              <form action="/goals/<?= $g['id'] ?>/toggle" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost" style="color: #FFFFFF; font-size: 0.75rem; border: 1px solid rgba(255,255,255,0.25); border-radius: var(--radius-full); padding: 6px 12px;">
                  <?= $g['status'] === 'paused' ? 'Resume' : 'Pause' ?>
                </button>
              </form>

              <button type="button" class="btn btn-ghost" style="color: #FFA299; font-size: 0.75rem; margin-left: auto;" @click="deleteId = <?= $g['id'] ?>; deleteName = '<?= e($g['name']) ?>'; deleteModal = true;">
                Delete
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Modal: Create New Goal -->
  <div class="modal-overlay" x-show="createModal" x-cloak>
    <div class="modal-content" @click.outside="createModal = false">
      <div class="card-header">
        <h3 class="card-title">Set New Savings Goal</h3>
        <button type="button" class="btn btn-ghost btn-icon" @click="createModal = false">&times;</button>
      </div>
      <form action="/goals" method="POST">
        <?= csrf_field() ?>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Goal Name</label>
            <input type="text" name="name" class="form-input" placeholder="e.g. Tesla X Model, Swiss Holiday, MacBook Pro" required>
          </div>

          <div class="form-group">
            <label class="form-label">Target Amount</label>
            <input type="number" step="0.01" min="1" name="target_amount" class="form-input" placeholder="0.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Target Completion Date (Optional)</label>
            <input type="date" name="target_date" class="form-input">
          </div>
        </div>
        <div style="padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color);">
          <button type="button" class="btn btn-secondary" @click="createModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Goal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Add Deposit Contribution -->
  <div class="modal-overlay" x-show="depositModal" x-cloak>
    <div class="modal-content" @click.outside="depositModal = false">
      <div class="card-header">
        <h3 class="card-title">Deposit to Goal: <span x-text="activeGoal?.name"></span></h3>
        <button type="button" class="btn btn-ghost btn-icon" @click="depositModal = false">&times;</button>
      </div>
      <form :action="'/goals/' + (activeGoal ? activeGoal.id : '') + '/contribute'" method="POST">
        <?= csrf_field() ?>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Deposit Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-input" placeholder="0.00" required autofocus>
          </div>

          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="contributed_on" class="form-input" value="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Note (Optional)</label>
            <input type="text" name="note" class="form-input" placeholder="e.g. Monthly salary savings contribution">
          </div>
        </div>
        <div style="padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color);">
          <button type="button" class="btn btn-secondary" @click="depositModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Contribution</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Goal Confirmation -->
  <div class="modal-overlay" x-show="deleteModal" x-cloak>
    <div class="modal-content" @click.outside="deleteModal = false">
      <div class="card-header">
        <h3 class="card-title">Delete Goal</h3>
        <button type="button" class="btn btn-ghost btn-icon" @click="deleteModal = false">&times;</button>
      </div>
      <form :action="'/goals/' + deleteId + '/delete'" method="POST">
        <?= csrf_field() ?>
        <div class="card-body">
          <p style="font-size: 0.9375rem; color: var(--ink-secondary);">
            Are you sure you want to delete <strong x-text="deleteName"></strong>? Previous contributions recorded under this goal will be removed.
          </p>
        </div>
        <div style="padding: 16px 24px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid var(--border-color);">
          <button type="button" class="btn btn-secondary" @click="deleteModal = false">Cancel</button>
          <button type="submit" class="btn" style="background: var(--color-expense); color: #fff;">Delete Goal</button>
        </div>
      </form>
    </div>
  </div>

</div>
