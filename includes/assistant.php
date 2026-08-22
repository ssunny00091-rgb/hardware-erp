<?php

declare(strict_types=1);

require_once __DIR__ . '/reports.php';

function openrouter_pick(string $name, string $fallback = ''): string
{
    $fromEnv = (string) (getenv($name) ?: ($_ENV[$name] ?? $_SERVER[$name] ?? ''));
    if (trim($fromEnv) !== '') {
        return $fromEnv;
    }
    $settings = read_app_settings();
    $fromFile = (string) ($settings[$name] ?? '');
    if (trim($fromFile) !== '') {
        return $fromFile;
    }
    try {
        $pdo = db();
        $st = $pdo->prepare('SELECT setting_value FROM app_settings WHERE setting_key = :k LIMIT 1');
        $st->execute(['k' => $name]);
        $v = $st->fetchColumn();
        if (is_string($v) && trim($v) !== '') {
            return $v;
        }
    } catch (Throwable $e) {
        // MySQL optional for key storage.
    }
    return $fallback;
}

function openrouter_config(): array
{
    $key = normalize_openrouter_key(openrouter_pick('OPENROUTER_API_KEY'));
    $model = trim(openrouter_pick('OPENROUTER_MODEL', 'google/gemini-2.5-flash'));
    $maxTokens = (int) openrouter_pick('OPENROUTER_MAX_TOKENS', '4000');
    if ($maxTokens < 256) {
        $maxTokens = 256;
    }
    if ($maxTokens > 16000) {
        $maxTokens = 16000;
    }
    return [
        'api_key' => $key,
        'model' => $model !== '' ? $model : 'google/gemini-2.5-flash',
        'max_tokens' => $maxTokens,
        'env_path' => APP_ROOT . DIRECTORY_SEPARATOR . '.env',
        'settings_path' => settings_json_path(),
    ];
}

