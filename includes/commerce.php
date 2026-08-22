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
        "ALTER TABLE sales ADD COLUMN due_date DATE NULL",
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
        try {
            $pdo->exec('ALTER TABLE parties ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL');
        } catch (Throwable $e) {
            // Already exists.
        }
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );
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
            'SELECT id FROM parties WHERE type = :type AND mobile = :mobile AND deleted_at IS NULL LIMIT 1'
        );
        try {
            $stmt->execute(['type' => $type, 'mobile' => $mobile]);
        } catch (Throwable $e) {
            $stmt = $pdo->prepare(
                'SELECT id FROM parties WHERE type = :type AND mobile = :mobile LIMIT 1'
            );
            $stmt->execute(['type' => $type, 'mobile' => $mobile]);
        }
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
        'SELECT id FROM parties WHERE type = :type AND LOWER(name) = LOWER(:name) AND deleted_at IS NULL LIMIT 1'
    );
    try {
        $stmt->execute(['type' => $type, 'name' => $name]);
    } catch (Throwable $e) {
        $stmt = $pdo->prepare(
            'SELECT id FROM parties WHERE type = :type AND LOWER(name) = LOWER(:name) LIMIT 1'
        );
        $stmt->execute(['type' => $type, 'name' => $name]);
    }
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

