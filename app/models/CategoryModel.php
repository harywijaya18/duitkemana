<?php

class CategoryModel extends Model
{
    public function createDefaultForUser(int $userId): void
    {
        $defaults = [
            ['Food', 'fa-utensils'],
            ['Transport', 'fa-motorcycle'],
            ['Shopping', 'fa-bag-shopping'],
            ['Bills', 'fa-file-invoice-dollar'],
            ['Entertainment', 'fa-film'],
            ['Other', 'fa-wallet'],
        ];

        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO categories (user_id, name, icon) VALUES (:user_id, :name, :icon)'
        );

        foreach ($defaults as [$name, $icon]) {
            $stmt->execute([
                ':user_id' => $userId,
                ':name' => $name,
                ':icon' => $icon,
            ]);
        }
    }

    public function allByUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE user_id = :user_id ORDER BY name ASC');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $name, string $icon): bool
    {
        $stmt = $this->db->prepare('INSERT INTO categories (user_id, name, icon) VALUES (:user_id, :name, :icon)');
        return $stmt->execute([
            ':user_id' => $userId,
            ':name' => $name,
            ':icon' => $icon,
        ]);
    }

    public function update(int $id, int $userId, string $name, string $icon): bool
    {
        $stmt = $this->db->prepare('UPDATE categories SET name = :name, icon = :icon WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':name' => $name,
            ':icon' => $icon,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id AND user_id = :user_id');
        return $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
        ]);
    }
}
