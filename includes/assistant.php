<?php

declare(strict_types=1);

function openrouter_config(): array
{
    $key = trim((string) (getenv('OPENROUTER_API_KEY') ?: ($_ENV['OPENROUTER_API_KEY'] ?? '')));
    $model = trim((string) (getenv('OPENROUTER_MODEL') ?: ($_ENV['OPENROUTER_MODEL'] ?? 'google/gemini-2.5-flash')));
    $maxTokens = (int) (getenv('OPENROUTER_MAX_TOKENS') ?: ($_ENV['OPENROUTER_MAX_TOKENS'] ?? 4000));
    if ($maxTokens < 256) {
        $maxTokens = 256;
    }
    if ($maxTokens > 8000) {
        $maxTokens = 8000;
    }
    return [
        'api_key' => $key,
        'model' => $model !== '' ? $model : 'google/gemini-2.5-flash',
        'max_tokens' => $maxTokens,
    ];
}

function assistant_system_prompt(): string
{
    $company = (require dirname(__DIR__) . '/config/config.php')['company'] ?? [];
    $name = (string) ($company['name'] ?? 'Hardware Store');
    $today = date('d/m/Y');
    return <<<PROMPT
You are the in-shop automation agent for {$name} hardware ERP.
Today's date is {$today} (dd/mm/yyyy). Reply in simple Hindi (ok to mix English shop words).

You MUST use tools to actually change the software. Do not pretend you saved something without a tool result.

When the user sends a photo, screenshot, scan, or PDF of a supplier bill / invoice / challan / quotation / WhatsApp bill / handwritten bill (any format):
1. Read all visible text even if rotated, blurry, or mixed Hindi/English.
2. Extract supplier name, mobile, address, GST, invoice number, bill date, line items (name, qty, unit, rate), grand total, amount paid.
3. Immediately call import_supplier_bill. Do not ask for confirmation.
4. If line items are unreadable but supplier name + total are visible, still import using total.
5. If supplier name is readable but items are not, still call add_or_update_party for the supplier.

Voice/text commands you should automate:
- New sale / bill banana (customer, items, qty, rate, paid/due, date, painter/plumber ref)
- Product add/update, stock poochna
- Purchase / supplier bill
- Ledger receipt/payment
- Dashboard totals, recent sales/purchases

Dates from users may be dd/mm/yyyy. Pass them as-is to tools.
Amounts are Indian rupees.
After tools, summarise what was saved: names, invoice nos, ₹ amounts, due, and next step if any.
PROMPT;
}

