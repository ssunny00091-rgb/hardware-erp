<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

session_start();

$config = require __DIR__ . '/config/config.php';
$db = $config['db'];

$step = (int) ($_SESSION['setup_step'] ?? 1);
if ($step < 1 || $step > 6) {
    $step = 1;
}

$error = '';
$notice = '';
$checks = [];

function setup_redirect(int $step): void
{
    $_SESSION['setup_step'] = $step;
    header('Location: ' . app_url('install.php'));
    exit;
}

function php_setup_checks(): array
{
    $pdoMysql = extension_loaded('pdo_mysql');
    return [
        ['label' => 'PHP 8.1 ya usse naya', 'ok' => version_compare(PHP_VERSION, '8.1.0', '>='), 'detail' => 'Abhi: ' . PHP_VERSION],
        ['label' => 'PDO extension', 'ok' => extension_loaded('pdo'), 'detail' => extension_loaded('pdo') ? 'Installed' : 'Missing'],
        ['label' => 'pdo_mysql (MySQL driver)', 'ok' => $pdoMysql, 'detail' => $pdoMysql ? 'Installed' : 'XAMPP/PHP mein mysql extension on karo'],
        ['label' => 'sql/schema.sql', 'ok' => is_readable(__DIR__ . '/sql/schema.sql'), 'detail' => 'Table structure'],
        ['label' => 'sql/seed.sql', 'ok' => is_readable(__DIR__ . '/sql/seed.sql'), 'detail' => 'Sample products/customers'],
        ['label' => 'Folder writable (.env save ke liye)', 'ok' => is_writable(__DIR__), 'detail' => __DIR__],
    ];
}

function posted_db(): array
{
    return [
        'host' => trim((string) ($_POST['db_host'] ?? '127.0.0.1')) ?: '127.0.0.1',
        'port' => (int) ($_POST['db_port'] ?? 3306) ?: 3306,
        'name' => preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($_POST['db_name'] ?? 'hardware_erp')) ?: 'hardware_erp',
        'user' => trim((string) ($_POST['db_user'] ?? 'root')) ?: 'root',
        'pass' => (string) ($_POST['db_pass'] ?? ''),
        'charset' => 'utf8mb4',
    ];
}

