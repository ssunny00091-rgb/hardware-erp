<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/config.php';
$company = $config['company'];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid invoice';
    exit;
}

$pdo = db();
$saleStmt = $pdo->prepare('SELECT * FROM sales WHERE id = :id');
$saleStmt->execute(['id' => $id]);
$sale = $saleStmt->fetch();

if (!$sale) {
    http_response_code(404);
    echo 'Invoice not found';
    exit;
}

$itemStmt = $pdo->prepare(
    'SELECT * FROM sale_items WHERE sale_id = :id ORDER BY id ASC'
);
$itemStmt->execute(['id' => $id]);
$items = $itemStmt->fetchAll();

if (!$items && !empty($sale['line_items'])) {
    $decoded = json_decode((string) $sale['line_items'], true);
    if (is_array($decoded)) {
        foreach ($decoded as $row) {
            $items[] = [
                'product_name' => (string) ($row['name'] ?? $row['product_name'] ?? ''),
                'color_code' => (string) ($row['color'] ?? $row['color_code'] ?? ''),
                'color_hex' => (string) ($row['color_hex'] ?? ''),
                'qty' => $row['qty'] ?? '',
                'unit' => $row['unit'] ?? 'Piece',
                'price' => $row['price'] ?? 0,
                'total' => $row['total'] ?? 0,
            ];
        }
    }
}

$date = date('d/m/Y', strtotime((string) $sale['created_at']));
$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $h($sale['invoice_no']) ?></title>
</head>
<body style="margin:0;padding:12px;font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff;font-size:14px;">
  <div style="margin-bottom:12px;">
    <button type="button" onclick="window.print()">Print</button>
    <a href="<?= $h(app_url('index.php')) ?>">Back</a>
  </div>

  <div style="text-align:center;border-bottom:2px solid #000;padding-bottom:8px;">
    <div style="font-size:20px;font-weight:bold;"><?= $h($company['name']) ?></div>
    <div><?= $h($company['address_line1']) ?></div>
    <div><?= $h($company['address_line2']) ?></div>
    <div>Phone: <?= $h($company['mobile']) ?></div>
    <div>GSTIN: <?= $h($company['gst']) ?></div>
  </div>

  <p>
    <strong>Invoice No:</strong> <?= $h($sale['invoice_no']) ?><br>
    <strong>Date:</strong> <?= $h($date) ?><br>
    <strong>Customer:</strong> <?= $h($sale['customer_name']) ?><br>
    <strong>Mobile:</strong> <?= $h($sale['mobile']) ?>
  </p>

  <p style="font-weight:bold;margin-bottom:4px;">Products</p>
  <?php if (!$items): ?>
    <p>No products saved on this invoice.</p>
  <?php else: ?>
    <?php foreach ($items as $i => $item): ?>
      <?php
        $colorLabel = trim((string) ($item['color_code'] ?? $item['color'] ?? ''));
        $colorHex = trim((string) ($item['color_hex'] ?? ''));
        $showColor = $colorLabel !== '' || ($colorHex !== '' && strcasecmp($colorHex, '#ffffff') !== 0);
      ?>
      <p style="margin:0;padding:6px 0;border-bottom:1px solid #000;">
        <?= $i + 1 ?>)
        <?= $h($item['product_name']) ?>
        <?php if ($showColor): ?>
          &nbsp;|&nbsp; Colour:
          <?php if ($colorHex !== ''): ?>
            <span style="display:inline-block;width:12px;height:12px;border:1px solid #000;background:<?= $h($colorHex) ?>;vertical-align:middle;"></span>
          <?php endif; ?>
          <?= $h($colorLabel !== '' ? $colorLabel : $colorHex) ?>
        <?php endif; ?>
        &nbsp;|&nbsp; Qty: <?= $h($item['qty']) ?> <?= $h($item['unit']) ?>
        &nbsp;|&nbsp; Rate: Rs. <?= money($item['price']) ?>
        &nbsp;|&nbsp; Amount: Rs. <?= money($item['total']) ?>
      </p>
    <?php endforeach; ?>
  <?php endif; ?>

  <p style="font-size:18px;font-weight:bold;margin-top:12px;">
    Grand Total: Rs. <?= money($sale['total']) ?>
  </p>
  <p style="text-align:center;">Thank You! Visit Again.</p>

  <style>
    @media print {
      button, a { display: none !important; }
      body { padding: 0 !important; }
    }
  </style>
</body>
</html>
