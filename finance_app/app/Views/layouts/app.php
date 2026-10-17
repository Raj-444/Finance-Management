<!DOCTYPE html>
<html lang="<?= e(\App\Core\I18n::getLocale()) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title ?? 'Dashboard') ?> - Personal Finance Manager</title>
  <?= csrf_meta() ?>
  <link rel="manifest" href="/manifest.webmanifest">
  <meta name="theme-color" content="#5B5BD6">
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script>window.PFM_USER_LOGGED_IN = <?= is_logged_in() ? 'true' : 'false' ?>;</script>
  <script src="/assets/js/app.js"></script>
  <script defer src="/assets/js/alpine.min.js"></script>
</head>
<body>

<div class="app-container">

  <!-- Desktop Sidebar -->
  <aside class="app-sidebar">
    <div class="sidebar-brand">
      <div style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #5B5BD6, #3B3B98); color: #fff;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
      </div>
      <span>Finance</span>
    </div>

    <nav class="sidebar-nav">
      <a href="/dashboard" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'dashboard') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        <span><?= e(__('nav_home')) ?></span>
      </a>

      <a href="/transactions" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'transactions') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span><?= e(__('nav_transactions')) ?></span>
      </a>

      <a href="/budgets" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'budgets') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span><?= e(__('nav_budgets')) ?></span>
      </a>

      <a href="/analytics" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'analytics') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        <span><?= e(__('nav_analytics')) ?></span>
      </a>

      <a href="/debts" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'debts') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span><?= e(__('nav_debts')) ?></span>
      </a>

      <a href="/goals" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'goals') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>
        <span><?= e(__('nav_goals')) ?></span>
      </a>

      <a href="/settings" class="nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'settings') ? 'active' : '' ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        <span><?= e(__('nav_settings')) ?></span>
      </a>

      <?php if (is_admin()): ?>
        <a href="/admin" class="nav-item" style="color: var(--color-warning);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span><?= e(__('nav_admin')) ?></span>
        </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div style="display: flex; align-items: center; gap: 10px; overflow: hidden;">
        <div style="width: 32px; height: 32px; border-radius: var(--radius-full); background-color: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8125rem;">
          <?= strtoupper(substr(current_user()['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          <div style="font-size: 0.8125rem; font-weight: 600; color: var(--ink-primary); text-overflow: ellipsis; overflow: hidden;"><?= e(current_user()['name'] ?? 'User') ?></div>
          <div style="font-size: 0.75rem; color: var(--ink-muted);"><?= e(current_user()['email'] ?? '') ?></div>
        </div>
      </div>

      <form method="POST" action="/logout">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-ghost btn-icon" title="<?= e(__('logout')) ?>">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </button>
      </form>
    </div>
  </aside>

  <!-- Main Content Layout -->
  <div class="app-main">
    <header class="app-header">
      <div style="font-size: 1.125rem; font-weight: 700; color: var(--ink-primary);">
        <?= e($title ?? 'Dashboard') ?>
      </div>

      <div style="display: flex; align-items: center; gap: 12px;">
        <!-- Language Switcher -->
        <form method="POST" action="/settings/locale" style="display: inline-block;">
          <?= csrf_field() ?>
          <?php if (\App\Core\I18n::getLocale() === 'en'): ?>
            <input type="hidden" name="locale" value="bn">
            <button type="submit" class="btn btn-ghost" style="padding: 6px 10px; font-size: 0.8125rem; font-weight: 600;">বাংলা</button>
          <?php else: ?>
            <input type="hidden" name="locale" value="en">
            <button type="submit" class="btn btn-ghost" style="padding: 6px 10px; font-size: 0.8125rem; font-weight: 600;">EN</button>
          <?php endif; ?>
        </form>

        <!-- Theme Toggle -->
        <button type="button" class="btn btn-ghost btn-icon" onclick="window.PFM.toggleTheme()" title="Toggle Dark/Light Mode">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
          </svg>
        </button>

        <!-- Quick Add Desktop Button -->
        <button type="button" class="btn btn-primary" id="btn-quick-add" style="padding: 8px 16px; font-size: 0.875rem;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          <span><?= e(__('quick_add')) ?> (N)</span>
        </button>
      </div>
    </header>

    <main class="app-content">
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

      <?= $content ?>
    </main>
  </div>

  <!-- Mobile Bottom Navigation -->
  <nav class="app-bottom-nav">
    <a href="/dashboard" class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'dashboard') ? 'active' : '' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
      <span><?= e(__('nav_home')) ?></span>
    </a>

    <a href="/transactions" class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'transactions') ? 'active' : '' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      <span><?= e(__('nav_transactions')) ?></span>
    </a>

    <!-- Center Floating Add Button -->
    <a href="#quick-add" class="bottom-nav-add" onclick="document.getElementById('btn-quick-add').click(); return false;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
      </svg>
    </a>

    <a href="/analytics" class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'analytics') ? 'active' : '' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
      <span><?= e(__('nav_analytics')) ?></span>
    </a>

    <a href="/settings" class="bottom-nav-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', 'settings') ? 'active' : '' ?>">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span><?= e(__('nav_settings')) ?></span>
    </a>
  </nav>

</div>

<div id="toast-container" class="toast-container"></div>

<script>
  // Register Service Worker for PWA
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
  }
</script>
</body>
</html>