if (isset($_GET['restart'])) {
    $_SESSION['setup_step'] = 1;
    header('Location: ' . app_url('install.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'step1') {
            $checks = php_setup_checks();
            foreach ($checks as $check) {
                if (!$check['ok']) {
                    throw new RuntimeException('Pehle red items fix karo, phir aage badho.');
                }
            }
            setup_redirect(2);
        }

        if ($action === 'step2') {
            $posted = posted_db();
            $pdo = mysql_server_pdo($posted);
            $pdo->query('SELECT 1');
            save_env_file([
                'DB_HOST' => $posted['host'],
                'DB_PORT' => (string) $posted['port'],
                'DB_NAME' => $posted['name'],
                'DB_USER' => $posted['user'],
                'DB_PASS' => $posted['pass'],
            ]);
            load_env_file();
            unset($GLOBALS['APP_CONFIG']);
            setup_redirect(3);
        }

        if ($action === 'step3') {
            $pdo = mysql_server_pdo($db);
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            setup_redirect(4);
        }

        if ($action === 'step4') {
            $pdo = mysql_server_pdo($db);
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
            $pdo->exec("USE `$name`");
            run_sql_file($pdo, __DIR__ . '/sql/schema.sql');
            setup_redirect(5);
        }

        if ($action === 'step5') {
            $pdo = mysql_server_pdo($db);
            $name = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
            $pdo->exec("USE `$name`");
            if (!empty($_POST['seed'])) {
                run_sql_file($pdo, __DIR__ . '/sql/seed.sql');
            }
            file_put_contents(__DIR__ . '/install.lock', date('c') . "\n");
            setup_redirect(6);
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$checks = php_setup_checks();
$allOk = !in_array(false, array_column($checks, 'ok'), true);
$alreadyInstalled = is_file(__DIR__ . '/install.lock');

$steps = [
    1 => 'PHP check',
    2 => 'MySQL details',
    3 => 'Database banao',
    4 => 'Tables banao',
    5 => 'Sample data',
    6 => 'Ho gaya',
];
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup — Hardware ERP</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 p-6 text-white">
  <div class="mx-auto max-w-3xl">
    <h1 class="mb-2 text-3xl font-bold">Hardware ERP — One by one setup</h1>
    <p class="mb-6 text-gray-300">Har step complete karke Next dabao. XAMPP default: host <code>127.0.0.1</code>, user <code>root</code>, password blank.</p>

    <ol class="mb-8 grid grid-cols-2 gap-2 md:grid-cols-6">
      <?php foreach ($steps as $number => $label): ?>
        <li class="rounded-xl px-3 py-2 text-center text-sm <?= $number === $step ? 'bg-green-600' : ($number < $step ? 'bg-green-900' : 'bg-white/10') ?>">
          <?= $number ?>. <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </li>
      <?php endforeach; ?>
    </ol>

    <?php if ($error): ?>
      <div class="mb-6 rounded-xl bg-red-700 p-4"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="rounded-2xl border border-white/20 bg-white/10 p-8">
      <?php if ($step === 1): ?>
        <h2 class="mb-4 text-2xl font-bold">Step 1 — PHP check</h2>
        <ul class="mb-6 space-y-2">
          <?php foreach ($checks as $check): ?>
            <li class="flex items-start justify-between gap-4 rounded-lg bg-black/30 p-3">
              <span><?= htmlspecialchars($check['label'], ENT_QUOTES, 'UTF-8') ?><br><span class="text-sm text-gray-400"><?= htmlspecialchars($check['detail'], ENT_QUOTES, 'UTF-8') ?></span></span>
              <span><?= $check['ok'] ? '✅' : '❌' ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <form method="post">
          <input type="hidden" name="action" value="step1">
          <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold disabled:opacity-40" <?= $allOk ? '' : 'disabled' ?>>Next: MySQL details →</button>
        </form>

      <?php elseif ($step === 2): ?>
        <h2 class="mb-4 text-2xl font-bold">Step 2 — MySQL details</h2>
        <p class="mb-4 text-gray-300">phpMyAdmin / XAMPP wale username-password yahan daalo. Database pehle se hona zaroori nahi.</p>
        <form method="post" class="grid gap-4">
          <input type="hidden" name="action" value="step2">
          <label>Host
            <input name="db_host" value="<?= htmlspecialchars($db['host'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border p-3 text-black">
          </label>
          <label>Port
            <input name="db_port" value="<?= htmlspecialchars((string) $db['port'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border p-3 text-black">
          </label>
          <label>Database name
            <input name="db_name" value="<?= htmlspecialchars($db['name'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border p-3 text-black">
          </label>
          <label>Username
            <input name="db_user" value="<?= htmlspecialchars($db['user'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border p-3 text-black">
          </label>
          <label>Password
            <input type="password" name="db_pass" value="<?= htmlspecialchars($db['pass'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border p-3 text-black" placeholder="XAMPP mein aksar khali">
          </label>
          <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold">Test connection + save →</button>
        </form>

      <?php elseif ($step === 3): ?>
        <h2 class="mb-4 text-2xl font-bold">Step 3 — Database banao</h2>
        <p class="mb-4 text-gray-300">Database <strong><?= htmlspecialchars($db['name'], ENT_QUOTES, 'UTF-8') ?></strong> create hogi (agar pehle se nahi hai).</p>
        <form method="post">
          <input type="hidden" name="action" value="step3">
          <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold">Create database →</button>
        </form>

      <?php elseif ($step === 4): ?>
        <h2 class="mb-4 text-2xl font-bold">Step 4 — Tables banao</h2>
        <p class="mb-4 text-gray-300">customers, products, sales, sale_items, purchases, purchase_items.</p>
        <form method="post">
          <input type="hidden" name="action" value="step4">
          <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold">Create tables →</button>
        </form>

      <?php elseif ($step === 5): ?>
        <h2 class="mb-4 text-2xl font-bold">Step 5 — Sample data</h2>
        <p class="mb-4 text-gray-300">Paint, cement, Fevicol jaise products aur 3 customers load ho sakte hain. Skip bhi kar sakte ho.</p>
        <form method="post" class="space-y-4">
          <input type="hidden" name="action" value="step5">
          <label class="flex items-center gap-2">
            <input type="checkbox" name="seed" value="1" checked>
            Sample products + customers load karo
          </label>
          <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold">Finish setup →</button>
        </form>

      <?php else: ?>
        <h2 class="mb-4 text-2xl font-bold">Step 6 — Setup complete</h2>
        <p class="mb-4 text-green-300">MySQL ready hai. Ab billing use kar sakte ho.</p>
        <p class="mb-6 text-gray-300">
          Host: <?= htmlspecialchars($db['host'], ENT_QUOTES, 'UTF-8') ?> |
          DB: <?= htmlspecialchars($db['name'], ENT_QUOTES, 'UTF-8') ?> |
          User: <?= htmlspecialchars($db['user'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <a class="inline-block rounded-xl bg-green-600 px-6 py-3 font-semibold" href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard kholo</a>
      <?php endif; ?>
    </div>

    <p class="mt-6 text-sm text-gray-400">
      <?php if ($alreadyInstalled): ?>Setup pehle ho chuka hai. <?php endif; ?>
      <a class="underline" href="<?= htmlspecialchars(app_url('install.php?restart=1'), ENT_QUOTES, 'UTF-8') ?>">Setup shuru se</a>
    </p>
  </div>
</body>
</html>
