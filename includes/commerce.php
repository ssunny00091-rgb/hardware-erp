<?php

declare(strict_types=1);

function party_types(): array
{
    return ['customer', 'supplier', 'painter', 'plumber', 'electrician'];
}

function ref_types(): array
{
    return ['painter', 'plumber', 'electrician'];
}

function normalize_party_type(string $type): string
{
    $type = strtolower(trim($type));
    return in_array($type, party_types(), true) ? $type : 'customer';
}

function normalize_ref_type(string $type): string
{
    $type = strtolower(trim($type));
    return in_array($type, ref_types(), true) ? $type : '';
}

function ensure_commerce_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS parties (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            mobile VARCHAR(20) DEFAULT "",
            address TEXT,
            type VARCHAR(30) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_parties_type_name (type, name),
            KEY idx_parties_mobile (mobile)
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ledger_entries (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            party_id INT UNSIGNED NOT NULL,
            entry_date DATE NOT NULL,
            particulars VARCHAR(255) NOT NULL,
            ref_no VARCHAR(50) DEFAULT "",
            debit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            credit DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            sale_id INT UNSIGNED NULL,
            purchase_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ledger_party (party_id),
            KEY idx_ledger_sale (sale_id),
            KEY idx_ledger_purchase (purchase_id)
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS purchase_payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            purchase_id INT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            paid_on DATE NOT NULL,
            notes VARCHAR(255) DEFAULT "",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_pp_purchase (purchase_id)
        ) ENGINE=InnoDB'
    );

    $alters = [
        "ALTER TABLE sales ADD COLUMN ref_type VARCHAR(30) NULL",
        "ALTER TABLE sales ADD COLUMN ref_party_id INT UNSIGNED NULL",
        "ALTER TABLE sales ADD COLUMN ref_name VARCHAR(255) NULL",
        "ALTER TABLE sales ADD COLUMN customer_party_id INT UNSIGNED NULL",
        "ALTER TABLE purchases ADD COLUMN supplier_party_id INT UNSIGNED NULL",
        "ALTER TABLE purchases ADD COLUMN paid DECIMAL(12,2) NULL",
        "ALTER TABLE sales ADD COLUMN sale_date DATE NULL",
    ];
    foreach ($alters as $sql) {
        try {
            $pdo->exec($sql);
        } catch (Throwable $e) {
            // Already exists.
        }
    }
    try {
        $pdo->exec('UPDATE sales SET sale_date = DATE(created_at) WHERE sale_date IS NULL');
    } catch (Throwable $e) {
        // ignore
    }
}

function parse_sale_date(mixed $raw): string
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return date('Y-m-d');
    }
    $iso = substr($raw, 0, 10);
    $dt = DateTime::createFromFormat('Y-m-d', $iso);
    if ($dt instanceof DateTime && $dt->format('Y-m-d') === $iso) {
        return $dt->format('Y-m-d');
    }
    foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $fmt) {
        $parsed = DateTime::createFromFormat('!' . $fmt, $raw);
        if ($parsed instanceof DateTime) {
            $errors = DateTime::getLastErrors();
            if ($errors === false || (((int) ($errors['warning_count'] ?? 0) === 0) && ((int) ($errors['error_count'] ?? 0) === 0))) {
                return $parsed->format('Y-m-d');
            }
        }
    }
    $ts = strtotime($raw);
    return $ts === false ? date('Y-m-d') : date('Y-m-d', $ts);
}

function format_display_date(mixed $raw): string
{
    return date('d/m/Y', strtotime(parse_sale_date($raw)));
}

function find_or_create_party(PDO $pdo, string $type, string $name, string $mobile = '', string $address = ''): ?int
{
    $type = normalize_party_type($type);
    $name = trim($name);
    $mobile = trim($mobile);
    $address = trim($address);
    if ($name === '') {
        return null;
    }

    if ($mobile !== '') {
        $stmt = $pdo->prepare(
            'SELECT id FROM parties WHERE type = :type AND mobile = :mobile LIMIT 1'
        );
        $stmt->execute(['type' => $type, 'mobile' => $mobile]);
        $row = $stmt->fetch();
        if ($row) {
            $id = (int) $row['id'];
            $pdo->prepare('UPDATE parties SET name = :name, address = :address WHERE id = :id')->execute([
                'name' => $name,
                'address' => $address,
                'id' => $id,
            ]);
            return $id;
        }
    }

    $stmt = $pdo->prepare(
        'SELECT id FROM parties WHERE type = :type AND LOWER(name) = LOWER(:name) LIMIT 1'
    );
    $stmt->execute(['type' => $type, 'name' => $name]);
    $row = $stmt->fetch();
    if ($row) {
        $id = (int) $row['id'];
        if ($mobile !== '' || $address !== '') {
            $pdo->prepare(
                'UPDATE parties SET mobile = CASE WHEN :mobile = "" THEN mobile ELSE :mobile2 END,
                 address = CASE WHEN :address = "" THEN address ELSE :address2 END
                 WHERE id = :id'
            )->execute([
                'mobile' => $mobile,
                'mobile2' => $mobile,
                'address' => $address,
                'address2' => $address,
                'id' => $id,
            ]);
        }
        return $id;
    }

    $insert = $pdo->prepare(
        'INSERT INTO parties (name, mobile, address, type) VALUES (:name, :mobile, :address, :type)'
    );
    $insert->execute([
        'name' => $name,
        'mobile' => $mobile,
        'address' => $address,
        'type' => $type,
    ]);

    return (int) $pdo->lastInsertId();
}

