<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $type = strtolower(trim((string) ($_GET['type'] ?? '')));
    $q = trim((string) ($_GET['q'] ?? ''));
    if ($type !== '' && !in_array($type, party_types(), true)) {
        json_response(['error' => 'Invalid type'], 422);
    }

    json_response(['parties' => search_parties_fuzzy($pdo, $q, $type, 80)]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
