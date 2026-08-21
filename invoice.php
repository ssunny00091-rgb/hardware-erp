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
$saleStmt = $pdo->prepare(
    'SELECT id, invoice_no, customer_name, mobile, address, gst, total, created_at
     FROM sales WHERE id = :id'
);
$saleStmt->execute(['id' => $id]);
$sale = $saleStmt->fetch();

if (!$sale) {
    http_response_code(404);
    echo 'Invoice not found';
    exit;
}

$itemStmt = $pdo->prepare(
    'SELECT product_name, qty, unit, price, total FROM sale_items WHERE sale_id = :id'
);
$itemStmt->execute(['id' => $id]);
$items = $itemStmt->fetchAll();
$date = date('d/m/Y', strtotime((string) $sale['created_at']));
$autoPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars((string) $sale['invoice_no'], ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/invoice-print.css'), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
  <div class="no-print">
    <button type="button" onclick="window.print()">🖨️ Print / Save PDF</button>
    <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Back</a>
  </div>

  <div class="sheet">
    <div class="center">
      <h1><?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></h1>
      <div><?= htmlspecialchars($company['address_line1'], ENT_QUOTES, 'UTF-8') ?></div>
      <div><?= htmlspecialchars($company['address_line2'], ENT_QUOTES, 'UTF-8') ?></div>
      <div>Phone: <?= htmlspecialchars($company['mobile'], ENT_QUOTES, 'UTF-8') ?></div>
      <div>GSTIN: <?= htmlspecialchars($company['gst'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="meta">
      <div>
        <div><strong>Invoice No:</strong> <?= htmlspecialchars((string) $sale['invoice_no'], ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Date:</strong> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <div style="text-align:right">
        <div><strong>Customer:</strong> <?= htmlspecialchars((string) $sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></div>
        <div><strong>Mobile:</strong> <?= htmlspecialchars((string) $sale['mobile'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($sale['address'])): ?>
          <div><strong>Address:</strong> <?= htmlspecialchars((string) $sale['address'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($sale['gst'])): ?>
          <div><strong>GST:</strong> <?= htmlspecialchars((string) $sale['gst'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Product</th>
          <th>Qty</th>
          <th>Unit</th>
          <th>Rate</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $item['qty'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align:center"><?= htmlspecialchars((string) $item['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align:right">Rs. <?= money($item['price']) ?></td>
            <td style="text-align:right">Rs. <?= money($item['total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="total">
      <span>Grand Total</span>
      <span>Rs. <?= money($sale['total']) ?></span>
    </div>
    <p class="thanks">Thank You! Visit Again.</p>
  </div>
  <?php if ($autoPrint): ?>
    <script>window.addEventListener("load", function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
