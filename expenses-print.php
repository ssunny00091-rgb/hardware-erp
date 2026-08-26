<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pdo = db();
$config = require __DIR__ . '/config/config.php';
$company = $config['company'];

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$fromDisplay = date('d/m/Y', strtotime($from));
$toDisplay = date('d/m/Y', strtotime($to));

$stmt = $pdo->prepare(
    "SELECT id, expense_date, category, description, amount, payment_mode
     FROM expenses
     WHERE expense_date BETWEEN :from AND :to
     ORDER BY expense_date DESC, id DESC"
);
$stmt->execute(['from' => $from, 'to' => $to]);
$expenses = $stmt->fetchAll();

$grandTotal = 0.0;
$catTotals = [];
foreach ($expenses as $e) {
    $grandTotal += (float) $e['amount'];
    $cat = $e['category'] ?? 'General';
    $catTotals[$cat] = ($catTotals[$cat] ?? 0) + (float) $e['amount'];
}
arsort($catTotals);

$daysDiff = max(1, (int) ((strtotime($to) - strtotime($from)) / 86400) + 1);
$avgPerDay = $grandTotal / $daysDiff;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expenses — <?= $h($company['name'] ?? '') ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; background: #fff; padding: 20px; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc2626; padding-bottom: 15px; }
    .header h1 { font-size: 22px; color: #dc2626; }
    .header p { font-size: 13px; color: #555; margin-top: 4px; }
    .period { font-size: 14px; font-weight: bold; color: #333; margin-top: 5px; }
    .summary { display: flex; gap: 15px; justify-content: center; margin: 15px 0; flex-wrap: wrap; }
    .summary-box { text-align: center; padding: 10px 18px; border: 1px solid #ddd; border-radius: 8px; min-width: 120px; }
    .summary-box .label { font-size: 10px; text-transform: uppercase; color: #666; letter-spacing: 0.5px; }
    .summary-box .value { font-size: 20px; font-weight: bold; color: #111; margin-top: 2px; }
    .summary-box.total { background: #fef2f2; border-color: #fca5a5; }
    .summary-box.total .value { color: #dc2626; }
    .cat-section { margin: 15px 0; }
    .cat-section h3 { font-size: 14px; color: #555; margin-bottom: 8px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    .cat-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 12px; }
    .cat-bar .name { width: 140px; font-weight: 600; }
    .cat-bar .bar { flex: 1; height: 14px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
    .cat-bar .fill { height: 100%; background: #dc2626; border-radius: 4px; }
    .cat-bar .amt { width: 80px; text-align: right; font-family: monospace; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
    th { background: #dc2626; color: #fff; padding: 10px 8px; text-align: left; font-weight: 600; }
    td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; }
    tr:nth-child(even) { background: #fef2f2; }
    .num { text-align: right; font-family: monospace; }
    .cat-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; color: #fff; }
    .footer { margin-top: 20px; text-align: center; font-size: 11px; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
    .no-print { margin: 20px 0; text-align: center; }
    .no-print button { padding: 12px 30px; font-size: 16px; border: none; border-radius: 8px; cursor: pointer; margin: 0 8px; }
    #printBtn { background: #dc2626; color: #fff; }
    #printBtn:hover { background: #b91c1c; }
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
  <h1>💸 General Expenses</h1>
  <p><?= $h($company['name'] ?? 'Hardware Store') ?></p>
  <p><?= $h($company['address_line1'] ?? '') ?> <?= $h($company['address_line2'] ?? '') ?></p>
  <div class="period">📅 <?= $h($fromDisplay) ?> se <?= $h($toDisplay) ?> (<?= $daysDiff ?> din)</div>
</div>

<div class="summary">
  <div class="summary-box total">
    <div class="label">Total Kharcha</div>
    <div class="value">₹<?= number_format($grandTotal, 2) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Total Entries</div>
    <div class="value"><?= count($expenses) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Avg / Din</div>
    <div class="value">₹<?= number_format($avgPerDay, 2) ?></div>
  </div>
  <div class="summary-box">
    <div class="label">Categories</div>
    <div class="value"><?= count($catTotals) ?></div>
  </div>
</div>

<?php if (!empty($catTotals)): ?>
<div class="cat-section">
  <h3>📊 Category-wise Breakdown</h3>
  <?php
  $maxCat = max($catTotals);
  $catColors = [
    'Rent' => '#2563eb', 'Salary' => '#7c3aed', 'Transport' => '#ca8a04',
    'Electricity' => '#ea580c', 'Phone/Internet' => '#0891b2', 'Office Supplies' => '#db2777',
    'Maintenance' => '#6b7280', 'Food/Tea' => '#16a34a', 'Labour' => '#4f46e5',
    'General' => '#64748b', 'Other' => '#94a3b8',
  ];
  foreach ($catTotals as $cat => $amt):
    $pct = $maxCat > 0 ? ($amt / $maxCat) * 100 : 0;
    $color = $catColors[$cat] ?? '#64748b';
  ?>
  <div class="cat-bar">
    <span class="name"><?= $h($cat) ?></span>
    <div class="bar"><div class="fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div></div>
    <span class="amt">₹<?= number_format($amt, 2) ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Date</th>
      <th>Category</th>
      <th>Description</th>
      <th class="num">Amount (₹)</th>
      <th>Mode</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($expenses)): ?>
      <tr><td colspan="6" style="text-align:center; padding:30px; color:#888;">Is period me koi kharcha nahi.</td></tr>
    <?php else: ?>
      <?php foreach ($expenses as $i => $e): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= $h(date('d/m/Y', strtotime($e['expense_date']))) ?></td>
          <td><span class="cat-badge" style="background:<?= $catColors[$e['category']] ?? '#64748b' ?>"><?= $h($e['category']) ?></span></td>
          <td><?= $h($e['description'] ?: '—') ?></td>
          <td class="num" style="font-weight:bold;">₹<?= number_format((float) $e['amount'], 2) ?></td>
          <td><?= $h($e['payment_mode'] ?: 'Cash') ?></td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<div class="footer">
  <?= $h($company['name'] ?? '') ?> — Expenses Report | <?= $h($fromDisplay) ?> to <?= $h($toDisplay) ?> | Generated on <?= date('d/m/Y h:i A') ?>
</div>

</body>
</html>
