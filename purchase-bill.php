<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Supplier Bill';
$activeNav = 'purchase';
require __DIR__ . '/includes/header.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    echo '<p>Invalid bill</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM purchases WHERE id = :id');
$stmt->execute(['id' => $id]);
$purchase = $stmt->fetch();
if (!$purchase) {
    echo '<p>Bill not found</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$itemStmt = $pdo->prepare(
    'SELECT * FROM purchase_items WHERE purchase_id = :id ORDER BY id ASC'
);
$itemStmt->execute(['id' => $id]);
$items = $itemStmt->fetchAll();

$payments = [];
try {
    $payStmt = $pdo->prepare(
        'SELECT * FROM purchase_payments WHERE purchase_id = :id ORDER BY paid_on ASC, id ASC'
    );
    $payStmt->execute(['id' => $id]);
    $payments = $payStmt->fetchAll();
} catch (Throwable $e) {
    $payments = [];
}

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$config = require __DIR__ . '/config/config.php';
$company = $config['company'];
$grand = (float) $purchase['total'];
$paid = (float) ($purchase['paid'] ?? 0);
if ($payments) {
    $paid = array_sum(array_map(static fn ($p) => (float) $p['amount'], $payments));
}
$due = max(0, $grand - $paid);
$billNo = (string) ($purchase['invoice_no'] !== '' ? $purchase['invoice_no'] : '#' . $purchase['id']);
$billDate = format_display_date($purchase['purchase_date']);
?>

<div class="no-print mb-4 flex flex-wrap gap-2">
  <a href="<?= $h(app_url('purchase.php')) ?>" class="rounded-lg bg-slate-600 px-4 py-2">← Purchase</a>
  <a href="<?= $h(app_url('purchase.php?edit=' . $id)) ?>" class="rounded-lg bg-amber-500 px-4 py-2">✏️ Edit bill</a>
  <button type="button" onclick="window.print()" class="rounded-lg bg-blue-600 px-4 py-2">🖨️ Print bill</button>
</div>

<article class="invoice mx-auto">
  <table class="tax-sheet">
    <thead>
      <tr><th colspan="6" class="title-cell">Purchase Bill</th></tr>
      <tr>
        <td colspan="6" class="company-cell">
          <div class="inv-name"><?= $h($company['name']) ?></div>
          <p><?= $h($company['address_line1']) ?></p>
          <p>GSTIN: <?= $h($company['gst']) ?></p>
        </td>
      </tr>
      <tr>
        <td colspan="3" class="party-cell">
          <div class="section-lbl">Supplier</div>
          <p class="party"><?= $h($purchase['supplier_name'] !== '' ? $purchase['supplier_name'] : 'Supplier') ?></p>
        </td>
        <td colspan="3" class="meta-cell">
          <div class="section-lbl">Bill Details</div>
          <div class="meta-line"><span>Bill No.</span><span><?= $h($billNo) ?></span></div>
          <div class="meta-line"><span>Date</span><span><?= $h($billDate) ?></span></div>
        </td>
      </tr>
      <tr>
        <th class="center">#</th>
        <th>Item Name</th>
        <th class="center">Qty</th>
        <th class="center">Unit</th>
        <th class="num">Price (₹)</th>
        <th class="num">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="6">No items</td></tr>
      <?php else: ?>
        <?php foreach ($items as $i => $item): ?>
          <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td class="item-name"><?= $h($item['product_name']) ?></td>
            <td class="center"><?= $h(rtrim(rtrim(money($item['qty']), '0'), '.')) ?></td>
            <td class="center"><?= $h($item['unit']) ?></td>
            <td class="num"><?= money($item['price']) ?></td>
            <td class="num"><?= money($item['total']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      <tr class="total-row">
        <td colspan="5">Bill Total</td>
        <td class="num"><?= money($grand) ?></td>
      </tr>
      <tr>
        <td colspan="3">Paid</td>
        <td colspan="3" class="num">₹ <?= money($paid) ?></td>
      </tr>
      <tr>
        <td colspan="3">Due</td>
        <td colspan="3" class="num">₹ <?= money($due) ?></td>
      </tr>
    </tbody>
  </table>
</article>

<section class="no-print mt-8 rounded-2xl border border-white/20 bg-white/10 p-4 sm:p-6">
  <h2 class="mb-4 text-2xl font-bold">Payments given to supplier</h2>
  <p class="mb-4 text-gray-300">Kab kitna paisa diya — date ke saath yahan save hota hai.</p>
  <div class="table-scroll mb-6 overflow-auto rounded-xl bg-white text-black">
    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-slate-100">
          <th class="border p-2 text-left">Date</th>
          <th class="border p-2 text-right">Amount</th>
          <th class="border p-2 text-left">Note</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$payments): ?>
          <tr><td class="border p-3" colspan="3">Abhi koi alag payment nahi. Bill save par jo paid likha tha woh neeche add kar sakte ho.</td></tr>
        <?php else: ?>
          <?php foreach ($payments as $row): ?>
            <tr>
              <td class="border p-2"><?= $h(format_display_date($row['paid_on'])) ?></td>
              <td class="border p-2 text-right">₹ <?= money($row['amount']) ?></td>
              <td class="border p-2"><?= $h($row['notes'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($due > 0.009): ?>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
      <span class="date-field">
        <input type="text" id="pay-on" inputmode="numeric" placeholder="dd/mm/yyyy" maxlength="10" autocomplete="off" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
        <input type="date" id="pay-on-picker" title="Calendar" aria-label="Calendar">
      </span>
      <input type="number" id="pay-amt" placeholder="Amount paid now" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
      <input type="text" id="pay-note" placeholder="Note (cash / UPI / cheque)" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900 md:col-span-1">
      <button type="button" id="btn-pay" class="rounded-xl bg-green-600 py-3 font-semibold">💰 Add Payment</button>
    </div>
    <p class="mt-2 text-sm text-amber-200">Due remaining: ₹<?= money($due) ?></p>
  <?php else: ?>
    <p class="font-semibold text-green-400">Is bill ki payment complete hai.</p>
  <?php endif; ?>
</section>

<script>
  const billId = <?= (int) $id ?>;
  const dueNow = <?= json_encode($due) ?>;
  bindDateField("pay-on", "pay-on-picker");
  setDateField("pay-on", "pay-on-picker", todayIsoDate());
  const btn = document.getElementById("btn-pay");
  if (btn) {
    btn.addEventListener("click", async () => {
      const amount = Number(document.getElementById("pay-amt").value);
      if (!(amount > 0)) {
        alert("Payment amount likho");
        return;
      }
      if (amount > dueNow + 0.05) {
        if (!confirm("Amount due se zyada hai. Phir bhi save karein?")) return;
      }
      try {
        await api("/api/purchases.php", {
          method: "POST",
          body: JSON.stringify({
            purchase_id: billId,
            amount,
            paid_on: parseToIsoDate(document.getElementById("pay-on").value),
            notes: document.getElementById("pay-note").value,
          }),
        });
        alert("✅ Payment save ho gaya");
        window.location.reload();
      } catch (err) {
        alert(err.message);
      }
    });
  }
</script>
<?php if (!empty($_GET['print'])): ?>
<script>window.addEventListener("load", function () { window.print(); });</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
