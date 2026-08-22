<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/config.php';
$company = $config['company'];
$id = (int) ($_GET['id'] ?? 0);
$format = strtolower(trim((string) ($_GET['format'] ?? 'print')));

if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid ledger';
    exit;
}

$pdo = db();
$ledger = load_party_ledger($pdo, $id);
if (!$ledger) {
    http_response_code(404);
    echo 'Ledger not found';
    exit;
}

$party = $ledger['party'];
$entries = $ledger['entries'];
$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $party['name']) ?: 'ledger';
$filename = 'ledger-' . $safeName . '-' . date('Ymd');

if ($format === 'csv' || $format === 'xlsx' || $format === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, [$company['name'] ?? 'Ledger']);
    fputcsv($out, ['Account', $party['name'], 'Type', $party['type']]);
    fputcsv($out, ['Mobile', $party['mobile'] ?? '', 'Address', $party['address'] ?? '']);
    fputcsv($out, []);
    fputcsv($out, ['Date', 'Particulars', 'Ref', 'Debit', 'Credit', 'Balance']);
    foreach ($entries as $row) {
        fputcsv($out, [
            $row['date'] ?? format_display_date($row['entry_date'] ?? ''),
            $row['particulars'] ?? '',
            $row['ref_no'] ?? '',
            money((float) ($row['debit'] ?? 0)),
            money((float) ($row['credit'] ?? 0)),
            money((float) ($row['balance'] ?? 0)),
        ]);
    }
    fputcsv($out, []);
    fputcsv($out, ['Total Debit', money((float) $ledger['debit']), 'Total Credit', money((float) $ledger['credit']), 'Balance', money((float) $ledger['balance'])]);
    fclose($out);
    exit;
}

$typeLabel = ucfirst((string) $party['type']);
$printedOn = date('d/m/Y');
$autoPrint = isset($_GET['print']);