function persist_openrouter_key(string $key, string $model = ''): array
{
    $key = normalize_openrouter_key($key);
    $values = ['OPENROUTER_API_KEY' => $key];
    if ($model !== '') {
        $values['OPENROUTER_MODEL'] = $model;
    }
    $savedTo = [];
    $errors = [];

    try {
        write_app_settings($values);
        $savedTo[] = 'data/app-settings.json';
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    try {
        save_env_file($values);
        $savedTo[] = '.env';
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    try {
        $pdo = db();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_settings (
                setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB'
        );
        $st = $pdo->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($values as $k => $v) {
            $st->execute(['k' => $k, 'v' => $v]);
        }
        $savedTo[] = 'mysql';
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    foreach ($values as $k => $v) {
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }

    if ($savedTo === []) {
        throw new RuntimeException(
            'Key kahin save nahi hui. Folder writable karo: ' . APP_ROOT
            . (count($errors) ? ' — ' . implode(' | ', $errors) : '')
        );
    }

    return [
        'ok' => true,
        'saved_to' => $savedTo,
        'errors' => $errors,
        'settings_path' => settings_json_path(),
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

When the user sends photo(s), screenshot, scan, or PDF of a supplier bill / invoice / challan / quotation / WhatsApp bill / handwritten bill (any format):
1. Read all visible text even if rotated, blurry, or mixed Hindi/English.
2. If there are 2 or more images/pages, they are pages of THE SAME bill unless the user says otherwise. Merge every page: one supplier, one invoice number, one date, ALL line items from all pages, one grand total (usually on the last page), one paid amount. Call import_supplier_bill ONCE only.
3. Extract supplier name, mobile, address, GST, invoice number, bill date, line items (name, qty, unit, rate), grand total, amount paid.
4. Immediately call import_supplier_bill. Do not ask for confirmation.
5. If line items are unreadable but supplier name + total are visible, still import using total.
6. If supplier name is readable but items are not, still call add_or_update_party for the supplier.

When user asks for ledger / hisaab / khata / "kitna lena dena":
1. Call get_ledger with the spoken name even if spelling looks wrong.
2. Always mention nearby similar names from the tool (match_score / nearby).
3. If one party is chosen, read dates (dd/mm/yyyy), debit, credit, running balance in ₹.
4. Do not create a new party just to view ledger.

Voice/text commands you should automate:
- New sale / bill banana (customer, items, qty, rate, paid/due, date, painter/plumber ref)
- Product add/update, stock poochna
- Purchase / supplier bill
- Ledger dikhana (spelling galat ho to bhi paas ke naam)
- Ledger receipt/payment
- Dashboard totals, recent sales/purchases
- Profit, sabse zyada / kam sale products — din, mahina, saal (get_profit_report)
- Kisi invoice / bill ka POORA detail (invoice number 11, bill no, sale id). get_invoice_detail call karo. Sirf total mat bolo. Customer, date, har item (qty, rate, amount), received, due, aur tool ke links (view / print-download / edit) mention karo.

When the user asks for an invoice or bill by number (e.g. "invoice 11", "invoice number 11 ka detail", "11 ka bill"):
1. Call get_invoice_detail with that number. Kind sale unless they said purchase/supplier.
2. Show full line items in a markdown table.
3. Tell them they can open, download/print, or edit using the links from the tool.

Dates from users may be dd/mm/yyyy. Pass them as-is to tools.
Amounts are Indian rupees.
After tools, summarise what was saved: names, invoice nos, ₹ amounts, due, and next step if any.

TABLE FORMAT (required):
- Any list, comparison, ledger, stock, bill, dashboard, or more than one number/name MUST be a GitHub markdown table.
- First one short Hindi line, then the table. Example:
| Date | Particulars | Debit | Credit | Balance |
| --- | --- | --- | --- | --- |
| 21/08/2026 | Sale INV-1 | 1500 | 0 | 1500 |
- Use columns that fit the question. Never answer such data as a long paragraph or bullet dump.
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
                'name' => 'get_profit_report',
                'description' => 'Profit, sales, cost, top-selling and slow-selling products for a day, month, year, or date range',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string', 'enum' => ['day', 'month', 'year', 'range']],
                        'date' => ['type' => 'string', 'description' => 'Day date dd/mm/yyyy'],
                        'month' => ['type' => 'string', 'description' => 'YYYY-MM'],
                        'year' => ['type' => 'integer'],
                        'from' => ['type' => 'string'],
                        'to' => ['type' => 'string'],
                    ],
                ],
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
                'description' => 'Fuzzy search people in ledger. Works with spelling mistakes and returns nearby similar names.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'painter', 'plumber', 'electrician']],
                        'q' => ['type' => 'string', 'description' => 'Name or mobile as spoken, even if misspelled'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_ledger',
                'description' => 'Show one party ledger (entries, debit, credit, balance). Use spoken name with typos; returns nearby matches too.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'party_id' => ['type' => 'integer'],
                        'name' => ['type' => 'string', 'description' => 'Spoken name, spelling can be wrong'],
                        'type' => ['type' => 'string', 'enum' => ['customer', 'supplier', 'painter', 'plumber', 'electrician']],
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
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_invoice_detail',
                'description' => 'Full sale or purchase bill: customer/supplier, date, every line item, totals, due, and page links to view, print/download, and edit. Use when user gives an invoice number like 11.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'invoice_no' => ['type' => 'string', 'description' => 'Invoice/bill number as spoken, e.g. 11'],
                        'sale_id' => ['type' => 'integer'],
                        'purchase_id' => ['type' => 'integer'],
                        'kind' => ['type' => 'string', 'enum' => ['sale', 'purchase', 'any']],
                    ],
                ],
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
            'get_profit_report' => reports_build($pdo, $args),
            'search_products' => assistant_tool_search_products($pdo, $args),
            'save_product' => assistant_tool_save_product($pdo, $args),
            'search_parties' => assistant_tool_search_parties($pdo, $args),
            'get_ledger' => assistant_tool_get_ledger($pdo, $args),
            'add_or_update_party' => assistant_tool_party($pdo, $args),
            'import_supplier_bill' => assistant_tool_import_bill($pdo, $args),
            'create_sale' => assistant_tool_create_sale($pdo, $args),
            'record_purchase_payment' => assistant_tool_purchase_pay($pdo, $args),
            'record_ledger_payment' => assistant_tool_ledger_pay($pdo, $args),
            'list_recent_sales' => assistant_tool_sales($pdo),
            'list_recent_purchases' => assistant_tool_purchases($pdo),
            'get_invoice_detail' => assistant_tool_get_invoice($pdo, $args),
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
    $q = trim((string) ($args['q'] ?? $args['name'] ?? ''));
    $parties = search_parties_fuzzy($pdo, $q, $type, 12);
    return [
        'query' => $q,
        'parties' => $parties,
        'note' => $parties === []
            ? 'Koi naam nahi mila'
            : 'Spelling galat ho to bhi paas ke naam yahan hain. Sabse upar sabse close match hai.',
    ];
}

function assistant_pick_party(array $matches): array
{
    if ($matches === []) {
        return ['party' => null, 'confident' => false, 'nearby' => []];
    }
    $best = $matches[0];
    $second = (int) ($matches[1]['match_score'] ?? 0);
    $score = (int) ($best['match_score'] ?? 0);
    if ($score < 40) {
        return ['party' => null, 'confident' => false, 'nearby' => $matches];
    }
    $confident = $score >= 700 && ($score - $second) >= 40;
    if (count($matches) === 1 && $score >= 40) {
        $confident = true;
    }
    return [
        'party' => $best,
        'confident' => $confident,
        'nearby' => $matches,
    ];
}

function assistant_tool_get_ledger(PDO $pdo, array $args): array
{
    $partyId = (int) ($args['party_id'] ?? 0);
    $type = strtolower(trim((string) ($args['type'] ?? '')));
    $name = trim((string) ($args['name'] ?? $args['q'] ?? ''));
    $nearby = [];

    if ($partyId <= 0) {
        if ($name === '') {
            return ['error' => 'Naam ya party_id bhejo'];
        }
        $nearby = search_parties_fuzzy($pdo, $name, $type, 12);
        if ($nearby === [] || (int) ($nearby[0]['match_score'] ?? 0) < 50) {
            $all = search_parties_fuzzy($pdo, $name, '', 12);
            if ($all !== []) {
                $nearby = $all;
            }
        }
        $pick = assistant_pick_party($nearby);
        if (!$pick['party']) {
            return [
                'found' => false,
                'query' => $name,
                'nearby' => $nearby,
                'note' => 'Exact naam nahi mila. Spelling ke aaspaas ye log hain — inme se sahi naam bolo.',
            ];
        }
        if (!$pick['confident']) {
            return [
                'found' => false,
                'need_pick' => true,
                'query' => $name,
                'nearby' => $nearby,
                'note' => 'Spelling ke aaspaas ye log mile. Inme se sahi naam batao, phir poora ledger dikhaunga.',
            ];
        }
        $partyId = (int) $pick['party']['id'];
        $nearby = $pick['nearby'];
    } else {
        $partyRow = $pdo->prepare('SELECT name, type FROM parties WHERE id = :id');
        $partyRow->execute(['id' => $partyId]);
        $got = $partyRow->fetch();
        if ($got) {
            $nearby = search_parties_fuzzy($pdo, (string) $got['name'], (string) $got['type'], 8);
        }
    }

    $ledger = load_party_ledger($pdo, $partyId);
    if (!$ledger) {
        return ['error' => 'Party not found', 'nearby' => $nearby];
    }
    $others = array_values(array_filter(
        $nearby,
        static fn (array $row): bool => (int) $row['id'] !== $partyId
    ));
    return [
        'found' => true,
        'query' => $name,
        'party' => $ledger['party'],
        'debit' => $ledger['debit'],
        'credit' => $ledger['credit'],
        'balance' => $ledger['balance'],
        'entries' => $ledger['entries'],
        'nearby' => array_slice($others, 0, 8),
        'note' => $others
            ? 'Yeh ledger open hai. Spelling ke aaspaas aur ye naam bhi hain.'
            : 'Yeh ledger open hai.',
    ];
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
        $type = strtolower(trim((string) ($args['type'] ?? '')));
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'party_id or name required'];
        }
        $matches = search_parties_fuzzy($pdo, $name, $type, 12);
        if ($matches === [] && $type !== '') {
            $matches = search_parties_fuzzy($pdo, $name, '', 12);
        }
        $pick = assistant_pick_party($matches);
        if (!$pick['confident'] || !$pick['party']) {
            return [
                'error' => 'Sahi naam confirm karo. Naya party nahi banaya.',
                'nearby' => $matches,
            ];
        }
        $partyId = (int) $pick['party']['id'];
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

