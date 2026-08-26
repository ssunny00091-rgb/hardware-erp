<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pdo = db();
$config = require __DIR__ . '/config/config.php';
$company = $config['company'];

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$painters = $pdo->query(
    "SELECT p.id, p.name, p.mobile, p.address,
            COALESCE(SUM(le.debit), 0) AS total_debit,
            COALESCE(SUM(le.credit), 0) AS total_credit,
            COALESCE(SUM(le.debit), 0) - COALESCE(SUM(le.credit), 0) AS balance
     FROM parties p
     LEFT JOIN ledger_entries le ON le.party_id = p.id
     WHERE p.type = 'painter' AND p.deleted_at IS NULL
     GROUP BY p.id, p.name, p.mobile, p.address
     ORDER BY balance DESC, p.name ASC"
)->fetchAll();

$grandDebit = 0;
$grandCredit = 0;
$grandBalance = 0;
foreach ($painters as $p) {
    $grandDebit += (float) $p['total_debit'];
    $grandCredit += (float) $p['total_credit'];
    $grandBalance += (float) $p['balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painter List — <?= $h($company['name'] ?? '') ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; background: #fff; padding: 20px; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 15px; }
    .header h1 { font-size: 22px; color: #1e40af; }
    .header p { font-size: 13px; color: #555; margin-top: 4px; }
    .summary { display: flex; gap: 20px; justify-content: center; margin: 15px 0; }
    .summary-box { text-align: center; padding: 10px 20px; border: 1px solid #ddd; border-radius: 8px; }
    .summary-box .label { font-size: 11px; text-transform: uppercase; color: #666; }
    .summary-box .value { font-size: 20px; font-weight: bold; color: #111; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
    th { background: #2563eb; color: #fff; padding: 10px 8px; text-align: left; font-weight: 600; }
    td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) { background: #f8fafc; }
    tr:hover { background: #eff6ff; }
    .num { text-align: right; font-family: monospace; }
    .positive { color: #16a34a; font-weight: bold; }
    .negative { color: #dc2626; font-weight: bold; }
    .zero { color: #6b7280; }
    .footer { margin-top: 20px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
    .no-print { margin: 20px 0; text-align: center; }
    .no-print button { padding: 12px 30px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin: 0 8px; }
    #printBtn { background: #2563eb; color: #fff; }
    #printBtn:hover { background: #1d4ed8; }
    @media print {
      .no-print { display: none !important; }
      body { padding: 0; }
      @page { margin: 10mm; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <button id="printBtn" onclick="window.print()">🖨️ Print / Save as PDF</button>
  <button onclick="window.close()">✕ Close</button>
</div>

<div class="header">
  <h1>🎨 Painter List</h1>
  <p><?= $h($company['name'] ?? 'Hardware Store') ?></p>
  <p><?= $h($company['address_line1'] ?? '') ?> <?= $h($company['address_line2'] ?? '') ?></p>
  <p>Generated: <?= date('d/m/Y h:i A') ?></p>
</div>

<div class="summary">
  <div class="summary-box">
    <div class="label">Total Painters</div>
    <div class="value"><?= count($painters) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Total Debit (Saman Liya)</div>
    <div class="value">₹<?= number_format($grandDebit, 2) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Total Credit (Payment)</div>
    <div class="value">₹<?= number_format($grandCredit, 2) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Pending Balance</div>
    <div class="value <?= $grandBalance > 0 ? 'negative' : ($grandBalance < 0 ? 'positive' : 'zero') ?>">₹<?= number_format($grandBalance, 2) ?></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Painter Name</th>
      <th>Mobile</th>
      <th>Address</th>
      <th class="num">Saman Liya (₹)</th>
      <th class="num">Payment Kiya (₹)</th>
      <th class="num">Balance (₹)</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($painters)): ?>
      <tr><td colspan="7" style="text-align:center; padding:30px; color:#888;">Koi painter nahi mila.</td></tr>
    <?php else: ?>
      <?php foreach ($painters as $i => $p): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= $h($p['name']) ?></strong></td>
          <td><?= $h($p['mobile'] ?: '—') ?></td>
          <td><?= $h($p['address'] ?: '—') ?></td>
          <td class="num"><?= number_format((float) $p['total_debit'], 2) ?></td>
          <td class="num"><?= number_format((float) $p['total_credit'], 2) ?></td>
          <td class="num <?= (float) $p['balance'] > 0 ? 'negative' : ((float) $p['balance'] < 0 ? 'positive' : 'zero') ?>">
            <?= number_format((float) $p['balance'], 2) ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<div class="footer">
  <?= $h($company['name'] ?? '') ?> — Painter Ledger List | Generated on <?= date('d/m/Y h:i A') ?>
</div>

</body>
</html>