function add_ledger_entry(
    PDO $pdo,
    int $partyId,
    string $date,
    string $particulars,
    string $refNo,
    float $debit,
    float $credit,
    ?int $saleId = null,
    ?int $purchaseId = null
): void {
    if ($partyId <= 0 || ($debit <= 0 && $credit <= 0)) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO ledger_entries
          (party_id, entry_date, particulars, ref_no, debit, credit, sale_id, purchase_id)
         VALUES
          (:party_id, :entry_date, :particulars, :ref_no, :debit, :credit, :sale_id, :purchase_id)'
    );
    $stmt->execute([
        'party_id' => $partyId,
        'entry_date' => $date,
        'particulars' => $particulars,
        'ref_no' => $refNo,
        'debit' => $debit,
        'credit' => $credit,
        'sale_id' => $saleId,
        'purchase_id' => $purchaseId,
    ]);
}

function delete_sale_ledgers(PDO $pdo, int $saleId): void
{
    $pdo->prepare('DELETE FROM ledger_entries WHERE sale_id = :id')->execute(['id' => $saleId]);
}

function delete_purchase_ledgers(PDO $pdo, int $purchaseId): void
{
    $pdo->prepare('DELETE FROM ledger_entries WHERE purchase_id = :id')->execute(['id' => $purchaseId]);
}

function restore_sale_stock(PDO $pdo, int $saleId): void
{
    $items = $pdo->prepare('SELECT product_id, qty FROM sale_items WHERE sale_id = :id');
    $items->execute(['id' => $saleId]);
    $restore = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
    foreach ($items as $item) {
        if (!empty($item['product_id'])) {
            $restore->execute([
                'qty' => $item['qty'],
                'id' => $item['product_id'],
            ]);
        }
    }
}

function parse_sale_products(array $products): array
{
    $valid = [];
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }
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
            'color' => trim((string) ($product['color'] ?? '')),
            'color_hex' => trim((string) ($product['color_hex'] ?? '')),
            'hsn' => trim((string) ($product['hsn'] ?? $product['hsn_code'] ?? '')),
            'qty' => $qty,
            'unit' => trim((string) ($product['unit'] ?? 'Piece')) ?: 'Piece',
            'price' => $price,
            'total' => $qty * $price,
        ];
    }
    return $valid;
}

