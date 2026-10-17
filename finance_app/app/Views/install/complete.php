<div class="card">
  <div class="card-body" style="text-align: center; padding: 40px 24px;">
    <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: var(--radius-full); background-color: var(--color-income-light); color: var(--color-income); margin-bottom: 20px;">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 6L9 17l-5-5"/>
      </svg>
    </div>

    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 8px;">Installation Complete!</h2>
    <p style="color: var(--ink-secondary); margin-bottom: 28px; max-width: 480px; margin-left: auto; margin-right: auto;">
      Database schema has been initialized, admin account created, and the installation has been securely locked.
    </p>

    <!-- Cron Setup Box -->
    <div style="background-color: var(--bg-subtle); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; text-align: left; margin-bottom: 28px;">
      <h3 style="font-size: 0.9375rem; font-weight: 700; margin-bottom: 8px; color: var(--ink-primary);">
        cPanel Scheduled Cron Job (Recommended)
      </h3>
      <p style="font-size: 0.8125rem; color: var(--ink-muted); margin-bottom: 12px;">
        To automate recurring transactions, budget warnings, and email digests, add this job in your cPanel Cron Jobs (runs every 15 minutes):
      </p>
      
      <div style="position: relative;">
        <pre style="background: var(--bg-surface); border: 1px solid var(--border-color); padding: 12px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.8125rem; overflow-x: auto; color: var(--color-primary); font-weight: 600;"><?= e($cronCmd) ?></pre>
      </div>
    </div>

    <div>
      <a href="/login" class="btn btn-primary" style="padding: 12px 32px; font-size: 1rem;">
        Go to Login Page
      </a>
    </div>
  </div>
</div>
