<?php

declare(strict_types=1);

function reports_sale_date_sql(string $alias = 's'): string
{
    return 'COALESCE(' . $alias . '.sale_date, DATE(' . $alias . '.created_at))';
}

function reports_resolve_range(array $args): array
{
    $period = strtolower(trim((string) ($args['period'] ?? 'day')));
    if (!in_array($period, ['day', 'month', 'year', 'range'], true)) {
        $period = 'day';
    }

    $today = date('Y-m-d');
    $from = $today;
    $to = $today;
    $label = format_display_date($today);

    if ($period === 'day') {
        $from = parse_sale_date($args['date'] ?? $args['from'] ?? $today);
        $to = $from;
        $label = format_display_date($from);
    } elseif ($period === 'month') {
        $raw = trim((string) ($args['month'] ?? $args['date'] ?? ''));
        if (preg_match('/^(\d{4})-(\d{2})/', $raw, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
        } else {
            $year = (int) date('Y');
            $month = (int) date('n');
        }
        $month = max(1, min(12, $month));
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));
        $label = date('F Y', strtotime($from));
    } elseif ($period === 'year') {
        $year = (int) ($args['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            $raw = parse_sale_date($args['date'] ?? $today);
            $year = (int) substr($raw, 0, 4);
        }
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);
        $label = (string) $year;
    } else {
        $from = parse_sale_date($args['from'] ?? $args['date'] ?? $today);
        $to = parse_sale_date($args['to'] ?? $from);
        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }
        $label = format_display_date($from) . ' – ' . format_display_date($to);
        $period = 'range';
    }

    return [
        'period' => $period,
        'from' => $from,
        'to' => $to,
        'label' => $label,
    ];
}

function reports_build(PDO $pdo, array $args): array
{
    $range = reports_resolve_range($args);
    $from = $range['from'];
    $to = $range['to'];
    $dateSql = reports_sale_date_sql('s');

    $salesTotal = 0.0;
    $bills = 0;
    try {
        $st = $pdo->prepare(
            "SELECT COALESCE(SUM(s.total), 0), COUNT(*) FROM sales s
             WHERE $dateSql BETWEEN :from AND :to"
        );
        $st->execute(['from' => $from, 'to' => $to]);
        $row = $st->fetch(PDO::FETCH_NUM);
        $salesTotal = (float) ($row[0] ?? 0);
        $bills = (int) ($row[1] ?? 0);
    } catch (Throwable $e) {
        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(s.total), 0), COUNT(*) FROM sales s
             WHERE DATE(s.created_at) BETWEEN :from AND :to'
        );
        $st->execute(['from' => $from, 'to' => $to]);
        $row = $st->fetch(PDO::FETCH_NUM);
        $salesTotal = (float) ($row[0] ?? 0);
        $bills = (int) ($row[1] ?? 0);
        $dateSql = 'DATE(s.created_at)';
    }

    $purchaseSpend = 0.0;
    try {
        $st = $pdo->prepare(
            'SELECT COALESCE(SUM(total), 0) FROM purchases
             WHERE purchase_date BETWEEN :from AND :to'
        );
        $st->execute(['from' => $from, 'to' => $to]);
        $purchaseSpend = (float) $st->fetchColumn();
    } catch (Throwable $e) {
        $purchaseSpend = 0.0;
    }

    $products = [];
    $cogs = 0.0;
    $missingCost = 0.0;
    try {
        $grp = "CASE
                      WHEN si.product_id IS NOT NULL AND si.product_id > 0 THEN CONCAT('id:', si.product_id)
                      ELSE CONCAT('n:', LOWER(TRIM(si.product_name)))
                    END";
        $sql = "SELECT
                    $grp AS grp,
                    MAX(COALESCE(NULLIF(p.product_name, ''), si.product_name)) AS product_name,
                    MAX(si.product_id) AS product_id,
                    SUM(si.qty) AS qty,
                    SUM(si.total) AS amount,
                    SUM(si.qty * COALESCE(p.purchase_price, 0)) AS cogs,
                    SUM(CASE WHEN COALESCE(p.purchase_price, 0) <= 0 THEN si.total ELSE 0 END) AS missing_cost_sales
                 FROM sale_items si
                 INNER JOIN sales s ON s.id = si.sale_id
                 LEFT JOIN products p ON p.id = si.product_id
                 WHERE $dateSql BETWEEN :from AND :to
                 GROUP BY $grp
                 ORDER BY qty DESC, amount DESC";
        $st = $pdo->prepare($sql);
        $st->execute(['from' => $from, 'to' => $to]);
        foreach ($st->fetchAll() as $p) {
            $qty = (float) $p['qty'];
            $amount = (float) $p['amount'];
            $itemCogs = (float) $p['cogs'];
            $cogs += $itemCogs;
            $missingCost += (float) $p['missing_cost_sales'];
            $products[] = [
                'product_id' => (int) ($p['product_id'] ?? 0),
                'name' => (string) ($p['product_name'] ?? ''),
                'qty' => $qty,
                'amount' => $amount,
                'cogs' => $itemCogs,
                'profit' => $amount - $itemCogs,
            ];
        }
    } catch (Throwable $e) {
        $products = [];
    }

    $profit = $salesTotal - $cogs;
    usort($products, static fn (array $a, array $b): int => $b['qty'] <=> $a['qty']);
    $top = array_slice($products, 0, 15);
    $slow = $products;
    usort($slow, static function (array $a, array $b): int {
        if ($a['qty'] === $b['qty']) {
            return $a['amount'] <=> $b['amount'];
        }
        return $a['qty'] <=> $b['qty'];
    });
    $slow = array_slice($slow, 0, 15);

    $unsold = [];
    try {
        $soldIds = [];
        foreach ($products as $p) {
            if ((int) $p['product_id'] > 0) {
                $soldIds[] = (int) $p['product_id'];
            }
        }
        $catalog = $pdo->query(
            'SELECT id, product_name, stock, selling_price FROM products ORDER BY product_name ASC LIMIT 400'
        )->fetchAll() ?: [];
        foreach ($catalog as $row) {
            $id = (int) $row['id'];
            if ($id > 0 && !in_array($id, $soldIds, true)) {
                $unsold[] = [
                    'product_id' => $id,
                    'name' => (string) $row['product_name'],
                    'stock' => (float) $row['stock'],
                    'price' => (float) $row['selling_price'],
                ];
            }
            if (count($unsold) >= 25) {
                break;
            }
        }
    } catch (Throwable $e) {
        $unsold = [];
    }

    $breakup = [];
    if ($range['period'] === 'month' || $range['period'] === 'range') {
        $breakup = reports_time_breakup($pdo, $dateSql, $from, $to, 'day');
    } elseif ($range['period'] === 'year') {
        $breakup = reports_time_breakup($pdo, $dateSql, $from, $to, 'month');
    }

    return [
        'period' => $range['period'],
        'from' => $from,
        'to' => $to,
        'label' => $range['label'],
        'summary' => [
            'sales' => $salesTotal,
            'cogs' => $cogs,
            'profit' => $profit,
            'bills' => $bills,
            'purchase_spend' => $purchaseSpend,
            'missing_cost_sales' => $missingCost,
        ],
        'top_products' => $top,
        'slow_products' => $slow,
        'unsold' => $unsold,
        'breakup' => $breakup,
        'note' => 'Profit = sale amount − product purchase price × qty. Jis item ka purchase rate 0 hai, uska profit poora nahi pakka.',
    ];
}

