<?php

declare(strict_types=1);

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function  parseJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $decode = json_decode($raw, true);
    return is_array($decode) ? $decode : [];
}

function isValidBarcode(string $code): bool
{
    return (bool) preg_match('/^BLJ-\d{6}-\d{4}$/', $code);
}

function generateBarcode(string $month, int $sequence): string
{
    return sprintf('BLJ-%s-%04d', str_replace('-', '', $month), $sequence);
}
