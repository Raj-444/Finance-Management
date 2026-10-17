<div class="page-container" x-data="{
  deleteModal: false,
  deleteId: null,
  deleteName: ''
}">
  <!-- Header -->
  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
      <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--ink-primary); margin: 0 0 4px 0;">
        Admin Panel & Diagnostics
      </h1>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin: 0;">
        System environment metrics, cPanel cron monitor, and user access management.
      </p>
    </div>

    <a href="/settings?tab=system" class="btn btn-ghost" style="border: 1px solid var(--border-color);">
      &larr; Back to Settings
    </a>
  </div>

  <!-- Diagnostics KPI Cards -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 16px 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">PHP Runtime</span>
      <div style="font-size: 1.25rem; font-weight: 800; color: var(--ink-primary); margin-top: 4px;">
        PHP <?= e($system['php_version']) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);"><?= e($system['server_os']) ?></span>
    </div>

    <div class="card" style="padding: 16px 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Memory & Execution</span>
      <div style="font-size: 1.25rem; font-weight: 800; color: var(--color-primary); margin-top: 4px;">
        <?= e($system['memory_limit']) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);">Max time: <?= e($system['max_execution_time']) ?></span>
    </div>

    <div class="card" style="padding: 16px 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Upload Limits</span>
      <div style="font-size: 1.25rem; font-weight: 800; color: var(--ink-primary); margin-top: 4px;">
        <?= e($system['upload_max_filesize']) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);">Post max: <?= e($system['post_max_size']) ?></span>
    </div>

    <div class="card" style="padding: 16px 20px;">
      <span style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-muted);">Last Cron Run</span>
      <div style="font-size: 1.125rem; font-weight: 700; color: var(--color-success); margin-top: 6px;">
        <?= e($lastCron) ?>
      </div>
      <span style="font-size: 0.75rem; color: var(--ink-muted);">Runs every 15 min via cPanel</span>
    </div>
  </div>

  <!-- PHP Extensions Status -->
  <div class="card" style="margin-bottom: 24px; padding: 20px;">
    <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 14px 0;">Required PHP Extensions</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
      <?php foreach ($system['extensions'] as $ext => $loaded): ?>
        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: var(--radius-full); font-size: 0.8125rem; font-weight: 600; background: <?= $loaded ? 'var(--color-success-light)' : 'var(--color-danger-light)' ?>; color: <?= $loaded ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
          <span><?= $loaded ? '✓' : '✗' ?></span>
          <span><?= e($ext) ?></span>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Users Management Table -->
  <div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between;">
      <h3 style="font-size: 1rem; font-weight: 700; color: var(--ink-primary); margin: 0;">Registered User Accounts</h3>
      <span style="font-size: 0.8125rem; color: var(--ink-muted); font-weight: 600;"><?= count($users) ?> total users</span>
    </div>

    <div class="table-responsive">
      <table class="table" style="margin: 0;">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name & Email</th>
            <th>Role</th>
            <th>Currency</th>
            <th>Status</th>
            <th>Created At</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <?php
              $isCurrent = ((int)$u['id'] === (int)\App\Core\Auth::id());
              $isAdmin = ($u['role'] === 'admin');
              $isActive = ((int)$u['is_active'] === 1);
            ?>
            <tr>
              <td><?= $u['id'] ?></td>
              <td>
                <div style="font-weight: 600; color: var(--ink-primary);"><?= e($u['name']) ?> <?= $isCurrent ? '<span style="font-size: 0.75rem; color: var(--color-primary);">(You)</span>' : '' ?></div>
                <div style="font-size: 0.75rem; color: var(--ink-muted);"><?= e($u['email']) ?></div>
              </td>
              <td>
                <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: <?= $isAdmin ? 'var(--color-warning-light)' : 'var(--surface-bg)' ?>; color: <?= $isAdmin ? 'var(--color-warning)' : 'var(--ink-secondary)' ?>;">
                  <?= strtoupper(e($u['role'])) ?>
                </span>
              </td>
              <td><span style="font-weight: 600;"><?= e($u['currency']) ?></span></td>
              <td>
                <span style="font-size: 0.75rem; font-weight: 700; padding: 2px 8px; border-radius: var(--radius-full); background: <?= $isActive ? 'var(--color-success-light)' : 'var(--color-danger-light)' ?>; color: <?= $isActive ? 'var(--color-success)' : 'var(--color-danger)' ?>;">
                  <?= $isActive ? 'Active' : 'Suspended' ?>
                </span>
              </td>
              <td><span style="font-size: 0.8125rem; color: var(--ink-muted);"><?= e(substr($u['created_at'], 0, 10)) ?></span></td>
              <td style="text-align: right;">
                <?php if (!$isCurrent): ?>
                  <div style="display: flex; gap: 6px; justify-content: flex-end;">
                    <form action="/admin/users/<?= $u['id'] ?>/role" method="POST" style="display: inline;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost" style="font-size: 0.75rem; padding: 2px 6px;">
                        <?= $isAdmin ? 'Demote' : 'Promote' ?>
                      </button>
                    </form>

                    <form action="/admin/users/<?= $u['id'] ?>/active" method="POST" style="display: inline;">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn btn-ghost" style="font-size: 0.75rem; padding: 2px 6px;">
                        <?= $isActive ? 'Suspend' : 'Activate' ?>
                      </button>
                    </form>

                    <button type="button" class="btn btn-ghost" @click="deleteId = <?= $u['id'] ?>; deleteName = '<?= addslashes($u['name']) ?>'; deleteModal = true;" style="color: var(--color-danger); font-size: 0.75rem; padding: 2px 6px;">
                      Delete
                    </button>
                  </div>
                <?php else: ?>
                  <span style="font-size: 0.75rem; color: var(--ink-muted); font-style: italic;">Current Session</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal: Delete User -->
  <div x-show="deleteModal" x-transition style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 16px;" x-cloak>
    <div class="card" @click.outside="deleteModal = false" style="width: 100%; max-width: 400px; padding: 24px; box-shadow: var(--shadow-xl);">
      <h3 style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary); margin: 0 0 8px 0;">Delete User Account?</h3>
      <p style="font-size: 0.875rem; color: var(--ink-muted); margin-bottom: 20px;">
        Are you sure you want to permanently delete <strong x-text="deleteName"></strong>? All their transactions and ledgers will be wiped out.
      </p>

      <form :action="'/admin/users/' + deleteId + '/delete'" method="POST" style="display: flex; justify-content: flex-end; gap: 10px;">
        <?= csrf_field() ?>
        <button type="button" class="btn btn-ghost" @click="deleteModal = false">Cancel</button>
        <button type="submit" class="btn btn-primary" style="background: var(--color-danger); border-color: var(--color-danger);">Delete Account</button>
      </form>
    </div>
  </div>
</div>
