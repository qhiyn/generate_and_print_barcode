<?php

declare(strict_types=1);

class ItemRepository
{
    public function __construct(private PDO $db) {}

    public function findPeriod(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM periods WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO items (period_id, barcode, name, category, unit, quantity, stock, price) VALUES (?, NULL, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['period_id'],
            $data['name'],
            $data['category'],
            $data['unit'],
            $data['quantity'],
            $data['quantity'],
            $data['price']
        ]);
        return $this->find((int) $this->db->lastInsertId());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, p.name AS period_name, p.month AS period_month, p.status AS period_status FROM items i JOIN periods p ON p.id = i.period_id WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function list(?int $periodID, ?bool $hasBarcode, int $page, int $perPage = 10): array
    {
        $where = [];
        $params = [];
        if ($periodID !== null) {
            $where[] = 'i.period_id = ?';
            $params[] = $periodID;
        }
        if ($hasBarcode === true) {
            $where[] = 'i.barcode IS NOT NULL';
        } elseif ($hasBarcode === false) {
            $where[] = 'i.barcode IS NULL';
        }
        $cond = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $count = $this->db->prepare("SELECT COUNT(*) AS total FROM items i {$cond}");
        $count->execute($params);
        $total = (int) $count->fetch()['total'];

        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT i.*, p.name AS period_name FROM items i JOIN periods p ON p.id = i.period_id {$cond} ORDER BY i.id ASC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return [
            'data' => $stmt->fetchAll(),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'total_pages' => $totalPages],
        ];
    }

    public function update(int $id, array $fields): array
    {
        $sets = [];
        $params = [];
        foreach ($fields as $key => $value) {
            $sets[] = "{$key} = ?";
            $params[] = $value;
        }
        if (!$sets) {
            return $this->find($id);
        }
        $params[] = $id;
        $stmt = $this->db->prepare('UPDATE items SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);
        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$id]);
    }
    public function withoutBarcode(int $periodId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM items WHERE period_id = ? AND barcode IS NULL ORDER BY id ASC');
        $stmt->execute([$periodId]);
        return $stmt->fetchAll();
    }

    public function maxSequence(int $periodId): int
    {
        $stmt = $this->db->prepare(
            "SELECT MAX(CAST(SUBSTRING(barcode, 12, 4) AS UNSIGNED)) AS max_seq
             FROM items WHERE period_id = ? AND barcode IS NOT NULL"
        );
        $stmt->execute([$periodId]);
        return (int) ($stmt->fetch()['max_seq'] ?? 0);
    }

    public function setBarcode(int $id, string $barcode): void
    {
        $stmt = $this->db->prepare('UPDATE items SET barcode = ? WHERE id = ? AND barcode IS NULL');
        $stmt->execute([$barcode, $id]);
    }

    public function findByBarcode(string $code): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, p.name AS period_name, p.month AS period_month
             FROM items i JOIN periods p ON p.id = i.period_id WHERE i.barcode = ?'
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM items WHERE id IN ({$placeholders}) ORDER BY id ASC");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }
}
