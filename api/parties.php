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

    $sql = 'SELECT id, name, mobile, address, type FROM parties';
    $params = [];
    $where = [];
    if ($type !== '') {
        $where[] = 'type = :type';
        $params['type'] = $type;
    }
    if ($q !== '') {
        $where[] = '(name LIKE :q OR mobile LIKE :q2)';
        $params['q'] = '%' . $q . '%';
        $params['q2'] = '%' . $q . '%';
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY name ASC LIMIT 80';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    json_response(['parties' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
