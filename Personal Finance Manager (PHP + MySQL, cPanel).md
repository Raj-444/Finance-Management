# Personal Finance Manager (PHP + MySQL, cPanel-ready): PRD, TRD, Schema & Build Prompts

Prepared for Raj | Stack: plain PHP 8.2 + MySQL/MariaDB, no Node on the server | Deploy: upload to cPanel, run the web installer | Target: MVP Ready

## 0. Decisions and assumptions

- **Zero Distraction** was cut off in your message. Assumption used here: a quiet, ad-free, minimal UI with a Focus mode (balance plus quick add only), quiet hours for emails, and alerts only for budgets. Send the original text if you meant something else.
- **cPanel-first.** Shared hosting means: no long-running processes, no Node/Redis, Composer may be missing. So the app ships with its `vendor/` folder and pre-built CSS/JS. Background work runs from a cPanel cron job.
- **Not included:** planner, alarms, pomodoro and other Life Easy modules. Only the Money Tracker ideas (lend/borrow, person-wise, pie chart, CSV/JSON export) are merged in here.

| Layer | Choice | Why |
| --- | --- | --- |
| Backend | PHP 8.2, own light MVC (front controller + router), PDO prepared statements | Runs on any cPanel, easy to audit |
| Database | MySQL 5.7+/MariaDB 10.3+, InnoDB, utf8mb4 | cPanel default |
| Frontend | Server-rendered PHP views + Alpine.js, self-hosted Chart.js, hand-written CSS with design tokens (pre-built, no build step on server) | Fast, no CDN dependence, premium look |
| Excel | OpenSpout (xlsx read/write, streaming, low memory), bundled in vendor | Works within shared-hosting memory limits |
| Email | PHPMailer (SMTP of the cPanel mailbox) | Reliable alerts and password reset |
| PWA | manifest + service worker (offline shell, install on phone) | App-like feel without an app |
| Money | BIGINT in minor units (paisa), currency default BDT | No float errors |

## 1. PRD

**Problem.** People track money in notes, chats and spreadsheets. Entry takes too long so they stop, budgets are never checked until the month ends, and nothing warns them in time.

**Target users.** Students, salaried people, freelancers, small family households. Single user per account, multi-user per installation (family or friends), one admin.

**Goals.**

- Add an expense in 2 taps and under 5 seconds.
- See where money went (daily, weekly, monthly) and what is left, at a glance.
- Get warned before the budget is crossed, not after.
- Move data in and out through Excel easily; own your data (backup, export).
- Looks premium on phone and desktop, works in Bangla and English.

**Non-goals (v1).** Bank API sync, live currency rates, investments/stock tracking, receipt OCR, shared household budgets with permissions (planned v1.1).

**Roles.** Admin (users, registration toggle, SMTP settings, backups), User (own data only).

**Success metrics.** Median add time under 5 seconds; installer finishes in under 5 minutes on a fresh cPanel; zero cross-user data access in tests; Lighthouse above 90 on dashboard (mobile).

## 2. Features

**A. Quick tracking (your request)**

- Category chips grid, tap category, type amount on a large keypad, Save. Defaults: today, last used account.
- Floating quick-add button on every page, keyboard shortcut N, Repeat last transaction, undo toast after save.
- Income, expense and transfer between accounts. Optional note, date, person and tags.
- Categories with icon and colour, sub-categories, archive instead of delete.

**B. Dashboard and logs (your request)**

- Hero card: total balance, this month income, expense, net, saved percent.
- Remaining budget rings, recent activity, upcoming recurring bills, savings goals progress.
- Transactions page: search (note, category, person), filters (date range, type, account, category, amount range), sort, pagination, bulk delete, edit in a side panel, saved filters.

**C. Excel support (your request)**

- Export filtered transactions to .xlsx and .csv (formula-injection safe). Download a ready import template.
- Import .xlsx/.csv: column mapping, live preview, row validation, duplicate warning, commit in one transaction, **Undo import** by batch.

**D. Analytics and charts (your request)**

- Daily, weekly, monthly spending trend (line/area), category breakdown (doughnut), income vs expense (bars), top categories and notes, month-over-month comparison, net worth over time.
- Range picker (7d, 30d, this month, custom), tooltip values, export chart data.

**E. Smart budgets (your request)**

- Overall and per-category monthly budgets, custom month start day (salary cycle).
- Spending forecast: projected month-end spend from the run rate plus upcoming recurring items.
- Alerts: at warn percent (default 80), when exceeded, and when forecast says you will exceed. In-app notification plus optional email, never repeated for the same level in a month.

**F. Money tracker (from your Life Easy list)**

