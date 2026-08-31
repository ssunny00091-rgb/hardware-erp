<?php

declare(strict_types=1);

$rows = $reminderBannerRows ?? [];
if (!is_array($rows) || $rows === []) {
    return;
}
$h = $h ?? static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<div class="mb-6 rounded-2xl border border-amber-400/40 bg-amber-600/90 p-4 text-white shadow-xl">
  <p class="font-bold">⏰ Aaj / overdue reminders (<?= (int) count($rows) ?>)</p>
  <p class="mt-1 text-sm text-amber-50">Chat se jo due reminder lage the — aaj yaad karna hai. Done dabane par list se hat jayega.</p>
  <ul class="mt-3 space-y-2 text-sm">
    <?php foreach ($rows as $row): ?>
      <?php
        $when = (string) ($row['when'] ?? '');
        $tag = $when === 'overdue' ? 'Overdue' : 'Aaj';
        $amt = isset($row['amount']) && $row['amount'] !== null && $row['amount'] !== ''
            ? '₹' . money((float) $row['amount'])
            : '';
      ?>
      <li class="flex flex-col gap-2 rounded-xl bg-black/20 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <strong><?= $h((string) ($row['party_name'] ?? '')) ?></strong>
          <span class="text-amber-100">(<?= $h((string) ($row['party_type'] ?? '')) ?> · <?= $h($tag) ?> · <?= $h((string) ($row['date'] ?? '')) ?>)</span>
          <?php if ($amt !== ''): ?><div><?= $h($amt) ?></div><?php endif; ?>
          <?php if (trim((string) ($row['note'] ?? '')) !== ''): ?>
            <div class="text-amber-100"><?= $h((string) $row['note']) ?></div>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= $h(app_url('due-report.php')) ?>" class="shrink-0">
          <input type="hidden" name="reminder_action" value="done">
          <input type="hidden" name="reminder_id" value="<?= (int) ($row['id'] ?? 0) ?>">
          <button type="submit" class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-amber-800">Done</button>
        </form>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="mt-3 text-xs"><a class="underline" href="<?= $h(app_url('due-report.php')) ?>">Saari list — Due / WA</a>
    · Assistant mein bolo: <em>Ram ko 25/08/2026 due reminder</em></p>
</div>
