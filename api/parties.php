<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = read_json_body();
        $name = trim((string) ($body['name'] ?? ''));
        $mobile = trim((string) ($body['mobile'] ?? ''));
        $type = strtolower(trim((string) ($body['type'] ?? 'customer')));
        $address = trim((string) ($body['address'] ?? ''));

        if ($name === '') {
            json_response(['error' => 'Name required'], 400);
        }
        if (!in_array($type, party_types(), true)) {
            $type = 'customer';
        }

        $id = find_or_create_party($pdo, $type, $name, $mobile, $address);
        json_response(['ok' => true, 'id' => $id, 'name' => $name, 'type' => $type, 'mobile' => $mobile]);
    }

    $type = strtolower(trim((string) ($_GET['type'] ?? '')));
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($type !== '' && !in_array($type, party_types(), true)) {
        json_response(['error' => 'Invalid type'], 422);
    }

    json_response(['parties' => search_parties_fuzzy($pdo, $q, $type, 80)]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
