<?php
declare(strict_types=1);

namespace App\Core;

class MigrationRunner
{
    public static function run(): array
    {
        $pdo = Database::getInstance();

        // Ensure schema_migrations table exists
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(100) PRIMARY KEY,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Fetch applied migrations
        $stmt = $pdo->query("SELECT version FROM schema_migrations");
        $applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $migrationsDir = dirname(__DIR__, 2) . '/database/migrations';
        if (!is_dir($migrationsDir)) {
            return ['applied' => [], 'message' => 'No migrations directory found.'];
        }

        $files = glob($migrationsDir . '/*.sql');
        sort($files);

        $newlyApplied = [];

        foreach ($files as $file) {
            $version = basename($file);
            if (in_array($version, $applied, true)) {
                continue;
            }

            Database::beginTransaction();
            try {
                Database::execSqlFile($file);
                Database::query("INSERT INTO schema_migrations (version) VALUES (?)", [$version]);
                Database::commit();
                $newlyApplied[] = $version;
                Logger::info("Applied database migration: {$version}");
            } catch (\Throwable $e) {
                Database::rollBack();
                Logger::exception($e, "Failed to apply migration {$version}");
                throw new \RuntimeException("Migration {$version} failed: " . $e->getMessage(), 0, $e);
            }
        }

        return [
            'applied' => $newlyApplied,
            'message' => count($newlyApplied) > 0 
                ? 'Successfully applied ' . count($newlyApplied) . ' migration(s).' 
                : 'Database is up to date.',
        ];
    }
}