function party_is_deleted(PDO $pdo, int $id): bool
{
    if ($id <= 0) {
        return true;
    }
    try {
        $stmt = $pdo->prepare('SELECT deleted_at FROM parties WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return true;
        }
        $deleted = $row['deleted_at'] ?? null;
        return $deleted !== null && $deleted !== '' && $deleted !== '0000-00-00 00:00:00';
    } catch (Throwable $e) {
        $stmt = $pdo->prepare('SELECT id FROM parties WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return !$stmt->fetch();
    }
}

function party_is_live(PDO $pdo, int $id): bool
{
    return $id > 0 && !party_is_deleted($pdo, $id);
}

function delete_party_record(PDO $pdo, int $partyId): void
{
    if ($partyId <= 0) {
        throw new InvalidArgumentException('party_id required');
    }
    $typeStmt = $pdo->prepare('SELECT type FROM parties WHERE id = :id');
    $typeStmt->execute(['id' => $partyId]);
    $typeRow = $typeStmt->fetch();
    if (!$typeRow) {
        throw new InvalidArgumentException('Party not found');
    }
    $type = normalize_party_type((string) $typeRow['type']);

    $saleIds = $pdo->prepare(
        'SELECT DISTINCT sale_id FROM ledger_entries WHERE party_id = :id AND sale_id IS NOT NULL'
    );
    $saleIds->execute(['id' => $partyId]);
    $purchaseIds = $pdo->prepare(
        'SELECT DISTINCT purchase_id FROM ledger_entries WHERE party_id = :id AND purchase_id IS NOT NULL'
    );
    $purchaseIds->execute(['id' => $partyId]);

    if ($type === 'customer') {
        $upd = $pdo->prepare(
            'UPDATE sales SET customer_party_id = :pid WHERE id = :sid AND (customer_party_id IS NULL OR customer_party_id = :pid2)'
        );
        foreach ($saleIds as $row) {
            $sid = (int) ($row['sale_id'] ?? 0);
            if ($sid > 0) {
                try {
                    $upd->execute(['pid' => $partyId, 'sid' => $sid, 'pid2' => $partyId]);
                } catch (Throwable $e) {
                    break;
                }
            }
        }
    } elseif (in_array($type, ref_types(), true)) {
        $upd = $pdo->prepare(
            'UPDATE sales SET ref_party_id = :pid WHERE id = :sid AND (ref_party_id IS NULL OR ref_party_id = :pid2)'
        );
        foreach ($saleIds as $row) {
            $sid = (int) ($row['sale_id'] ?? 0);
            if ($sid > 0) {
                try {
                    $upd->execute(['pid' => $partyId, 'sid' => $sid, 'pid2' => $partyId]);
                } catch (Throwable $e) {
                    break;
                }
            }
        }
    } elseif ($type === 'supplier') {
        $upd = $pdo->prepare(
            'UPDATE purchases SET supplier_party_id = :pid WHERE id = :sid AND (supplier_party_id IS NULL OR supplier_party_id = :pid2)'
        );
        foreach ($purchaseIds as $row) {
            $sid = (int) ($row['purchase_id'] ?? 0);
            if ($sid > 0) {
                try {
                    $upd->execute(['pid' => $partyId, 'sid' => $sid, 'pid2' => $partyId]);
                } catch (Throwable $e) {
                    break;
                }
            }
        }
    }

    $pdo->prepare('DELETE FROM ledger_entries WHERE party_id = :id')->execute(['id' => $partyId]);

    try {
        $pdo->prepare('UPDATE parties SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $partyId]);
        return;
    } catch (Throwable $e) {
        // Column missing — unlink bills then hard-delete.
    }

    foreach ([
        'UPDATE sales SET customer_party_id = NULL WHERE customer_party_id = :id',
        'UPDATE sales SET ref_party_id = NULL WHERE ref_party_id = :id',
        'UPDATE purchases SET supplier_party_id = NULL WHERE supplier_party_id = :id',
    ] as $sql) {
        try {
            $pdo->prepare($sql)->execute(['id' => $partyId]);
        } catch (Throwable $e) {
            // Column may not exist yet.
        }
    }
    $pdo->prepare('DELETE FROM parties WHERE id = :id')->execute(['id' => $partyId]);
}

function update_party_details(PDO $pdo, int $id, string $name, string $mobile = '', string $address = ''): void
{
    $name = trim($name);
    if ($id <= 0 || $name === '') {
        throw new InvalidArgumentException('Name required');
    }
    if (!party_is_live($pdo, $id)) {
        throw new InvalidArgumentException('Party not found');
    }
    $pdo->prepare('UPDATE parties SET name = :name, mobile = :mobile, address = :address WHERE id = :id')->execute([
        'name' => $name,
        'mobile' => trim($mobile),
        'address' => trim($address),
        'id' => $id,
    ]);
}

function update_ledger_entry(PDO $pdo, int $id, array $body): void
{
    $stmt = $pdo->prepare('SELECT * FROM ledger_entries WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new InvalidArgumentException('Entry not found');
    }
    $date = parse_sale_date($body['entry_date'] ?? $row['entry_date']);
    $particulars = trim((string) ($body['particulars'] ?? $row['particulars']));
    if ($particulars === '') {
        $particulars = (string) $row['particulars'];
    }
    $refNo = trim((string) ($body['ref_no'] ?? $row['ref_no'] ?? ''));
    $debit = array_key_exists('debit', $body) ? (float) $body['debit'] : (float) $row['debit'];
    $credit = array_key_exists('credit', $body) ? (float) $body['credit'] : (float) $row['credit'];
    if ($debit < 0) {
        $debit = 0;
    }
    if ($credit < 0) {
        $credit = 0;
    }
    if ($debit <= 0 && $credit <= 0) {
        throw new InvalidArgumentException('Debit ya credit amount likho');
    }
    $pdo->prepare(
        'UPDATE ledger_entries
         SET entry_date = :entry_date, particulars = :particulars, ref_no = :ref_no, debit = :debit, credit = :credit
         WHERE id = :id'
    )->execute([
        'entry_date' => $date,
        'particulars' => $particulars,
        'ref_no' => $refNo,
        'debit' => $debit,
        'credit' => $credit,
        'id' => $id,
    ]);
}

function normalize_search_name(string $value): string
{
    $value = trim($value);
    $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    return trim($value);
}

function compact_search_name(string $value): string
{
    return str_replace(' ', '', normalize_search_name($value));
}

function party_match_score(string $query, string $name, string $mobile = ''): int
{
    $q = normalize_search_name($query);
    $n = normalize_search_name($name);
    if ($q === '' || $n === '') {
        return 0;
    }
    if ($q === $n) {
        return 1000;
    }
    $qc = compact_search_name($query);
    $nc = compact_search_name($name);
    if ($qc !== '' && $qc === $nc) {
        return 960;
    }
    if ($qc !== '' && str_contains($nc, $qc)) {
        return 900;
    }
    if ($nc !== '' && str_contains($qc, $nc) && strlen($nc) >= 3) {
        return 860;
    }

    $qTokens = array_values(array_filter(explode(' ', $q)));
    $nTokens = array_values(array_filter(explode(' ', $n)));
    $tokenHits = 0;
    foreach ($qTokens as $token) {
        foreach ($nTokens as $nt) {
            if ($token === $nt || str_starts_with($nt, $token) || str_starts_with($token, $nt)) {
                $tokenHits++;
                continue 2;
            }
            $a = substr($token, 0, 255);
            $b = substr($nt, 0, 255);
            $allow = max(1, (int) floor(max(strlen($b), strlen($a)) / 3));
            if (function_exists('levenshtein') && levenshtein($a, $b) <= $allow) {
                $tokenHits++;
                continue 2;
            }
        }
    }
    if ($tokenHits === count($qTokens) && $tokenHits > 0) {
        return 720 + ($tokenHits * 25);
    }
    if ($tokenHits > 0) {
        return 420 + ($tokenHits * 50);
    }

    similar_text($qc, $nc, $pct);
    $pct = (float) $pct;
    $lev = 99;
    if (function_exists('levenshtein') && $qc !== '' && $nc !== '') {
        $lev = levenshtein(substr($qc, 0, 255), substr($nc, 0, 255));
    }
    $maxLen = max(strlen($qc), strlen($nc), 1);
    if ($lev <= 2 || ($lev / $maxLen) <= 0.35) {
        return max(200, 640 - ($lev * 25));
    }
    if ($pct >= 50) {
        return (int) (180 + $pct);
    }

    $digits = preg_replace('/\D+/', '', $query) ?? '';
    $mob = preg_replace('/\D+/', '', $mobile) ?? '';
    if (strlen($digits) >= 4 && $mob !== '' && str_contains($mob, $digits)) {
        return 930;
    }

    return (int) $pct;
}

function search_parties_fuzzy(PDO $pdo, string $query, string $type = '', int $limit = 12): array
{
    $query = trim($query);
    $type = strtolower(trim($type));
    $sql = 'SELECT id, name, mobile, address, type FROM parties WHERE deleted_at IS NULL';
    $params = [];
    if ($type !== '' && in_array($type, party_types(), true)) {
        $sql .= ' AND type = :type';
        $params['type'] = $type;
    }
    $sql .= ' ORDER BY name ASC LIMIT 800';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        $sql = 'SELECT id, name, mobile, address, type FROM parties';
        $params = [];
        if ($type !== '' && in_array($type, party_types(), true)) {
            $sql .= ' WHERE type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY name ASC LIMIT 800';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
    }
    if ($query === '') {
        return array_slice($rows, 0, $limit);
    }

    $scored = [];
    $digits = preg_replace('/\D+/', '', $query) ?? '';
    foreach ($rows as $row) {
        $score = party_match_score($query, (string) ($row['name'] ?? ''), (string) ($row['mobile'] ?? ''));
        if ($digits !== '' && strlen($digits) >= 4) {
            $mob = preg_replace('/\D+/', '', (string) ($row['mobile'] ?? '')) ?? '';
            if ($mob !== '' && str_contains($mob, $digits)) {
                $score = max($score, 930);
            }
        }
        $row['match_score'] = $score;
        $scored[] = $row;
    }
    usort($scored, static fn (array $a, array $b): int => ((int) $b['match_score']) <=> ((int) $a['match_score']));

    $best = (int) ($scored[0]['match_score'] ?? 0);
    if ($best < 35) {
        return array_slice($scored, 0, $limit);
    }
    $filtered = array_values(array_filter($scored, static fn (array $row): bool => ((int) $row['match_score']) >= 35));
    if ($filtered === []) {
        $filtered = $scored;
    }
    return array_slice($filtered, 0, $limit);
}

function load_party_ledger(PDO $pdo, int $partyId): ?array
{
    $party = $pdo->prepare('SELECT id, name, mobile, address, type FROM parties WHERE id = :id AND deleted_at IS NULL');
    try {
        $party->execute(['id' => $partyId]);
        $row = $party->fetch();
    } catch (Throwable $e) {
        $party = $pdo->prepare('SELECT id, name, mobile, address, type FROM parties WHERE id = :id');
        $party->execute(['id' => $partyId]);
        $row = $party->fetch();
    }
    if (!$row) {
        return null;
    }
    $entries = $pdo->prepare(
        'SELECT id, entry_date, particulars, ref_no, debit, credit, sale_id, purchase_id
         FROM ledger_entries
         WHERE party_id = :id
         ORDER BY entry_date ASC, id ASC'
    );
    $entries->execute(['id' => $partyId]);
    $list = $entries->fetchAll() ?: [];
    $balance = 0.0;
    foreach ($list as &$entry) {
        $balance += (float) $entry['debit'] - (float) $entry['credit'];
        $entry['balance'] = $balance;
        $entry['date'] = format_display_date($entry['entry_date'] ?? '');
    }
    unset($entry);
    return [
        'party' => $row,
        'entries' => $list,
        'debit' => array_sum(array_map(static fn ($e) => (float) $e['debit'], $list)),
        'credit' => array_sum(array_map(static fn ($e) => (float) $e['credit'], $list)),
        'balance' => $balance,
    ];
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
    if (party_is_deleted($pdo, $partyId)) {
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

function restore_purchase_stock(PDO $pdo, int $purchaseId): void
{
    $items = $pdo->prepare('SELECT product_id, qty FROM purchase_items WHERE purchase_id = :id');
    $items->execute(['id' => $purchaseId]);
    $restore = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');
    foreach ($items as $item) {
        if (!empty($item['product_id'])) {
            $restore->execute([
                'qty' => $item['qty'],
                'id' => $item['product_id'],
            ]);
        }
    }
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

    try {
        $pdo->prepare(
            'UPDATE sales SET customer_party_id = :cid, ref_party_id = :rid WHERE id = :id'
        )->execute([
            'cid' => $customerPartyId ?: null,
            'rid' => $refPartyId ?: null,
            'id' => $saleId,
        ]);
    } catch (Throwable $e) {
        try {
            $pdo->prepare('UPDATE sales SET customer_party_id = :cid WHERE id = :id')->execute([
                'cid' => $customerPartyId ?: null,
                'id' => $saleId,
            ]);
        } catch (Throwable $e2) {
            // Columns may be missing.
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

    try {
        $pdo->prepare('UPDATE purchases SET supplier_party_id = :pid WHERE id = :id')->execute([
            'pid' => $supplierPartyId,
            'id' => $purchaseId,
        ]);
    } catch (Throwable $e) {
        // Column may be missing.
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
    if ($partyId > 0 && party_is_deleted($pdo, $partyId)) {
        $partyId = 0;
    }
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
        $linkedCustomer = (int) ($sale['customer_party_id'] ?? 0);
        if ($linkedCustomer > 0 && party_is_deleted($pdo, $linkedCustomer)) {
            continue;
        }
        $customerName = trim((string) ($sale['customer_name'] ?? ''));
        $customerPartyId = $linkedCustomer > 0 && party_is_live($pdo, $linkedCustomer)
            ? $linkedCustomer
            : find_or_create_party(
                $pdo,
                'customer',
                $customerName !== '' ? $customerName : 'Walk-in',
                (string) ($sale['mobile'] ?? ''),
                (string) ($sale['address'] ?? '')
            );
        $refType = normalize_ref_type((string) ($sale['ref_type'] ?? ''));
        $refName = trim((string) ($sale['ref_name'] ?? ''));
        $linkedRef = (int) ($sale['ref_party_id'] ?? 0);
        $refPartyId = null;
        if ($linkedRef > 0 && party_is_deleted($pdo, $linkedRef)) {
            $refPartyId = null;
        } elseif ($linkedRef > 0 && party_is_live($pdo, $linkedRef)) {
            $refPartyId = $linkedRef;
        } elseif ($refType !== '' && $refName !== '') {
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
        $linkedSupplier = (int) ($row['supplier_party_id'] ?? 0);
        if ($linkedSupplier > 0 && party_is_deleted($pdo, $linkedSupplier)) {
            continue;
        }
        $partyId = $linkedSupplier > 0 && party_is_live($pdo, $linkedSupplier)
            ? $linkedSupplier
            : find_or_create_party($pdo, 'supplier', $name);
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

function persist_purchase(PDO $pdo, array $body): array
{
    $products = $body['products'] ?? [];
    if (!is_array($products)) {
        throw new InvalidArgumentException('Invalid products');
    }

    $valid = [];
    foreach ($products as $product) {
        if (!is_array($product)) {
            continue;
        }
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

    $supplier = trim((string) ($body['supplier_name'] ?? ''));
    $mobile = trim((string) ($body['mobile'] ?? $body['supplier_mobile'] ?? ''));
    $address = trim((string) ($body['address'] ?? $body['supplier_address'] ?? ''));
    $invoiceNo = trim((string) ($body['invoice_no'] ?? ''));
    $purchaseDate = parse_sale_date($body['purchase_date'] ?? '');
    $grandHint = (float) ($body['total'] ?? 0);
    if ($valid === [] && $grandHint > 0 && $supplier !== '') {
        $valid[] = [
            'product_id' => null,
            'name' => 'As per supplier bill',
            'qty' => 1.0,
            'unit' => 'Lot',
            'price' => $grandHint,
            'total' => $grandHint,
        ];
    }
    if ($valid === []) {
        throw new InvalidArgumentException('Add at least one valid product');
    }

    $grandTotal = array_sum(array_column($valid, 'total'));
    $paidRaw = $body['paid'] ?? null;
    $paid = $paidRaw === null || $paidRaw === '' ? 0.0 : (float) $paidRaw;

    $supplierPartyId = $supplier !== '' ? find_or_create_party($pdo, 'supplier', $supplier, $mobile, $address) : null;
    $purchaseId = (int) ($body['id'] ?? 0);

    if ($purchaseId > 0) {
        $exists = $pdo->prepare('SELECT id FROM purchases WHERE id = :id');
        $exists->execute(['id' => $purchaseId]);
        if (!$exists->fetch()) {
            throw new InvalidArgumentException('Purchase not found');
        }
        restore_purchase_stock($pdo, $purchaseId);
        $pdo->prepare('DELETE FROM purchase_items WHERE purchase_id = :id')->execute(['id' => $purchaseId]);
        delete_purchase_ledgers($pdo, $purchaseId);
        try {
            $paySum = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM purchase_payments WHERE purchase_id = :id');
            $paySum->execute(['id' => $purchaseId]);
            $fromPays = (float) $paySum->fetchColumn();
            if ($fromPays > 0) {
                $paid = $fromPays;
            }
        } catch (Throwable $e) {
            // no payments table
        }
        try {
            $pdo->prepare(
                'UPDATE purchases SET supplier_name = :supplier_name, invoice_no = :invoice_no, purchase_date = :purchase_date,
                 total = :total, paid = :paid, supplier_party_id = :supplier_party_id WHERE id = :id'
            )->execute([
                'supplier_name' => $supplier,
                'invoice_no' => $invoiceNo,
                'purchase_date' => $purchaseDate,
                'total' => $grandTotal,
                'paid' => $paid,
                'supplier_party_id' => $supplierPartyId,
                'id' => $purchaseId,
            ]);
        } catch (Throwable $e) {
            $pdo->prepare(
                'UPDATE purchases SET supplier_name = :supplier_name, invoice_no = :invoice_no, purchase_date = :purchase_date,
                 total = :total WHERE id = :id'
            )->execute([
                'supplier_name' => $supplier,
                'invoice_no' => $invoiceNo,
                'purchase_date' => $purchaseDate,
                'total' => $grandTotal,
                'id' => $purchaseId,
            ]);
        }
    } else {
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
    }

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

    return [
        'ok' => true,
        'id' => $purchaseId,
        'total' => $grandTotal,
        'paid' => $paid,
        'due' => max(0, $grandTotal - $paid),
        'supplier_party_id' => $supplierPartyId,
        'invoice_no' => $invoiceNo,
        'purchase_date' => $purchaseDate,
        'supplier_name' => $supplier,
    ];
}