function assistant_tool_schemas(): array
{
    $productItem = [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'qty' => ['type' => 'number'],
            'unit' => ['type' => 'string'],
            'price' => ['type' => 'number', 'description' => 'Rate per unit'],
            'product_id' => ['type' => 'integer'],
            'color' => ['type' => 'string'],
            'hsn' => ['type' => 'string'],
        ],
        'required' => ['name', 'qty', 'price'],
    ];

    return [
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_dashboard',
                'description' => 'Today sales, purchases, cash and pending totals',
                'parameters' => ['type' => 'object', 'properties' => new stdClass()],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_products',
                'description' => 'Search product catalogue and stock',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'q' => ['type' => 'string', 'description' => 'Name or brand, empty for all (limited)'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'save_product',
                'description' => 'Create or update a product',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer', 'description' => 'Set to update existing'],
                        'product_name' => ['type' => 'string'],
                        'brand' => ['type' => 'string'],
                        'category' => ['type' => 'string'],
                        'unit' => ['type' => 'string'],
                        'purchase_price' => ['type' => 'number'],
                        'selling_price' => ['type' => 'number'],
                        'stock' => ['type' => 'number'],
                        'gst_percent' => ['type' => 'number'],
                        'hsn_code' => ['type' => 'string'],
                    ],
                    'required' => ['product_name'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_parties',
                'description' => 'Find customers, suppliers, painters, plumbers, electricians',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'painter', 'plumber', 'electrician']],
                        'q' => ['type' => 'string'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'add_or_update_party',
                'description' => 'Add or update a person/company in ledger (supplier, customer, painter, plumber, electrician)',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'painter', 'plumber', 'electrician']],
                        'name' => ['type' => 'string'],
                        'mobile' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                    ],
                    'required' => ['type', 'name'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'import_supplier_bill',
                'description' => 'Create supplier in ledger AND save purchase bill + stock from an extracted invoice photo or spoken purchase',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'supplier_name' => ['type' => 'string'],
                        'mobile' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'invoice_no' => ['type' => 'string'],
                        'purchase_date' => ['type' => 'string', 'description' => 'Bill date dd/mm/yyyy or yyyy-mm-dd'],
                        'paid' => ['type' => 'number', 'description' => 'Amount already paid to supplier'],
                        'total' => ['type' => 'number', 'description' => 'Grand total if line items missing'],
                        'products' => ['type' => 'array', 'items' => $productItem],
                    ],
                    'required' => ['supplier_name'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'create_sale',
                'description' => 'Save a customer tax invoice / sale',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_name' => ['type' => 'string'],
                        'mobile' => ['type' => 'string'],
                        'address' => ['type' => 'string'],
                        'gst' => ['type' => 'string'],
                        'sale_date' => ['type' => 'string'],
                        'received' => ['type' => 'number', 'description' => 'Cash received; omit for full paid'],
                        'ref_type' => ['type' => 'string', 'enum' => ['painter', 'plumber', 'electrician']],
                        'ref_name' => ['type' => 'string'],
                        'ref_mobile' => ['type' => 'string'],
                        'products' => ['type' => 'array', 'items' => $productItem],
                    ],
                    'required' => ['products'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_purchase_payment',
                'description' => 'Add a later payment against an existing supplier purchase bill',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'purchase_id' => ['type' => 'integer'],
                        'amount' => ['type' => 'number'],
                        'paid_on' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['purchase_id', 'amount'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'record_ledger_payment',
                'description' => 'Add receipt (customer) or payment (supplier) on a party ledger',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'party_id' => ['type' => 'integer'],
                        'name' => ['type' => 'string', 'description' => 'If party_id unknown, search by name'],
                        'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'painter', 'plumber', 'electrician']],
                        'amount' => ['type' => 'number'],
                        'entry_date' => ['type' => 'string'],
                        'notes' => ['type' => 'string'],
                    ],
                    'required' => ['amount'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_recent_sales',
                'description' => 'Latest sale invoices',
                'parameters' => ['type' => 'object', 'properties' => new stdClass()],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'list_recent_purchases',
                'description' => 'Latest supplier bills',
                'parameters' => ['type' => 'object', 'properties' => new stdClass()],
            ],
        ],
    ];
}

function assistant_decode_args(mixed $raw): array
{
    if (is_array($raw)) {
        return $raw;
    }
    $raw = trim((string) $raw);
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function assistant_run_tool(PDO $pdo, string $name, array $args): array
{
    try {
        return match ($name) {
            'get_dashboard' => assistant_tool_dashboard($pdo),
            'search_products' => assistant_tool_search_products($pdo, $args),
            'save_product' => assistant_tool_save_product($pdo, $args),
            'search_parties' => assistant_tool_search_parties($pdo, $args),
            'add_or_update_party' => assistant_tool_party($pdo, $args),
            'import_supplier_bill' => assistant_tool_import_bill($pdo, $args),
            'create_sale' => assistant_tool_create_sale($pdo, $args),
            'record_purchase_payment' => assistant_tool_purchase_pay($pdo, $args),
            'record_ledger_payment' => assistant_tool_ledger_pay($pdo, $args),
            'list_recent_sales' => assistant_tool_sales($pdo),
            'list_recent_purchases' => assistant_tool_purchases($pdo),
            default => ['error' => 'Unknown tool: ' . $name],
        };
    } catch (Throwable $e) {
        return ['error' => $e->getMessage()];
    }
}

function assistant_tool_dashboard(PDO $pdo): array
{
    $today = date('Y-m-d');
    try {
        $salesToday = $pdo->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM sales WHERE COALESCE(sale_date, DATE(created_at)) = :d'
        );
        $salesToday->execute(['d' => $today]);
    } catch (Throwable $e) {
        $salesToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) FROM sales WHERE DATE(created_at) = :d');
        $salesToday->execute(['d' => $today]);
    }
    $purchaseToday = $pdo->prepare('SELECT COALESCE(SUM(total), 0) FROM purchases WHERE purchase_date = :d');
    $purchaseToday->execute(['d' => $today]);
    $cash = (float) $pdo->query('SELECT COALESCE(SUM(COALESCE(received, total)), 0) FROM sales')->fetchColumn();
    $saleDue = (float) $pdo->query('SELECT COALESCE(SUM(total - COALESCE(received, total)), 0) FROM sales')->fetchColumn();
    $purchaseDue = 0.0;
    try {
        $purchaseDue = (float) $pdo->query(
            'SELECT COALESCE(SUM(total - COALESCE(paid, 0)), 0) FROM purchases'
        )->fetchColumn();
    } catch (Throwable $e) {
        $purchaseDue = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM purchases')->fetchColumn();
    }
    return [
        'today_sales' => (float) $salesToday->fetchColumn(),
        'today_purchase' => (float) $purchaseToday->fetchColumn(),
        'cash_in_hand' => $cash,
        'pending_payment' => $saleDue + $purchaseDue,
        'date' => date('d/m/Y'),
    ];
}

