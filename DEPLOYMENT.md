# Personal Finance Manager — cPanel Deployment Guide

A lightweight, self-hosted, offline-capable Personal Finance application built in PHP 8.2 & MySQL, designed specifically for shared hosting (cPanel) environments with zero Node.js/Redis dependencies.

---

## 1. Server Requirements

Ensure your cPanel hosting account satisfies the following requirements:
* **PHP**: Version 8.2 or higher (Selectable via cPanel **MultiPHP Manager**).
* **Database**: MySQL 5.7+ or MariaDB 10.3+ (InnoDB engine, `utf8mb4_unicode_ci`).
* **PHP Extensions**:
  - `pdo` and `pdo_mysql`
  - `mbstring`
  - `json`
  - `curl`
  - `fileinfo`
  - `openssl`
  - `zlib`
  - `zip` (for exports/backups)
* **PHP Settings**:
  - `memory_limit`: 128M (or higher)
  - `upload_max_filesize`: 10M (or higher)
  - `post_max_size`: 12M (or higher)

---

## 2. Directory Architecture

The application uses a secure two-tier directory layout:

```
/home/username/
├── finance_app/          <-- Application backend (Protected ABOVE web root)
│   ├── app/
│   ├── bin/
│   ├── config/
│   ├── database/
│   ├── storage/
│   └── vendor/
└── public_html/          <-- Web Root (Publicly accessible)
    ├── index.php
    ├── .htaccess
    ├── manifest.webmanifest
    ├── sw.js
    └── assets/
```

> **Security Advantage:** Sensitive files (`config.php`, `.env`, database schemas, session files, logs) reside above `public_html/` and cannot be accessed directly via web browsers.

---

## 3. Step-by-Step Installation

### Step A: Provision MySQL Database
1. Log into your cPanel dashboard.
2. Under **Databases**, open **MySQL Database Wizard**.
3. Create a new database (e.g., `username_finance`).
4. Create a database user (e.g., `username_dbuser`) with a strong password.
5. Assign the user to the database with **ALL PRIVILEGES**. Note down the database name, username, and password.

### Step B: Upload Application Files
1. In cPanel, navigate to **File Manager**.
2. Go to your home directory (`/home/username/`).
3. Upload `dist/personal_finance_manager_cpanel.zip` directly into `/home/username/`.
4. Right-click the `.zip` file and select **Extract**.
5. Verify that:
   - `finance_app/` is extracted directly in `/home/username/finance_app/`.
   - The contents of `public_html/` are placed inside your domain's web root (`/home/username/public_html/`).

### Step C: Verify File Permissions
In File Manager, verify permissions:
* `finance_app/storage/` and its subdirectories (`logs/`, `sessions/`, `cache/`, `backups/`, `tmp/`): **0755** (Writable by PHP).
* `finance_app/config/`: **0755** (Writable by installer).
* Regular PHP files: **0644**.

### Step D: Run the Web Installer Wizard
1. Open your browser and visit:
   ```
   https://yourdomain.com/install
   ```
2. The 4-step setup wizard will guide you:
   - **Step 1 — System Checks**: Validates PHP version, extensions, and folder write permissions.
   - **Step 2 — Database Connection**: Enter your MySQL host (`localhost`), database name, username, and password. Click **Test Connection** to confirm connectivity.
   - **Step 3 — Administrator Account**: Create your primary admin email, password, and choose your primary currency (`BDT`, `USD`, `EUR`, `INR`, etc.). Option to pre-load preset accounts (Cash, bKash, Nagad, Bank).
   - **Step 4 — Finalize**: The installer creates all 16 tables, writes `finance_app/config/config.php`, and places `installed.lock`.

---

## 4. Setting Up Scheduled Cron Jobs

The application requires a background cron job to post scheduled recurring rules, evaluate real-time budget forecasts, dispatch email warnings, and perform log maintenance.

1. In cPanel, open **Cron Jobs**.
2. Under **Add New Cron Job**, select **Every 15 minutes** (`*/15 * * * *`).
3. In the **Command** field, enter:
   ```bash
   /usr/local/bin/php /home/username/finance_app/bin/cron.php >/dev/null 2>&1
   ```
   *(Replace `/usr/local/bin/php` with your cPanel PHP CLI path and `username` with your cPanel username)*.
4. Click **Add New Cron Job**.

---

## 5. Security & Post-Installation Checklist

- [ ] Verify that `/install` redirects away and is locked.
- [ ] Log in with your admin credentials at `https://yourdomain.com/login`.
- [ ] Visit **Settings > Email (SMTP)** and configure your SMTP credentials. Click **Send Test Email** to verify email delivery.
- [ ] Visit **Admin Panel** (`/admin`) to confirm all PHP extensions show green checkmarks and the cPanel cron monitor displays recent executions.
- [ ] Optional: If you do not want public users registering, disable **Public Registration** in **Settings > Email & System**.

---

## 6. Offline PWA (Progressive Web App)

The application supports progressive web app installation:
* On Android/Chrome: Tap **Install App** or the browser prompt to add Personal Finance Manager to your home screen.
* On iOS/Safari: Tap **Share** &rarr; **Add to Home Screen**.
* The self-hosted Service Worker caches core styles and scripts, allowing rapid glance views even during intermittent mobile connectivity.
