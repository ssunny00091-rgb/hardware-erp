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
    'SELECT si.*, p.hsn_code AS product_hsn
     FROM sale_items si
     LEFT JOIN products p ON p.id = si.product_id
     WHERE si.sale_id = :id
     ORDER BY si.id ASC'
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
                'hsn_code' => (string) ($row['hsn'] ?? $row['hsn_code'] ?? ''),
                'product_hsn' => '',
                'qty' => $row['qty'] ?? '',
                'unit' => $row['unit'] ?? 'Piece',
                'price' => $row['price'] ?? 0,
                'total' => $row['total'] ?? 0,
            ];
        }
    }
}

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$date = date('d-m-Y', strtotime((string) $sale['created_at']));
$party = $sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in Customer';
$totalQty = 0.0;
foreach ($items as $item) {
    $totalQty += (float) ($item['qty'] ?? 0);
}
$grand = (float) $sale['total'];
$received = isset($sale['received']) && $sale['received'] !== null && $sale['received'] !== ''
    ? (float) $sale['received']
    : $grand;
$balance = $grand - $received;
$state = gst_state_label((string) $company['gst']);
$email = (string) ($company['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice <?= $h($sale['invoice_no']) ?></title>
  <link rel="stylesheet" href="<?= $h(app_url('assets/css/invoice-print.css')) ?>">
</head>
<body class="invoice-page">
  <div class="no-print">
    <button type="button" onclick="window.print()">Print / Save PDF</button>
    <a href="<?= $h(app_url('index.php')) ?>">Back</a>
  </div>

  <article class="invoice">
    <div class="inv-title">Tax Invoice</div>
    <div class="inv-company">
      <div class="inv-name"><?= $h($company['name']) ?></div>
      <p><?= $h($company['address_line1']) ?></p>
      <p><?= $h($company['address_line2']) ?></p>
      <p>
        Phone: <?= $h($company['mobile']) ?>
        <?php if ($email !== ''): ?>
          &nbsp;|&nbsp; Email: <?= $h($email) ?>
        <?php endif; ?>
      </p>
      <p>
        GSTIN: <?= $h($company['gst']) ?>
        <?php if ($state !== ''): ?>
          &nbsp;|&nbsp; State: <?= $h($state) ?>
        <?php endif; ?>
      </p>
    </div>

    <table class="inv-split">
      <tr>
        <td>
          <div class="lbl">Bill To</div>
          <p class="party"><?= $h($party) ?></p>
          <?php if (!empty($sale['mobile'])): ?><p>Phone: <?= $h($sale['mobile']) ?></p><?php endif; ?>
          <?php if (!empty($sale['address'])): ?><p><?= $h($sale['address']) ?></p><?php endif; ?>
          <?php if (!empty($sale['gst'])): ?><p>GSTIN: <?= $h($sale['gst']) ?></p><?php endif; ?>
        </td>
        <td class="right-col">
          <div class="lbl">Invoice Details</div>
          <div class="kv"><span>Invoice No.</span><strong><?= $h($sale['invoice_no']) ?></strong></div>
          <div class="kv"><span>Date</span><strong><?= $h($date) ?></strong></div>
        </td>
      </tr>
    </table>

    <table class="items">
      <thead>
        <tr>
          <th class="center" style="width:32px">#</th>
          <th>Item Name</th>
          <th>Colour / Shade</th>
          <th class="center">HSN/SAC</th>
          <th class="center">Qty</th>
          <th class="center">Unit</th>
          <th class="num">Price/Unit (₹)</th>
          <th class="num">Amount (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$items): ?>
          <tr>
            <td colspan="8">No products on this invoice.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($items as $i => $item): ?>
            <?php
              $colorLabel = trim((string) ($item['color_code'] ?? $item['color'] ?? ''));
              $colorHex = trim((string) ($item['color_hex'] ?? ''));
              $showColor = $colorLabel !== '' || ($colorHex !== '' && strcasecmp($colorHex, '#ffffff') !== 0);
              $hsn = trim((string) ($item['hsn_code'] ?? ''));
              if ($hsn === '') {
                  $hsn = trim((string) ($item['product_hsn'] ?? ''));
              }
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
              <td class="center"><?= $h($hsn !== '' ? $hsn : '—') ?></td>
              <td class="center"><?= $h(rtrim(rtrim(money($item['qty']), '0'), '.')) ?></td>
              <td class="center"><?= $h($item['unit']) ?></td>
              <td class="num"><?= money($item['price']) ?></td>
              <td class="num"><?= money($item['total']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4">Total</td>
          <td class="center"><?= $h(rtrim(rtrim(money($totalQty), '0'), '.')) ?></td>
          <td></td>
          <td></td>
          <td class="num"><?= money($grand) ?></td>
        </tr>
      </tfoot>
    </table>

    <table class="inv-bottom">
      <tr>
        <td class="words-box">
          <div class="words-label">Invoice Amount In Words</div>
          <div><?= $h(amount_in_words($grand)) ?></div>
          <div class="terms">
            <strong>Terms and Conditions</strong>
            Thank you for doing business with us.
          </div>
        </td>
        <td class="totals-box">
          <div class="tot-row"><span>Sub Total</span><span>₹ <?= money($grand) ?></span></div>
          <div class="tot-row grand"><span>Total</span><span>₹ <?= money($grand) ?></span></div>
          <div class="tot-row"><span>Received</span><span>₹ <?= money($received) ?></span></div>
          <div class="tot-row"><span>Balance</span><span>₹ <?= money($balance) ?></span></div>
        </td>
      </tr>
    </table>

    <div class="inv-sign">
      <div>
        <div class="who">For <?= $h($company['name']) ?></div>
        <div class="role">Authorized Signatory</div>
      </div>
    </div>
  </article>
</body>
</html>