function assistant_tool_search_products(PDO $pdo, array $args): array
{
    $q = trim((string) ($args['q'] ?? ''));
    if ($q === '') {
        $stmt = $pdo->query(
            'SELECT id, product_name, brand, unit, purchase_price, selling_price, stock, hsn_code
             FROM products ORDER BY product_name ASC LIMIT 40'
        );
        return ['products' => $stmt->fetchAll()];
    }
    $stmt = $pdo->prepare(
        'SELECT id, product_name, brand, unit, purchase_price, selling_price, stock, hsn_code
         FROM products
         WHERE product_name LIKE :q OR brand LIKE :q2
         ORDER BY product_name ASC LIMIT 40'
    );
    $like = '%' . $q . '%';
    $stmt->execute(['q' => $like, 'q2' => $like]);
    return ['products' => $stmt->fetchAll()];
}

function assistant_tool_save_product(PDO $pdo, array $args): array
{
    $name = trim((string) ($args['product_name'] ?? ''));
    if ($name === '') {
        return ['error' => 'product_name required'];
    }
    $id = (int) ($args['id'] ?? 0);
    $fields = [
        'product_name' => $name,
        'brand' => trim((string) ($args['brand'] ?? '')),
        'category' => trim((string) ($args['category'] ?? '')),
        'unit' => trim((string) ($args['unit'] ?? 'Piece')) ?: 'Piece',
        'purchase_price' => (float) ($args['purchase_price'] ?? 0),
        'selling_price' => (float) ($args['selling_price'] ?? 0),
        'stock' => (float) ($args['stock'] ?? 0),
        'gst_percent' => (float) ($args['gst_percent'] ?? 18),
        'hsn_code' => trim((string) ($args['hsn_code'] ?? '')),
    ];
    if ($id > 0) {
        $fields['id'] = $id;
        $pdo->prepare(
            'UPDATE products SET product_name=:product_name, brand=:brand, category=:category, unit=:unit,
             purchase_price=:purchase_price, selling_price=:selling_price, stock=:stock,
             gst_percent=:gst_percent, hsn_code=:hsn_code WHERE id=:id'
        )->execute($fields);
        return ['ok' => true, 'id' => $id, 'updated' => true];
    }
    $pdo->prepare(
        'INSERT INTO products (product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code)
         VALUES (:product_name, :brand, :category, :unit, :purchase_price, :selling_price, :stock, :gst_percent, :hsn_code)'
    )->execute($fields);
    return ['ok' => true, 'id' => (int) $pdo->lastInsertId(), 'created' => true];
}

