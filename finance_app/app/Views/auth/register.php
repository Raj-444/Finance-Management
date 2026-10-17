<form method="POST" action="/register">
  <?= csrf_field() ?>

  <div class="form-group">
    <label class="form-label"><?= e(__('name')) ?></label>
    <input type="text" name="name" class="form-input" placeholder="Your Name" required autofocus value="<?= e(old('name')) ?>">
  </div>

  <div class="form-group">
    <label class="form-label"><?= e(__('email')) ?></label>
    <input type="email" name="email" class="form-input" placeholder="name@example.com" required value="<?= e(old('email')) ?>">
  </div>

  <div class="form-group">
    <label class="form-label"><?= e(__('password')) ?></label>
    <input type="password" name="password" class="form-input" placeholder="At least 8 characters" required minlength="8">
  </div>

  <div class="form-group">
    <label class="form-label"><?= e(__('confirm_password')) ?></label>
    <input type="password" name="password_confirmation" class="form-input" placeholder="Repeat password" required minlength="8">
  </div>

  <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; margin-top: 8px;">
    <?= e(__('register')) ?>
  </button>

  <div style="text-align: center; margin-top: 24px; font-size: 0.875rem; color: var(--ink-secondary);">
    <?= e(__('already_have_account')) ?>
    <a href="/login" style="font-weight: 600;"><?= e(__('login')) ?></a>
  </div>
</form>
