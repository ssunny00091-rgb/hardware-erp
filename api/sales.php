<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $saleStmt = $pdo->prepare(
                'SELECT id, invoice_no, customer_name, mobile, address, gst, total, created_at
                 FROM sales WHERE id = :id'
            );
            $saleStmt->execute(['id' => $id]);
            $sale = $saleStmt->fetch();
            if (!$sale) {
                json_response(['error' => 'Sale not found'], 404);
            }

            $itemStmt = $pdo->prepare(
                'SELECT product_name AS name, qty, unit, price, total
                 FROM sale_items WHERE sale_id = :id'
            );
            $itemStmt->execute(['id' => $id]);
            $sale['products'] = $itemStmt->fetchAll();
            json_response(['sale' => $sale]);
        }

        $stmt = $pdo->query(
            'SELECT id, invoice_no, customer_name, mobile, total, created_at
             FROM sales
             ORDER BY created_at DESC, id DESC
             LIMIT 200'
        );
        json_response(['sales' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
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
            if ($name === '') {
                continue;
            }
            if ($qty <= 0) {
                $qty = 1;
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

        $customerName = trim((string) ($body['customer_name'] ?? ''));
        $mobile = trim((string) ($body['mobile'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));
        $gst = trim((string) ($body['gst'] ?? ''));
        $grandTotal = array_sum(array_column($valid, 'total'));
        $invoiceNo = generate_invoice_number();

        $pdo->beginTransaction();

        $customerId = null;
        if ($mobile !== '') {
            $lookup = $pdo->prepare('SELECT id FROM customers WHERE mobile = :mobile LIMIT 1');
            $lookup->execute(['mobile' => $mobile]);
            $existing = $lookup->fetch();
            if ($existing) {
                $customerId = (int) $existing['id'];
                $update = $pdo->prepare(
                    'UPDATE customers SET name = :name, address = :address, gst = :gst WHERE id = :id'
                );
                $update->execute([
                    'name' => $customerName !== '' ? $customerName : 'Walk-in',
                    'address' => $address,
                    'gst' => $gst,
                    'id' => $customerId,
                ]);
            } else {
                $insert = $pdo->prepare(
                    'INSERT INTO customers (name, mobile, address, gst) VALUES (:name, :mobile, :address, :gst)'
                );
                $insert->execute([
                    'name' => $customerName !== '' ? $customerName : 'Walk-in',
                    'mobile' => $mobile,
                    'address' => $address,
                    'gst' => $gst,
                ]);
                $customerId = (int) $pdo->lastInsertId();
            }
        }

        $lineItemsJson = json_encode($valid, JSON_UNESCAPED_UNICODE);
        try {
            $saleStmt = $pdo->prepare(
                'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total, line_items)
                 VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total, :line_items)'
            );
            $saleStmt->execute([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'mobile' => $mobile,
                'address' => $address,
                'gst' => $gst,
                'total' => $grandTotal,
                'line_items' => $lineItemsJson,
            ]);
        } catch (Throwable $e) {
            $saleStmt = $pdo->prepare(
                'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total)
                 VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total)'
            );
            $saleStmt->execute([
                'invoice_no' => $invoiceNo,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'mobile' => $mobile,
                'address' => $address,
                'gst' => $gst,
                'total' => $grandTotal,
            ]);
        }
        $saleId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO sale_items (sale_id, product_id, product_name, qty, unit, price, total)
             VALUES (:sale_id, :product_id, :product_name, :qty, :unit, :price, :total)'
        );
        $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');

        foreach ($valid as $item) {
            $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
            if (!$productId) {
                $productId = find_or_create_product($pdo, $item['name'], $item['unit'], (float) $item['price']);
            }

            try {
                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            } catch (Throwable $e) {
                $itemStmt->execute([
                    'sale_id' => $saleId,
                    'product_id' => null,
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            }

            if ($productId) {
                $stockStmt->execute(['qty' => $item['qty'], 'id' => $productId]);
            }
        }

        $pdo->commit();
        json_response([
            'ok' => true,
            'id' => $saleId,
            'invoice_no' => $invoiceNo,
            'total' => $grandTotal,
        ]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid sale id'], 422);
        }

        $pdo->beginTransaction();
        $items = $pdo->prepare('SELECT product_id, qty FROM sale_items WHERE sale_id = :id');
        $items->execute(['id' => $id]);
        $restore = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $restore->execute([
                    'qty' => $item['qty'],
                    'id' => $item['product_id'],
                ]);
            }
        }
        $del = $pdo->prepare('DELETE FROM sales WHERE id = :id');
        $del->execute(['id' => $id]);
        $pdo->commit();
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