- Lend/Borrow with person-wise ledger, partial repayments, due dates, Open/Settled tabs, pie overview.

**G. Recommended additions**

- Multiple accounts (cash, bank, bKash, Nagad, card) with balances and opening balance.
- Recurring transactions (rent, subscriptions, salary) auto-posted by cron or confirmed manually.
- Savings goals with contributions and target dates.
- Bangla/English UI, optional lakh/crore number format, Bangla digits option.
- Dark mode, installable PWA, Focus mode (Zero Distraction), quiet hours.
- Security: strong password hashing, login rate limit, optional TOTP 2FA (v1.1), session management page.
- Data safety: one-click JSON backup and restore, scheduled backup to storage, export everything, delete account.
- Monthly email summary (opt-in).

## 3. UX and design system (premium)

- **Tokens.** Light: bg #F7F8FC, surface #FFFFFF, ink #0F172A, muted #64748B, primary #5B5BD6, income #12B76A, expense #F04438, warning #F79009. Dark: bg #0B1020, surface #121A2F. Radius 16 and 24, soft layered shadows, 8px spacing grid.
- **Type.** Plus Jakarta Sans for Latin, Hind Siliguri for Bangla, both self-hosted woff2 with `font-display: swap`. Tabular numbers for amounts.
- **Layout.** Mobile: bottom navigation (Home, Transactions, Add, Analytics, More). Desktop: slim sidebar, wide dashboard grid.
- **Details.** Gradient hero card (one accent gradient only), subtle count-up on balances, skeleton loaders, meaningful empty states, haptic-like button press, smooth sheet for quick add, chart gradients and rounded bars, 44px touch targets, visible focus, WCAG AA contrast, respects reduced motion.
- **No fake content.** Sample data only if the user ticks Load sample data in the installer, clearly labelled and removable.

## 4. TRD

**Folder structure (outside the web root)**

```text
/home/CPANELUSER/finance_app/
  app/ Controllers/ Models/ Services/ Views/ Lang/ Helpers/
  bin/cron.php
  config/ (generated by installer, denied from web)
  database/schema.sql, migrations/
  storage/ logs/ sessions/ cache/ backups/ tmp/
  vendor/            (OpenSpout, PHPMailer, committed)
  bootstrap.php
/home/CPANELUSER/public_html/        (or the subdomain document root)
  index.php          (requires ../finance_app/bootstrap.php)
  .htaccess  manifest.webmanifest  sw.js
  assets/ css/ js/ fonts/ img/ icons/
```

If the host forces everything into `public_html`, ship a fallback layout with `.htaccess` that denies `app/`, `config/`, `storage/`, `vendor/`, `database/`, and tell the user this is less safe.

**Routing.** `.htaccess` rewrites everything except real files to `index.php`; a router maps method + path to controllers. JSON endpoints under `/api/` return `{ok, data, error}` for Alpine fetch calls (add transaction, search, charts).

**Installer (`/install`).** Checks PHP version, extensions (pdo\_mysql, mbstring, zip, xml, libxml, openssl, json, fileinfo), writable folders, then asks DB host/name/user/password, tests the connection, runs `schema.sql`, creates the admin, writes `config/config.php`, optionally loads sample data, writes `storage/installed.lock`, and disables itself. Re-running is blocked by the lock.

**Security.**

- Every query scoped by `user_id`; ownership checked for each id from the request (IDOR tests).
- PDO prepared statements only, `emulate_prepares = false`. Output escaped by default in views.
- `password_hash` (Argon2id if available, else bcrypt), `password_needs_rehash` on login.
- CSRF token on every state-changing request; SameSite=Lax, HttpOnly, Secure session cookies, `session_regenerate_id` on login, sessions stored in `storage/sessions`.
- Login rate limit from `login_attempts` (5 failures per 15 minutes per email and IP), generic error messages, password reset with hashed, expiring, single-use tokens.
- Security headers (CSP without inline scripts where practical, X-Content-Type-Options, Referrer-Policy, frame-ancestors none), HTTPS redirect.
- Uploads: extension and MIME check, size limit 5 MB, max 5,000 rows per import, stored in `storage/tmp` and deleted after processing.
- Export cells starting with `=`, `+`, `-`, `@` are prefixed to stop spreadsheet formula injection.
- Errors logged to `storage/logs`, never shown to users in production.

**Budget forecast logic.** For the active period: `spent` = expenses so far; `elapsed` = days elapsed (min 1); `projected = spent / elapsed * days_in_period + upcoming_recurring_not_yet_posted`. Levels: `warn` when spent >= warn percent of budget, `exceeded` when spent > budget, `forecast` when projected > budget while spent <= budget. Checked after each expense insert and by cron; one alert per (budget, level).

