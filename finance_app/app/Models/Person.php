<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Person
{
    public static function getAllForUser(int $userId): array
    {
        return Database::fetchAll("SELECT * FROM people WHERE user_id = ? ORDER BY name ASC", [$userId]);
    }

    public static function findById(int $id, int $userId): ?array
    {
        return Database::fetch("SELECT * FROM people WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "INSERT INTO people (user_id, name, phone, note) VALUES (?, ?, ?, ?)";
        Database::query($sql, [
            $userId,
            trim($data['name']),
            trim($data['phone'] ?? ''),
            trim($data['note'] ?? ''),
        ]);

        return (int) Database::lastInsertId();
    }

    public static function findOrCreateByName(int $userId, string $name): int
    {
        $name = trim($name);
        $existing = Database::fetch("SELECT id FROM people WHERE user_id = ? AND name = ? LIMIT 1", [$userId, $name]);
        if ($existing) {
            return (int) $existing['id'];
        }

        return self::create($userId, ['name' => $name]);
    }
}
