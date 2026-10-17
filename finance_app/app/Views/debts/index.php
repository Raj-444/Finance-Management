<div class="page-container" x-data="{
  addModal: false,
  paymentModal: false,
  activeDebt: null,
  deleteModal: false,
  deleteId: null,
  deletePerson: '',
  showNewPerson: false,
}">
  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 class="page-title" style="margin: 0 0 4px 0;">
        Lend & Borrow Tracker
      </h1>
      <p class="page-subtitle" style="margin: 0;">
        Track personal loans, friendly advances, and partial repayments with automated settlements.
      </p>
    </div>

    <button type="button" class="btn btn-primary" @click="addModal = true" style="display: flex; align-items: center; gap: 8px;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
      <span>New Debt / Loan</span>
    </button>
  </div>

  <!-- Summary KPI Cards -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">You Are Owed (Lent)</span>
      <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-success); margin-top: 6px;">
        <?= format_money((int)($summary['total_lent_open'] ?? 0)) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);">Money to receive back</span>
    </div>

    <div class="card" style="padding: 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">You Owe (Borrowed)</span>
      <div style="font-size: 1.5rem; font-weight: 800; color: var(--color-danger); margin-top: 6px;">
        <?= format_money((int)($summary['total_borrowed_open'] ?? 0)) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);">Money to repay</span>
    </div>

    <div class="card" style="padding: 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Net Position</span>
      <div style="font-size: 1.5rem; font-weight: 800; margin-top: 6px; color: <?= (($summary['net_balance'] ?? 0) >= 0) ? 'var(--color-primary)' : 'var(--color-danger)' ?>;">
        <?= format_money((int)($summary['net_balance'] ?? 0)) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);"><?= (($summary['net_balance'] ?? 0) >= 0) ? 'Positive net balance' : 'Net payable' ?></span>
    </div>
  </div>

  <!-- Status Tabs -->
  <div style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
    <a href="/debts?status=open" class="btn <?= $status === 'open' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size: 0.8125rem; padding: 6px 14px;">Open Debts</a>
    <a href="/debts?status=settled" class="btn <?= $status === 'settled' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size: 0.8125rem; padding: 6px 14px;">Settled History</a>
    <a href="/debts?status=all" class="btn <?= $status === 'all' ? 'btn-primary' : 'btn-ghost' ?>" style="font-size: 0.8125rem; padding: 6px 14px;">All Records</a>
  </div>

  <!-- Debts Grid -->
  <?php if (empty($debts)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin-bottom: 8px;">No <?= e(ucfirst($status)) ?> Debt Records</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); max-width: 400px; margin: 0 auto 20px;">
        Track amounts you have lent to friends or borrowed from relatives, record partial payments, and keep clean ledgers.
      </p>
      <button type="button" class="btn btn-primary" @click="addModal = true">Add First Record</button>
    </div>
  <?php else: ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
      <?php foreach ($debts as $d): ?>
        <?php
          $isLent = ($d['direction'] === 'lent');
          $principal = (int)$d['principal'];
          $paid = (int)$d['total_paid'];
          $balance = (int)$d['balance'];
          $pct = ($principal > 0) ? min(100, round(($paid / $principal) * 100)) : 100;
          $isOverdue = (!empty($d['due_on']) && $d['due_on'] < date('Y-m-d') && $d['status'] === 'open');
        ?>
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <!-- Header -->
            <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px;">
              <div>
                <span style="display: inline-block; padding: 3px 8px; border-radius: var(--radius-full); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; background: <?= $isLent ? 'var(--color-success-light)' : 'var(--color-danger-light)' ?>; color: <?= $isLent ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                  <?= $isLent ? 'Lent To' : 'Borrowed From' ?>
                </span>
                <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0;">
                  <?= e($d['person_name'] ?? 'Contact') ?>
                </h3>
              </div>

              <span style="font-size: 0.75rem; font-weight: 700; padding: 4px 8px; border-radius: var(--radius-full); background: <?= ($d['status'] === 'settled') ? 'var(--color-primary-light)' : 'var(--surface-bg)' ?>; color: <?= ($d['status'] === 'settled') ? 'var(--color-primary)' : 'var(--ink-secondary)' ?>;">
                <?= ucfirst(e($d['status'])) ?>
              </span>
            </div>

            <!-- Principal & Remaining -->
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <div>
                <span style="font-size: 0.8125rem; color: var(--ink-muted);">Remaining: </span>
                <span style="font-size: 1.25rem; font-weight: 800; color: <?= $isLent ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                  <?= format_money($balance) ?>
                </span>
              </div>
              <div style="font-size: 0.8125rem; color: var(--ink-muted);">
                of <span style="font-weight: 600; color: var(--ink-primary);"><?= format_money($principal) ?></span>
              </div>
            </div>

            <!-- Progress Bar -->
            <div style="width: 100%; height: 6px; background: var(--surface-bg); border-radius: 3px; overflow: hidden; margin-bottom: 12px;">
              <div style="height: 100%; width: <?= $pct ?>%; background: <?= $isLent ? 'var(--color-success)' : 'var(--color-danger)' ?>; border-radius: 3px;"></div>
            </div>

            <!-- Meta details -->
            <div style="font-size: 0.8125rem; color: var(--ink-muted); display: flex; flex-direction: column; gap: 4px; margin-bottom: 12px;">
              <div>Opened: <span style="font-weight: 600; color: var(--ink-primary);"><?= e($d['opened_on']) ?></span></div>
              <?php if (!empty($d['due_on'])): ?>
                <div>
                  Due: <span style="font-weight: 600; color: <?= $isOverdue ? 'var(--color-danger)' : 'var(--ink-primary)' ?>;"><?= e($d['due_on']) ?> <?= $isOverdue ? '(Overdue!)' : '' ?></span>
                </div>
              <?php endif; ?>
              <?php if (!empty($d['note'])): ?>
                <div style="font-style: italic; color: var(--ink-secondary);">"<?= e($d['note']) ?>"</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Actions -->
          <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 12px; margin-top: 8px; flex-wrap: wrap; gap: 8px;">
            <?php if ($d['status'] === 'open'): ?>
              <button type="button" class="btn btn-ghost" @click="activeDebt = <?= htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') ?>; paymentModal = true;" style="font-size: 0.8125rem; padding: 4px 8px; color: var(--color-primary); font-weight: 600;">
                + Add Payment
              </button>
            <?php endif; ?>

            <div style="display: flex; gap: 6px; margin-left: auto;">
              <form action="/debts/<?= $d['id'] ?>/toggle" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost" style="font-size: 0.75rem; padding: 4px 6px;">
                  <?= $d['status'] === 'open' ? 'Settle' : 'Reopen' ?>
                </button>
              </form>

              <button type="button" class="btn btn-ghost" @click="deleteId = <?= $d['id'] ?>; deletePerson = '<?= addslashes($d['person_name'] ?? 'Contact') ?>'; deleteModal = true;" style="font-size: 0.75rem; padding: 4px 6px; color: var(--color-danger);">
                Delete
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Modal: Add New Debt/Loan -->
  <div x-show="addModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="addModal = false" style="width: 100%; max-width: 460px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">Add Debt / Loan Record</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="addModal = false">&times;</button>
      </div>

      <form method="POST" action="/debts">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label">Direction</label>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer;">
              <input type="radio" name="direction" value="lent" checked>
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--color-success);">Lent (I gave)</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md); cursor: pointer;">
              <input type="radio" name="direction" value="borrowed">
              <span style="font-size: 0.875rem; font-weight: 600; color: var(--color-danger);">Borrowed (I took)</span>
            </label>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <label class="form-label" style="margin: 0;">Person / Contact</label>
            <button type="button" class="btn btn-ghost" @click="showNewPerson = !showNewPerson" style="font-size: 0.75rem; padding: 0 4px;" x-text="showNewPerson ? 'Choose Existing' : '+ Add New Contact'"></button>
          </div>

          <div x-show="!showNewPerson">
            <select name="person_id" class="form-input">
              <option value="">Select a contact...</option>
              <?php foreach ($people as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div x-show="showNewPerson">
            <input type="text" name="new_person_name" class="form-input" placeholder="Enter contact name (e.g. Shakib)">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="principal">Principal Amount (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="principal" id="principal" class="form-input" placeholder="e.g. 5000" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label" for="opened_on">Date Opened</label>
            <input type="date" name="opened_on" id="opened_on" class="form-input" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="due_on">Due Date (Optional)</label>
            <input type="date" name="due_on" id="due_on" class="form-input">
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="note">Note / Purpose</label>
          <input type="text" name="note" id="note" class="form-input" placeholder="e.g. Rent share, emergency medical, etc.">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="addModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Record</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Record Payment -->
  <div x-show="paymentModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="paymentModal = false" style="width: 100%; max-width: 420px; padding: 24px; box-shadow: var(--shadow-xl);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: var(--ink-primary);">Record Repayment</h2>
        <button type="button" class="btn btn-ghost btn-icon" @click="paymentModal = false">&times;</button>
      </div>

      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 16px;">
        Recording payment for <strong x-text="activeDebt ? activeDebt.person_name : ''"></strong>.
      </p>

      <form :action="'/debts/' + (activeDebt ? activeDebt.id : 0) + '/payments'" method="POST">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="pay_amount">Payment Amount (<?= e(\App\Core\Auth::user()['currency'] ?? 'BDT') ?>)</label>
          <input type="number" step="any" min="1" name="amount" id="pay_amount" class="form-input" placeholder="e.g. 2000" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="paid_on">Date Paid</label>
          <input type="date" name="paid_on" id="paid_on" class="form-input" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="pay_note">Note</label>
          <input type="text" name="note" id="pay_note" class="form-input" placeholder="e.g. Partial repayment via bKash">
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-ghost" @click="paymentModal = false">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Payment</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Delete Confirmation -->
  <div x-show="deleteModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="deleteModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Delete Debt Record?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to delete this debt entry for <strong x-text="deletePerson"></strong>? Associated payment logs will be deleted.
      </p>

      <form :action="'/debts/' + deleteId + '/delete'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="deleteModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Delete</button>
      </form>
    </div>
  </div>
</div>