function assistant_tool_search_parties(PDO $pdo, array $args): array
{
    $type = strtolower(trim((string) ($args['type'] ?? '')));
    $q = trim((string) ($args['q'] ?? ''));
    $sql = 'SELECT id, name, mobile, address, type FROM parties';
    $params = [];
    $where = [];
    if ($type !== '' && in_array($type, party_types(), true)) {
        $where[] = 'type = :type';
        $params['type'] = $type;
    }
    if ($q !== '') {
        $where[] = '(name LIKE :q OR mobile LIKE :q2)';
        $params['q'] = '%' . $q . '%';
        $params['q2'] = '%' . $q . '%';
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY name ASC LIMIT 40';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return ['parties' => $stmt->fetchAll()];
}

function assistant_tool_party(PDO $pdo, array $args): array
{
    $type = normalize_party_type((string) ($args['type'] ?? 'supplier'));
    $name = trim((string) ($args['name'] ?? ''));
    if ($name === '') {
        return ['error' => 'name required'];
    }
    $id = find_or_create_party(
        $pdo,
        $type,
        $name,
        trim((string) ($args['mobile'] ?? '')),
        trim((string) ($args['address'] ?? ''))
    );
    return ['ok' => true, 'party_id' => $id, 'type' => $type, 'name' => $name];
}

function assistant_tool_import_bill(PDO $pdo, array $args): array
{
    $name = trim((string) ($args['supplier_name'] ?? ''));
    if ($name === '') {
        $name = 'Supplier (photo)';
        $args['supplier_name'] = $name;
    }
    $partyId = find_or_create_party(
        $pdo,
        'supplier',
        $name,
        trim((string) ($args['mobile'] ?? '')),
        trim((string) ($args['address'] ?? ''))
    );
    $products = $args['products'] ?? [];
    $hasLines = false;
    if (is_array($products)) {
        foreach ($products as $row) {
            if (is_array($row) && trim((string) ($row['name'] ?? '')) !== '' && (float) ($row['qty'] ?? 0) > 0 && (float) ($row['price'] ?? 0) > 0) {
                $hasLines = true;
                break;
            }
        }
    }
    if (!$hasLines && (float) ($args['total'] ?? 0) <= 0) {
        return [
            'ok' => true,
            'supplier_saved' => true,
            'party_id' => $partyId,
            'purchase_id' => null,
            'note' => 'Supplier add ho gaya. Bill items/total nahi mile isliye purchase nahi bani.',
        ];
    }
    $own = !$pdo->inTransaction();
    if ($own) {
        $pdo->beginTransaction();
    }
    try {
        $saved = persist_purchase($pdo, $args);
        if ($own) {
            $pdo->commit();
        }
        $saved['supplier_saved'] = true;
        $saved['party_id'] = $partyId ?: $saved['supplier_party_id'];
        return $saved;
    } catch (Throwable $e) {
        if ($own && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['error' => $e->getMessage(), 'party_id' => $partyId, 'supplier_saved' => (bool) $partyId];
    }
}

function assistant_tool_create_sale(PDO $pdo, array $args): array
{
    $valid = parse_sale_products($args['products'] ?? []);
    if ($valid === []) {
        return ['error' => 'Sale ke liye kam se kam ek product chahiye'];
    }
    $customerName = trim((string) ($args['customer_name'] ?? ''));
    $mobile = trim((string) ($args['mobile'] ?? ''));
    $address = trim((string) ($args['address'] ?? ''));
    $gst = trim((string) ($args['gst'] ?? ''));
    $refType = normalize_ref_type((string) ($args['ref_type'] ?? ''));
    $refName = trim((string) ($args['ref_name'] ?? ''));
    $refMobile = trim((string) ($args['ref_mobile'] ?? ''));
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
    $receivedRaw = $args['received'] ?? null;
    $received = $receivedRaw === null || $receivedRaw === '' ? $grandTotal : (float) $receivedRaw;
    $saleDate = parse_sale_date($args['sale_date'] ?? '');
    $data = compact(
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
    $own = !$pdo->inTransaction();
    if ($own) {
        $pdo->beginTransaction();
    }
    try {
        $saved = persist_sale_header($pdo, $data, null, null, $saleDate);
        if ($own) {
            $pdo->commit();
        }
        return ['ok' => true] + $saved + ['sale_date' => $saleDate];
    } catch (Throwable $e) {
        if ($own && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function assistant_tool_purchase_pay(PDO $pdo, array $args): array
{
    $id = (int) ($args['purchase_id'] ?? 0);
    $amount = (float) ($args['amount'] ?? 0);
    if ($id <= 0 || $amount <= 0) {
        return ['error' => 'purchase_id and amount required'];
    }
    $date = parse_sale_date($args['paid_on'] ?? '');
    $own = !$pdo->inTransaction();
    if ($own) {
        $pdo->beginTransaction();
    }
    try {
        $result = record_purchase_payment($pdo, $id, $amount, $date, trim((string) ($args['notes'] ?? '')));
        if ($own) {
            $pdo->commit();
        }
        return ['ok' => true] + $result;
    } catch (Throwable $e) {
        if ($own && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function assistant_tool_ledger_pay(PDO $pdo, array $args): array
{
    $partyId = (int) ($args['party_id'] ?? 0);
    $amount = (float) ($args['amount'] ?? 0);
    if ($amount <= 0) {
        return ['error' => 'amount required'];
    }
    if ($partyId <= 0) {
        $type = normalize_party_type((string) ($args['type'] ?? 'customer'));
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'party_id or name required'];
        }
        $partyId = (int) find_or_create_party($pdo, $type, $name);
    }
    $party = $pdo->prepare('SELECT * FROM parties WHERE id = :id');
    $party->execute(['id' => $partyId]);
    $row = $party->fetch();
    if (!$row) {
        return ['error' => 'Party not found'];
    }
    $date = parse_sale_date($args['entry_date'] ?? '');
    $notes = trim((string) ($args['notes'] ?? ''));
    $type = (string) $row['type'];
    $particulars = $notes !== '' ? $notes : ($type === 'supplier' ? 'Payment' : 'Receipt');
    if ($type === 'supplier') {
        add_ledger_entry($pdo, $partyId, $date, $particulars, '', $amount, 0);
    } else {
        add_ledger_entry($pdo, $partyId, $date, $particulars, '', 0, $amount);
    }
    return ['ok' => true, 'party_id' => $partyId, 'name' => $row['name'], 'type' => $type, 'amount' => $amount];
}

function assistant_tool_sales(PDO $pdo): array
{
    try {
        $stmt = $pdo->query(
            'SELECT id, invoice_no, customer_name, total, received, sale_date, created_at
             FROM sales ORDER BY id DESC LIMIT 15'
        );
    } catch (Throwable $e) {
        $stmt = $pdo->query(
            'SELECT id, invoice_no, customer_name, total, created_at FROM sales ORDER BY id DESC LIMIT 15'
        );
    }
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['date'] = format_display_date($row['sale_date'] ?? $row['created_at'] ?? '');
        $rows[] = $row;
    }
    return ['sales' => $rows];
}

function assistant_tool_purchases(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, supplier_name, invoice_no, purchase_date, total, paid FROM purchases ORDER BY id DESC LIMIT 15'
    );
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['date'] = format_display_date($row['purchase_date'] ?? '');
        $rows[] = $row;
    }
    return ['purchases' => $rows];
}

function http_post_json(string $url, array $headers, array $payload, int $timeout = 120): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new RuntimeException('Request encode nahi hua');
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        $hdr = [];
        foreach ($headers as $k => $v) {
            $hdr[] = is_int($k) ? (string) $v : $k . ': ' . $v;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $hdr,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('OpenRouter connect fail: ' . $err);
        }
        $decoded = json_decode((string) $raw, true);
        if ($code >= 400) {
            $msg = is_array($decoded) ? (string) ($decoded['error']['message'] ?? $decoded['error'] ?? $raw) : (string) $raw;
            throw new RuntimeException('OpenRouter: ' . $msg);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenRouter ne galat jawab diya');
        }
        return $decoded;
    }

    $headerLines = '';
    foreach ($headers as $k => $v) {
        $headerLines .= (is_int($k) ? (string) $v : $k . ': ' . $v) . "\r\n";
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => $headerLines . "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
    ]);
    $raw = file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException('OpenRouter connect fail');
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('OpenRouter ne galat jawab diya');
    }
    if (isset($decoded['error'])) {
        throw new RuntimeException('OpenRouter: ' . (string) ($decoded['error']['message'] ?? json_encode($decoded['error'])));
    }
    return $decoded;
}

