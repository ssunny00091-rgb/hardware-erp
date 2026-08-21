<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $today = date('Y-m-d');

    try {
        $salesToday = $pdo->prepare(
            'SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE COALESCE(sale_date, DATE(created_at)) = :d'
        );
        $salesToday->execute(['d' => $today]);
    } catch (Throwable $e) {
        $salesToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = :d');
        $salesToday->execute(['d' => $today]);
    }

    $purchaseToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) AS total FROM purchases WHERE purchase_date = :d');
    $purchaseToday->execute(['d' => $today]);

    $cash = $pdo->query(
        'SELECT COALESCE(SUM(COALESCE(received, total)), 0) AS total FROM sales'
    )->fetch();
    $saleDue = $pdo->query(
        'SELECT COALESCE(SUM(total - COALESCE(received, total)), 0) AS total FROM sales'
    )->fetch();
    $purchaseDue = 0.0;
    try {
        $purchaseDue = (float) $pdo->query(
            'SELECT COALESCE(SUM(total - COALESCE(paid, 0)), 0) AS total FROM purchases'
        )->fetchColumn();
    } catch (Throwable $e) {
        $purchaseDue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM purchases')->fetchColumn();
    }

    json_response([
        'today_sales' => (float) $salesToday->fetchColumn(),
        'today_purchase' => (float) $purchaseToday->fetchColumn(),
        'cash_in_hand' => (float) ($cash['total'] ?? 0),
        'pending_payment' => (float) ($saleDue['total'] ?? 0) + $purchaseDue,
    ]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
