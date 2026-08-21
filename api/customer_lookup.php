<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $mobile = trim((string) ($_GET['mobile'] ?? ''));

    if ($mobile === '') {
        json_response(['customer' => null]);
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, mobile, address, gst FROM customers WHERE mobile = :mobile LIMIT 1'
    );
    $stmt->execute(['mobile' => $mobile]);
    $customer = $stmt->fetch();

    json_response(['customer' => $customer ?: null]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