function openrouter_complete(array $messages, bool $withTools = true): array
{
    $cfg = openrouter_config();
    if ($cfg['api_key'] === '') {
        throw new RuntimeException('OpenRouter API key nahi mili. Assistant page pe key save karo.');
    }
    $payload = [
        'model' => $cfg['model'],
        'messages' => $messages,
        'temperature' => 0.2,
        'max_tokens' => $cfg['max_tokens'],
    ];
    if ($withTools) {
        $payload['tools'] = assistant_tool_schemas();
        $payload['tool_choice'] = 'auto';
    }
    return http_post_json(
        'https://openrouter.ai/api/v1/chat/completions',
        [
            'Authorization: Bearer ' . $cfg['api_key'],
            'Content-Type: application/json',
            'HTTP-Referer: https://github.com/ssunny00091-rgb/hardware-erp',
            'X-Title: Hardware ERP Assistant',
        ],
        $payload
    );
}

function assistant_text_from_message(mixed $content): string
{
    if (is_string($content)) {
        return trim($content);
    }
    if (!is_array($content)) {
        return '';
    }
    $bits = [];
    foreach ($content as $part) {
        if (is_string($part)) {
            $bits[] = $part;
        } elseif (is_array($part) && ($part['type'] ?? '') === 'text') {
            $bits[] = (string) ($part['text'] ?? '');
        }
    }
    return trim(implode("\n", $bits));
}

function assistant_normalize_history(array $history): array
{
    $out = [];
    foreach ($history as $msg) {
        if (!is_array($msg)) {
            continue;
        }
        $role = (string) ($msg['role'] ?? '');
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        $text = assistant_text_from_message($msg['content'] ?? '');
        if ($text === '') {
            continue;
        }
        $out[] = ['role' => $role, 'content' => $text];
    }
    if (count($out) > 16) {
        $out = array_slice($out, -16);
    }
    return $out;
}

