<form method="POST" action="/forgot-password">
  <?= csrf_field() ?>

  <div style="margin-bottom: 20px;">
    <p style="font-size: 0.875rem; color: var(--ink-secondary);">
      Enter your account email address and we'll send you a secure link to reset your password.
    </p>
  </div>

  <div class="form-group">
    <label class="form-label"><?= e(__('email')) ?></label>
    <input type="email" name="email" class="form-input" placeholder="name@example.com" required autofocus>
  </div>

  <button type="submit" class="btn btn-primary btn-block" style="padding: 12px; font-size: 1rem; margin-top: 8px;">
    <?= e(__('send_reset_link')) ?>
  </button>

  <div style="text-align: center; margin-top: 24px; font-size: 0.875rem;">
    <a href="/login" style="font-weight: 600; color: var(--ink-secondary);">← <?= e(__('back_to_login')) ?></a>
  </div>
</form>