function insert_sale_items(PDO $pdo, int $saleId, array $valid): void
{
    $itemStmt = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, product_id, product_name, color_code, color_hex, hsn_code, qty, unit, price, total)
         VALUES (:sale_id, :product_id, :product_name, :color_code, :color_hex, :hsn_code, :qty, :unit, :price, :total)'
    );
    $itemStmtLegacy = $pdo->prepare(
        'INSERT INTO sale_items (sale_id, product_id, product_name, qty, unit, price, total)
         VALUES (:sale_id, :product_id, :product_name, :qty, :unit, :price, :total)'
    );
    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');
    $hsnStmt = $pdo->prepare('SELECT hsn_code FROM products WHERE id = :id');

    foreach ($valid as $item) {
        $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
        if (!$productId) {
            $productId = find_or_create_product($pdo, $item['name'], $item['unit'], (float) $item['price']);
        }

        $hsn = $item['hsn'];
        if ($hsn === '' && $productId) {
            $hsnStmt->execute(['id' => $productId]);
            $hsn = trim((string) ($hsnStmt->fetchColumn() ?: ''));
        }

        $payload = [
            'sale_id' => $saleId,
            'product_id' => $productId,
            'product_name' => $item['name'],
            'color_code' => $item['color'] !== '' ? $item['color'] : null,
            'color_hex' => $item['color_hex'] !== '' ? $item['color_hex'] : null,
            'hsn_code' => $hsn !== '' ? $hsn : null,
            'qty' => $item['qty'],
            'unit' => $item['unit'],
            'price' => $item['price'],
            'total' => $item['total'],
        ];
        try {
            $itemStmt->execute($payload);
        } catch (Throwable $e) {
            $itemStmtLegacy->execute([
                'sale_id' => $saleId,
                'product_id' => $productId ?: null,
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
}

function upsert_legacy_customer(PDO $pdo, string $name, string $mobile, string $address, string $gst): ?int
{
    if ($mobile === '') {
        return null;
    }
    $lookup = $pdo->prepare('SELECT id FROM customers WHERE mobile = :mobile LIMIT 1');
    $lookup->execute(['mobile' => $mobile]);
    $existing = $lookup->fetch();
    $label = $name !== '' ? $name : 'Walk-in';
    if ($existing) {
        $id = (int) $existing['id'];
        $pdo->prepare(
            'UPDATE customers SET name = :name, address = :address, gst = :gst WHERE id = :id'
        )->execute([
            'name' => $label,
            'address' => $address,
            'gst' => $gst,
            'id' => $id,
        ]);
        return $id;
    }
    $pdo->prepare(
        'INSERT INTO customers (name, mobile, address, gst) VALUES (:name, :mobile, :address, :gst)'
    )->execute([
        'name' => $label,
        'mobile' => $mobile,
        'address' => $address,
        'gst' => $gst,
    ]);
    return (int) $pdo->lastInsertId();
}

function post_sale_ledgers(
    PDO $pdo,
    int $saleId,
    string $invoiceNo,
    string $entryDate,
    float $total,
    float $received,
    ?int $customerPartyId,
    ?int $refPartyId,
    string $refType,
    string $refName
): void {
    delete_sale_ledgers($pdo, $saleId);
    $label = 'Sale Invoice ' . $invoiceNo;
    if ($refName !== '') {
        $label .= ' (' . ucfirst($refType !== '' ? $refType : 'ref') . ': ' . $refName . ')';
    }

    if ($customerPartyId) {
        add_ledger_entry($pdo, $customerPartyId, $entryDate, $label, $invoiceNo, $total, 0, $saleId, null);
        if ($received > 0) {
            add_ledger_entry($pdo, $customerPartyId, $entryDate, 'Receipt against ' . $invoiceNo, $invoiceNo, 0, $received, $saleId, null);
        }
    }

    if ($refPartyId && $refPartyId !== $customerPartyId) {
        $refLabel = 'Material taken — Invoice ' . $invoiceNo;
        add_ledger_entry($pdo, $refPartyId, $entryDate, $refLabel, $invoiceNo, $total, 0, $saleId, null);
        if ($received > 0) {
            add_ledger_entry($pdo, $refPartyId, $entryDate, 'Receipt against ' . $invoiceNo, $invoiceNo, 0, $received, $saleId, null);
        }
    }
}

function post_purchase_ledgers(
    PDO $pdo,
    int $purchaseId,
    string $invoiceNo,
    string $entryDate,
    float $total,
    float $paid,
    ?int $supplierPartyId
): void {
    delete_purchase_ledgers($pdo, $purchaseId);
    if (!$supplierPartyId) {
        return;
    }
    add_ledger_entry(
        $pdo,
        $supplierPartyId,
        $entryDate,
        'Purchase ' . ($invoiceNo !== '' ? $invoiceNo : '#' . $purchaseId),
        $invoiceNo,
        0,
        $total,
        null,
        $purchaseId
    );
    if ($paid > 0) {
        add_ledger_entry(
            $pdo,
            $supplierPartyId,
            $entryDate,
            'Payment against purchase ' . $invoiceNo,
            $invoiceNo,
            $paid,
            0,
            null,
            $purchaseId
        );
        $exists = $pdo->prepare('SELECT COUNT(*) FROM purchase_payments WHERE purchase_id = :id');
        try {
            $exists->execute(['id' => $purchaseId]);
            if ((int) $exists->fetchColumn() === 0) {
                insert_purchase_payment_row($pdo, $purchaseId, $paid, $entryDate, 'Paid with bill');
            }
        } catch (Throwable $e) {
            insert_purchase_payment_row($pdo, $purchaseId, $paid, $entryDate, 'Paid with bill');
        }
    }
}

function insert_purchase_payment_row(PDO $pdo, int $purchaseId, float $amount, string $date, string $notes): void
{
    if ($amount <= 0) {
        return;
    }
    try {
        $pdo->prepare(
            'INSERT INTO purchase_payments (purchase_id, amount, paid_on, notes)
             VALUES (:purchase_id, :amount, :paid_on, :notes)'
        )->execute([
            'purchase_id' => $purchaseId,
            'amount' => $amount,
            'paid_on' => $date,
            'notes' => $notes,
        ]);
    } catch (Throwable $e) {
        // Table may not exist on very old DB; schema helper creates it.
    }
}

function record_purchase_payment(PDO $pdo, int $purchaseId, float $amount, string $date, string $notes): array
{
    if ($amount <= 0) {
        throw new InvalidArgumentException('Payment amount galat hai.');
    }
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id');
    $stmt->execute(['id' => $purchaseId]);
    $purchase = $stmt->fetch();
    if (!$purchase) {
        throw new RuntimeException('Purchase bill nahi mili.');
    }

    $partyId = isset($purchase['supplier_party_id']) ? (int) $purchase['supplier_party_id'] : 0;
    if ($partyId <= 0) {
        $name = trim((string) ($purchase['supplier_name'] ?? ''));
        $partyId = $name !== '' ? (int) find_or_create_party($pdo, 'supplier', $name) : 0;
        if ($partyId) {
            try {
                $pdo->prepare('UPDATE purchases SET supplier_party_id = :pid WHERE id = :id')->execute([
                    'pid' => $partyId,
                    'id' => $purchaseId,
                ]);
            } catch (Throwable $e) {
                // Column may be missing.
            }
        }
    }

    $already = (float) ($purchase['paid'] ?? 0);
    $total = (float) $purchase['total'];
    $newPaid = $already + $amount;

    insert_purchase_payment_row($pdo, $purchaseId, $amount, $date, $notes !== '' ? $notes : 'Payment');
    try {
        $pdo->prepare('UPDATE purchases SET paid = :paid WHERE id = :id')->execute([
            'paid' => $newPaid,
            'id' => $purchaseId,
        ]);
    } catch (Throwable $e) {
        // paid column missing
    }

    $invoiceNo = (string) ($purchase['invoice_no'] ?? '');
    if ($partyId > 0) {
        add_ledger_entry(
            $pdo,
            $partyId,
            $date,
            $notes !== '' ? $notes : ('Payment against purchase ' . ($invoiceNo !== '' ? $invoiceNo : '#' . $purchaseId)),
            $invoiceNo,
            $amount,
            0,
            null,
            $purchaseId
        );
    }

    return [
        'paid' => $newPaid,
        'due' => max(0, $total - $newPaid),
        'total' => $total,
    ];
}

function backfill_ledgers(PDO $pdo): void
{
    $sales = $pdo->query(
        'SELECT s.* FROM sales s
         WHERE NOT EXISTS (SELECT 1 FROM ledger_entries e WHERE e.sale_id = s.id)'
    )->fetchAll();
    foreach ($sales as $sale) {
        $customerName = trim((string) ($sale['customer_name'] ?? ''));
        $customerPartyId = find_or_create_party(
            $pdo,
            'customer',
            $customerName !== '' ? $customerName : 'Walk-in',
            (string) ($sale['mobile'] ?? ''),
            (string) ($sale['address'] ?? '')
        );
        $refType = normalize_ref_type((string) ($sale['ref_type'] ?? ''));
        $refName = trim((string) ($sale['ref_name'] ?? ''));
        $refPartyId = null;
        if ($refType !== '' && $refName !== '') {
            $refPartyId = find_or_create_party($pdo, $refType, $refName);
        }
        $total = (float) $sale['total'];
        $received = isset($sale['received']) && $sale['received'] !== null && $sale['received'] !== ''
            ? (float) $sale['received']
            : $total;
        $date = parse_sale_date($sale['sale_date'] ?? $sale['created_at'] ?? '');
        post_sale_ledgers(
            $pdo,
            (int) $sale['id'],
            (string) $sale['invoice_no'],
            $date,
            $total,
            $received,
            $customerPartyId,
            $refPartyId,
            $refType,
            $refName
        );
    }

    $purchases = $pdo->query(
        'SELECT p.* FROM purchases p
         WHERE NOT EXISTS (SELECT 1 FROM ledger_entries e WHERE e.purchase_id = p.id)'
    )->fetchAll();
    foreach ($purchases as $row) {
        $name = trim((string) ($row['supplier_name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $partyId = find_or_create_party($pdo, 'supplier', $name);
        $paid = isset($row['paid']) && $row['paid'] !== null && $row['paid'] !== ''
            ? (float) $row['paid']
            : 0.0;
        post_purchase_ledgers(
            $pdo,
            (int) $row['id'],
            (string) ($row['invoice_no'] ?? ''),
            (string) $row['purchase_date'],
            (float) $row['total'],
            $paid,
            $partyId
        );
    }
}