function assistant_file_part(string $mime, string $filename, string $binary): ?array
{
    $mime = strtolower($mime);
    $b64 = base64_encode($binary);
    if (str_contains($mime, 'pdf') || str_ends_with(strtolower($filename), '.pdf')) {
        return [
            'type' => 'file',
            'file' => [
                'filename' => $filename !== '' ? $filename : 'bill.pdf',
                'file_data' => 'data:application/pdf;base64,' . $b64,
            ],
        ];
    }
    if (!preg_match('#^image/(jpeg|jpg|png|webp|gif)#', $mime) && !preg_match('/\.(jpe?g|png|webp|gif)$/i', $filename)) {
        $mime = 'image/jpeg';
    }
    if ($mime === 'image/jpg') {
        $mime = 'image/jpeg';
    }
    return [
        'type' => 'image_url',
        'image_url' => [
            'url' => 'data:' . $mime . ';base64,' . $b64,
        ],
    ];
}

function assistant_collect_uploads(): array
{
    $parts = [];
    if (!empty($_FILES['files'])) {
        $bucket = $_FILES['files'];
        $count = is_array($bucket['name']) ? count($bucket['name']) : 1;
        for ($i = 0; $i < $count && $i < 6; $i++) {
            $err = is_array($bucket['error']) ? (int) $bucket['error'][$i] : (int) $bucket['error'];
            if ($err !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = is_array($bucket['tmp_name']) ? (string) $bucket['tmp_name'][$i] : (string) $bucket['tmp_name'];
            $name = is_array($bucket['name']) ? (string) $bucket['name'][$i] : (string) $bucket['name'];
            $mime = is_array($bucket['type']) ? (string) $bucket['type'][$i] : (string) $bucket['type'];
            $size = is_array($bucket['size']) ? (int) $bucket['size'][$i] : (int) $bucket['size'];
            if ($size > 8 * 1024 * 1024 || !is_readable($tmp)) {
                continue;
            }
            $bin = file_get_contents($tmp);
            if ($bin === false || $bin === '') {
                continue;
            }
            $part = assistant_file_part($mime, $name, $bin);
            if ($part) {
                $parts[] = $part;
            }
        }
    }
    return $parts;
}

function assistant_chat(PDO $pdo, string $userText, array $history, array $fileParts): array
{
    $userText = trim($userText);
    if ($userText === '' && $fileParts === []) {
        throw new InvalidArgumentException('Message ya photo bhejo');
    }
    if ($userText === '' && $fileParts !== []) {
        $userText = 'Is photo/PDF se supplier aur unka bill automatically add karo. Jo dikh raha hai usko save karo.';
    }

    $messages = [['role' => 'system', 'content' => assistant_system_prompt()]];
    foreach (assistant_normalize_history($history) as $msg) {
        $messages[] = $msg;
    }

    $content = [['type' => 'text', 'text' => $userText]];
    foreach ($fileParts as $part) {
        $content[] = $part;
    }
    $messages[] = ['role' => 'user', 'content' => $content];

    $actions = [];
    $reply = '';
    for ($i = 0; $i < 8; $i++) {
        $resp = openrouter_complete($messages, true);
        $choice = $resp['choices'][0]['message'] ?? [];
        if (!is_array($choice)) {
            throw new RuntimeException('Model ne khali jawab diya');
        }
        $messages[] = $choice;
        $toolCalls = $choice['tool_calls'] ?? [];
        if (!is_array($toolCalls) || $toolCalls === []) {
            $reply = assistant_text_from_message($choice['content'] ?? '');
            break;
        }
        foreach ($toolCalls as $call) {
            $fn = (string) ($call['function']['name'] ?? '');
            $args = assistant_decode_args($call['function']['arguments'] ?? '{}');
            $result = $fn !== '' ? assistant_run_tool($pdo, $fn, $args) : ['error' => 'empty tool'];
            $actions[] = [
                'tool' => $fn,
                'ok' => empty($result['error']),
                'result' => $result,
            ];
            $messages[] = [
                'role' => 'tool',
                'tool_call_id' => (string) ($call['id'] ?? ('call_' . $i)),
                'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
            ];
        }
    }
    if ($reply === '' && $actions) {
        $reply = 'Kaam ho gaya.';
    }
    return ['reply' => $reply, 'actions' => $actions];
}