function reports_time_breakup(PDO $pdo, string $dateSql, string $from, string $to, string $grain): array
{
    $bucket = $grain === 'month'
        ? "DATE_FORMAT($dateSql, '%Y-%m')"
        : $dateSql;
    $rows = [];
    try {
        $st = $pdo->prepare(
            "SELECT $bucket AS bucket,
                    COALESCE(SUM(s.total), 0) AS sales,
                    COUNT(*) AS bills
             FROM sales s
             WHERE $dateSql BETWEEN :from AND :to
             GROUP BY bucket
             ORDER BY bucket ASC"
        );
        $st->execute(['from' => $from, 'to' => $to]);
        $salesRows = $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $cogsBy = [];
    try {
        $st = $pdo->prepare(
            "SELECT $bucket AS bucket,
                    COALESCE(SUM(si.qty * COALESCE(p.purchase_price, 0)), 0) AS cogs
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             LEFT JOIN products p ON p.id = si.product_id
             WHERE $dateSql BETWEEN :from AND :to
             GROUP BY bucket"
        );
        $st->execute(['from' => $from, 'to' => $to]);
        foreach ($st->fetchAll() as $row) {
            $cogsBy[(string) $row['bucket']] = (float) $row['cogs'];
        }
    } catch (Throwable $e) {
        $cogsBy = [];
    }

    foreach ($salesRows as $row) {
        $key = (string) $row['bucket'];
        $sales = (float) $row['sales'];
        $cogs = $cogsBy[$key] ?? 0.0;
        $label = $grain === 'month'
            ? date('M Y', strtotime($key . '-01'))
            : format_display_date($key);
        $rows[] = [
            'key' => $key,
            'label' => $label,
            'sales' => $sales,
            'cogs' => $cogs,
            'profit' => $sales - $cogs,
            'bills' => (int) $row['bills'],
        ];
    }
    return $rows;
}
