<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/helpers.php';
require __DIR__ . '/../src/Validator.php';
require __DIR__ . '/../src/ItemRepository.php';
require __DIR__ . '/../src/BarcodeService.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

if ($uri === '/' || $uri === '/index.html') {
    readfile(__DIR__ . '/index.html');
    exit;
}
foreach (['/app.js' => 'application/javascript', '/styles.css' => 'text/css', '/print.html' => 'text/html'] as $file => $type) {
    if ($uri === $file) {
        header("Content-Type: {$type}");
        readfile(__DIR__ . $file);
        exit;
    }
}

try {
    $db = Database::connection();
    $repo = new ItemRepository($db);
    $barcodes = new BarcodeService($db, $repo);

    if ($method === 'POST' && $uri === '/api/items') {
        $body = parseJsonBody();
        $errors = Validator::validateCreate($body);
        if ($errors) {
            jsonResponse(['message' => 'Validasi gagal', 'errors' => $errors], 422);
        }
        $period = $repo->findPeriod((int) $body['period_id']);
        if (!$period) {
            jsonResponse(['message' => 'Periode tidak ditemukan'], 404);
        }
        if ($period['status'] === 'CLOSED') {
            jsonResponse(['message' => 'Mutasi dilarang pada periode CLOSED'], 422);
        }
        jsonResponse(['data' => $repo->create($body)], 201);
    }

    if ($method === 'GET' && $uri === '/api/items') {
        $periodId = isset($_GET['period_id']) && $_GET['period_id'] !== '' ? (int) $_GET['period_id'] : null;
        $hasBarcode = !isset($_GET['has_barcode']) || $_GET['has_barcode'] === ''
            ? null
            : filter_var($_GET['has_barcode'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        jsonResponse($repo->list($periodId, $hasBarcode, (int) ($_GET['page'] ?? 1)));
    }

    if (preg_match('#^/api/items/(\d+)$#', $uri, $m)) {
        $item = $repo->find((int) $m[1]);
        if (!$item) {
            jsonResponse(['message' => 'Barang tidak ditemukan'], 404);
        }
        $period = $repo->findPeriod((int) $item['period_id']);
        if ($method === 'GET') {
            jsonResponse(['data' => $item]);
        }
        if ($period['status'] === 'CLOSED') {
            jsonResponse(['message' => 'Mutasi dilarang pada periode CLOSED'], 422);
        }
        if ($method === 'PATCH') {
            jsonResponse(['data' => $repo->update($item['id'], Validator::filterPatch(parseJsonBody()))]);
        }
        if ($method === 'DELETE') {
            if ($item['barcode'] !== null) {
                jsonResponse(['message' => 'Barang yang sudah memiliki barcode tidak boleh dihapus'], 422);
            }
            $repo->delete($item['id']);
            jsonResponse(['message' => 'Barang dihapus']);
        }
    }

    if ($method === 'POST' && preg_match('#^/api/periods/(\d+)/barcodes$#', $uri, $m)) {
        $period = $repo->findPeriod((int) $m[1]);
        if (!$period) {
            jsonResponse(['message' => 'Periode tidak ditemukan'], 404);
        }
        if ($period['status'] === 'CLOSED') {
            jsonResponse(['message' => 'Generate dilarang pada periode CLOSED'], 422);
        }
        jsonResponse($barcodes->generateForPeriod($period));
    }

    if ($method === 'GET' && preg_match('#^/api/barcodes/(.+)$#', $uri, $m)) {
        $code = urldecode($m[1]);
        if (!isValidBarcode($code)) {
            jsonResponse(['message' => 'Format barcode tidak valid. Gunakan BLJ-{YYYYMM}-{NNNN}'], 422);
        }
        $item = $repo->findByBarcode($code);
        if (!$item) {
            jsonResponse(['message' => 'Barcode tidak ditemukan'], 404);
        }
        jsonResponse(['data' => $item]);
    }

    if ($method === 'GET' && $uri === '/api/labels') {
        if (!isset($_GET['ids']) || trim((string) $_GET['ids']) === '') {
            jsonResponse(['message' => 'Parameter ids wajib diisi'], 422);
        }
        $ids = Validator::parseIds((string) $_GET['ids']);
        if (!$ids) {
            jsonResponse(['message' => 'Parameter ids tidak valid', 'invalid_ids' => [$_GET['ids']]], 422);
        }
        jsonResponse(['data' => $barcodes->labels($ids, Validator::validateCopies($_GET['copies'] ?? null))]);
    }

    jsonResponse(['message' => 'Endpoint tidak ditemukan'], 404);
} catch (PDOException $e) {
    jsonResponse(['message' => 'Database error', 'error' => $e->getMessage()], 500);
}
