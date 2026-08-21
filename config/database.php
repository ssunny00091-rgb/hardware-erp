<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'],
        $db['name'],
        $db['charset']
    );

    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    try {
        $pdo->exec('ALTER TABLE sales ADD COLUMN line_items TEXT NULL');
    } catch (Throwable $e) {
        // Column already exists.
    }
    try {
        $pdo->exec('ALTER TABLE sale_items ADD COLUMN color_code VARCHAR(120) NULL');
    } catch (Throwable $e) {
        // Column already exists.
    }
    try {
        $pdo->exec('ALTER TABLE sale_items ADD COLUMN color_hex VARCHAR(7) NULL');
    } catch (Throwable $e) {
        // Column already exists.
    }

    return $pdo;
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function generate_invoice_number(): string
{
    return sprintf(
        'INV-%s-%04d',
        date('Ymd'),
        random_int(1000, 9999)
    );
}

function money(float|int|string $amount): string
{
    return number_format((float) $amount, 2, '.', '');
}

function find_or_create_product(PDO $pdo, string $name, string $unit, float $sellingPrice, float $purchasePrice = 0.0): int
{
    $find = $pdo->prepare(
        'SELECT id FROM products WHERE LOWER(product_name) = LOWER(:name) LIMIT 1'
    );
    $find->execute(['name' => $name]);
    $found = $find->fetch();
    if ($found) {
        return (int) $found['id'];
    }

    $insert = $pdo->prepare(
        'INSERT INTO products
          (product_name, brand, category, unit, purchase_price, selling_price, stock, gst_percent, hsn_code)
         VALUES
          (:product_name, :brand, :category, :unit, :purchase_price, :selling_price, :stock, :gst_percent, :hsn_code)'
    );
    $insert->execute([
        'product_name' => $name,
        'brand' => '',
        'category' => '',
        'unit' => $unit !== '' ? $unit : 'Piece',
        'purchase_price' => $purchasePrice,
        'selling_price' => $sellingPrice,
        'stock' => 0,
        'gst_percent' => 18,
        'hsn_code' => '',
    ]);

    return (int) $pdo->lastInsertId();
}
