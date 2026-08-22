<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pdo = db();
$config = require __DIR__ . '/config/config.php';
$company = $config['company'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = (string) ($_POST['reminder_action'] ?? '');
    $id = (int) ($_POST['reminder_id'] ?? 0);
    if ($action === 'done' && $id > 0) {
        reminder_set_status($pdo, $id, 'done');
        header('Location: ' . app_url('due-report.php?ok=done'));
        exit;
    }
    if ($action === 'cancel' && $id > 0) {
        reminder_set_status($pdo, $id, 'cancelled');
        header('Location: ' . app_url('due-report.php?ok=cancel'));
        exit;
    }
    if ($action === 'add') {
        reminder_save($pdo, [
            'name' => (string) ($_POST['name'] ?? ''),
            'type' => (string) ($_POST['type'] ?? 'customer'),
            'remind_on' => (string) ($_POST['remind_on'] ?? ''),
            'amount' => $_POST['amount'] ?? '',
            'note' => (string) ($_POST['note'] ?? ''),
            'mobile' => (string) ($_POST['mobile'] ?? ''),
            'force' => true,
        ]);
        header('Location: ' . app_url('due-report.php?ok=add'));
        exit;
    }
}

$pageTitle = 'Due / WhatsApp';
$activeNav = 'due';
require __DIR__ . '/includes/header.php';

$owner = owner_whatsapp_number();
$rows = due_sales_rows($pdo);
$report = whatsapp_due_report_text($pdo, $company);
$waOwner = whatsapp_url($owner, $report);
$auto = isset($_GET['auto']);
$chatReminders = reminder_list($pdo, 'open');
$reminderFlash = (string) ($_GET['ok'] ?? '');

$h = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$overdue = 0;
$todayDue = 0;
$totalDue = 0.0;
foreach ($rows as $row) {
    $totalDue += (float) $row['due_amount'];
    if ($row['status'] === 'overdue') {
        $overdue++;
    }
    if ($row['status'] === 'today') {
        $todayDue++;
    }
}
$todayReminders = reminder_list($pdo, 'today');
?>

<h1 class="mb-2 text-2xl font-bold sm:text-4xl">⏰ Due reminder + WhatsApp</h1>
<p class="mb-4 text-sm text-gray-300">
  Customer ko <strong>invoice preview jaisa PDF</strong> WhatsApp se bhejo. Raat 9 baje aapke number
  <strong>+91 <?= $h(substr($owner, -10)) ?></strong> par due report khulni chahiye.
  Chat assistant se customer/supplier ka date reminder bhi laga sakte ho — us din software aur WhatsApp dono yaad dilayenge.
</p>

<?php if ($reminderFlash === 'done'): ?>
  <div class="mb-4 rounded-xl bg-emerald-600 px-4 py-3 font-semibold">Reminder done mark ho gaya.</div>
<?php elseif ($reminderFlash === 'cancel'): ?>
  <div class="mb-4 rounded-xl bg-slate-600 px-4 py-3 font-semibold">Reminder cancel ho gaya.</div>
<?php elseif ($reminderFlash === 'add'): ?>
  <div class="mb-4 rounded-xl bg-emerald-600 px-4 py-3 font-semibold">Reminder save ho gaya.</div>
<?php endif; ?>

<?php $reminderBannerRows = $todayReminders; require __DIR__ . '/includes/reminder-banner.php'; ?>

<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
  <article class="rounded-2xl bg-rose-700 p-5"><h3>Overdue bills</h3><p class="text-3xl font-bold"><?= (int) $overdue ?></p></article>
  <article class="rounded-2xl bg-amber-600 p-5"><h3>Aaj due bills</h3><p class="text-3xl font-bold"><?= (int) $todayDue ?></p></article>
  <article class="rounded-2xl bg-orange-700 p-5"><h3>Chat reminders aaj</h3><p class="text-3xl font-bold"><?= (int) count($todayReminders) ?></p></article>
  <article class="rounded-2xl bg-emerald-700 p-5"><h3>Kul pending bills</h3><p class="text-3xl font-bold">₹<?= $h(money($totalDue)) ?></p></article>
</div>

