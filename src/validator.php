<?php

declare(strict_types=1);

class Validator
{
    private const CATEGORIES = ['ATK', 'Pantry', 'Kebersihan', 'Lainya'];
    private const PATCHABLE = ['name', 'category', 'unit', 'price'];

    public static function categories(): array
    {
        return self::CATEGORIES;
    }

    public static function validateCreate(array $body): array
    {
        $errors = [];
        foreach (['period_id', 'name', 'category', 'unit', 'quantity', 'price'] as $field) {
            if (!isset($body[$field]) || $body[$field] === '' || $body[$field] === null) {
                $errors[$field] = 'Field required';
            }
        }
        if (isset($body['category']) && !in_array($body['category'], self::CATEGORIES, true)) {
            $errors['category'] = 'Must be one of: ' . implode(', ', self::CATEGORIES);
        }
        foreach (['quantity', 'price'] as $num) {
            if (isset($body[$num]) && !is_numeric($body[$num])) {
                $errors[$num] = 'Must be numeric';
            }
        }
        return $errors;
    }

    public static function filterPatch(array $body): array
    {
        $out = [];
        foreach (self::PATCHABLE as $field) {
            if (array_key_exists($field, $body)) {
                $out[$field] = $body[$field];
            }
        }
        if (isset($out['category']) && !in_array($out['category'], self::CATEGORIES, true)) {
            jsonResponse(['message' => 'Validasi gagal', 'errors' => ['category' => 'Kategori tidak valid']], 422);
        }
        return $out;
    }

    public static function parseIds(string $raw): array
    {
        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }
        return array_values(array_unique($ids));
    }

    public static function validateCopies(mixed $copies): int
    {
        $n = $copies === null || $copies === '' ? 1 : (int) $copies;
        if ($n < 1 || $n > 50) {
            jsonResponse(['message' => 'Copies harus di kisaran 1 hingga 50'], 422);
        }
        return $n;
    }
}
