<div class="card" x-data="installWizard()">
  <div class="card-header">
    <div style="font-weight: 700; font-size: 1.125rem;">Installation & Environment Setup</div>
    <span class="badge badge-primary">Step 1 of 2</span>
  </div>

  <div class="card-body">
    <!-- Step 1: System Checks Accordion / Panel -->
    <div style="margin-bottom: 28px;">
      <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 12px; color: var(--ink-primary); display: flex; align-items: center; justify-content: space-between;">
        <span>Server Requirements Check</span>
        <?php if ($allPassed): ?>
          <span class="badge badge-income">All Passed</span>
        <?php else: ?>
          <span class="badge badge-expense">Action Required</span>
        <?php endif; ?>
      </h3>

      <div style="background-color: var(--bg-subtle); border-radius: var(--radius-md); padding: 16px; font-size: 0.875rem;">
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border-color);">
          <span>PHP Version (>= 8.2.0)</span>
          <span><strong><?= e($checks['php_version']) ?></strong> <?= $checks['php_passed'] ? '✅' : '❌' ?></span>
        </div>

        <div style="margin-top: 8px;">
          <div style="font-weight: 600; margin-bottom: 6px;">PHP Extensions:</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
            <?php foreach ($checks['extensions'] as $ext => $loaded): ?>
              <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                <span><?= e($ext) ?></span>
                <span><?= $loaded ? '✅' : '❌' ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="margin-top: 12px;">
          <div style="font-weight: 600; margin-bottom: 6px;">Writable Storage Folders:</div>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px;">
            <?php foreach ($checks['directories'] as $dir => $writable): ?>
              <div style="display: flex; justify-content: space-between; padding: 2px 0;">
                <span><?= e($dir) ?></span>
                <span><?= $writable ? '✅' : '❌' ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if (!$allPassed): ?>
      <div class="toast toast-error" style="position: static; margin-bottom: 24px;">
        <span>Please resolve the missing PHP extensions or directory permissions before continuing.</span>
      </div>
    <?php endif; ?>

    <!-- Installation Form -->
    <form method="POST" action="/install" @submit="isSubmitting = true">
      <?= csrf_field() ?>

      <!-- Database Setup -->
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 16px; color: var(--ink-primary);">1. Database Configuration</h3>

        <div class="form-group">
          <label class="form-label">Database Driver</label>
          <select name="driver" class="form-select" x-model="driver">
            <option value="mysql">MySQL / MariaDB (Recommended for cPanel)</option>
            <option value="sqlite">SQLite (Local testing / Single file)</option>
          </select>
        </div>

        <div x-show="driver === 'mysql'">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Database Host</label>
              <input type="text" name="db_host" class="form-input" x-model="dbHost" placeholder="localhost">
            </div>
            <div class="form-group">
              <label class="form-label">Port</label>
              <input type="number" name="db_port" class="form-input" x-model="dbPort" placeholder="3306">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Database Name</label>
            <input type="text" name="db_name" class="form-input" x-model="dbName" placeholder="e.g. cpaneluser_finance">
            <div class="form-helper">In cPanel, this typically starts with your username prefix.</div>
          </div>

          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Database Username</label>
              <input type="text" name="db_user" class="form-input" x-model="dbUser" placeholder="e.g. cpaneluser_dbuser">
            </div>
            <div class="form-group">
              <label class="form-label">Database Password</label>
              <input type="password" name="db_pass" class="form-input" x-model="dbPass" placeholder="••••••••">
            </div>
          </div>

          <div style="margin-bottom: 16px;">
            <button type="button" class="btn btn-secondary" @click="testConnection()" :disabled="testingDb">
              <span x-text="testingDb ? 'Testing Connection...' : 'Test Database Connection'"></span>
            </button>
            <div x-show="testMessage" style="margin-top: 8px; font-size: 0.875rem;" :style="{ color: testSuccess ? 'var(--color-income)' : 'var(--color-expense)' }" x-text="testMessage"></div>
          </div>
        </div>

        <div x-show="driver === 'sqlite'">
          <div class="form-group">
            <label class="form-label">SQLite File Path</label>
            <input type="text" name="db_name" class="form-input" value="<?= e(dirname(__DIR__, 3) . '/storage/database.sqlite') ?>">
            <div class="form-helper">Database will be created in your storage folder.</div>
          </div>
        </div>
      </div>

      <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 24px 0;">

      <!-- Admin Account Setup -->
      <div style="margin-bottom: 24px;">
        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 16px; color: var(--ink-primary);">2. Administrator Account</h3>

        <div class="form-group">
          <label class="form-label">Admin Full Name</label>
          <input type="text" name="admin_name" class="form-input" placeholder="Raj" required>
        </div>

        <div class="grid-2">
          <div class="form-group">
            <label class="form-label">Admin Email</label>
            <input type="email" name="admin_email" class="form-input" placeholder="admin@yourdomain.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Admin Password (min. 8 chars)</label>
            <input type="password" name="admin_pass" class="form-input" placeholder="••••••••" required minlength="8">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Default Currency</label>
          <select name="currency" class="form-select">
            <option value="BDT">BDT (৳) - Bangladeshi Taka</option>
            <option value="USD">USD ($) - US Dollar</option>
            <option value="EUR">EUR (€) - Euro</option>
            <option value="INR">INR (₹) - Indian Rupee</option>
          </select>
        </div>

        <div style="margin-top: 16px;">
          <label style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer; font-size: 0.875rem;">
            <input type="checkbox" name="load_sample_data" value="1" checked style="margin-top: 3px;">
            <span>Load Bangladesh presets & sample accounts (Cash, bKash, Nagad, Bank, typical categories)</span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="font-size: 1rem; padding: 14px;" :disabled="!allChecksPassed || isSubmitting">
        <span x-text="isSubmitting ? 'Installing Database & Setting Up...' : 'Install Database & Complete Setup'"></span>
      </button>
    </form>
  </div>
</div>

<script>
function installWizard() {
  return {
    allChecksPassed: <?= $allPassed ? 'true' : 'false' ?>,
    driver: 'mysql',
    dbHost: 'localhost',
    dbPort: 3306,
    dbName: '',
    dbUser: '',
    dbPass: '',
    testingDb: false,
    testSuccess: false,
    testMessage: '',
    isSubmitting: false,

    async testConnection() {
      this.testingDb = true;
      this.testMessage = '';

      try {
        const response = await fetch('/install/test-db', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            driver: this.driver,
            host: this.dbHost,
            port: this.dbPort,
            database: this.dbName,
            username: this.dbUser,
            password: this.dbPass
          })
        });

        const data = await response.json();
        this.testSuccess = data.ok;
        this.testMessage = data.message;
      } catch (err) {
        this.testSuccess = false;
        this.testMessage = 'Network error testing connection.';
      } finally {
        this.testingDb = false;
      }
    }
  };
}
</script>
