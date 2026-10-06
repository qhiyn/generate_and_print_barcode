<?php

declare(strict_types=1);

class BarcodeService
{
    public function __construct(private PDO $db, private ItemRepository $items) {}

    public function generateForPeriod(array $period): array
    {
        $pending = $this->items->withoutBarcode((int) $period['id']);
        if (!$pending) {
            return ['message' => 'Semua barang di periode ini sudah memiliki barcode', 'data' => []];
        }
        $seq = $this->items->maxSequence((int) $period['id']);
        $result = [];
        $this->db->beginTransaction();
        try {
            foreach ($pending as $item) {
                $seq++;
                $code = generateBarcode($period['month'], $seq);
                $this->items->setBarcode((int) $item['id'], $code);
                $result[] = [
                    'id' => (int) $item['id'],
                    'name' => $item['name'],
                    'barcode' => $code
                ];
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return ['message' => count($result) . ' code berhasil di generate', 'data' => $result];
    }

    public function labels(array $ids, int $copies): array
    {
        $rows = $this->items->findByIds($ids);
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['id']] = $row;
        }
        $invalid = [];
        foreach ($ids as $id) {
            if (!isset($byId[$id]) || $byId[$id]['barcode'] === null) {
                $invalid[] = $id;
            }
        }
        if ($invalid) {
            jsonResponse(['message' => 'Terdapat ID tidak valida atau belum memiliki barcode', 'invalid_ids' => $invalid], 422);
        }
        $labels = [];
        foreach ($ids as $id) {
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = ['barcode' => $byId[$id]['barcode'], 'name' => $byId[$id]['name']];
            }
        }
        return $labels;
    }
}
