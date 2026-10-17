<!DOCTYPE html>
<html lang="<?= e(\App\Core\I18n::getLocale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Installer') ?> - Personal Finance Manager</title>
  <?= csrf_meta() ?>
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script src="/assets/js/app.js"></script>
  <script defer src="/assets/js/alpine.min.js"></script>
</head>
<body style="background-color: var(--bg-page);">
  <div style="min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px;">
    
    <div style="width: 100%; max-width: 650px;">
      <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 16px; background: linear-gradient(135deg, #5B5BD6, #3B3B98); color: #fff; margin-bottom: 12px; box-shadow: 0 4px 12px rgba(91, 91, 214, 0.3);">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--ink-primary);">Personal Finance Manager</h1>
        <p style="color: var(--ink-muted); font-size: 0.9375rem;">Web Setup Wizard</p>
      </div>

      <!-- Flash Messages -->
      <?php if (has_flash('error')): ?>
        <div class="toast toast-error" style="margin-bottom: 20px; width: 100%; animation: none;">
          <span><?= e(flash('error')) ?></span>
        </div>
      <?php endif; ?>
      <?php if (has_flash('success')): ?>
        <div class="toast toast-success" style="margin-bottom: 20px; width: 100%; animation: none;">
          <span><?= e(flash('success')) ?></span>
        </div>
      <?php endif; ?>

      <?= $content ?>

    </div>

  </div>
</body>
</html>
