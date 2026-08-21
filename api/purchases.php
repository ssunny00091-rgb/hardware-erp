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
                'SELECT product_id, product_name AS name, qty, unit, price, total
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
            $date = parse_sale_date($body['paid_on'] ?? $body['entry_date'] ?? '');
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

        $pdo->beginTransaction();
        try {
            $result = persist_purchase($pdo, $body);
        } catch (InvalidArgumentException $e) {
            $pdo->rollBack();
            json_response(['error' => $e->getMessage()], 422);
        }
        $pdo->commit();
        json_response($result);
    }

    if ($method === 'PUT') {
        $body = read_json_body();
        $editId = (int) ($body['id'] ?? $_GET['id'] ?? 0);
        if ($editId <= 0) {
            json_response(['error' => 'Invalid purchase id'], 422);
        }
        $body['id'] = $editId;
        if (!isset($body['products']) || !is_array($body['products'])) {
            json_response(['error' => 'Invalid products'], 422);
        }
        $pdo->beginTransaction();
        try {
            $result = persist_purchase($pdo, $body);
        } catch (InvalidArgumentException $e) {
            $pdo->rollBack();
            json_response(['error' => $e->getMessage()], 422);
        }
        $pdo->commit();
        json_response($result);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
