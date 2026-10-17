<form method="POST" action="/login">
  <?= csrf_field() ?>

  <div class="form-group">
    <label class="form-label"><?= e(__('email')) ?></label>
    <input type="email" name="email" class="form-input" placeholder="name@example.com" required autofocus value="<?= e(old('email')) ?>">
  </div>

  <div class="form-group">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <label class="form-label" style="margin-bottom: 0;"><?= e(__('password')) ?></label>
      <a href="/forgot-password" style="font-size: 0.8125rem; font-weight: 500; color: var(--color-primary);"><?= e(__('forgot_password')) ?></a>
    </div>
    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
  </div>

  <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; margin-top: 8px;">
    <?= e(__('login')) ?>
  </button>

  <div style="text-align: center; margin-top: 24px; font-size: 0.875rem; color: var(--ink-secondary);">
    <?= e(__('dont_have_account')) ?>
    <a href="/register" style="font-weight: 600;"><?= e(__('register')) ?></a>
  </div>
</form>
