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

$billDate = $sale['sale_date'] ?? $sale['created_at'];
$date = format_display_date($billDate);
$party = $sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in Customer';
$grand = (float) $sale['total'];
$received = isset($sale['received']) && $sale['received'] !== null && $sale['received'] !== ''
    ? (float) $sale['received']
    : $grand;
$balance = $grand - $received;
$state = gst_state_label((string) $company['gst']);
$email = (string) ($company['email'] ?? '');

$autoWaPdf = isset($_GET['whatsapp']);
$waPhone = trim((string) ($_GET['phone'] ?? $sale['mobile'] ?? ''));
$safeInv = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($sale['invoice_no'] ?? 'invoice')) ?: 'invoice';
$waFilename = 'Invoice-' . $safeInv . '.pdf';
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
    <button type="button" class="wa-btn" id="btn-wa-pdf">WhatsApp PDF</button>
    <a href="<?= $h(app_url('index.php?edit=' . $id)) ?>">Edit</a>
    <a href="<?= $h(app_url('index.php')) ?>">Back</a>
  </div>

  <article class="invoice">
    <table class="tax-sheet">
      <colgroup>
        <col class="c-no">
        <col class="c-item">
        <col class="c-color">
        <col class="c-hsn">
        <col class="c-qty">
        <col class="c-unit">
        <col class="c-rate">
        <col class="c-amt">
      </colgroup>
      <thead>
        <tr>
          <th colspan="8" class="title-cell">Tax Invoice</th>
        </tr>
        <tr>
          <td colspan="8" class="company-cell">
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
          </td>
        </tr>
        <tr>
          <td colspan="4" class="party-cell">
            <div class="section-lbl">Bill To</div>
            <p class="party"><?= $h($party) ?></p>
            <?php if (!empty($sale['mobile'])): ?><p>Phone: <?= $h($sale['mobile']) ?></p><?php endif; ?>
            <?php if (!empty($sale['address'])): ?><p><?= $h($sale['address']) ?></p><?php endif; ?>
            <?php if (!empty($sale['gst'])): ?><p>GSTIN: <?= $h($sale['gst']) ?></p><?php endif; ?>
          </td>
          <td colspan="4" class="meta-cell">
            <div class="section-lbl">Invoice Details</div>
            <div class="meta-line"><span>Invoice No.</span><span><?= $h($sale['invoice_no']) ?></span></div>
            <div class="meta-line"><span>Date</span><span><?= $h($date) ?></span></div>
            <?php if ($balance > 0.009 && !empty($sale['due_date'])): ?>
              <div class="meta-line"><span>Due Date</span><span><?= $h(format_display_date($sale['due_date'])) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($sale['ref_name'])): ?>
              <div class="meta-line"><span><?= $h(ucfirst((string) ($sale['ref_type'] ?: 'Ref'))) ?></span><span><?= $h($sale['ref_name']) ?></span></div>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th class="center">#</th>
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
              <td class="item-name"><?= $h($item['product_name']) ?></td>
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
        <tr>
          <td colspan="5" class="words-cell">
            <div class="words-label">Invoice Amount In Words</div>
            <div><?= $h(amount_in_words($grand)) ?></div>
          </td>
          <td colspan="3" style="padding:0">
            <table class="inner-tot">
              <tr class="grand"><td>Total</td><td class="num">₹ <?= money($grand) ?></td></tr>
              <tr><td>Received</td><td class="num">₹ <?= money($received) ?></td></tr>
              <tr><td>Balance</td><td class="num">₹ <?= money($balance) ?></td></tr>
            </table>
          </td>
        </tr>
        <tr>
          <td colspan="5" class="terms-cell">
            <div class="terms-label">Terms and Conditions</div>
            Thank you for doing business with us.
          </td>
          <td colspan="3" class="sign-cell">
            <div class="sign-who">For <?= $h($company['name']) ?></div>
            <div>Authorized Signatory</div>
          </td>
        </tr>
      </tbody>
    </table>
  </article>
  <script src="<?= $h(app_url('assets/vendor/html2canvas.min.js')) ?>"></script>
  <script src="<?= $h(app_url('assets/vendor/jspdf.umd.min.js')) ?>"></script>
  <script src="<?= $h(app_url('assets/js/whatsapp-pdf.js')) ?>"></script>
  <script>
    (function () {
      const btn = document.getElementById("btn-wa-pdf");
      const ctx = {
        element: document.querySelector("article.invoice"),
        filename: <?= json_encode($waFilename, JSON_UNESCAPED_UNICODE) ?>,
        phone: <?= json_encode($waPhone, JSON_UNESCAPED_UNICODE) ?>,
      };
      bindWhatsAppPdfButton(btn, ctx);
      <?php if ($autoWaPdf): ?>
      window.addEventListener("load", function () {
        btn.click();
      });
      <?php endif; ?>
    })();
  </script>
  <?php if (!empty($_GET['print'])): ?>
  <script>window.addEventListener("load", function () { window.print(); });</script>
  <?php endif; ?>
</body>
</html>
