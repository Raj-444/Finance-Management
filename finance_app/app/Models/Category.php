<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    public static function getAllForUser(int $userId, ?string $type = null, bool $includeArchived = false): array
    {
        $params = [$userId];
        $where = "WHERE user_id = ?";

        if (!$includeArchived) {
            $where .= " AND is_archived = 0";
        }

        if ($type !== null) {
            $where .= " AND type = ?";
            $params[] = $type;
        }

        $sql = "SELECT * FROM categories {$where} ORDER BY type ASC, sort_order ASC, name ASC";
        return Database::fetchAll($sql, $params);
    }

    public static function findById(int $id, int $userId): ?array
    {
        return Database::fetch("SELECT * FROM categories WHERE id = ? AND user_id = ? LIMIT 1", [$id, $userId]);
    }

    public static function create(int $userId, array $data): int
    {
        $sql = "INSERT INTO categories (user_id, parent_id, type, name, icon, color, sort_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        Database::query($sql, [
            $userId,
            !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            $data['type'],
            trim($data['name']),
            $data['icon'] ?? 'tag',
            $data['color'] ?? '#64748B',
            (int)($data['sort_order'] ?? 0),
        ]);

        return (int) Database::lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $sql = "UPDATE categories SET name = ?, icon = ?, color = ? WHERE id = ? AND user_id = ?";
        return Database::execute($sql, [
            trim($data['name']),
            $data['icon'] ?? 'tag',
            $data['color'] ?? '#64748B',
            $id,
            $userId,
        ]) > 0;
    }

    public static function archive(int $id, int $userId): bool
    {
        return Database::execute("UPDATE categories SET is_archived = 1 WHERE id = ? AND user_id = ?", [$id, $userId]) > 0;
    }
}
