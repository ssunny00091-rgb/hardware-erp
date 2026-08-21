<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $q = trim((string) ($_GET['q'] ?? ''));

    if ($q === '') {
        $stmt = $pdo->query(
            'SELECT id, product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code
             FROM products
             ORDER BY product_name ASC'
        );
        json_response(['products' => $stmt->fetchAll()]);
    }

    $stmt = $pdo->prepare(
        'SELECT id, product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code
         FROM products
         WHERE product_name LIKE :q_name OR brand LIKE :q_brand
         ORDER BY product_name ASC
         LIMIT 50'
    );
    $like = '%' . $q . '%';
    $stmt->execute(['q_name' => $like, 'q_brand' => $like]);
    json_response(['products' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
