<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';
$company = $config['company'];
$pageTitle = $pageTitle ?? $company['name'];
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/style.css">
  <script src="/assets/js/app.js"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
  <div class="animated-bg" aria-hidden="true">
    <span class="blob blob-blue"></span>
    <span class="blob blob-orange"></span>
    <span class="blob blob-green"></span>
    <span class="blob blob-purple"></span>
  </div>

  <nav class="relative z-20 border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
      <a href="/index.php" class="text-lg font-bold"><?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></a>
      <div class="flex gap-2">
        <a href="/index.php" class="rounded-lg px-4 py-2 <?= $activeNav === 'home' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">🏠 Dashboard</a>
        <a href="/products.php" class="rounded-lg px-4 py-2 <?= $activeNav === 'products' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">📦 Products</a>
        <a href="/purchase.php" class="rounded-lg px-4 py-2 <?= $activeNav === 'purchase' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">🛒 Purchase</a>
      </div>
    </div>
  </nav>

  <main class="relative z-10 mx-auto min-h-screen max-w-7xl p-6">
