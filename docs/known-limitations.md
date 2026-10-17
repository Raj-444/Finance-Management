# Known Limitations & Technical Constraints

### Personal Finance Manager (PHP 8.2 + MySQL, cPanel-Ready)

This document provides transparency into the architectural trade-offs, hosting constraints, and design limits of version 1.0 of the application.

---

### 1. Shared Hosting & Runtime Constraints

| Dimension | Constraint | Technical Rationale & Mitigation |
| :--- | :--- | :--- |
| **Server Architecture** | Shared cPanel (No daemons) | Designed without Node.js, Redis, or long-running worker processes to guarantee 100% out-of-the-box compatibility with budget shared cPanel accounts. |
| **Execution Timeout** | 30s – 60s max execution time | Shared hosts terminate PHP scripts exceeding `max_execution_time`. Long-running tasks are split into chunked batches or offloaded to the 15-minute cPanel cron runner. |
| **Memory Ceiling** | 128 MB – 256 MB per process | Excel and CSV exports and imports use OpenSpout streaming readers and writers, keeping peak memory below 15 MB even on multi-thousand row files. |
| **File Upload Limit** | 5 MB / 5,000 rows per file | Spreadsheets larger than 5,000 rows may encounter HTTP execution timeouts on budget hosting. Large migrations should be split into smaller monthly or annual files. |

---

### 2. Scheduled Cron Runner

- **Frequency:** The scheduled runner (`finance_app/bin/cron.php`) is designed to run every 15 minutes via the cPanel Cron Jobs interface:
  ```bash
  */15 * * * * /usr/local/bin/php /home/CPANELUSER/finance_app/bin/cron.php >/dev/null 2>&1
  ```
- **Execution Windows:** Due recurring bills scheduled for a given date are posted on the first cron run of that date (typically 00:00 to 00:15 local server time).
- **Idempotency & Stale Locks:** The runner utilizes a PID lock file (`storage/cron.lock`) with automatic 30-minute stale detection to prevent overlapping runs if a previous run is delayed.
- **Quiet Hours:** If the user configures quiet hours (e.g. 23:00 to 07:00), budget email alerts are suppressed during the quiet window and held until the next active window.

---

### 3. Dual Database Driver Compatibility

The application is engineered with dual database support to provide zero-configuration local desktop development alongside production cPanel MySQL:

1. **MySQL 5.7+ / MariaDB 10.3+ (Production Target):**
   - Full InnoDB storage engine with strict `utf8mb4_unicode_ci` collation.
   - Atomic database transactions with row-level locking.
   - Declarative `ON DELETE CASCADE` foreign keys for clean data integrity.
   - Big integer minor units (`BIGINT`) for monetary calculations (100 paisa = 1 BDT) preventing floating-point rounding errors.

2. **SQLite 3 (Local Development & Automated Testing):**
   - Requires `PRAGMA foreign_keys = ON;` executed immediately upon connection.
   - Database functions `NOW()`, `DATE_SUB()`, and `DATE_ADD()` are emulated in PHP via PDO SQLite driver function hooks.

---

### 4. PWA & Offline Behavior

- **Offline Shell:** The Service Worker (`public_html/sw.js`) caches the core app shell, CSS tokens, Alpine.js, and self-hosted Chart.js.
- **Read-Only Offline:** When offline, previously cached views and layouts can be reviewed; state-changing transactions require network connectivity to write to the MySQL/SQLite database with atomic transaction integrity.
- **CDN Independence:** No external third-party CDN scripts or fonts are linked. Plus Jakarta Sans and Hind Siliguri are self-hosted with `font-display: swap`.

---

### 5. Formula Injection Defense (DDE)

- When generating `.csv` or `.xlsx` exports, spreadsheet cells beginning with `=`, `+`, `-`, `@`, `\t`, or `\r` are automatically prefixed with a single quotation mark (`'`).
- This prevents Dynamic Data Exchange (DDE) command execution when opening exported files in desktop spreadsheet tools (e.g., Microsoft Excel, LibreOffice Calc).

---

### 6. Scope Exclusions (Planned for v1.1)

The following features were intentionally excluded from version 1.0 to prioritize core speed, privacy, and zero server maintenance:
1. **Direct Bank Open Banking Sync:** Automated scraping or Open Banking API connectivity requires bank approval tokens, webhooks, and daemon processes incompatible with shared hosting.
2. **Camera Receipt OCR:** Optical character recognition requires heavy machine learning binaries (Tesseract / Python) that are typically blocked on shared hosts.
3. **Multi-User Shared Household Roles:** Version 1.0 enforces strict per-user data isolation. Multi-member shared household ledgers with permission roles are scheduled for v1.1.
4. **Live Currency Exchange API:** All accounts in v1.0 operate within the user's selected primary currency (default BDT). Multi-currency conversion with real-time rate feeds is planned for v1.1.
