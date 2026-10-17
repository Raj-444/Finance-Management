<form method="POST" action="/reset-password">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">

  <div style="margin-bottom: 20px;">
    <p style="font-size: 0.875rem; color: var(--ink-secondary);">
      Create a new strong password for your account.
    </p>
  </div>

  <div class="form-group">
    <label class="form-label">New Password</label>
    <input type="password" name="password" class="form-input" placeholder="At least 8 characters" required minlength="8" autofocus>
  </div>

  <div class="form-group">
    <label class="form-label">Confirm New Password</label>
    <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat new password" required minlength="8">
  </div>

  <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; margin-top: 8px;">
    <?= e(__('reset_password')) ?>
  </button>
</form>
