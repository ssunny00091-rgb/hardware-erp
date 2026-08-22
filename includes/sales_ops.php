<?php

declare(strict_types=1);

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

function load_sale_full(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $saleStmt = $pdo->prepare('SELECT * FROM sales WHERE id = :id');
    $saleStmt->execute(['id' => $id]);
    $sale = $saleStmt->fetch();
    if (!$sale) {
        return null;
    }
    try {
        $itemStmt = $pdo->prepare(
            'SELECT product_id, product_name AS name, color_code AS color, color_hex, hsn_code AS hsn, qty, unit, price, total
             FROM sale_items WHERE sale_id = :id ORDER BY id ASC'
        );
        $itemStmt->execute(['id' => $id]);
        $products = $itemStmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        $legacy = $pdo->prepare(
            'SELECT product_id, product_name AS name, qty, unit, price, total
             FROM sale_items WHERE sale_id = :id ORDER BY id ASC'
        );
        $legacy->execute(['id' => $id]);
        $products = $legacy->fetchAll() ?: [];
    }
    if ($products === [] && !empty($sale['line_items'])) {
        $decoded = json_decode((string) $sale['line_items'], true);
        if (is_array($decoded)) {
            $products = $decoded;
        }
    }
    $sale['products'] = $products;
    $total = (float) ($sale['total'] ?? 0);
    $received = isset($sale['received']) && $sale['received'] !== null && $sale['received'] !== ''
        ? (float) $sale['received']
        : $total;
    $sale['received'] = $received;
    $sale['due'] = max(0, $total - $received);
    $sale['date'] = format_display_date($sale['sale_date'] ?? $sale['created_at'] ?? '');
    return $sale;
}

function find_sales_by_invoice_query(PDO $pdo, string $query): array
{
    $query = trim($query);
    $query = preg_replace('/^(invoice|inv|bill|no|#)\s*/i', '', $query) ?? $query;
    $query = trim($query, " \t#");
    if ($query === '') {
        return [];
    }
    $found = [];
    $add = static function (array $row) use (&$found): void {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            $found[$id] = $row;
        }
    };

    $exact = $pdo->prepare('SELECT id, invoice_no, customer_name FROM sales WHERE invoice_no = :q LIMIT 10');
    $exact->execute(['q' => $query]);
    foreach ($exact as $row) {
        $add($row);
    }

    if (preg_match('/^[0-9]+$/', $query)) {
        $num = (int) $query;
        $byId = $pdo->prepare('SELECT id, invoice_no, customer_name FROM sales WHERE id = :id LIMIT 1');
        $byId->execute(['id' => $num]);
        $row = $byId->fetch();
        if ($row) {
            $add($row);
        }
        try {
            $cast = $pdo->prepare(
                "SELECT id, invoice_no, customer_name FROM sales
                 WHERE invoice_no REGEXP '^[0-9]+$' AND CAST(invoice_no AS UNSIGNED) = :n
                 LIMIT 10"
            );
            $cast->execute(['n' => $num]);
            foreach ($cast as $row) {
                $add($row);
            }
        } catch (Throwable $e) {
            // ignore
        }
    }

    if ($found === []) {
        $like = $pdo->prepare(
            'SELECT id, invoice_no, customer_name FROM sales
             WHERE invoice_no LIKE :q OR customer_name LIKE :q2
             ORDER BY id DESC LIMIT 8'
        );
        $like->execute(['q' => '%' . $query . '%', 'q2' => '%' . $query . '%']);
        foreach ($like as $row) {
            $add($row);
        }
    }

    return array_values($found);
}
