<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

function sale_payload(array $body): array
{
    $valid = parse_sale_products($body['products'] ?? []);
    if ($valid === []) {
        json_response(['error' => 'Add at least one valid product'], 422);
    }

    $customerName = trim((string) ($body['customer_name'] ?? ''));
    $mobile = trim((string) ($body['mobile'] ?? ''));
    $address = trim((string) ($body['address'] ?? ''));
    $gst = trim((string) ($body['gst'] ?? ''));
    $refType = normalize_ref_type((string) ($body['ref_type'] ?? ''));
    $refName = trim((string) ($body['ref_name'] ?? ''));
    $refMobile = trim((string) ($body['ref_mobile'] ?? ''));
    if ($refType === '') {
        $refName = '';
        $refMobile = '';
    }
    if ($customerName === '' && $refName !== '') {
        $customerName = $refName;
        if ($mobile === '') {
            $mobile = $refMobile;
        }
    }

    $grandTotal = array_sum(array_column($valid, 'total'));
    $receivedRaw = $body['received'] ?? null;
    $received = $receivedRaw === null || $receivedRaw === ''
        ? $grandTotal
        : (float) $receivedRaw;
    $saleDate = parse_sale_date($body['sale_date'] ?? '');

    return compact(
        'valid',
        'customerName',
        'mobile',
        'address',
        'gst',
        'refType',
        'refName',
        'refMobile',
        'grandTotal',
        'received',
        'saleDate'
    );
}

