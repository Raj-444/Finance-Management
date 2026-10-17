<!DOCTYPE html>
<html lang="<?= e(\App\Core\I18n::getLocale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Sign In') ?> - Personal Finance Manager</title>
  <?= csrf_meta() ?>
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/app.js"></script>
</head>
<body class="auth-page">

  <!-- Top Utilities: Locale Switcher & Theme Toggle -->
  <div style="position: absolute; top: 20px; right: 24px; display: flex; align-items: center; gap: 12px; z-index: 10;">
    <!-- Language Toggle -->
    <form method="POST" action="/settings/locale" style="display: inline-block;">
      <?= csrf_field() ?>
      <?php if (\App\Core\I18n::getLocale() === 'en'): ?>
        <input type="hidden" name="locale" value="bn">
        <button type="submit" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 700; color: #FFFFFF; background: rgba(255,255,255,0.14); border-radius: var(--radius-full);">বাংলা</button>
      <?php else: ?>
        <input type="hidden" name="locale" value="en">
        <button type="submit" class="btn btn-ghost" style="padding: 6px 14px; font-size: 0.8125rem; font-weight: 700; color: #FFFFFF; background: rgba(255,255,255,0.14); border-radius: var(--radius-full);">English</button>
      <?php endif; ?>
    </form>

    <!-- Theme Toggle -->
    <button type="button" class="btn btn-ghost btn-icon" onclick="window.PFM.toggleTheme()" title="Toggle Dark/Light Mode" style="color: #FFFFFF; background: rgba(255,255,255,0.14);">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
      </svg>
    </button>
  </div>

  <div class="auth-card">
    <div class="auth-brand">
      <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #5B5BD6, #3B3B98); color: #fff; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(91, 91, 214, 0.25);">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
      </div>
      <h1 class="auth-title"><?= e(__('app_name')) ?></h1>
      <p class="auth-subtitle"><?= e(__('tagline')) ?></p>
    </div>

    <!-- Flash Messages -->
    <?php if (has_flash('error')): ?>
      <div class="toast toast-error" style="position: static; margin-bottom: 20px; width: 100%; animation: none;">
        <span><?= e(flash('error')) ?></span>
      </div>
    <?php endif; ?>
    <?php if (has_flash('success')): ?>
      <div class="toast toast-success" style="position: static; margin-bottom: 20px; width: 100%; animation: none;">
        <span><?= e(flash('success')) ?></span>
      </div>
    <?php endif; ?>
    <?php if (has_flash('warning')): ?>
      <div class="toast toast-warning" style="position: static; margin-bottom: 20px; width: 100%; animation: none;">
        <span><?= e(flash('warning')) ?></span>
      </div>
    <?php endif; ?>
    <?php if (has_flash('info')): ?>
      <div class="toast toast-info" style="position: static; margin-bottom: 20px; width: 100%; animation: none;">
        <span><?= e(flash('info')) ?></span>
      </div>
    <?php endif; ?>

    <?= $content ?>

  </div>

</body>
</html>