function assistant_bill_actions_sale(int $id): array
{
    return [
        ['label' => 'Bill kholo', 'href' => app_url('invoice.php?id=' . $id), 'target' => '_blank', 'kind' => 'view'],
        ['label' => 'Download / Print', 'href' => app_url('invoice.php?id=' . $id . '&print=1'), 'target' => '_blank', 'kind' => 'download'],
        ['label' => 'Edit bill', 'href' => app_url('index.php?edit=' . $id), 'kind' => 'edit'],
    ];
}

function assistant_bill_actions_purchase(int $id): array
{
    return [
        ['label' => 'Bill kholo', 'href' => app_url('purchase-bill.php?id=' . $id), 'target' => '_blank', 'kind' => 'view'],
        ['label' => 'Download / Print', 'href' => app_url('purchase-bill.php?id=' . $id . '&print=1'), 'target' => '_blank', 'kind' => 'download'],
        ['label' => 'Edit bill', 'href' => app_url('purchase.php?edit=' . $id), 'kind' => 'edit'],
    ];
}

function assistant_format_sale_detail(array $sale): array
{
    $id = (int) ($sale['id'] ?? 0);
    $products = is_array($sale['products'] ?? null) ? $sale['products'] : [];
    $items = [];
    foreach ($products as $p) {
        if (!is_array($p)) {
            continue;
        }
        $items[] = [
            'name' => (string) ($p['name'] ?? $p['product_name'] ?? ''),
            'color' => (string) ($p['color'] ?? $p['color_code'] ?? ''),
            'qty' => $p['qty'] ?? '',
            'unit' => (string) ($p['unit'] ?? 'Piece'),
            'price' => (float) ($p['price'] ?? 0),
            'total' => (float) ($p['total'] ?? ((float) ($p['qty'] ?? 0) * (float) ($p['price'] ?? 0))),
        ];
    }
    return [
        'ok' => true,
        'kind' => 'sale',
        'id' => $id,
        'invoice_no' => (string) ($sale['invoice_no'] ?? ''),
        'customer_name' => (string) ($sale['customer_name'] ?? ''),
        'mobile' => (string) ($sale['mobile'] ?? ''),
        'address' => (string) ($sale['address'] ?? ''),
        'gst' => (string) ($sale['gst'] ?? ''),
        'ref_type' => (string) ($sale['ref_type'] ?? ''),
        'ref_name' => (string) ($sale['ref_name'] ?? ''),
        'date' => (string) ($sale['date'] ?? format_display_date($sale['sale_date'] ?? $sale['created_at'] ?? '')),
        'total' => (float) ($sale['total'] ?? 0),
        'received' => (float) ($sale['received'] ?? 0),
        'due' => (float) ($sale['due'] ?? 0),
        'products' => $items,
        'actions' => assistant_bill_actions_sale($id),
    ];
}

function assistant_load_purchase_full(PDO $pdo, int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }
    $items = $pdo->prepare(
        'SELECT product_id, product_name AS name, qty, unit, price, total
         FROM purchase_items WHERE purchase_id = :id ORDER BY id ASC'
    );
    $items->execute(['id' => $id]);
    $row['products'] = $items->fetchAll() ?: [];
    $paid = (float) ($row['paid'] ?? 0);
    $row['due'] = max(0, (float) $row['total'] - $paid);
    $row['date'] = format_display_date($row['purchase_date'] ?? '');
    $row['actions'] = assistant_bill_actions_purchase($id);
    return $row;
}