function persist_sale_header(PDO $pdo, array $data, ?int $saleId, ?string $invoiceNo, string $entryDate): array
{
    $customerId = upsert_legacy_customer(
        $pdo,
        $data['customerName'],
        $data['mobile'],
        $data['address'],
        $data['gst']
    );
    $customerPartyId = find_or_create_party(
        $pdo,
        'customer',
        $data['customerName'] !== '' ? $data['customerName'] : 'Walk-in',
        $data['mobile'],
        $data['address']
    );
    $refPartyId = null;
    if ($data['refType'] !== '' && $data['refName'] !== '') {
        $refPartyId = find_or_create_party($pdo, $data['refType'], $data['refName'], $data['refMobile']);
    }

    $lineItemsJson = json_encode($data['valid'], JSON_UNESCAPED_UNICODE);
    $fields = [
        'customer_id' => $customerId,
        'customer_name' => $data['customerName'],
        'mobile' => $data['mobile'],
        'address' => $data['address'],
        'gst' => $data['gst'],
        'total' => $data['grandTotal'],
        'received' => $data['received'],
        'line_items' => $lineItemsJson,
        'ref_type' => $data['refType'] !== '' ? $data['refType'] : null,
        'ref_party_id' => $refPartyId,
        'ref_name' => $data['refName'] !== '' ? $data['refName'] : null,
        'customer_party_id' => $customerPartyId,
        'sale_date' => $data['saleDate'],
        'created_at' => $data['saleDate'] . ' ' . date('H:i:s'),
    ];

    if ($saleId) {
        $sets = [];
        $payload = [];
        foreach ($fields as $col => $value) {
            $sets[] = $col . ' = :' . $col;
            $payload[$col] = $value;
        }
        $payload['id'] = $saleId;
        try {
            $pdo->prepare('UPDATE sales SET ' . implode(', ', $sets) . ' WHERE id = :id')->execute($payload);
        } catch (Throwable $e) {
            $pdo->prepare(
                'UPDATE sales SET customer_id = :customer_id, customer_name = :customer_name, mobile = :mobile,
                 address = :address, gst = :gst, total = :total WHERE id = :id'
            )->execute([
                'customer_id' => $customerId,
                'customer_name' => $data['customerName'],
                'mobile' => $data['mobile'],
                'address' => $data['address'],
                'gst' => $data['gst'],
                'total' => $data['grandTotal'],
                'id' => $saleId,
            ]);
        }
        $id = $saleId;
        $no = $invoiceNo ?? '';
    } else {
        $no = generate_invoice_number($pdo);
        $inserted = false;
        $attempts = [
            'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total, received, line_items, ref_type, ref_party_id, ref_name, customer_party_id, sale_date, created_at)
             VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total, :received, :line_items, :ref_type, :ref_party_id, :ref_name, :customer_party_id, :sale_date, :created_at)',
            'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total, received, line_items, ref_type, ref_party_id, ref_name, customer_party_id, created_at)
             VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total, :received, :line_items, :ref_type, :ref_party_id, :ref_name, :customer_party_id, :created_at)',
            'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total, received, line_items)
             VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total, :received, :line_items)',
            'INSERT INTO sales (invoice_no, customer_id, customer_name, mobile, address, gst, total)
             VALUES (:invoice_no, :customer_id, :customer_name, :mobile, :address, :gst, :total)',
        ];
        foreach ($attempts as $sql) {
            try {
                $payload = [
                    'invoice_no' => $no,
                    'customer_id' => $customerId,
                    'customer_name' => $data['customerName'],
                    'mobile' => $data['mobile'],
                    'address' => $data['address'],
                    'gst' => $data['gst'],
                    'total' => $data['grandTotal'],
                ];
                foreach (['received', 'line_items', 'ref_type', 'ref_party_id', 'ref_name', 'customer_party_id', 'sale_date', 'created_at'] as $col) {
                    if (str_contains($sql, ':' . $col)) {
                        $payload[$col] = $fields[$col];
                    }
                }
                $pdo->prepare($sql)->execute($payload);
                $inserted = true;
                break;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                if (!str_contains($msg, 'Unknown column') && !str_contains($msg, "doesn't exist")) {
                    throw $e;
                }
            }
        }
        if (!$inserted) {
            throw new RuntimeException('Sale save nahi hua.');
        }
        $id = (int) $pdo->lastInsertId();
    }

    insert_sale_items($pdo, $id, $data['valid']);
    post_sale_ledgers(
        $pdo,
        $id,
        $no,
        $entryDate,
        $data['grandTotal'],
        $data['received'],
        $customerPartyId,
        $refPartyId,
        $data['refType'],
        $data['refName']
    );

    return ['id' => $id, 'invoice_no' => $no, 'total' => $data['grandTotal']];
}

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        if (isset($_GET['next_invoice'])) {
            json_response(['invoice_no' => peek_next_invoice_number($pdo)]);
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $saleStmt = $pdo->prepare('SELECT * FROM sales WHERE id = :id');
            $saleStmt->execute(['id' => $id]);
            $sale = $saleStmt->fetch();
            if (!$sale) {
                json_response(['error' => 'Sale not found'], 404);
            }

            $itemStmt = $pdo->prepare(
                'SELECT product_id, product_name AS name, color_code AS color, color_hex, hsn_code AS hsn, qty, unit, price, total
                 FROM sale_items WHERE sale_id = :id ORDER BY id ASC'
            );
            try {
                $itemStmt->execute(['id' => $id]);
                $sale['products'] = $itemStmt->fetchAll();
            } catch (Throwable $e) {
                $legacy = $pdo->prepare(
                    'SELECT product_id, product_name AS name, qty, unit, price, total
                     FROM sale_items WHERE sale_id = :id ORDER BY id ASC'
                );
                $legacy->execute(['id' => $id]);
                $sale['products'] = $legacy->fetchAll();
            }
            json_response(['sale' => $sale]);
        }

        try {
            $stmt = $pdo->query(
                'SELECT id, invoice_no, customer_name, mobile, total, received, ref_type, ref_name, sale_date, created_at
                 FROM sales
                 ORDER BY COALESCE(sale_date, DATE(created_at)) DESC, id DESC
                 LIMIT 200'
            );
            json_response(['sales' => $stmt->fetchAll()]);
        } catch (Throwable $e) {
            $stmt = $pdo->query(
                'SELECT id, invoice_no, customer_name, mobile, total, created_at
                 FROM sales
                 ORDER BY created_at DESC, id DESC
                 LIMIT 200'
            );
            json_response(['sales' => $stmt->fetchAll()]);
        }
    }

    if ($method === 'POST' || $method === 'PUT') {
        $body = read_json_body();
        $data = sale_payload($body);
        $editId = (int) ($body['id'] ?? $_GET['id'] ?? 0);

        $pdo->beginTransaction();
        if ($method === 'PUT' || $editId > 0) {
            if ($editId <= 0) {
                json_response(['error' => 'Invalid sale id'], 422);
            }
            $existing = $pdo->prepare('SELECT id, invoice_no, created_at FROM sales WHERE id = :id');
            $existing->execute(['id' => $editId]);
            $row = $existing->fetch();
            if (!$row) {
                json_response(['error' => 'Sale not found'], 404);
            }
            restore_sale_stock($pdo, $editId);
            $pdo->prepare('DELETE FROM sale_items WHERE sale_id = :id')->execute(['id' => $editId]);
            $saved = persist_sale_header(
                $pdo,
                $data,
                $editId,
                (string) $row['invoice_no'],
                $data['saleDate']
            );
        } else {
            $saved = persist_sale_header($pdo, $data, null, null, $data['saleDate']);
        }
        $pdo->commit();
        json_response(['ok' => true] + $saved);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid sale id'], 422);
        }

        $pdo->beginTransaction();
        restore_sale_stock($pdo, $id);
        delete_sale_ledgers($pdo, $id);
        $pdo->prepare('DELETE FROM sales WHERE id = :id')->execute(['id' => $id]);
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