$autoWaPdf = isset($_GET['whatsapp']);
$waPhone = trim((string) ($_GET['phone'] ?? $party['mobile'] ?? ''));
$waFilename = $filename . '.pdf';
$waCaption = whatsapp_ledger_text($company, $ledger);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ledger — <?= $h($party['name']) ?></title>
  <link rel="stylesheet" href="<?= $h(app_url('assets/css/invoice-print.css')) ?>">
  <style>
    body { background: #e2e8f0; margin: 0; font-family: Arial, sans-serif; color: #111; }
    .wrap { max-width: 900px; margin: 16px auto; background: #fff; padding: 20px; }
    .no-print { margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px; }
    .btn { border: 0; border-radius: 8px; padding: 10px 16px; color: #fff; cursor: pointer; text-decoration: none; display: inline-block; }
    table.ledger { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.ledger th, table.ledger td { border: 1px solid #333; padding: 6px 8px; }
    table.ledger th { background: #f1f5f9; }
    .num { text-align: right; }
    .head { text-align: center; margin-bottom: 12px; }
    .head h1 { margin: 0; font-size: 22px; }
    .meta { display: flex; justify-content: space-between; gap: 16px; margin: 12px 0; }
    .tot { font-weight: bold; background: #f8fafc; }
    @media print {
      body { background: #fff; }
      .no-print { display: none !important; }
      .wrap { margin: 0; padding: 0; max-width: none; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="no-print">
      <button type="button" class="btn" style="background:#2563eb" onclick="window.print()">🖨️ Print / Save PDF</button>
      <a class="btn" style="background:#059669" href="<?= $h(app_url('ledger-print.php?id=' . $id . '&format=csv')) ?>">⬇ Excel (CSV)</a>
      <button type="button" class="btn" id="btn-wa-pdf" style="background:#16a34a">WhatsApp PDF</button>
      <a class="btn" style="background:#475569" href="<?= $h(app_url('ledger.php')) ?>">← Ledger</a>
    </div>
    <div id="ledger-sheet">
    <div class="head">
      <h1><?= $h($company['name'] ?? '') ?></h1>
      <p><?= $h($company['address_line1'] ?? '') ?></p>
      <p><?= $h($company['address_line2'] ?? '') ?></p>
      <p>Phone: <?= $h($company['mobile'] ?? '') ?><?php if (!empty($company['gst'])): ?> | GSTIN: <?= $h($company['gst']) ?><?php endif; ?></p>
      <h2 style="margin:12px 0 0"><?= $h($typeLabel) ?> Ledger / Account Statement</h2>
    </div>
    <div class="meta">
      <div>
        <strong><?= $h($party['name']) ?></strong><br>
        <?php if (!empty($party['mobile'])): ?>Mobile: <?= $h($party['mobile']) ?><br><?php endif; ?>
        <?php if (!empty($party['address'])): ?><?= $h($party['address']) ?><?php endif; ?>
      </div>
      <div>
        Print date: <?= $h($printedOn) ?><br>
        Debit: ₹<?= $h(money((float) $ledger['debit'])) ?><br>
        Credit: ₹<?= $h(money((float) $ledger['credit'])) ?><br>
        <strong>Balance: ₹<?= $h(money((float) $ledger['balance'])) ?></strong>
      </div>
    </div>
    <table class="ledger">
      <thead>
        <tr>
          <th>Date</th>
          <th>Particulars</th>
          <th>Ref</th>
          <th class="num">Debit (₹)</th>
          <th class="num">Credit (₹)</th>
          <th class="num">Balance (₹)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr><td colspan="6">Koi entry nahi.</td></tr>
        <?php else: ?>
          <?php foreach ($entries as $row): ?>
            <tr>
              <td><?= $h($row['date'] ?? format_display_date($row['entry_date'] ?? '')) ?></td>
              <td><?= $h($row['particulars'] ?? '') ?></td>
              <td><?= $h($row['ref_no'] ?? '') ?></td>
              <td class="num"><?= ((float) ($row['debit'] ?? 0)) > 0 ? $h(money((float) $row['debit'])) : '' ?></td>
              <td class="num"><?= ((float) ($row['credit'] ?? 0)) > 0 ? $h(money((float) $row['credit'])) : '' ?></td>
              <td class="num"><?= $h(money((float) ($row['balance'] ?? 0))) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <tr class="tot">
          <td colspan="3">Total</td>
          <td class="num"><?= $h(money((float) $ledger['debit'])) ?></td>
          <td class="num"><?= $h(money((float) $ledger['credit'])) ?></td>
          <td class="num"><?= $h(money((float) $ledger['balance'])) ?></td>
        </tr>
      </tbody>
    </table>
    <p style="margin-top:28px;font-size:12px;color:#444">Yeh statement <?= $h($company['name'] ?? '') ?> ke records se nikala gaya hai.</p>
    </div>
  </div>
  <script src="<?= $h(app_url('assets/vendor/html2pdf.bundle.min.js')) ?>"></script>
  <script src="<?= $h(app_url('assets/js/whatsapp-pdf.js')) ?>"></script>
  <script>
    (function () {
      const btn = document.getElementById("btn-wa-pdf");
      const ctx = {
        element: document.getElementById("ledger-sheet"),
        filename: <?= json_encode($waFilename, JSON_UNESCAPED_UNICODE) ?>,
        phone: <?= json_encode($waPhone, JSON_UNESCAPED_UNICODE) ?>,
        caption: <?= json_encode($waCaption, JSON_UNESCAPED_UNICODE) ?>,
      };
      bindWhatsAppPdfButton(btn, ctx);
      <?php if ($autoWaPdf): ?>
      window.addEventListener("load", function () { btn.click(); });
      <?php endif; ?>
    })();
  </script>
  <?php if ($autoPrint): ?>
    <script>window.addEventListener("load", () => window.print());</script>
  <?php endif; ?>
</body>
</html>
