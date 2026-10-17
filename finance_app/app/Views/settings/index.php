<?php
$pending = \App\Core\Session::get('pending_import');
$wizardMode = ($tab === 'data' && !empty($_GET['wizard']) && $pending);
?>

<div class="page-container" x-data="{
  tab: '<?= e($tab) ?>',
  exportModal: false,
  restoreModal: false,
  undoModal: false,
  undoId: null,
  undoFilename: '',
  smtpTesting: false,
  smtpResult: null,
  allowReg: <?= $allowRegistration ? 'true' : 'false' ?>,
  testSmtp() {
    this.smtpTesting = true;
    this.smtpResult = null;
    const token = document.querySelector('meta[name=\"csrf-token\"]')?.content;
    fetch('/settings/smtp/test', {
      method: 'POST',
      headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      this.smtpTesting = false;
      this.smtpResult = data;
    })
    .catch(e => {
      this.smtpTesting = false;
      this.smtpResult = { ok: false, error: 'Network or request error' };
    });
  },
  toggleRegistration() {
    const token = document.querySelector('meta[name=\"csrf-token\"]')?.content;
    fetch('/settings/registration/toggle', {
      method: 'POST',
      headers: { 'X-CSRF-Token': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ allow: this.allowReg })
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) this.allowReg = data.allow;
    });
  }
}">

  <!-- Header -->
  <div style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--ink-primary); margin: 0 0 4px 0;">
      Settings & Preferences
    </h1>
    <p style="font-size: 0.875rem; color: var(--ink-muted); margin: 0;">
      Manage your financial profile, security, data imports/exports, and full database backups.
    </p>
  </div>

  <!-- Settings Tabs Bar -->
  <div style="display: flex; gap: 8px; border-bottom: 1px solid var(--border-color); margin-bottom: 24px; overflow-x: auto; padding-bottom: 8px;">
    <button type="button" @click="tab = 'profile'" :class="tab === 'profile' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 8px 16px; font-size: 0.875rem;">
      Profile & Preferences
    </button>
    <button type="button" @click="tab = 'security'" :class="tab === 'security' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 8px 16px; font-size: 0.875rem;">
      Security & Password
    </button>
    <button type="button" @click="tab = 'data'" :class="tab === 'data' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 8px 16px; font-size: 0.875rem;">
      Excel / CSV Import & Export
    </button>
    <button type="button" @click="tab = 'backup'" :class="tab === 'backup' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 8px 16px; font-size: 0.875rem;">
      Full JSON Backup & Restore
    </button>
    <?php if (\App\Core\Auth::isAdmin()): ?>
      <button type="button" @click="tab = 'system'" :class="tab === 'system' ? 'btn btn-primary' : 'btn btn-ghost'" style="padding: 8px 16px; font-size: 0.875rem;">
        Email (SMTP) & System
      </button>
    <?php endif; ?>
  </div>

  <!-- TAB 1: Profile & Preferences -->
  <div x-show="tab === 'profile'">
    <div class="card" style="max-width: 640px; padding: 24px;">
      <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">Personal Preferences</h2>

      <form method="POST" action="/settings/profile">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="name">Your Name</label>
          <input type="text" name="name" id="name" class="form-input" value="<?= e($user['name']) ?>" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" id="email" class="form-input" value="<?= e($user['email']) ?>" disabled style="background: var(--surface-bg); color: var(--ink-muted);">
          <span style="font-size: 0.75rem; color: var(--ink-muted);">Email cannot be changed directly for security audit trail.</span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label" for="currency">Primary Currency</label>
            <select name="currency" id="currency" class="form-input">
              <option value="BDT" <?= ($user['currency'] === 'BDT') ? 'selected' : '' ?>>BDT (৳ - Bangladeshi Taka)</option>
              <option value="USD" <?= ($user['currency'] === 'USD') ? 'selected' : '' ?>>USD ($ - US Dollar)</option>
              <option value="EUR" <?= ($user['currency'] === 'EUR') ? 'selected' : '' ?>>EUR (€ - Euro)</option>
              <option value="GBP" <?= ($user['currency'] === 'GBP') ? 'selected' : '' ?>>GBP (£ - British Pound)</option>
              <option value="INR" <?= ($user['currency'] === 'INR') ? 'selected' : '' ?>>INR (₹ - Indian Rupee)</option>
              <option value="AED" <?= ($user['currency'] === 'AED') ? 'selected' : '' ?>>AED (د.إ - UAE Dirham)</option>
              <option value="SAR" <?= ($user['currency'] === 'SAR') ? 'selected' : '' ?>>SAR (﷼ - Saudi Riyal)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="number_format">Number Notation</label>
            <select name="number_format" id="number_format" class="form-input">
              <option value="indian" <?= (($user['number_format'] ?? 'indian') === 'indian') ? 'selected' : '' ?>>Indian Lakh/Crore (1,50,000.00)</option>
              <option value="international" <?= (($user['number_format'] ?? 'indian') === 'international') ? 'selected' : '' ?>>International Millions (150,000.00)</option>
            </select>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
          <div class="form-group">
            <label class="form-label" for="locale">Interface Language</label>
            <select name="locale" id="locale" class="form-input">
              <option value="en" <?= ($user['locale'] === 'en') ? 'selected' : '' ?>>English</option>
              <option value="bn" <?= ($user['locale'] === 'bn') ? 'selected' : '' ?>>বাংলা (Bangla)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="month_start_day">Month Start Day</label>
            <input type="number" min="1" max="28" name="month_start_day" id="month_start_day" class="form-input" value="<?= e($user['month_start_day'] ?? 1) ?>" required>
            <span style="font-size: 0.75rem; color: var(--ink-muted);">e.g., Set to 5 if salary is deposited on the 5th.</span>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
          <div class="form-group">
            <label class="form-label" for="quiet_from">Quiet Hours (From)</label>
            <input type="time" name="quiet_from" id="quiet_from" class="form-input" value="<?= e($user['quiet_from'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="quiet_to">Quiet Hours (To)</label>
            <input type="time" name="quiet_to" id="quiet_to" class="form-input" value="<?= e($user['quiet_to'] ?? '') ?>">
            <span style="font-size: 0.75rem; color: var(--ink-muted);">Mutes email alerts between these hours.</span>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Save Preferences</button>
      </form>
    </div>

    <!-- Monthly Summary Email Card -->
    <div class="card" style="max-width: 650px; padding: 24px; margin-top: 24px;" x-data="{ sending: false, statusMsg: '', isError: false }">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 10px;">
        <div>
          <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 4px 0;">Monthly Financial Statement</h3>
          <p style="font-size: 0.8125rem; color: var(--ink-muted); margin: 0;">Automatically compiled on the 1st of every month and dispatched to your email.</p>
        </div>
        <button 
          type="button" 
          class="btn btn-ghost" 
          style="border: 1px solid var(--border-color); white-space: nowrap;"
          :disabled="sending"
          @click="
            sending = true;
            statusMsg = '';
            fetch('/settings/summary/test', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.json())
              .then(data => {
                sending = false;
                if (data.ok) {
                  statusMsg = data.message || 'Monthly summary dispatched successfully!';
                  isError = false;
                } else {
                  statusMsg = data.error || 'Failed to dispatch summary email.';
                  isError = true;
                }
              })
              .catch(() => {
                sending = false;
                statusMsg = 'Connection error.';
                isError = true;
              });
          "
        >
          <span x-show="!sending">Send Test Statement</span>
          <span x-show="sending">Dispatching...</span>
        </button>
      </div>
      <div x-show="statusMsg" x-text="statusMsg" :style="isError ? 'color: var(--color-danger); font-size: 0.8125rem; font-weight: 600;' : 'color: var(--color-success); font-size: 0.8125rem; font-weight: 600;'"></div>
    </div>
  </div>

  <!-- TAB 2: Security & Password -->
  <div x-show="tab === 'security'">
    <div class="card" style="max-width: 500px; padding: 24px;">
      <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">Change Account Password</h2>

      <form method="POST" action="/settings/password">
        <?= csrf_field() ?>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" name="current_password" id="current_password" class="form-input" required>
        </div>

        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" name="new_password" id="new_password" class="form-input" minlength="8" required>
          <span style="font-size: 0.75rem; color: var(--ink-muted);">Minimum 8 characters.</span>
        </div>

        <div class="form-group" style="margin-bottom: 24px;">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-input" minlength="8" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Password</button>
      </form>
    </div>

    <!-- Active Session & Device -->
    <div class="card" style="max-width: 500px; padding: 24px; margin-top: 24px;">
      <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 12px 0;">Active Session & Device</h3>
      <div style="background: var(--bg-surface-alt, #F8FAFC); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; margin-bottom: 16px; font-size: 0.8125rem;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--ink-muted);">Current IP Address:</span>
          <span style="font-family: monospace; font-weight: 600; color: var(--ink-primary);"><?= e($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
          <span style="color: var(--ink-muted);">Session Identifier:</span>
          <span style="font-family: monospace; color: var(--ink-muted);">••••••••<?= substr(\App\Core\Session::id(), -6) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between;">
          <span style="color: var(--ink-muted);">Device Client:</span>
          <span style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--ink-primary);" title="<?= e($_SERVER['HTTP_USER_AGENT'] ?? 'Web Client') ?>">
            <?= e(substr($_SERVER['HTTP_USER_AGENT'] ?? 'Web Client', 0, 36)) ?>...
          </span>
        </div>
      </div>
      <form method="POST" action="/settings/sessions/revoke-others">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-ghost" style="border: 1px solid var(--border-color); width: 100%;">
          Log Out All Other Devices
        </button>
      </form>
    </div>

    <!-- Danger Zone: Delete Account -->
    <div class="card" style="max-width: 500px; padding: 24px; margin-top: 24px; border: 1px solid rgba(240, 68, 56, 0.4); background: rgba(240, 68, 56, 0.02);">
      <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--color-danger); margin: 0 0 8px 0;">Danger Zone: Delete Account</h3>
      <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-bottom: 16px; line-height: 1.5;">
        Permanently remove your account and all associated financial records (accounts, transactions, debts, savings goals, and budgets). This action cannot be reversed.
      </p>

      <form method="POST" action="/settings/account/delete" onsubmit="return confirm('WARNING: Are you absolutely certain you want to permanently delete your account and all financial data? This CANNOT be undone.');">
        <?= csrf_field() ?>
        <div class="form-group" style="margin-bottom: 16px;">
          <label class="form-label" for="delete_confirm_password" style="color: var(--color-danger);">Enter password to confirm deletion</label>
          <input type="password" name="password" id="delete_confirm_password" class="form-input" placeholder="Your current account password" required>
        </div>
        <button type="submit" class="btn" style="background: var(--color-danger); color: #fff; width: 100%;">
          Permanently Delete My Account
        </button>
      </form>
    </div>
  </div>

  <!-- TAB 3: Excel / CSV Import & Export -->
  <div x-show="tab === 'data'">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;" class="settings-grid">
      <!-- Export Box -->
      <div class="card" style="padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--color-success-light); color: var(--color-success); display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
          </div>
          <div>
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0;">Export Transactions</h3>
            <span style="font-size: 0.75rem; color: var(--ink-muted);">Download clean filtered spreadsheets</span>
          </div>
        </div>

        <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
          Export your entire transaction ledger. Formatted with formula injection protection (DDE sanitization).
        </p>

        <div style="display: flex; gap: 10px;">
          <a href="/settings/export/csv" class="btn btn-primary" style="display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>Download CSV</span>
          </a>
          <a href="/settings/export/xlsx" class="btn btn-ghost" style="border: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Download Excel (.xlsx)</span>
          </a>
        </div>
      </div>

      <!-- Import Box -->
      <div class="card" style="padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </div>
          <div>
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0;">Import Transactions</h3>
            <span style="font-size: 0.75rem; color: var(--ink-muted);">Upload .csv or .xlsx (Max 5MB / 5,000 rows)</span>
          </div>
        </div>

        <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 16px;">
          Upload bank statements or expense sheets with column mapping preview, duplicate detection, and single-click undo rollback.
        </p>

        <form method="POST" action="/settings/import/upload" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="file" name="import_file" accept=".csv, .xlsx" class="form-input" style="padding: 6px;" required>
            <button type="submit" class="btn btn-primary" style="white-space: nowrap;">Upload & Preview</button>
          </div>
        </form>

        <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 0.8125rem;">
          <span style="color: var(--ink-muted);">Need a pre-formatted format?</span>
          <div style="display: flex; gap: 10px;">
            <a href="/settings/import/template?format=csv" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">Download .CSV Template</a>
            <span style="color: var(--border-color);">|</span>
            <a href="/settings/import/template?format=xlsx" style="color: var(--color-primary); font-weight: 600; text-decoration: none;">Download .XLSX Template</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Wizard Mapping Section -->
    <?php if ($wizardMode): ?>
      <div class="card" style="padding: 24px; margin-bottom: 24px; border: 2px solid var(--color-primary);">
        <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">
          Column Mapping & Validation Wizard: <?= e($pending['filename']) ?>
        </h3>
        <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
          Map the columns from your uploaded spreadsheet to transaction fields. We found <?= count($pending['rows']) ?> rows.
        </p>

        <form method="POST" action="/settings/import/commit">
          <?= csrf_field() ?>

          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
            <div class="form-group">
              <label class="form-label">Date Column</label>
              <select name="date_col" class="form-input" required>
                <?php foreach ($pending['headers'] as $i => $h): ?>
                  <option value="<?= $i ?>" <?= (stripos($h, 'date') !== false) ? 'selected' : '' ?>>Col <?= $i + 1 ?>: <?= e($h) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Amount Column</label>
              <select name="amount_col" class="form-input" required>
                <?php foreach ($pending['headers'] as $i => $h): ?>
                  <option value="<?= $i ?>" <?= (stripos($h, 'amount') !== false || stripos($h, 'total') !== false) ? 'selected' : '' ?>>Col <?= $i + 1 ?>: <?= e($h) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Type Column (Optional)</label>
              <select name="type_col" class="form-input">
                <option value="-1">Default to Expense</option>
                <?php foreach ($pending['headers'] as $i => $h): ?>
                  <option value="<?= $i ?>" <?= (stripos($h, 'type') !== false) ? 'selected' : '' ?>>Col <?= $i + 1 ?>: <?= e($h) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Category Column (Optional)</label>
              <select name="cat_col" class="form-input">
                <option value="-1">None / General</option>
                <?php foreach ($pending['headers'] as $i => $h): ?>
                  <option value="<?= $i ?>" <?= (stripos($h, 'category') !== false || stripos($h, 'cat') !== false) ? 'selected' : '' ?>>Col <?= $i + 1 ?>: <?= e($h) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Note / Description (Optional)</label>
              <select name="note_col" class="form-input">
                <option value="-1">None</option>
                <?php foreach ($pending['headers'] as $i => $h): ?>
                  <option value="<?= $i ?>" <?= (stripos($h, 'note') !== false || stripos($h, 'desc') !== false || stripos($h, 'particulars') !== false) ? 'selected' : '' ?>>Col <?= $i + 1 ?>: <?= e($h) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Destination Account</label>
              <select name="default_account_id" class="form-input" required>
                <?php foreach ($accounts as $acc): ?>
                  <option value="<?= $acc['id'] ?>"><?= e($acc['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div style="margin-bottom: 24px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
              <input type="checkbox" name="skip_duplicates" value="1" checked style="width: 18px; height: 18px;">
              <span style="font-size: 0.875rem; color: var(--ink-primary); font-weight: 500;">
                Skip duplicate transactions automatically (matches Date, Amount, Account, and Note)
              </span>
            </label>
          </div>

          <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <a href="/settings?tab=data" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
              Commit Import (<?= count($pending['rows']) ?> rows)
            </button>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <!-- Import Batches History -->
    <div class="card" style="padding: 0; overflow: hidden;">
      <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color);">
        <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0;">
          Import History & Rollback Ledgers
        </h3>
      </div>
      <div class="table-responsive">
        <table class="table" style="margin: 0;">
          <thead>
            <tr>
              <th>Batch Date</th>
              <th>Filename</th>
              <th style="text-align: right;">Total Rows</th>
              <th style="text-align: right;">Imported</th>
              <th style="text-align: right;">Skipped</th>
              <th style="text-align: center;">Status</th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($importBatches as $b): ?>
              <tr>
                <td><?= e($b['created_at']) ?></td>
                <td style="font-weight: 600; color: var(--ink-primary);"><?= e($b['filename']) ?></td>
                <td style="text-align: right;"><?= $b['rows_total'] ?></td>
                <td style="text-align: right; font-weight: 700; color: var(--color-success);"><?= $b['rows_imported'] ?></td>
                <td style="text-align: right; color: var(--ink-muted);"><?= $b['rows_skipped'] ?></td>
                <td style="text-align: center;">
                  <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: <?= ($b['status'] === 'committed') ? 'var(--color-success-light)' : 'var(--surface-bg)' ?>; color: <?= ($b['status'] === 'committed') ? 'var(--color-success)' : 'var(--ink-muted)' ?>;">
                    <?= ucfirst(e($b['status'])) ?>
                  </span>
                </td>
                <td style="text-align: right;">
                  <?php if ($b['status'] === 'committed'): ?>
                    <button type="button" class="btn btn-ghost" @click="undoId = <?= $b['id'] ?>; undoFilename = '<?= addslashes($b['filename']) ?>'; undoModal = true;" style="color: var(--color-danger); font-size: 0.8125rem; padding: 2px 8px;">
                      Undo Import
                    </button>
                  <?php else: ?>
                    <span style="color: var(--ink-muted); font-size: 0.75rem;">Rolled back</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($importBatches)): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--ink-muted); padding: 24px;">No import batches recorded yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 4: Full JSON Backup & Restore -->
  <div x-show="tab === 'backup'">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="settings-grid">
      <!-- Backup Export Card -->
      <div class="card" style="padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          </div>
          <div>
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0;">Create Database Backup</h3>
            <span style="font-size: 0.75rem; color: var(--ink-muted);">Complete JSON snapshot</span>
          </div>
        </div>

        <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
          Exports your entire ledger including accounts, categories, tags, debts, goals, recurring rules, and preferences into a single portable JSON file.
        </p>

        <a href="/settings/backup/download" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span>Download Complete Backup (.json)</span>
        </a>
      </div>

      <!-- Backup Restore Card -->
      <div class="card" style="padding: 24px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
          <div style="width: 40px; height: 40px; border-radius: var(--radius-md); background: var(--color-warning-light); color: var(--color-warning); display: flex; align-items: center; justify-content: center;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          </div>
          <div>
            <h3 style="font-size: 1.0625rem; font-weight: 700; color: var(--ink-primary); margin: 0;">Restore from Backup</h3>
            <span style="font-size: 0.75rem; color: var(--ink-muted);">Upload .json snapshot</span>
          </div>
        </div>

        <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 16px;">
          Restore your full ledger from a previous JSON backup. Existing records will be cleanly replaced with the backup contents.
        </p>

        <form method="POST" action="/settings/backup/restore" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div style="display: flex; gap: 10px; align-items: center;">
            <input type="file" name="backup_file" accept=".json" class="form-input" style="padding: 6px;" required>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Restoring this backup will replace current user data with the snapshot. Are you sure you wish to proceed?')">Restore</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- TAB 5: Email (SMTP) & System (Admin Only) -->
  <?php if (\App\Core\Auth::isAdmin()): ?>
    <div x-show="tab === 'system'">
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;" class="settings-grid">
        <!-- SMTP Settings -->
        <div class="card" style="padding: 24px;">
          <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">SMTP Email Configuration</h2>
          <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
            Configure your shared cPanel or transactional SMTP service (Gmail, SendGrid, Amazon SES) for budget alerts and password reset emails.
          </p>

          <form method="POST" action="/settings/smtp">
            <?= csrf_field() ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="smtp_host">SMTP Host</label>
                <input type="text" name="smtp_host" id="smtp_host" class="form-input" value="<?= e($smtp['host']) ?>" placeholder="e.g. mail.yourdomain.com or smtp.gmail.com">
              </div>
              <div class="form-group">
                <label class="form-label" for="smtp_port">Port</label>
                <input type="number" name="smtp_port" id="smtp_port" class="form-input" value="<?= e($smtp['port']) ?>" placeholder="587">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group">
                <label class="form-label" for="smtp_username">Username</label>
                <input type="text" name="smtp_username" id="smtp_username" class="form-input" value="<?= e($smtp['username']) ?>" placeholder="user@yourdomain.com">
              </div>
              <div class="form-group">
                <label class="form-label" for="smtp_password">Password</label>
                <input type="password" name="smtp_password" id="smtp_password" class="form-input" value="<?= e($smtp['password']) ?>" placeholder="••••••••">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" for="smtp_encryption">Encryption</label>
                <select name="smtp_encryption" id="smtp_encryption" class="form-input">
                  <option value="tls" <?= ($smtp['encryption'] === 'tls') ? 'selected' : '' ?>>TLS (Port 587)</option>
                  <option value="ssl" <?= ($smtp['encryption'] === 'ssl') ? 'selected' : '' ?>>SSL (Port 465)</option>
                  <option value="none" <?= ($smtp['encryption'] === 'none') ? 'selected' : '' ?>>None (Port 25)</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="smtp_from_address">From Email</label>
                <input type="email" name="smtp_from_address" id="smtp_from_address" class="form-input" value="<?= e($smtp['from_address']) ?>" placeholder="noreply@domain.com">
              </div>

              <div class="form-group">
                <label class="form-label" for="smtp_from_name">From Name</label>
                <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-input" value="<?= e($smtp['from_name']) ?>" placeholder="Personal Finance">
              </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: center;">
              <button type="submit" class="btn btn-primary">Save SMTP Settings</button>
              <button type="button" class="btn btn-ghost" @click="testSmtp()" :disabled="smtpTesting" style="border: 1px solid var(--border-color);">
                <span x-text="smtpTesting ? 'Sending test...' : 'Send Test Email'"></span>
              </button>
            </div>

            <div x-show="smtpResult" style="margin-top: 14px;" x-cloak>
              <div :class="smtpResult && smtpResult.ok ? 'toast toast-success' : 'toast toast-error'" style="position: static; animation: none;">
                <span x-text="smtpResult && smtpResult.ok ? smtpResult.message : (smtpResult ? smtpResult.error : '')"></span>
              </div>
            </div>
          </form>
        </div>

        <!-- Registration & App Policies -->
        <div class="card" style="padding: 24px;">
          <h2 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 16px 0;">Access Policies</h2>

          <div style="margin-bottom: 20px;">
            <label style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
              <div>
                <div style="font-weight: 600; color: var(--ink-primary); font-size: 0.875rem;">Public Registration</div>
                <div style="font-size: 0.75rem; color: var(--ink-muted);">Allow new users to sign up via /register</div>
              </div>
              <input type="checkbox" x-model="allowReg" @change="toggleRegistration()" style="width: 20px; height: 20px; cursor: pointer;">
            </label>
          </div>

          <div style="border-top: 1px solid var(--border-color); padding-top: 16px;">
            <a href="/admin" class="btn btn-ghost" style="width: 100%; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; gap: 8px;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
              <span>Open Admin Panel & Diagnostics</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Modal: Undo Import Confirmation -->
  <div x-show="undoModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="undoModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Undo Import Batch?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to remove all transactions imported from <strong x-text="undoFilename"></strong>? This rollback is atomic.
      </p>

      <form :action="'/settings/import/' + undoId + '/undo'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="undoModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Rollback Import</button>
      </form>
    </div>
  </div>
</div>

<style>
@media (max-width: 768px) {
  .settings-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
