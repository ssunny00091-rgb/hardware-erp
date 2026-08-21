<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $purchase = $stmt->fetch();
            if (!$purchase) {
                json_response(['error' => 'Purchase not found'], 404);
            }
            $items = $pdo->prepare(
                'SELECT product_name AS name, qty, unit, price, total
                 FROM purchase_items WHERE purchase_id = :id ORDER BY id ASC'
            );
            $items->execute(['id' => $id]);
            $purchase['products'] = $items->fetchAll();
            $payments = [];
            try {
                $pay = $pdo->prepare(
                    'SELECT id, amount, paid_on, notes FROM purchase_payments
                     WHERE purchase_id = :id ORDER BY paid_on ASC, id ASC'
                );
                $pay->execute(['id' => $id]);
                $payments = $pay->fetchAll();
            } catch (Throwable $e) {
                $payments = [];
            }
            $purchase['payments'] = $payments;
            $paid = (float) ($purchase['paid'] ?? 0);
            $purchase['due'] = max(0, (float) $purchase['total'] - $paid);
            json_response(['purchase' => $purchase]);
        }

        try {
            $stmt = $pdo->query(
                'SELECT id, supplier_name, invoice_no, purchase_date, total, paid, created_at
                 FROM purchases
                 ORDER BY purchase_date DESC, id DESC
                 LIMIT 200'
            );
            json_response(['purchases' => $stmt->fetchAll()]);
        } catch (Throwable $e) {
            $stmt = $pdo->query(
                'SELECT id, supplier_name, invoice_no, purchase_date, total, created_at
                 FROM purchases
                 ORDER BY purchase_date DESC, id DESC
                 LIMIT 200'
            );
            json_response(['purchases' => $stmt->fetchAll()]);
        }
    }

    if ($method === 'POST') {
        $body = read_json_body();
        $payPurchaseId = (int) ($body['purchase_id'] ?? 0);
        if ($payPurchaseId > 0 && !isset($body['products'])) {
            $amount = (float) ($body['amount'] ?? 0);
            $date = trim((string) ($body['paid_on'] ?? $body['entry_date'] ?? date('Y-m-d')));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }
            $notes = trim((string) ($body['notes'] ?? ''));
            $pdo->beginTransaction();
            $result = record_purchase_payment($pdo, $payPurchaseId, $amount, $date, $notes);
            $pdo->commit();
            json_response(['ok' => true] + $result);
        }

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
    $paidRaw = $body['paid'] ?? null;
    $paid = $paidRaw === null || $paidRaw === '' ? 0.0 : (float) $paidRaw;

    $pdo->beginTransaction();

    $supplierPartyId = $supplier !== '' ? find_or_create_party($pdo, 'supplier', $supplier) : null;

    try {
        $purchaseStmt = $pdo->prepare(
            'INSERT INTO purchases (supplier_name, invoice_no, purchase_date, total, paid, supplier_party_id)
             VALUES (:supplier_name, :invoice_no, :purchase_date, :total, :paid, :supplier_party_id)'
        );
        $purchaseStmt->execute([
            'supplier_name' => $supplier,
            'invoice_no' => $invoiceNo,
            'purchase_date' => $purchaseDate,
            'total' => $grandTotal,
            'paid' => $paid,
            'supplier_party_id' => $supplierPartyId,
        ]);
    } catch (Throwable $e) {
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
    }
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

    post_purchase_ledgers($pdo, $purchaseId, $invoiceNo, $purchaseDate, $grandTotal, $paid, $supplierPartyId);
    $pdo->commit();
    json_response(['ok' => true, 'id' => $purchaseId, 'total' => $grandTotal]);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