**Cron (cPanel Cron Jobs, every 15 minutes).** Posts due recurring items, evaluates budget alerts, sends queued emails, rotates old logs/backups, builds the monthly summary on the 1st.

```text
*/15 * * * * /usr/local/bin/php /home/CPANELUSER/finance_app/bin/cron.php >/dev/null 2>&1
```

The PHP path differs per host (for example `/opt/cpanel/ea-php82/root/usr/bin/php`); the installer shows the exact line to copy.

**Import/export.** Import flow: upload, parse with OpenSpout, map columns (date, amount, type, category, account, note, person), preview first 50 rows with errors flagged, unknown categories can be created or mapped, dedupe warning by hash of date + amount + account + note, commit in one DB transaction with an `import_batch_id`; Undo soft-deletes that batch. Export streams rows to keep memory low.

**Performance.** Indexes on `(user_id, tx_date)`, `(user_id, category_id, tx_date)`; chart data aggregated in SQL by day, week, month; pagination 25 per page; assets fingerprinted with long cache headers; gzip via `.htaccess`.

**Edge cases.** Transfers between accounts (two-sided, counted neither as income nor expense), deleting an account with transactions (archive only), changing a category with budgets, month start day other than 1, leap years, timezone (store dates as local DATE, user timezone setting), negative or zero amounts rejected, imports with mixed date formats.

## 5. Database schema (MySQL/MariaDB)

