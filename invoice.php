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

$itemStmt = $pdo->prepare('SELECT * FROM sale_items WHERE sale_id = :id ORDER BY id ASC');
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
  <link rel="stylesheet" href="<?= $h(app_url('assets/css/invoice-print.css')) ?>">
</head>
<body>
  <div class="no-print">
    <button type="button" onclick="window.print()">Print / Save PDF</button>
    <a href="<?= $h(app_url('index.php')) ?>">Back</a>
  </div>

  <article class="invoice">
    <header class="invoice-hero">
      <div class="badge">Tax Invoice</div>
      <h1><?= $h($company['name']) ?></h1>
      <p><?= $h($company['address_line1']) ?></p>
      <p><?= $h($company['address_line2']) ?></p>
      <p>Phone: <?= $h($company['mobile']) ?> &nbsp;|&nbsp; GSTIN: <?= $h($company['gst']) ?></p>
    </header>

    <div class="invoice-body">
      <div class="meta-grid">
        <div class="meta-card">
          <h3>Bill To</h3>
          <p><strong><?= $h($sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in Customer') ?></strong></p>
          <p>Mobile: <?= $h($sale['mobile']) ?></p>
          <?php if (!empty($sale['address'])): ?>
            <p><?= $h($sale['address']) ?></p>
          <?php endif; ?>
          <?php if (!empty($sale['gst'])): ?>
            <p>GST: <?= $h($sale['gst']) ?></p>
          <?php endif; ?>
        </div>
        <div class="meta-card">
          <h3>Invoice</h3>
          <p><strong><?= $h($sale['invoice_no']) ?></strong></p>
          <p>Date: <?= $h($date) ?></p>
        </div>
      </div>

      <table class="items">
        <thead>
          <tr>
            <th class="center" style="width:36px">#</th>
            <th>Product</th>
            <th>Colour / Shade</th>
            <th class="center">Qty</th>
            <th class="center">Unit</th>
            <th class="num">Rate</th>
            <th class="num">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr>
              <td colspan="7">No products on this invoice.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $i => $item): ?>
              <?php
                $colorLabel = trim((string) ($item['color_code'] ?? $item['color'] ?? ''));
                $colorHex = trim((string) ($item['color_hex'] ?? ''));
                $showColor = $colorLabel !== '' || ($colorHex !== '' && strcasecmp($colorHex, '#ffffff') !== 0);
              ?>
              <tr>
                <td class="center"><?= $i + 1 ?></td>
                <td><?= $h($item['product_name']) ?></td>
                <td>
                  <?php if ($showColor): ?>
                    <?php if ($colorHex !== ''): ?>
                      <span class="swatch" style="background:<?= $h($colorHex) ?>"></span>
                    <?php endif; ?>
                    <?= $h($colorLabel !== '' ? $colorLabel : $colorHex) ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td class="center"><?= $h($item['qty']) ?></td>
                <td class="center"><?= $h($item['unit']) ?></td>
                <td class="num">Rs. <?= money($item['price']) ?></td>
                <td class="num">Rs. <?= money($item['total']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <div class="totals">
        <div class="totals-box">
          <span>Grand Total</span>
          <span>Rs. <?= money($sale['total']) ?></span>
        </div>
      </div>
      <p class="thanks">Thank you for your business. Visit again.</p>
    </div>
  </article>
</body>
</html>
