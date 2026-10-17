<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $instance = null;
    private static array $customConfig = [];

    public static function setConfig(array $config): void
    {
        self::$customConfig = $config;
        self::$instance = null; // Reset connection if config changes
    }

    public static function getConnection(): PDO
    {
        return self::getInstance();
    }

    public static function getInstance(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $dbConfig = !empty(self::$customConfig) ? self::$customConfig : Config::get('db', []);
        $driver = $dbConfig['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dbPath = $dbConfig['database'] ?? (dirname(__DIR__, 2) . '/storage/database.sqlite');
                $dsn = "sqlite:" . $dbPath;
                $pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON;');
                
                // Emulate MySQL functions in SQLite for seamless testing
                $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
                $pdo->sqliteCreateFunction('DATE_SUB', function($date, $expr) {
                    return date('Y-m-d H:i:s', strtotime('-15 minutes', strtotime($date ?: 'now')));
                });
                $pdo->sqliteCreateFunction('DATE_ADD', function($date, $expr) {
                    return date('Y-m-d H:i:s', strtotime('+60 minutes', strtotime($date ?: 'now')));
                });
            } else {
                $host = $dbConfig['host'] ?? 'localhost';
                $port = $dbConfig['port'] ?? 3306;
                $database = $dbConfig['database'] ?? '';
                $username = $dbConfig['username'] ?? '';
                $password = $dbConfig['password'] ?? '';
                $charset = $dbConfig['charset'] ?? 'utf8mb4';

                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
                $pdo = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
                ]);
            }

            self::$instance = $pdo;
            return self::$instance;
        } catch (PDOException $e) {
            Logger::error("Database connection failure: " . $e->getMessage());
            throw new \RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(?string $name = null): string
    {
        return self::getInstance()->lastInsertId($name);
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }

    /**
     * Test a given DB configuration (used by installer)
     */
    public static function testConnection(array $dbConfig): array
    {
        $driver = $dbConfig['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dbPath = $dbConfig['database'] ?? ':memory:';
                $pdo = new PDO("sqlite:" . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                return ['success' => true, 'message' => 'Connected to SQLite database successfully.'];
            }

            $host = $dbConfig['host'] ?? 'localhost';
            $port = $dbConfig['port'] ?? 3306;
            $database = $dbConfig['database'] ?? '';
            $username = $dbConfig['username'] ?? '';
            $password = $dbConfig['password'] ?? '';
            $charset = $dbConfig['charset'] ?? 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            return ['success' => true, 'message' => 'Connected to MySQL database successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Execute SQL statements from a file (handles comments and multiple statements)
     */
    public static function execSqlFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("SQL file does not exist: {$filePath}");
        }

        $sql = file_get_contents($filePath);
        $pdo = self::getInstance();

        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Split by semicolon (ignoring semicolons in quotes)
        $statements = preg_split('/;\s*[\r\n]+/', trim($sql));

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                $pdo->exec($stmt);
            }
        }
    }
}
