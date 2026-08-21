<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
$config = require dirname(__DIR__) . '/config/config.php';
$company = $config['company'];
$pageTitle = $pageTitle ?? $company['name'];
$activeNav = $activeNav ?? 'home';
?>
<!DOCTYPE html>
<html lang="hi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <script>window.APP_BASE = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;</script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="<?= htmlspecialchars(app_url('assets/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
  <style>
    input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]),
    select, textarea {
      color: #111827 !important;
      background-color: #ffffff !important;
      caret-color: #111827;
    }
    input::placeholder, textarea::placeholder { color: #6b7280 !important; opacity: 1 !important; }
  </style>
  <script src="<?= htmlspecialchars(app_url('assets/js/app.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
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
      <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-lg font-bold"><?= htmlspecialchars($company['name'], ENT_QUOTES, 'UTF-8') ?></a>
      <div class="flex gap-2">
        <a href="<?= htmlspecialchars(app_url('index.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-4 py-2 <?= $activeNav === 'home' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">🏠 Dashboard</a>
        <a href="<?= htmlspecialchars(app_url('products.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-4 py-2 <?= $activeNav === 'products' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">📦 Products</a>
        <a href="<?= htmlspecialchars(app_url('purchase.php'), ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg px-4 py-2 <?= $activeNav === 'purchase' ? 'bg-green-600' : 'bg-white/10 hover:bg-white/20' ?>">🛒 Purchase</a>
      </div>
    </div>
  </nav>

  <main class="relative z-10 mx-auto min-h-screen max-w-7xl p-6">
