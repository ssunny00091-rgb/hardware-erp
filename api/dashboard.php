<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $today = date('Y-m-d');

    $salesToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = :d');
    $salesToday->execute(['d' => $today]);

    $purchaseToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) AS total FROM purchases WHERE purchase_date = :d');
    $purchaseToday->execute(['d' => $today]);

    $cash = $pdo->query('SELECT COALESCE(SUM(total), 0) AS total FROM sales')->fetch();
    $pending = $pdo->query('SELECT COALESCE(SUM(total), 0) AS total FROM purchases')->fetch();

    json_response([
        'today_sales' => (float) $salesToday->fetchColumn(),
        'today_purchase' => (float) $purchaseToday->fetchColumn(),
        'cash_in_hand' => (float) ($cash['total'] ?? 0),
        'pending_payment' => (float) ($pending['total'] ?? 0),
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