function assistant_tool_get_invoice(PDO $pdo, array $args): array
{
    $kind = strtolower(trim((string) ($args['kind'] ?? 'any')));
    if (!in_array($kind, ['sale', 'purchase', 'any'], true)) {
        $kind = 'any';
    }
    $saleId = (int) ($args['sale_id'] ?? 0);
    $purchaseId = (int) ($args['purchase_id'] ?? 0);
    $invoice = trim((string) ($args['invoice_no'] ?? $args['number'] ?? $args['q'] ?? ''));

    if ($saleId > 0 && $kind !== 'purchase') {
        $sale = load_sale_full($pdo, $saleId);
        return $sale ? assistant_format_sale_detail($sale) : ['error' => 'Sale bill nahi mili'];
    }
    if ($purchaseId > 0 && $kind !== 'sale') {
        $purchase = assistant_load_purchase_full($pdo, $purchaseId);
        return $purchase
            ? (['ok' => true, 'kind' => 'purchase'] + $purchase)
            : ['error' => 'Purchase bill nahi mili'];
    }

    if ($invoice === '') {
        return ['error' => 'Invoice number batao'];
    }

    if ($kind !== 'purchase') {
        $matches = find_sales_by_invoice_query($pdo, $invoice);
        if (count($matches) === 1) {
            $sale = load_sale_full($pdo, (int) $matches[0]['id']);
            return $sale ? assistant_format_sale_detail($sale) : ['error' => 'Sale bill nahi mili'];
        }
        if (count($matches) > 1) {
            return [
                'found' => false,
                'need_pick' => true,
                'query' => $invoice,
                'matches' => $matches,
                'note' => 'Ek se zyada sale mili. Invoice number confirm karo.',
            ];
        }
        if ($kind === 'sale') {
            return ['error' => 'Sale invoice nahi mili: ' . $invoice];
        }
    }

    $q = preg_replace('/^(invoice|inv|bill|no|#)\s*/i', '', $invoice) ?? $invoice;
    $q = trim((string) $q, " \t#");
    $purchaseMatches = [];
    $addP = static function (array $row) use (&$purchaseMatches): void {
        $id = (int) ($row['id'] ?? 0);
        if ($id > 0) {
            $purchaseMatches[$id] = $row;
        }
    };
    $st = $pdo->prepare('SELECT id, invoice_no, supplier_name FROM purchases WHERE invoice_no = :q LIMIT 10');
    $st->execute(['q' => $q]);
    foreach ($st as $row) {
        $addP($row);
    }
    if (preg_match('/^[0-9]+$/', $q)) {
        $byId = $pdo->prepare('SELECT id, invoice_no, supplier_name FROM purchases WHERE id = :id LIMIT 1');
        $byId->execute(['id' => (int) $q]);
        $row = $byId->fetch();
        if ($row) {
            $addP($row);
        }
    }
    $plist = array_values($purchaseMatches);
    if (count($plist) === 1) {
        $purchase = assistant_load_purchase_full($pdo, (int) $plist[0]['id']);
        return $purchase
            ? (['ok' => true, 'kind' => 'purchase'] + $purchase)
            : ['error' => 'Purchase bill nahi mili'];
    }
    if (count($plist) > 1) {
        return [
            'found' => false,
            'need_pick' => true,
            'query' => $invoice,
            'matches' => $plist,
            'note' => 'Ek se zyada supplier bill mili.',
        ];
    }

    return ['error' => 'Is number ki koi bill nahi mili: ' . $invoice];
}

