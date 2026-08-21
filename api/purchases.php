<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $stmt = $pdo->query(
            'SELECT id, supplier_name, invoice_no, purchase_date, total, created_at
             FROM purchases
             ORDER BY purchase_date DESC, id DESC
             LIMIT 200'
        );
        json_response(['purchases' => $stmt->fetchAll()]);
    }

    if ($method !== 'POST') {
        json_response(['error' => 'Method not allowed'], 405);
    }

    $body = read_json_body();
    $products = $body['products'] ?? [];
    if (!is_array($products)) {
        json_response(['error' => 'Invalid products'], 422);
    }

    $valid = [];
    foreach ($products as $product) {
        $name = trim((string) ($product['name'] ?? ''));
        $qty = (float) ($product['qty'] ?? 0);
        $price = (float) ($product['price'] ?? 0);
        if ($name === '' || $qty <= 0 || $price <= 0) {
            continue;
        }
        $valid[] = [
            'product_id' => isset($product['product_id']) ? (int) $product['product_id'] : null,
            'name' => $name,
            'qty' => $qty,
            'unit' => trim((string) ($product['unit'] ?? 'Piece')) ?: 'Piece',
            'price' => $price,
            'total' => $qty * $price,
        ];
    }

    if ($valid === []) {
        json_response(['error' => 'Add at least one valid product'], 422);
    }

    $supplier = trim((string) ($body['supplier_name'] ?? ''));
    $invoiceNo = trim((string) ($body['invoice_no'] ?? ''));
    $purchaseDate = trim((string) ($body['purchase_date'] ?? date('Y-m-d')));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchaseDate)) {
        $purchaseDate = date('Y-m-d');
    }
    $grandTotal = array_sum(array_column($valid, 'total'));

    $pdo->beginTransaction();

    $purchaseStmt = $pdo->prepare(
        'INSERT INTO purchases (supplier_name, invoice_no, purchase_date, total)
         VALUES (:supplier_name, :invoice_no, :purchase_date, :total)'
    );
    $purchaseStmt->execute([
        'supplier_name' => $supplier,
        'invoice_no' => $invoiceNo,
        'purchase_date' => $purchaseDate,
        'total' => $grandTotal,
    ]);
    $purchaseId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO purchase_items (purchase_id, product_id, product_name, qty, unit, price, total)
         VALUES (:purchase_id, :product_id, :product_name, :qty, :unit, :price, :total)'
    );
    $stockStmt = $pdo->prepare(
        'UPDATE products SET stock = stock + :qty, purchase_price = :price WHERE id = :id'
    );

    foreach ($valid as $item) {
        $productId = $item['product_id'] ?: null;
        if (!$productId) {
            $productId = find_or_create_product($pdo, $item['name'], $item['unit'], 0.0, $item['price']);
        }

        $itemStmt->execute([
            'purchase_id' => $purchaseId,
            'product_id' => $productId,
            'product_name' => $item['name'],
            'qty' => $item['qty'],
            'unit' => $item['unit'],
            'price' => $item['price'],
            'total' => $item['total'],
        ]);

        if ($productId) {
            $stockStmt->execute([
                'qty' => $item['qty'],
                'price' => $item['price'],
                'id' => $productId,
            ]);
        }
    }

    $pdo->commit();
    json_response(['ok' => true, 'id' => $purchaseId, 'total' => $grandTotal]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
