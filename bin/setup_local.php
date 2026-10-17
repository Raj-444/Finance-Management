<?php
declare(strict_types=1);

/**
 * Localhost Automated Setup & Seeder Script
 * Sets up SQLite database with complete schema, admin account, preset accounts,
 * categories, and sample transactions for immediate testing.
 */

define('ROOT_DIR', dirname(__DIR__));
require_once __DIR__ . '/../finance_app/app/Core/Autoloader.php';
\App\Core\Autoloader::register();
require_once __DIR__ . '/../finance_app/app/Helpers/helpers.php';

use App\Core\Config;
use App\Core\Database;

echo "==> Setting up local environment for Personal Finance Manager...\n";

$dbPath = dirname(__DIR__) . '/finance_app/storage/database.sqlite';
$lockFile = dirname(__DIR__) . '/finance_app/storage/installed.lock';
$configFile = dirname(__DIR__) . '/finance_app/config/config.php';

// Remove old DB and lock if re-running
@unlink($dbPath);
@unlink($lockFile);

$pdo = new PDO("sqlite:" . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "==> Creating SQLite database tables...\n";

// Execute complete SQLite schema
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'user',
  currency TEXT NOT NULL DEFAULT 'BDT',
  locale TEXT NOT NULL DEFAULT 'en',
  timezone TEXT NOT NULL DEFAULT 'Asia/Dhaka',
  theme TEXT NOT NULL DEFAULT 'system',
  month_start_day INTEGER NOT NULL DEFAULT 1,
  week_start INTEGER NOT NULL DEFAULT 6,
  number_format TEXT NOT NULL DEFAULT 'indian',
  email_alerts INTEGER NOT NULL DEFAULT 1,
  focus_mode INTEGER NOT NULL DEFAULT 0,
  quiet_from TEXT NULL,
  quiet_to TEXT NULL,
  totp_secret TEXT NULL,
  is_active INTEGER NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS login_attempts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL,
  ip BLOB NOT NULL,
  success INTEGER NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  type TEXT NOT NULL DEFAULT 'cash',
  opening_balance INTEGER NOT NULL DEFAULT 0,
  color TEXT NOT NULL DEFAULT '#5B5BD6',
  icon TEXT NULL,
  is_archived INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  parent_id INTEGER NULL,
  type TEXT NOT NULL,
  name TEXT NOT NULL,
  icon TEXT NULL,
  color TEXT NOT NULL DEFAULT '#64748B',
  is_archived INTEGER NOT NULL DEFAULT 0,
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS people (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  phone TEXT NULL,
  note TEXT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS import_batches (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  filename TEXT NOT NULL,
  rows_total INTEGER NOT NULL DEFAULT 0,
  rows_imported INTEGER NOT NULL DEFAULT 0,
  rows_skipped INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'committed',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recurring_rules (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  account_id INTEGER NOT NULL,
  category_id INTEGER NULL,
  amount INTEGER NOT NULL,
  note TEXT NULL,
  frequency TEXT NOT NULL,
  interval_n INTEGER NOT NULL DEFAULT 1,
  next_run DATE NOT NULL,
  end_date DATE NULL,
  auto_post INTEGER NOT NULL DEFAULT 1,
  is_active INTEGER NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  account_id INTEGER NOT NULL,
  to_account_id INTEGER NULL,
  category_id INTEGER NULL,
  person_id INTEGER NULL,
  amount INTEGER NOT NULL,
  tx_date DATE NOT NULL,
  note TEXT NULL,
  tags TEXT NULL,
  recurring_id INTEGER NULL,
  import_batch_id INTEGER NULL,
  dedupe_hash TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (account_id) REFERENCES accounts(id),
  FOREIGN KEY (to_account_id) REFERENCES accounts(id),
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE SET NULL,
  FOREIGN KEY (recurring_id) REFERENCES recurring_rules(id) ON DELETE SET NULL,
  FOREIGN KEY (import_batch_id) REFERENCES import_batches(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS budgets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  category_id INTEGER NULL,
  period_start DATE NOT NULL,
  amount INTEGER NOT NULL,
  warn_percent INTEGER NOT NULL DEFAULT 80,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  budget_id INTEGER NULL,
  title TEXT NOT NULL,
  body TEXT NULL,
  link TEXT NULL,
  emailed_at DATETIME NULL,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS debts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  person_id INTEGER NOT NULL,
  direction TEXT NOT NULL,
  principal INTEGER NOT NULL,
  opened_on DATE NOT NULL,
  due_on DATE NULL,
  note TEXT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS debt_payments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  debt_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  amount INTEGER NOT NULL,
  paid_on DATE NOT NULL,
  note TEXT NULL,
  FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS savings_goals (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  target_amount INTEGER NOT NULL,
  target_date DATE NULL,
  color TEXT NOT NULL DEFAULT '#12B76A',
  icon TEXT NULL,
  status TEXT NOT NULL DEFAULT 'active',
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS goal_contributions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  goal_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  amount INTEGER NOT NULL,
  contributed_on DATE NOT NULL,
  note TEXT NULL,
  FOREIGN KEY (goal_id) REFERENCES savings_goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS app_settings (
  k TEXT PRIMARY KEY,
  v TEXT NULL
);

CREATE TABLE IF NOT EXISTS activity_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NULL,
  action TEXT NOT NULL,
  entity TEXT NULL,
  entity_id INTEGER NULL,
  ip BLOB NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
");

echo "==> Creating Administrator Account...\n";
$passwordHash = password_hash('password123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
  INSERT INTO users (name, email, password_hash, role, currency, locale, timezone, theme, month_start_day, week_start, number_format)
  VALUES (?, ?, ?, 'admin', 'BDT', 'en', 'Asia/Dhaka', 'system', 1, 6, 'indian')
");
$stmt->execute(['Raj', 'admin@raj.com', $passwordHash]);
$userId = (int) $pdo->lastInsertId();

echo "==> Seeding Bangladesh Accounts...\n";
$accounts = [
    ['Cash Wallet', 'cash', 500000, '#12B76A', 'wallet'],            // ৳ 5,000.00
    ['bKash Account', 'mobile_wallet', 1500000, '#E2136E', 'smartphone'], // ৳ 15,000.00
    ['Nagad Wallet', 'mobile_wallet', 800000, '#F7931E', 'smartphone'],   // ৳ 8,000.00
    ['City Bank Account', 'bank', 4500000, '#5B5BD6', 'credit-card'],     // ৳ 45,000.00
];

$accIds = [];
$accStmt = $pdo->prepare("INSERT INTO accounts (user_id, name, type, opening_balance, color, icon) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($accounts as $a) {
    $accStmt->execute([$userId, $a[0], $a[1], $a[2], $a[3], $a[4]]);
    $accIds[$a[0]] = (int) $pdo->lastInsertId();
}

echo "==> Seeding Expense and Income Categories...\n";
$categories = [
    // Expenses
    ['Food & Dining', 'expense', '#F04438', 'utensils'],
    ['Groceries', 'expense', '#F79009', 'shopping-cart'],
    ['Rent & Housing', 'expense', '#7F56D9', 'home'],
    ['Utilities & Bills', 'expense', '#2E90FA', 'bolt'],
    ['Transportation & Fuel', 'expense', '#06AED4', 'car'],
    ['Shopping & Apparel', 'expense', '#EE46BC', 'shopping-bag'],
    ['Health & Medical', 'expense', '#12B76A', 'activity'],
    ['Entertainment', 'expense', '#FB6514', 'film'],
    // Incomes
    ['Monthly Salary', 'income', '#12B76A', 'briefcase'],
    ['Freelance Projects', 'income', '#32D583', 'code'],
    ['Investment Return', 'income', '#0BA5EC', 'trending-up'],
    ['Gifts & Others', 'income', '#9E77ED', 'gift'],
];

$catIds = [];
$catStmt = $pdo->prepare("INSERT INTO categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)");
foreach ($categories as $c) {
    $catStmt->execute([$userId, $c[0], $c[1], $c[2], $c[3]]);
    $catIds[$c[0]] = (int) $pdo->lastInsertId();
}

echo "==> Seeding Sample People...\n";
$people = [
    ['Tanvir Ahmed', '01711000001', 'Friend from university'],
    ['Sabbir Hossain', '01811000002', 'Colleague'],
    ['Rahim Mia', '01911000003', 'Office canteen manager'],
];
$personIds = [];
$personStmt = $pdo->prepare("INSERT INTO people (user_id, name, phone, note) VALUES (?, ?, ?, ?)");
foreach ($people as $p) {
    $personStmt->execute([$userId, $p[0], $p[1], $p[2]]);
    $personIds[$p[0]] = (int) $pdo->lastInsertId();
}

echo "==> Seeding Sample Transactions for This Month...\n";
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));
$threeDaysAgo = date('Y-m-d', strtotime('-3 days'));
$fiveDaysAgo = date('Y-m-d', strtotime('-5 days'));
$tenDaysAgo = date('Y-m-d', strtotime('-10 days'));

$transactions = [
    // Income
    ['income', $accIds['City Bank Account'], null, $catIds['Monthly Salary'], null, 6500000, $tenDaysAgo, 'October Salary credit', 'salary'],
    ['income', $accIds['bKash Account'], null, $catIds['Freelance Projects'], null, 1200000, $fiveDaysAgo, 'Web design project payment', 'freelance'],
    
    // Expenses
    ['expense', $accIds['City Bank Account'], null, $catIds['Rent & Housing'], null, 2000000, $fiveDaysAgo, 'Apartment monthly rent', 'rent'],
    ['expense', $accIds['bKash Account'], null, $catIds['Utilities & Bills'], null, 350000, $threeDaysAgo, 'Electricity and Internet bills', 'bills'],
    ['expense', $accIds['Cash Wallet'], null, $catIds['Groceries'], null, 245000, $yesterday, 'Weekly kitchen bazaar at Shwapno', 'groceries'],
    ['expense', $accIds['Nagad Wallet'], null, $catIds['Food & Dining'], null, 85000, $today, 'Dinner with friends at Takeout', 'hangout'],
    ['expense', $accIds['Cash Wallet'], null, $catIds['Transportation & Fuel'], null, 42000, $today, 'Uber ride to office & CNG', 'commute'],

    // Transfer
    ['transfer', $accIds['City Bank Account'], $accIds['bKash Account'], null, null, 500000, $yesterday, 'Bank to bKash add money for shopping', 'transfer'],
];

$txStmt = $pdo->prepare("
  INSERT INTO transactions (user_id, type, account_id, to_account_id, category_id, person_id, amount, tx_date, note, tags)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($transactions as $t) {
    $txStmt->execute([
        $userId, $t[0], $t[1], $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $t[8]
    ]);
}

echo "==> Writing config and installed.lock...\n";
$configData = [
    'app' => [
        'name' => 'Personal Finance Manager',
        'env' => 'development',
        'debug' => true,
        'url' => 'http://localhost:8000',
        'timezone' => 'Asia/Dhaka',
        'locale' => 'en',
        'currency' => 'BDT',
    ],
    'db' => [
        'driver' => 'sqlite',
        'database' => $dbPath,
    ],
    'session' => [
        'lifetime' => 120,
        'path' => dirname(__DIR__) . '/finance_app/storage/sessions',
        'cookie' => 'pfm_session',
    ],
    'mail' => [
        'driver' => 'smtp',
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => '',
        'from_name' => 'Personal Finance Manager',
    ],
];

file_put_contents($configFile, "<?php\nreturn " . var_export($configData, true) . ";\n");
file_put_contents($lockFile, "Installed on " . date('c') . "\n");

echo "\n==> Setup complete!\n";
echo "========================================================\n";
echo "URL:      http://localhost:8000\n";
echo "Email:    admin@raj.com\n";
echo "Password: password123\n";
echo "========================================================\n";
