<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'POST') {
        $body = read_json_body();
        $name = trim((string) ($body['product_name'] ?? ''));
        if ($name === '') {
            json_response(['error' => 'Please enter Product Name'], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO products
              (product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code)
             VALUES
              (:product_name, :brand, :category, :unit, :purchase_price, :selling_price, :stock, :gst_percent, :hsn_code)'
        );
        $stmt->execute([
            'product_name' => $name,
            'brand' => trim((string) ($body['brand'] ?? '')),
            'category' => trim((string) ($body['category'] ?? '')),
            'unit' => trim((string) ($body['unit'] ?? 'Piece')) ?: 'Piece',
            'purchase_price' => (float) ($body['purchase_price'] ?? 0),
            'selling_price' => (float) ($body['selling_price'] ?? 0),
            'stock' => (float) ($body['stock'] ?? 0),
            'gst_percent' => (float) ($body['gst_percent'] ?? 18),
            'hsn_code' => trim((string) ($body['hsn_code'] ?? '')),
        ]);

        json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
    }

    if ($method === 'PUT') {
        $body = read_json_body();
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid product id'], 422);
        }

        $stmt = $pdo->prepare(
            'UPDATE products SET
                product_name = :product_name,
                brand = :brand,
                category = :category,
                unit = :unit,
                purchase_price = :purchase_price,
                selling_price = :selling_price,
                stock = :stock,
                gst_percent = :gst_percent,
                hsn_code = :hsn_code
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'product_name' => trim((string) ($body['product_name'] ?? '')),
            'brand' => trim((string) ($body['brand'] ?? '')),
            'category' => trim((string) ($body['category'] ?? '')),
            'unit' => trim((string) ($body['unit'] ?? 'Piece')) ?: 'Piece',
            'purchase_price' => (float) ($body['purchase_price'] ?? 0),
            'selling_price' => (float) ($body['selling_price'] ?? 0),
            'stock' => (float) ($body['stock'] ?? 0),
            'gst_percent' => (float) ($body['gst_percent'] ?? 18),
            'hsn_code' => trim((string) ($body['hsn_code'] ?? '')),
        ]);

        json_response(['ok' => true]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            $body = read_json_body();
            $id = (int) ($body['id'] ?? 0);
        }
        if ($id <= 0) {
            json_response(['error' => 'Invalid product id'], 422);
        }

        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
