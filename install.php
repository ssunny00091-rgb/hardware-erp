<?php

declare(strict_types=1);

/**
 * One-time installer: creates the database/tables if missing and seeds sample data.
 * Visit /install.php after setting DB credentials in config/config.php or env vars.
 */

$config = require __DIR__ . '/config/config.php';
$db = $config['db'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rootDsn = sprintf(
            'mysql:host=%s;port=%d;charset=%s',
            $db['host'],
            $db['port'],
            $db['charset']
        );
        $pdo = new PDO($rootDsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $db['name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbName`");

        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Could not read sql/schema.sql');
        }

        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            if ($statement === '' || str_starts_with($statement, '--') || stripos($statement, 'CREATE DATABASE') === 0 || stripos($statement, 'USE ') === 0) {
                continue;
            }
            $pdo->exec($statement);
        }

        if (!empty($_POST['seed'])) {
            $seed = file_get_contents(__DIR__ . '/sql/seed.sql');
            if ($seed !== false) {
                foreach (array_filter(array_map('trim', explode(';', $seed))) as $statement) {
                    if ($statement === '' || str_starts_with($statement, '--') || stripos($statement, 'USE ') === 0) {
                        continue;
                    }
                    $pdo->exec($statement);
                }
            }
        }

        $message = 'Database is ready. You can open the dashboard.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Install Hardware ERP</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 p-8 text-white">
  <div class="mx-auto max-w-xl rounded-2xl border border-white/20 bg-white/10 p-8">
    <h1 class="mb-4 text-3xl font-bold">MySQL Setup</h1>
    <p class="mb-6 text-gray-300">
      Host: <?= htmlspecialchars($db['host'], ENT_QUOTES, 'UTF-8') ?> |
      DB: <?= htmlspecialchars($db['name'], ENT_QUOTES, 'UTF-8') ?> |
      User: <?= htmlspecialchars($db['user'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <?php if ($message): ?>
      <p class="mb-4 rounded bg-green-700 p-3"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
      <a class="inline-block rounded bg-green-600 px-4 py-2" href="/index.php">Open Dashboard</a>
    <?php else: ?>
      <?php if ($error): ?>
        <p class="mb-4 rounded bg-red-700 p-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <form method="post">
        <label class="mb-4 flex items-center gap-2">
          <input type="checkbox" name="seed" value="1" checked>
          Load sample products and customers
        </label>
        <button class="rounded-xl bg-green-600 px-6 py-3 font-semibold" type="submit">Create tables</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