```sql
SET NAMES utf8mb4;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  currency CHAR(3) NOT NULL DEFAULT 'BDT',
  locale VARCHAR(5) NOT NULL DEFAULT 'en',
  timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Dhaka',
  theme ENUM('light','dark','system') NOT NULL DEFAULT 'system',
  month_start_day TINYINT UNSIGNED NOT NULL DEFAULT 1,
  week_start TINYINT UNSIGNED NOT NULL DEFAULT 6,
  number_format ENUM('intl','indian') NOT NULL DEFAULT 'indian',
  email_alerts TINYINT(1) NOT NULL DEFAULT 1,
  focus_mode TINYINT(1) NOT NULL DEFAULT 0,
  quiet_from TIME NULL, quiet_to TIME NULL,
  totp_secret VARCHAR(64) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL, ip VARBINARY(16) NOT NULL, success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_time (email, attempted_at), KEY idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL, used_at DATETIME NULL,
  KEY idx_token (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  type ENUM('cash','bank','mobile_wallet','card','savings','other') NOT NULL DEFAULT 'cash',
  opening_balance BIGINT NOT NULL DEFAULT 0,
  color CHAR(7) NOT NULL DEFAULT '#5B5BD6', icon VARCHAR(40) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0, sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_name (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  type ENUM('expense','income') NOT NULL,
  name VARCHAR(80) NOT NULL, icon VARCHAR(40) NULL, color CHAR(7) NOT NULL DEFAULT '#64748B',
  is_archived TINYINT(1) NOT NULL DEFAULT 0, sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_cat (user_id, type, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE people (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, phone VARCHAR(30) NULL, note VARCHAR(255) NULL,
  UNIQUE KEY uq_person (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_batches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, filename VARCHAR(190) NOT NULL,
  rows_total INT UNSIGNED NOT NULL DEFAULT 0, rows_imported INT UNSIGNED NOT NULL DEFAULT 0, rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('committed','undone') NOT NULL DEFAULT 'committed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE recurring_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('expense','income') NOT NULL,
  account_id INT UNSIGNED NOT NULL, category_id INT UNSIGNED NULL,
  amount BIGINT UNSIGNED NOT NULL, note VARCHAR(255) NULL,
  frequency ENUM('daily','weekly','monthly','yearly') NOT NULL, interval_n TINYINT UNSIGNED NOT NULL DEFAULT 1,
  next_run DATE NOT NULL, end_date DATE NULL,
  auto_post TINYINT(1) NOT NULL DEFAULT 1, is_active TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_due (is_active, next_run),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('expense','income','transfer') NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  to_account_id INT UNSIGNED NULL,
  category_id INT UNSIGNED NULL, person_id INT UNSIGNED NULL,
  amount BIGINT UNSIGNED NOT NULL,
  tx_date DATE NOT NULL, note VARCHAR(255) NULL, tags VARCHAR(190) NULL,
  recurring_id INT UNSIGNED NULL, import_batch_id INT UNSIGNED NULL, dedupe_hash CHAR(40) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT chk_amount CHECK (amount > 0),
  KEY idx_user_date (user_id, tx_date),
  KEY idx_user_cat_date (user_id, category_id, tx_date),
  KEY idx_user_acc (user_id, account_id),
  KEY idx_dedupe (user_id, dedupe_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id),
  FOREIGN KEY (to_account_id) REFERENCES accounts(id),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE SET NULL,
  FOREIGN KEY (recurring_id) REFERENCES recurring_rules(id) ON DELETE SET NULL,
  FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE budgets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,                       -- NULL = overall monthly budget
  period_start DATE NOT NULL,                          -- first day of the budget period
  amount BIGINT UNSIGNED NOT NULL,
  warn_percent TINYINT UNSIGNED NOT NULL DEFAULT 80,
  scope_key INT UNSIGNED GENERATED ALWAYS AS (IFNULL(category_id, 0)) STORED,
  UNIQUE KEY uq_budget (user_id, period_start, scope_key),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('budget_warn','budget_exceeded','budget_forecast','recurring_posted','system') NOT NULL,
  budget_id INT UNSIGNED NULL,
  title VARCHAR(150) NOT NULL, body VARCHAR(500) NULL, link VARCHAR(190) NULL,
  emailed_at DATETIME NULL, read_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_alert_once (budget_id, type),
  KEY idx_user_unread (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE debts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, person_id INT UNSIGNED NOT NULL,
  direction ENUM('lent','borrowed') NOT NULL,
  principal BIGINT UNSIGNED NOT NULL, opened_on DATE NOT NULL, due_on DATE NULL, note VARCHAR(255) NULL,
  status ENUM('open','settled') NOT NULL DEFAULT 'open',
  KEY idx_user_status (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE debt_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  debt_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  amount BIGINT UNSIGNED NOT NULL, paid_on DATE NOT NULL, note VARCHAR(255) NULL,
  FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE savings_goals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL,
  target_amount BIGINT UNSIGNED NOT NULL, target_date DATE NULL,
  color CHAR(7) NOT NULL DEFAULT '#12B76A', icon VARCHAR(40) NULL,
  status ENUM('active','achieved','archived') NOT NULL DEFAULT 'active',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goal_contributions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL,
  amount BIGINT NOT NULL, contributed_on DATE NOT NULL, note VARCHAR(255) NULL,
  FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE app_settings (
  k VARCHAR(60) PRIMARY KEY, v TEXT NULL                 -- registration_open, smtp_*, app_name, version
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL, entity VARCHAR(40) NULL, entity_id BIGINT UNSIGNED NULL,
  ip VARBINARY(16) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Query notes.** Account balance = opening + income - expense, plus incoming transfers minus outgoing transfers, always filtered by `deleted_at IS NULL`. Period spend uses the user's `month_start_day`. Budgets alert at most once per level per budget (unique key on `budget_id, type`).

## 6. cPanel deployment steps

1. cPanel > **MultiPHP Manager**: select PHP 8.2 for the domain. **Select PHP Version**: enable pdo\_mysql, mbstring, zip, xml, fileinfo, intl (if offered).
2. **MySQL Databases**: create a database, a user, and add the user to the database with all privileges. Note the full prefixed names.
3. **File Manager** (or FTP): upload the release zip, extract `finance_app/` one level above `public_html`, and the `public/` contents into `public_html` (or the subdomain folder).
4. Open `https://yourdomain/install`, follow the wizard (checks, DB, admin account, optional sample data).
5. cPanel > **Cron Jobs**: paste the cron line shown by the installer (every 15 minutes).
6. Force HTTPS (AutoSSL is free in cPanel), then log in, set SMTP in Admin > Settings, send a test email.
7. Take the first backup (Settings > Backup) and also enable the cPanel backup for the account.

**Update flow.** Upload the new release, open `/admin/update`, which runs pending files in `database/migrations/` once and records the version.

## 7. Acceptance tests

1. Installer on a clean cPanel: all checks pass, tables created, admin created, installer locked afterwards.
2. User A cannot read, edit, delete or export user B's transactions, accounts, budgets or debts by changing ids in URLs or API calls.
3. Adding an expense from the quick-add sheet takes at most 2 taps after choosing the category plus the amount, and shows an undo toast.
4. Balance, monthly totals and chart values match a manual calculation on a seeded test dataset, including transfers.
5. Budget at 80 percent, over 100 percent and forecast cases each create exactly one alert and one email; editing a transaction below the line does not duplicate alerts.
6. Import of a 2,000-row xlsx with 20 bad rows imports the valid rows, lists the invalid ones, and Undo removes the whole batch.
7. Exported xlsx opens correctly; a note starting with `=cmd` is exported as text, not a formula.
8. Five wrong passwords lock further attempts for the window; password reset tokens expire and work once.
9. Cron posts a due recurring rule once, advances `next_run`, and survives running twice in the same minute.
10. Lighthouse mobile above 90 on dashboard; layout works at 360px width; dark mode and Bangla text render correctly.