function assistant_invoice_query_from_text(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    $kind = 'any';
    if (preg_match('/purchase|supplier|kharid/i', $text)) {
        $kind = 'purchase';
    } elseif (preg_match('/sale|customer|invoice|inv\b/i', $text)) {
        $kind = 'sale';
    }
    if (preg_match('/\b(?:invoice|inv|bill)\s*(?:number|no\.?|#)?\s*([A-Za-z0-9\/-]{1,20})\b/i', $text, $m)) {
        if (preg_match('/[0-9]/', $m[1])) {
            return ['invoice_no' => $m[1], 'kind' => $kind];
        }
    }
    if (preg_match('/\b([0-9]{1,8})\s*(?:ka|ki|ke)\s*(?:bill|invoice|inv)/i', $text, $m)) {
        return ['invoice_no' => $m[1], 'kind' => $kind];
    }
    return null;
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

function openrouter_error_hindi(int $code, string $rawMsg): string
{
    $msg = trim($rawMsg);
    $low = strtolower($msg);
    if ($code === 401 || str_contains($low, 'user not found') || str_contains($low, 'invalid api') || str_contains($low, 'unauthorized') || str_contains($low, 'no cookie') || str_contains($low, 'missing authentication')) {
        return 'OpenRouter key galat / expire hai. openrouter.ai/keys se naya key copy karo — sirf sk-or-v1- se start hona chahiye (ChatGPT/OpenAI key nahi chalega). Quotes ke bina paste karo.';
    }
    if ($code === 402 || str_contains($low, 'insufficient') || str_contains($low, 'credits') || str_contains($low, 'payment required')) {
        return 'OpenRouter account mein credits khatam hain. openrouter.ai/settings/credits pe credits add karo, phir try karo.';
    }
    if ($code === 403 || str_contains($low, 'forbidden')) {
        return 'Is OpenRouter key ko is model ki permission nahi hai. Dusra model try karo, ya key permissions check karo.';
    }
    if ($code === 429 || str_contains($low, 'rate limit')) {
        return 'OpenRouter rate limit. 20 second baad phir Send dabao.';
    }
    if ($code === 404 || str_contains($low, 'not a valid model') || str_contains($low, 'no endpoints')) {
        return 'Model ID galat hai. google/gemini-2.5-flash likho, Save key, phir try.';
    }
    if ($msg === '') {
        return 'OpenRouter error (HTTP ' . $code . '). Internet aur key check karo.';
    }
    return 'OpenRouter: ' . $msg;
}

function openrouter_headers(?string $apiKey = null): array
{
    $key = $apiKey !== null ? normalize_openrouter_key($apiKey) : openrouter_config()['api_key'];
    return [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'HTTP-Referer: https://github.com/ssunny00091-rgb/hardware-erp',
        'X-Title: Hardware ERP Assistant',
        'X-OpenRouter-Title: Hardware ERP Assistant',
    ];
}

function openrouter_ca_file(): string
{
    $candidates = [
        APP_ROOT . '/config/cacert.pem',
        (string) ini_get('curl.cainfo'),
        (string) ini_get('openssl.cafile'),
    ];
    foreach ($candidates as $path) {
        if ($path !== '' && is_readable($path)) {
            return $path;
        }
    }
    return '';
}

function openrouter_is_ssl_error(string $err): bool
{
    $err = strtolower($err);
    return str_contains($err, 'ssl')
        || str_contains($err, 'certificate')
        || str_contains($err, 'cert')
        || str_contains($err, 'cainfo')
        || str_contains($err, 'ca bundle');
}

function openrouter_http(string $method, string $url, ?array $payload = null, ?string $apiKey = null, int $timeout = 120): array
{
    $body = null;
    if ($payload !== null) {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            throw new RuntimeException('Request encode nahi hua');
        }
    }
    $headers = openrouter_headers($apiKey);

    if (function_exists('curl_init')) {
        $attempt = static function (bool $insecure) use ($method, $url, $headers, $body, $timeout): array {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 25,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            ];
            if (defined('CURL_IPRESOLVE_V4')) {
                $opts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            }
            if ($body !== null) {
                $opts[CURLOPT_POSTFIELDS] = $body;
            }
            $ca = openrouter_ca_file();
            if ($insecure) {
                $opts[CURLOPT_SSL_VERIFYPEER] = false;
                $opts[CURLOPT_SSL_VERIFYHOST] = 0;
            } elseif ($ca !== '') {
                $opts[CURLOPT_CAINFO] = $ca;
                $opts[CURLOPT_SSL_VERIFYPEER] = true;
            }
            curl_setopt_array($ch, $opts);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            return ['raw' => $raw, 'code' => $code, 'err' => $err];
        };

        $res = $attempt(false);
        if ($res['raw'] === false && openrouter_is_ssl_error((string) $res['err'])) {
            $res = $attempt(true);
        }
        if ($res['raw'] === false) {
            $err = trim((string) $res['err']);
            if (openrouter_is_ssl_error($err)) {
                throw new RuntimeException('OpenRouter SSL fail. XAMPP mein internet/SSL issue hai. Apache restart karke phir try karo. Detail: ' . $err);
            }
            if ($err === '') {
                throw new RuntimeException('OpenRouter connect fail. Internet check karo, firewall mein openrouter.ai allow karo.');
            }
            throw new RuntimeException('OpenRouter connect fail: ' . $err);
        }
        $decoded = json_decode((string) $res['raw'], true);
        $code = (int) $res['code'];
        if ($code >= 400) {
            $msg = '';
            if (is_array($decoded)) {
                $err = $decoded['error'] ?? null;
                if (is_array($err)) {
                    $msg = (string) ($err['message'] ?? json_encode($err));
                } elseif (is_string($err)) {
                    $msg = $err;
                } else {
                    $msg = (string) $res['raw'];
                }
            } else {
                $msg = (string) $res['raw'];
            }
            throw new RuntimeException(openrouter_error_hindi($code, $msg));
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('OpenRouter ne galat jawab diya');
        }
        return $decoded;
    }

    if (!ini_get('allow_url_fopen')) {
        throw new RuntimeException('PHP curl band hai. XAMPP php.ini mein extension=curl uncomment karo, Apache restart.');
    }
    $ca = openrouter_ca_file();
    $ssl = ['verify_peer' => true, 'verify_peer_name' => true];
    if ($ca !== '') {
        $ssl['cafile'] = $ca;
    }
    $ctx = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'timeout' => $timeout,
            'ignore_errors' => true,
        ],
        'ssl' => $ssl,
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        throw new RuntimeException('OpenRouter connect fail. php.ini mein extension=curl on karo (XAMPP).');
    }
    $decoded = json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['error'])) {
        $err = $decoded['error'];
        $msg = is_array($err) ? (string) ($err['message'] ?? json_encode($err)) : (string) $err;
        throw new RuntimeException(openrouter_error_hindi(0, $msg));
    }
    if (!is_array($decoded)) {
        throw new RuntimeException('OpenRouter ne galat jawab diya');
    }
    return $decoded;
}

