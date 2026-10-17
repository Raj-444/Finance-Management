<div style="display: flex; flex-direction: column; gap: 24px;">

  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
    <div>
      <h2 class="page-title">Accounts & Wallets</h2>
      <p class="page-subtitle">Manage your cash, mobile wallets (bKash, Nagad), bank accounts, and cards.</p>
    </div>
  </div>

  <div class="grid-2" style="align-items: flex-start;">
    
    <!-- Accounts List -->
    <div>
      <div style="display: flex; flex-direction: column; gap: 14px;">
        <?php foreach ($accounts as $acc): ?>
          <div class="card" style="margin-bottom: 0; border-left: 5px solid <?= e($acc['color']) ?>;">
            <div class="card-body" style="padding: 18px 20px; display: flex; align-items: center; justify-content: space-between;">
              <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--ink-muted); text-transform: uppercase;">
                  <?= e(str_replace('_', ' ', $acc['type'])) ?>
                </div>
                <div style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 2px 0;">
                  <?= e($acc['name']) ?>
                </div>
                <div style="font-size: 0.8125rem; color: var(--ink-muted);">
                  Opening: <?= money($acc['opening_balance']) ?>
                </div>
              </div>

              <div style="text-align: right;">
                <div style="font-size: 1.375rem; font-weight: 800; color: var(--ink-primary);" class="tabular-nums">
                  <?= money($acc['current_balance']) ?>
                </div>
                <span class="badge badge-primary" style="margin-top: 4px;">Active</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Create Account Form Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">+ Add New Account / Wallet</h3>
      </div>
      <div class="card-body">
        <form method="POST" action="/accounts">
          <?= csrf_field() ?>

          <div class="form-group">
            <label class="form-label">Account Name</label>
            <input type="text" name="name" class="form-input" placeholder="e.g. Brac Bank, Upay Wallet" required>
          </div>

          <div class="form-group">
            <label class="form-label">Account Type</label>
            <select name="type" class="form-select">
              <option value="cash">Cash</option>
              <option value="mobile_wallet">Mobile Wallet (bKash / Nagad / Rocket)</option>
              <option value="bank">Bank Account</option>
              <option value="card">Credit / Debit Card</option>
              <option value="savings">Savings Account</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Opening Balance (BDT)</label>
              <input type="number" step="0.01" name="opening_balance" class="form-input" placeholder="0.00" value="0.00">
            </div>

            <div class="form-group">
              <label class="form-label">Color Accent</label>
              <input type="color" name="color" class="form-input" value="#5B5BD6" style="height: 44px; padding: 4px; cursor: pointer;">
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-weight: 700;">
            Create Account
          </button>
        </form>
      </div>
    </div>

  </div>

</div>