## 8. Build prompts (use with Antigravity or Claude Code, one phase at a time)

**Global rules (paste at the start of every phase)**

```text
Build a Personal Finance Manager in plain PHP 8.2 and MySQL/MariaDB that deploys on shared cPanel hosting. Rules: no Node, Redis or long-running processes on the server; ship vendor/ and pre-built assets; PDO prepared statements only; every query scoped by user_id with ownership checks; money as BIGINT minor units; CSRF on all state-changing requests; escape all output; strong password hashing; the app lives outside the web root with a thin public_html/index.php; cron via bin/cron.php. UI must feel premium: design tokens, self-hosted Plus Jakarta Sans and Hind Siliguri, light and dark mode, mobile bottom navigation, smooth quick-add sheet, skeleton loaders, empty states, accessible contrast, English and Bangla. No fake data in production. Follow the schema, rules and acceptance tests in the attached document. After each phase, run available tests and report only what you actually verified, plus anything that needs manual cPanel testing.
```

**Phase 1: foundation, installer, auth, design system**

```text
Phase 1. Create the folder structure, front controller, router, config loader, PDO wrapper, view engine with escaping helpers, i18n (en, bn), session and CSRF services, security headers, and error logging to storage/logs. Build the web installer at /install (requirement checks, DB connection test, schema.sql execution, admin creation, optional sample data, lock file). Build auth: register (toggle in settings), login with rate limiting from login_attempts, logout, password reset by email via PHPMailer, session regeneration. Build the design system as plain CSS tokens and components (buttons, inputs, cards, sheet, toast, table, badges, skeletons), the app shell (mobile bottom nav, desktop sidebar), light/dark theme, and a PWA manifest. Include schema.sql and a migrations runner. Do not build finance features yet.
```

**Phase 2: accounts, categories, transactions, quick add, dashboard**

```text
Phase 2. Build accounts, categories (with sensible default sets for Bangladesh, editable), people, and transactions (expense, income, transfer) with soft delete. Build the quick-add bottom sheet (category grid, large keypad, date and account defaults, note, optional person, undo toast, floating button and N shortcut, repeat last). Build the transactions page with search, filters (date range, type, account, category, amount range), sort, pagination, side-panel edit, bulk delete and saved filters. Build the dashboard: hero balance card, month income, expense, net, savings percent, recent activity, account balances. All JSON endpoints under /api with CSRF. Add Focus mode (hides charts, shows balance and quick add). Add PHPUnit-style tests for balance maths and IDOR checks.
```

**Phase 3: budgets, forecast, analytics, recurring, debts, goals**

```text
Phase 3. Build monthly budgets (overall and per category, custom month start day, warn percent), the forecast service (run rate plus upcoming recurring), and alert evaluation after each expense insert and in cron, creating one notification and one optional email per budget and level. Build analytics with self-hosted Chart.js: daily, weekly and monthly trend, category doughnut, income vs expense bars, month comparison, net worth over time, range picker, all aggregated in SQL. Build recurring rules with cron posting, the person-wise lend/borrow tracker with partial repayments and Open/Settled tabs plus overview chart, and savings goals with contributions. Add bin/cron.php with idempotent jobs and quiet-hours handling. Show notifications in an in-app bell.
```

**Phase 4: Excel, backup, settings, PWA, hardening, release**

```text
Phase 4. Add Excel and CSV export (OpenSpout, filtered, streaming, formula-injection safe) and an import template. Build the import wizard (upload, column mapping, preview with row errors, duplicate warning, one-transaction commit, Undo by batch, limits of 5 MB and 5,000 rows). Add JSON backup and restore, account export and account deletion, admin settings (SMTP with test email, registration toggle, app name), session list, monthly summary email, and the service worker for an offline shell. Then harden: security headers, CSP, upload checks, log rotation, rate limits, and run every acceptance test from the document. Produce a release zip layout, README with the cPanel steps (MultiPHP, extensions, DB creation, upload, installer, cron), a demo seed option clearly labelled as sample data, and docs/known-limitations.md.
```

## 9. Later (v1.1)

TOTP 2FA, shared household budgets with roles, receipt photo attachments, bKash/Nagad statement import presets, yearly report PDF, multi-currency with manual rates, WhatsApp or Telegram alert via webhook.
