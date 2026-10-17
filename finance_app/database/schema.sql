-- Personal Finance Manager Database Schema
-- Compatible with MySQL 5.7+ and MariaDB 10.3+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS app_settings;
DROP TABLE IF EXISTS goal_contributions;
DROP TABLE IF EXISTS savings_goals;
DROP TABLE IF EXISTS debt_payments;
DROP TABLE IF EXISTS debts;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS recurring_rules;
DROP TABLE IF EXISTS import_batches;
DROP TABLE IF EXISTS people;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS accounts;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS users;

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
  quiet_from TIME NULL,
  quiet_to TIME NULL,
  totp_secret VARCHAR(64) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip VARBINARY(16) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_time (email, attempted_at),
  KEY idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  KEY idx_token (token_hash),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(80) NOT NULL,
  type ENUM('cash','bank','mobile_wallet','card','savings','other') NOT NULL DEFAULT 'cash',
  opening_balance BIGINT NOT NULL DEFAULT 0,
  color CHAR(7) NOT NULL DEFAULT '#5B5BD6',
  icon VARCHAR(40) NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_name (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NULL,
  type ENUM('expense','income') NOT NULL,
  name VARCHAR(80) NOT NULL,
  icon VARCHAR(40) NULL,
  color CHAR(7) NOT NULL DEFAULT '#64748B',
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  sort_order SMALLINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_cat (user_id, type, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE people (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(30) NULL,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_person (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE import_batches (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  filename VARCHAR(190) NOT NULL,
  rows_total INT UNSIGNED NOT NULL DEFAULT 0,
  rows_imported INT UNSIGNED NOT NULL DEFAULT 0,
  rows_skipped INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('committed','undone') NOT NULL DEFAULT 'committed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE recurring_rules (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('expense','income') NOT NULL,
  account_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NULL,
  amount BIGINT UNSIGNED NOT NULL,
  note VARCHAR(255) NULL,
  frequency ENUM('daily','weekly','monthly','yearly') NOT NULL,
  interval_n TINYINT UNSIGNED NOT NULL DEFAULT 1,
  next_run DATE NOT NULL,
  end_date DATE NULL,
  auto_post TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
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
  category_id INT UNSIGNED NULL,
  person_id INT UNSIGNED NULL,
  amount BIGINT UNSIGNED NOT NULL,
  tx_date DATE NOT NULL,
  note VARCHAR(255) NULL,
  tags VARCHAR(190) NULL,
  recurring_id INT UNSIGNED NULL,
  import_batch_id INT UNSIGNED NULL,
  dedupe_hash CHAR(40) NULL,
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
  category_id INT UNSIGNED NULL,
  period_start DATE NOT NULL,
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
  title VARCHAR(150) NOT NULL,
  body VARCHAR(500) NULL,
  link VARCHAR(190) NULL,
  emailed_at DATETIME NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_alert_once (budget_id, type),
  KEY idx_user_unread (user_id, read_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE debts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  person_id INT UNSIGNED NOT NULL,
  direction ENUM('lent','borrowed') NOT NULL,
  principal BIGINT UNSIGNED NOT NULL,
  opened_on DATE NOT NULL,
  due_on DATE NULL,
  note VARCHAR(255) NULL,
  status ENUM('open','settled') NOT NULL DEFAULT 'open',
  KEY idx_user_status (user_id, status),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE debt_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  debt_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  paid_on DATE NOT NULL,
  note VARCHAR(255) NULL,
  FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE savings_goals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  target_amount BIGINT UNSIGNED NOT NULL,
  target_date DATE NULL,
  color CHAR(7) NOT NULL DEFAULT '#12B76A',
  icon VARCHAR(40) NULL,
  status ENUM('active','achieved','archived') NOT NULL DEFAULT 'active',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goal_contributions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  amount BIGINT NOT NULL,
  contributed_on DATE NOT NULL,
  note VARCHAR(255) NULL,
  FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE app_settings (
  k VARCHAR(60) PRIMARY KEY,
  v TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  entity VARCHAR(40) NULL,
  entity_id BIGINT UNSIGNED NULL,
  ip VARBINARY(16) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_time (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
