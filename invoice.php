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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars((string) $sale['invoice_no'], ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
  <style>
    @page { size: A4 portrait; margin: 8mm; }
    @media print {
      html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; height: auto !important; }
      .no-print { display: none !important; }
      .print-area { margin: 0 !important; padding: 0 !important; box-shadow: none !important; max-width: none !important; }
    }
  </style>
</head>
<body class="bg-gray-100 p-4 text-black">
  <div class="no-print mb-4 flex gap-3">
    <button onclick="window.print()" class="rounded bg-blue-600 px-4 py-2 text-white">🖨️ Print / Save PDF</button>
    <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded bg-gray-700 px-4 py-2 text-white">Back</a>
  </div>

  <div class="print-area mx-auto max-w-3xl bg-white p-4 text-sm">
    <div class="border-b-2 border-green-700 pb-2 text-center">
      <h1 class="text-xl font-bold text-green-700"><?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></h1>
      <p><?= htmlspecialchars($company['address_line1'], ENT_QUOTES, 'UTF-8') ?></p>
      <p><?= htmlspecialchars($company['address_line2'], ENT_QUOTES, 'UTF-8') ?></p>
      <p>📞 <?= htmlspecialchars($company['mobile'], ENT_QUOTES, 'UTF-8') ?></p>
      <p>GSTIN : <?= htmlspecialchars($company['gst'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>

    <div class="mt-3 flex justify-between">
      <div>
        <p><strong>Invoice No :</strong> <?= htmlspecialchars((string) $sale['invoice_no'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <div class="text-right">
        <p><strong>Customer :</strong> <?= htmlspecialchars((string) $sale['customer_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Mobile :</strong> <?= htmlspecialchars((string) $sale['mobile'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($sale['address'])): ?>
          <p><strong>Address :</strong> <?= htmlspecialchars((string) $sale['address'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if (!empty($sale['gst'])): ?>
          <p><strong>GST :</strong> <?= htmlspecialchars((string) $sale['gst'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <table class="mt-3 w-full border-collapse border">
      <thead>
        <tr class="bg-gray-200">
          <th class="border px-2 py-1">#</th>
          <th class="border px-2 py-1">Product</th>
          <th class="border px-2 py-1">Qty</th>
          <th class="border px-2 py-1">Unit</th>
          <th class="border px-2 py-1">Rate</th>
          <th class="border px-2 py-1">Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
          <tr>
            <td class="border px-2 py-1"><?= $i + 1 ?></td>
            <td class="border px-2 py-1"><?= htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="border px-2 py-1"><?= htmlspecialchars((string) $item['qty'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="border px-2 py-1 text-center"><?= htmlspecialchars((string) $item['unit'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="border px-2 py-1 text-right">₹<?= money($item['price']) ?></td>
            <td class="border px-2 py-1 text-right">₹<?= money($item['total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="mt-3 flex justify-end">
      <div class="w-64 border p-3 text-lg font-bold">
        <div class="flex justify-between">
          <span>Grand Total</span>
          <span>₹<?= money($sale['total']) ?></span>
        </div>
      </div>
    </div>
    <p class="mt-4 text-center italic">Thank You! Visit Again.</p>
  </div>
</body>
</html>