function openrouter_test_key(string $apiKey): array
{
    $apiKey = normalize_openrouter_key($apiKey);
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Key khali hai'];
    }
    if (!str_starts_with($apiKey, 'sk-or-')) {
        return [
            'ok' => false,
            'error' => 'Yeh OpenRouter key nahi lagti. sk-or-v1- se start honi chahiye. OpenAI/ChatGPT key yahan nahi chalti.',
        ];
    }
    try {
        $data = openrouter_http('GET', 'https://openrouter.ai/api/v1/key', null, $apiKey, 40);
        $info = is_array($data['data'] ?? null) ? $data['data'] : $data;
        $remaining = $info['limit_remaining'] ?? $info['limitRemaining'] ?? null;
        if ($remaining !== null && is_numeric($remaining) && (float) $remaining <= 0) {
            return [
                'ok' => false,
                'error' => 'Key sahi hai lekin credits 0 hain. openrouter.ai/settings/credits pe add karo.',
                'info' => $info,
            ];
        }
        return ['ok' => true, 'info' => $info];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function openrouter_complete(array $messages, bool $withTools = true): array
{
    $cfg = openrouter_config();
    if ($cfg['api_key'] === '') {
        throw new RuntimeException('OpenRouter API key nahi mili. Assistant page pe key save karo.');
    }
    $primary = $cfg['model'];
    $fallbacks = array_values(array_unique(array_filter([
        $primary,
        'google/gemini-2.5-flash',
        'google/gemini-2.0-flash-001',
        'openai/gpt-4o-mini',
    ])));
    $payload = [
        'model' => $primary,
        'models' => $fallbacks,
        'route' => 'fallback',
        'messages' => $messages,
        'temperature' => 0.2,
        'max_tokens' => max(1024, $cfg['max_tokens']),
        'reasoning' => ['effort' => 'none'],
    ];
    if ($withTools) {
        $payload['tools'] = assistant_tool_schemas();
        $payload['tool_choice'] = 'auto';
    }
    try {
        return openrouter_http(
            'POST',
            'https://openrouter.ai/api/v1/chat/completions',
            $payload,
            $cfg['api_key']
        );
    } catch (Throwable $e) {
        $msg = strtolower($e->getMessage());
        if (isset($payload['reasoning']) && (str_contains($msg, 'reasoning') || str_contains($msg, 'unknown'))) {
            unset($payload['reasoning']);
            return openrouter_http(
                'POST',
                'https://openrouter.ai/api/v1/chat/completions',
                $payload,
                $cfg['api_key']
            );
        }
        throw $e;
    }
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
        for ($i = 0; $i < $count && $i < 12; $i++) {
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
        $n = count($fileParts);
        $userText = $n > 1
            ? ('Yeh ek hi supplier bill ki ' . $n . ' pages/photos hain. Page 1 pehli image, phir page 2, 3... Saari pages padhkar EK hi bill mein merge karke import_supplier_bill ek baar call karo. Saari items, ek total, ek supplier.')
            : 'Is photo/PDF se supplier aur unka bill automatically add karo. Jo dikh raha hai usko save karo.';
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
    $asked = assistant_invoice_query_from_text($userText);
    if ($asked) {
        $already = false;
        foreach ($actions as $a) {
            if (($a['tool'] ?? '') === 'get_invoice_detail' && empty($a['result']['error'])) {
                $already = true;
                break;
            }
        }
        if (!$already) {
            $result = assistant_tool_get_invoice($pdo, $asked);
            $actions[] = [
                'tool' => 'get_invoice_detail',
                'ok' => empty($result['error']),
                'result' => $result,
            ];
        }
    }
    if ($reply === '' && $actions) {
        $reply = 'Kaam ho gaya.';
    }
    foreach ($actions as $a) {
        if (($a['tool'] ?? '') === 'get_invoice_detail' && empty($a['result']['error']) && empty($a['result']['need_pick'])) {
            if ($reply === '' || $reply === 'Kaam ho gaya.') {
                $no = (string) ($a['result']['invoice_no'] ?? '');
                $reply = 'Invoice ' . ($no !== '' ? $no . ' ' : '') . 'ka poora detail neeche hai. Bill kholo, download/print, ya edit kar sakte ho.';
            }
            break;
        }
    }
    return [
        'reply' => $reply,
        'actions' => $actions,
        'tables' => assistant_tables_from_actions($actions),
    ];
}

function assistant_table_pack(string $title, array $headers, array $rows, array $extra = []): ?array
{
    if ($rows === []) {
        return null;
    }
    return array_merge([
        'title' => $title,
        'headers' => $headers,
        'rows' => $rows,
    ], $extra);
}

function assistant_inr(mixed $value): string
{
    return '₹' . money((float) $value);
}

function assistant_tables_from_actions(array $actions): array
{
    $tables = [];
    $push = static function (?array $table) use (&$tables): void {
        if ($table) {
            $tables[] = $table;
        }
    };

    foreach ($actions as $action) {
        if (!is_array($action)) {
            continue;
        }
        $tool = (string) ($action['tool'] ?? '');
        $r = is_array($action['result'] ?? null) ? $action['result'] : [];
        if (!empty($r['error'])) {
            continue;
        }

        if ($tool === 'get_dashboard') {
            $push(assistant_table_pack('Dashboard', ['Item', 'Amount'], [
                ['Aaj ki sales', assistant_inr($r['today_sales'] ?? 0)],
                ['Aaj ki purchase', assistant_inr($r['today_purchase'] ?? 0)],
                ['Cash in hand', assistant_inr($r['cash_in_hand'] ?? 0)],
                ['Pending', assistant_inr($r['pending_payment'] ?? 0)],
            ]));
        }

        if ($tool === 'get_profit_report' && !empty($r['summary']) && is_array($r['summary'])) {
            $s = $r['summary'];
            $push(assistant_table_pack(
                'Profit — ' . (string) ($r['label'] ?? ''),
                ['Item', 'Amount'],
                [
                    ['Sale', assistant_inr($s['sales'] ?? 0)],
                    ['Cost', assistant_inr($s['cogs'] ?? 0)],
                    ['Profit', assistant_inr($s['profit'] ?? 0)],
                    ['Supplier kharid', assistant_inr($s['purchase_spend'] ?? 0)],
                    ['Bills', (string) ($s['bills'] ?? 0)],
                ]
            ));
            $topRows = [];
            foreach (($r['top_products'] ?? []) as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $topRows[] = [
                    (string) ($p['name'] ?? ''),
                    (string) ($p['qty'] ?? ''),
                    assistant_inr($p['amount'] ?? 0),
                    assistant_inr($p['profit'] ?? 0),
                ];
            }
            $push(assistant_table_pack('Sabse zyada sale', ['Product', 'Qty', 'Sale', 'Profit'], $topRows));
            $slowRows = [];
            foreach (($r['slow_products'] ?? []) as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $slowRows[] = [
                    (string) ($p['name'] ?? ''),
                    (string) ($p['qty'] ?? ''),
                    assistant_inr($p['amount'] ?? 0),
                    assistant_inr($p['profit'] ?? 0),
                ];
            }
            $push(assistant_table_pack('Kam sale', ['Product', 'Qty', 'Sale', 'Profit'], $slowRows));
            if (!empty($r['breakup']) && is_array($r['breakup'])) {
                $bRows = [];
                foreach ($r['breakup'] as $b) {
                    if (!is_array($b)) {
                        continue;
                    }
                    $bRows[] = [
                        (string) ($b['label'] ?? ''),
                        (string) ($b['bills'] ?? ''),
                        assistant_inr($b['sales'] ?? 0),
                        assistant_inr($b['profit'] ?? 0),
                    ];
                }
                $push(assistant_table_pack('Breakup', ['Period', 'Bills', 'Sale', 'Profit'], $bRows));
            }
        }

        if ($tool === 'search_products' && !empty($r['products']) && is_array($r['products'])) {
            $rows = [];
            foreach ($r['products'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $rows[] = [
                    (string) ($p['product_name'] ?? ''),
                    (string) ($p['brand'] ?? ''),
                    (string) ($p['unit'] ?? ''),
                    (string) ($p['stock'] ?? ''),
                    assistant_inr($p['selling_price'] ?? 0),
                    assistant_inr($p['purchase_price'] ?? 0),
                ];
            }
            $push(assistant_table_pack('Products / Stock', ['Name', 'Brand', 'Unit', 'Stock', 'Sale', 'Purchase'], $rows));
        }

        if (($tool === 'search_parties' || $tool === 'get_ledger') && !empty($r['parties']) && is_array($r['parties'])) {
            $rows = [];
            foreach ($r['parties'] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $rows[] = [
                    (string) ($p['name'] ?? ''),
                    (string) ($p['type'] ?? ''),
                    (string) ($p['mobile'] ?? ''),
                    (string) ($p['address'] ?? ''),
                ];
            }
            $push(assistant_table_pack('Paas ke naam', ['Name', 'Type', 'Mobile', 'Address'], $rows));
        }

        if ($tool === 'get_ledger') {
            if (!empty($r['nearby']) && is_array($r['nearby'])) {
                $rows = [];
                foreach ($r['nearby'] as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $rows[] = [
                        (string) ($p['name'] ?? ''),
                        (string) ($p['type'] ?? ''),
                        (string) ($p['mobile'] ?? ''),
                    ];
                }
                $push(assistant_table_pack('Spelling ke aaspaas', ['Name', 'Type', 'Mobile'], $rows));
            }
            if (!empty($r['party']) && is_array($r['party'])) {
                $push(assistant_table_pack(
                    (string) ($r['party']['name'] ?? 'Ledger') . ' — summary',
                    ['Debit', 'Credit', 'Balance'],
                    [[assistant_inr($r['debit'] ?? 0), assistant_inr($r['credit'] ?? 0), assistant_inr($r['balance'] ?? 0)]]
                ));
            }
            if (!empty($r['entries']) && is_array($r['entries'])) {
                $rows = [];
                foreach ($r['entries'] as $e) {
                    if (!is_array($e)) {
                        continue;
                    }
                    $rows[] = [
                        (string) ($e['date'] ?? $e['entry_date'] ?? ''),
                        (string) ($e['particulars'] ?? ''),
                        (string) ($e['ref_no'] ?? ''),
                        assistant_inr($e['debit'] ?? 0),
                        assistant_inr($e['credit'] ?? 0),
                        assistant_inr($e['balance'] ?? 0),
                    ];
                }
                $who = (string) (($r['party']['name'] ?? '') ?: 'Ledger');
                $push(assistant_table_pack($who . ' — hisaab', ['Date', 'Particulars', 'Ref', 'Debit', 'Credit', 'Balance'], $rows));
            }
        }

        if ($tool === 'list_recent_sales' && !empty($r['sales']) && is_array($r['sales'])) {
            $rows = [];
            foreach ($r['sales'] as $s) {
                if (!is_array($s)) {
                    continue;
                }
                $rows[] = [
                    (string) ($s['invoice_no'] ?? ''),
                    (string) ($s['customer_name'] ?? ''),
                    (string) ($s['date'] ?? ''),
                    assistant_inr($s['total'] ?? 0),
                    assistant_inr($s['received'] ?? $s['total'] ?? 0),
                ];
            }
            $push(assistant_table_pack('Recent sales', ['Invoice', 'Customer', 'Date', 'Total', 'Received'], $rows));
        }

        if ($tool === 'list_recent_purchases' && !empty($r['purchases']) && is_array($r['purchases'])) {
            $rows = [];
            foreach ($r['purchases'] as $s) {
                if (!is_array($s)) {
                    continue;
                }
                $rows[] = [
                    (string) ($s['invoice_no'] ?? ''),
                    (string) ($s['supplier_name'] ?? ''),
                    (string) ($s['date'] ?? $s['purchase_date'] ?? ''),
                    assistant_inr($s['total'] ?? 0),
                    assistant_inr($s['paid'] ?? 0),
                ];
            }
            $push(assistant_table_pack('Recent purchases', ['Bill', 'Supplier', 'Date', 'Total', 'Paid'], $rows));
        }

        if ($tool === 'import_supplier_bill' && !empty($r['id'])) {
            $push(assistant_table_pack('Supplier bill saved', ['Field', 'Value'], [
                ['Supplier', (string) ($r['supplier_name'] ?? '')],
                ['Bill no', (string) ($r['invoice_no'] ?? '')],
                ['Date', format_display_date($r['purchase_date'] ?? '')],
                ['Total', assistant_inr($r['total'] ?? 0)],
                ['Paid', assistant_inr($r['paid'] ?? 0)],
                ['Due', assistant_inr($r['due'] ?? 0)],
            ]));
        }

        if ($tool === 'create_sale' && !empty($r['invoice_no'])) {
            $saleId = (int) ($r['id'] ?? 0);
            $extra = $saleId > 0 ? ['actions' => assistant_bill_actions_sale($saleId)] : [];
            $push(assistant_table_pack('Sale saved', ['Field', 'Value'], [
                ['Invoice', (string) ($r['invoice_no'] ?? '')],
                ['Total', assistant_inr($r['total'] ?? 0)],
                ['Date', format_display_date($r['sale_date'] ?? '')],
            ], $extra));
        }

        if ($tool === 'get_invoice_detail') {
            if (!empty($r['need_pick']) && !empty($r['matches']) && is_array($r['matches'])) {
                $rows = [];
                foreach ($r['matches'] as $m) {
                    if (!is_array($m)) {
                        continue;
                    }
                    $rows[] = [
                        (string) ($m['invoice_no'] ?? $m['id'] ?? ''),
                        (string) ($m['customer_name'] ?? $m['supplier_name'] ?? ''),
                        (string) ($m['id'] ?? ''),
                    ];
                }
                $push(assistant_table_pack('Kaun si bill?', ['Invoice', 'Name', 'ID'], $rows));
            } elseif (($r['kind'] ?? '') === 'purchase' && !empty($r['id'])) {
                $header = [
                    ['Bill no', (string) ($r['invoice_no'] ?? '')],
                    ['Supplier', (string) ($r['supplier_name'] ?? '')],
                    ['Date', (string) ($r['date'] ?? format_display_date($r['purchase_date'] ?? ''))],
                    ['Total', assistant_inr($r['total'] ?? 0)],
                    ['Paid', assistant_inr($r['paid'] ?? 0)],
                    ['Due', assistant_inr($r['due'] ?? 0)],
                ];
                $push(assistant_table_pack(
                    'Purchase bill ' . (string) ($r['invoice_no'] ?? ''),
                    ['Field', 'Value'],
                    $header,
                    ['actions' => $r['actions'] ?? assistant_bill_actions_purchase((int) $r['id'])]
                ));
                $itemRows = [];
                foreach (($r['products'] ?? []) as $i => $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $itemRows[] = [
                        (string) ($i + 1),
                        (string) ($p['name'] ?? $p['product_name'] ?? ''),
                        (string) ($p['qty'] ?? ''),
                        (string) ($p['unit'] ?? ''),
                        assistant_inr($p['price'] ?? 0),
                        assistant_inr($p['total'] ?? 0),
                    ];
                }
                $push(assistant_table_pack('Items', ['#', 'Product', 'Qty', 'Unit', 'Rate', 'Amount'], $itemRows));
            } elseif (!empty($r['id']) || !empty($r['invoice_no'])) {
                $header = [
                    ['Invoice', (string) ($r['invoice_no'] ?? '')],
                    ['Customer', (string) ($r['customer_name'] ?? '')],
                    ['Mobile', (string) ($r['mobile'] ?? '')],
                    ['Address', (string) ($r['address'] ?? '')],
                    ['GST', (string) ($r['gst'] ?? '')],
                    ['Date', (string) ($r['date'] ?? '')],
                ];
                if (!empty($r['ref_name'])) {
                    $header[] = ['Reference', trim((string) ($r['ref_type'] ?? '') . ' ' . (string) $r['ref_name'])];
                }
                $header[] = ['Total', assistant_inr($r['total'] ?? 0)];
                $header[] = ['Received', assistant_inr($r['received'] ?? 0)];
                $header[] = ['Due', assistant_inr($r['due'] ?? 0)];
                $saleId = (int) ($r['id'] ?? 0);
                $push(assistant_table_pack(
                    'Invoice ' . (string) ($r['invoice_no'] ?? ''),
                    ['Field', 'Value'],
                    $header,
                    ['actions' => $r['actions'] ?? ($saleId > 0 ? assistant_bill_actions_sale($saleId) : [])]
                ));
                $itemRows = [];
                foreach (($r['products'] ?? []) as $i => $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $itemRows[] = [
                        (string) ($i + 1),
                        (string) ($p['name'] ?? ''),
                        (string) ($p['color'] ?? ''),
                        (string) ($p['qty'] ?? ''),
                        (string) ($p['unit'] ?? ''),
                        assistant_inr($p['price'] ?? 0),
                        assistant_inr($p['total'] ?? 0),
                    ];
                }
                $push(assistant_table_pack('Items', ['#', 'Product', 'Colour', 'Qty', 'Unit', 'Rate', 'Amount'], $itemRows));
            }
        }
    }

    return $tables;
}