<section class="mb-8 rounded-2xl border border-white/15 bg-white/10 p-4">
  <h2 class="mb-3 text-xl font-bold">Chat / manual reminder</h2>
  <p class="mb-4 text-sm text-gray-300">Assistant mein bolo: <em>Ram ko 25/08/2026 due reminder</em> ya yahan form bharo. Us date ko Home/Assistant par banner aayega, raat 9 baje WhatsApp report mein bhi.</p>
  <form method="post" class="grid grid-cols-1 gap-3 md:grid-cols-6">
    <input type="hidden" name="reminder_action" value="add">
    <input type="text" name="name" required placeholder="Customer / supplier naam" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900 md:col-span-2">
    <select name="type" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
      <option value="customer">Customer</option>
      <option value="supplier">Supplier</option>
      <option value="painter">Painter</option>
      <option value="plumber">Plumber</option>
      <option value="electrician">Electrician</option>
    </select>
    <input type="date" name="remind_on" required class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    <input type="number" step="0.01" name="amount" placeholder="Amount ₹" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    <input type="text" name="note" placeholder="Note" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900 md:col-span-2">
    <input type="text" name="mobile" placeholder="Mobile" class="rounded-xl border border-gray-300 bg-white p-3 text-gray-900">
    <button type="submit" class="rounded-xl bg-amber-600 px-4 py-3 font-semibold md:col-span-2">Reminder lagaao</button>
  </form>

  <div class="mt-6 table-scroll overflow-auto rounded-2xl border border-white/15">
    <table class="w-full text-left text-sm">
      <thead class="bg-white/10">
        <tr>
          <th class="p-3">Status</th>
          <th class="p-3">Naam</th>
          <th class="p-3">Date</th>
          <th class="p-3 text-right">₹</th>
          <th class="p-3">Note</th>
          <th class="p-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($chatReminders === []): ?>
          <tr><td class="p-4" colspan="6">Koi pending chat reminder nahi. Assistant se lagaao.</td></tr>
        <?php else: ?>
          <?php foreach ($chatReminders as $row): ?>
            <?php
              $when = (string) ($row['when'] ?? '');
              $tag = $when === 'overdue' ? 'Overdue' : ($when === 'today' ? 'Aaj' : 'Upcoming');
            ?>
            <tr class="border-t border-white/10">
              <td class="p-3"><?= $h($tag) ?></td>
              <td class="p-3"><?= $h((string) ($row['party_name'] ?? '')) ?>
                <div class="text-xs text-gray-400"><?= $h((string) ($row['party_type'] ?? '')) ?> <?= $h((string) ($row['mobile'] ?? '')) ?></div>
              </td>
              <td class="p-3"><?= $h((string) ($row['date'] ?? '')) ?></td>
              <td class="p-3 text-right"><?= isset($row['amount']) && $row['amount'] !== null && $row['amount'] !== '' ? '₹' . $h(money((float) $row['amount'])) : '—' ?></td>
              <td class="p-3"><?= $h((string) ($row['note'] ?? '')) ?></td>
              <td class="p-3">
                <div class="flex flex-wrap gap-2">
                  <form method="post">
                    <input type="hidden" name="reminder_action" value="done">
                    <input type="hidden" name="reminder_id" value="<?= (int) $row['id'] ?>">
                    <button class="rounded bg-emerald-600 px-3 py-1" type="submit">Done</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="reminder_action" value="cancel">
                    <input type="hidden" name="reminder_id" value="<?= (int) $row['id'] ?>">
                    <button class="rounded bg-white/20 px-3 py-1" type="submit">Cancel</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="mb-6 flex flex-wrap gap-3">
  <?php if ($waOwner !== ''): ?>
    <a id="btn-wa-owner" class="rounded-xl bg-green-600 px-5 py-3 font-semibold" href="<?= $h($waOwner) ?>" target="_blank">📲 Report mere WhatsApp par</a>
  <?php endif; ?>
</div>

<div class="mb-8 rounded-2xl border border-white/15 bg-white/10 p-4 text-sm text-gray-200">
  <p class="font-semibold text-white">Raat 9 baje automatic:</p>
  <ol class="mt-2 list-decimal space-y-1 pl-5">
    <li>Windows Search → <strong>Task Scheduler</strong> kholo.</li>
    <li>Create Basic Task → naam: <em>Hardware Due WhatsApp</em>.</li>
    <li>Daily, time <strong>9:05 PM</strong>.</li>
    <li>Action: Start a program → file:
      <code class="break-all"><?= $h(str_replace('/', '\\', APP_ROOT . '/scripts/send-due-whatsapp.bat')) ?></code>
    </li>
    <li>Shop PC on ho, WhatsApp Desktop/Web login ho. Browser WhatsApp kholega — <strong>Send</strong> dabao.</li>
  </ol>
  <p class="mt-3 text-xs text-gray-400">Bill WhatsApp par PDF jata hai (invoice preview jaisa). Phone/PC par share sheet se WhatsApp choose karo. Desktop par PDF download hota hai — chat khulne ke baad 📎 se wahi PDF attach karo. Raat wali due report text rehti hai; Send tap zaroori hai.</p>
</div>

<div class="table-scroll overflow-auto rounded-2xl border border-white/15 bg-white/10">
  <table class="w-full text-left">
    <thead class="bg-white/10">
      <tr>
        <th class="p-3">Status</th>
        <th class="p-3">Invoice</th>
        <th class="p-3">Customer</th>
        <th class="p-3">Due date</th>
        <th class="p-3 text-right">Due ₹</th>
        <th class="p-3">WhatsApp</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($rows === []): ?>
        <tr><td class="p-4" colspan="6">Koi pending due nahi.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <?php
            $waCust = whatsapp_digits((string) ($row['mobile'] ?? '')) !== ''
                ? app_url('invoice.php?id=' . (int) $row['id'] . '&whatsapp=1')
                : '';
            $st = (string) $row['status'];
            $stLabel = $st === 'overdue' ? 'Overdue' : ($st === 'today' ? 'Aaj' : ($st === 'upcoming' ? 'Upcoming' : 'Open'));
            ?>
          <tr class="border-t border-white/10">
            <td class="p-3"><?= $h($stLabel) ?></td>
            <td class="p-3"><?= $h((string) $row['invoice_no']) ?></td>
            <td class="p-3"><?= $h((string) ($row['customer_name'] ?? '')) ?><div class="text-xs text-gray-400"><?= $h((string) ($row['mobile'] ?? '')) ?></div></td>
            <td class="p-3"><?= !empty($row['due_date']) ? $h(format_display_date($row['due_date'])) : '—' ?></td>
            <td class="p-3 text-right">₹<?= $h(money((float) $row['due_amount'])) ?></td>
            <td class="p-3">
              <?php if ($waCust !== ''): ?>
                <a class="rounded bg-green-600 px-3 py-1 text-white" href="<?= $h($waCust) ?>" target="_blank">Bill WA</a>
              <?php else: ?>
                <span class="text-xs text-gray-400">mobile nahi</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($auto && $waOwner !== ''): ?>
<script>
  window.location.href = <?= json_encode($waOwner, JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
