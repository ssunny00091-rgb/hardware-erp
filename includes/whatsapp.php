<?php

declare(strict_types=1);

function owner_whatsapp_number(): string
{
    $config = require dirname(__DIR__) . '/config/config.php';
    $raw = (string) ($config['company']['whatsapp'] ?? $config['company']['owner_whatsapp'] ?? '9831046765');
    return whatsapp_digits($raw);
}

function whatsapp_digits(string $phone): string
{
    $n = preg_replace('/\D+/', '', $phone) ?? '';
    if ($n === '') {
        return '';
    }
    if (strlen($n) === 10) {
        return '91' . $n;
    }
    if (strlen($n) === 11 && str_starts_with($n, '0')) {
        return '91' . substr($n, 1);
    }
    if (strlen($n) === 12 && str_starts_with($n, '91')) {
        return $n;
    }
    if (strlen($n) > 12 && str_starts_with($n, '91')) {
        return substr($n, 0, 12);
    }
    return $n;
}

function whatsapp_url(string $phone, string $text): string
{
    $n = whatsapp_digits($phone);
    if ($n === '') {
        return '';
    }
    if (strlen($text) > 3500) {
        $text = substr($text, 0, 3400) . "\n\n...baaki detail shop software mein hai.";
    }
    return 'https://wa.me/' . $n . '?text=' . rawurlencode($text);
}

function whatsapp_inr(float $n): string
{
    return 'Rs ' . money($n);
}

function whatsapp_invoice_text(array $company, array $sale, array $items): string
{
    $name = (string) ($company['name'] ?? 'Hardware Store');
    $inv = (string) ($sale['invoice_no'] ?? '');
    $party = (string) (($sale['customer_name'] ?? '') !== '' ? $sale['customer_name'] : 'Customer');
    $date = format_display_date($sale['sale_date'] ?? $sale['created_at'] ?? '');
    $grand = (float) ($sale['total'] ?? 0);
    $received = isset($sale['received']) && $sale['received'] !== null && $sale['received'] !== ''
        ? (float) $sale['received']
        : $grand;
    $due = max(0, $grand - $received);
    $dueDate = trim((string) ($sale['due_date'] ?? ''));

    $lines = [
        '*' . $name . '*',
        'Tax Invoice',
        'Invoice: ' . $inv,
        'Date: ' . $date,
        'Customer: ' . $party,
    ];
    if (!empty($sale['mobile'])) {
        $lines[] = 'Mobile: ' . $sale['mobile'];
    }
    $lines[] = '';
    $i = 1;
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $pname = (string) ($item['product_name'] ?? $item['name'] ?? '');
        $qty = $item['qty'] ?? '';
        $unit = (string) ($item['unit'] ?? '');
        $total = (float) ($item['total'] ?? 0);
        $color = trim((string) ($item['color_code'] ?? $item['color'] ?? ''));
        $bit = $i . '. ' . $pname;
        if ($color !== '') {
            $bit .= ' (' . $color . ')';
        }
        $bit .= '  ' . $qty . ' ' . $unit . ' = ' . whatsapp_inr($total);
        $lines[] = $bit;
        $i++;
    }
    $lines[] = '';
    $lines[] = '*Total: ' . whatsapp_inr($grand) . '*';
    $lines[] = 'Received: ' . whatsapp_inr($received);
    $lines[] = 'Due: ' . whatsapp_inr($due);
    if ($due > 0.009 && $dueDate !== '') {
        $lines[] = 'Due date: ' . format_display_date($dueDate);
    }
    $lines[] = '';
    $lines[] = 'Phone: ' . (string) ($company['mobile'] ?? '');
    $lines[] = 'Dhanyavaad!';
    return implode("\n", $lines);
}

function whatsapp_ledger_text(array $company, array $ledger): string
{
    $party = $ledger['party'] ?? [];
    $name = (string) ($party['name'] ?? 'Party');
    $shop = (string) ($company['name'] ?? 'Hardware Store');
    $lines = [
        '*' . $shop . '*',
        'Ledger / Hisaab — ' . $name,
        'Date: ' . date('d/m/Y'),
        '',
        'Debit: ' . whatsapp_inr((float) ($ledger['debit'] ?? 0)),
        'Credit: ' . whatsapp_inr((float) ($ledger['credit'] ?? 0)),
        '*Balance: ' . whatsapp_inr((float) ($ledger['balance'] ?? 0)) . '*',
        '',
    ];
    $entries = is_array($ledger['entries'] ?? null) ? $ledger['entries'] : [];
    $slice = array_slice($entries, -12);
    foreach ($slice as $e) {
        if (!is_array($e)) {
            continue;
        }
        $lines[] = format_display_date($e['entry_date'] ?? $e['date'] ?? '') . '  '
            . (string) ($e['particulars'] ?? '') . '  Dr '
            . whatsapp_inr((float) ($e['debit'] ?? 0)) . ' / Cr '
            . whatsapp_inr((float) ($e['credit'] ?? 0));
    }
    $lines[] = '';
    $lines[] = 'Phone: ' . (string) ($company['mobile'] ?? '');
    return implode("\n", $lines);
}

function due_sales_rows(PDO $pdo): array
{
    $dateSql = 'COALESCE(s.sale_date, DATE(s.created_at))';
    try {
        $sql = "SELECT s.id, s.invoice_no, s.customer_name, s.mobile, s.total,
                       COALESCE(s.received, s.total) AS received,
                       s.due_date, $dateSql AS bill_date
                FROM sales s
                WHERE (s.total - COALESCE(s.received, s.total)) > 0.009
                ORDER BY COALESCE(s.due_date, $dateSql) ASC, s.id ASC
                LIMIT 80";
        $rows = $pdo->query($sql)->fetchAll() ?: [];
    } catch (Throwable $e) {
        try {
            $rows = $pdo->query(
                "SELECT s.id, s.invoice_no, s.customer_name, s.mobile, s.total,
                        COALESCE(s.received, s.total) AS received,
                        $dateSql AS bill_date
                 FROM sales s
                 WHERE (s.total - COALESCE(s.received, s.total)) > 0.009
                 ORDER BY s.id ASC
                 LIMIT 80"
            )->fetchAll() ?: [];
        } catch (Throwable $e2) {
            $rows = $pdo->query(
                'SELECT s.id, s.invoice_no, s.customer_name, s.mobile, s.total
                 FROM sales s ORDER BY s.id DESC LIMIT 40'
            )->fetchAll() ?: [];
        }
    }
    $out = [];
    $today = date('Y-m-d');
    foreach ($rows as $row) {
        $total = (float) ($row['total'] ?? 0);
        $received = isset($row['received']) ? (float) $row['received'] : $total;
        $due = max(0, $total - $received);
        if ($due <= 0.009) {
            continue;
        }
        $dueDate = trim((string) ($row['due_date'] ?? ''));
        $status = 'open';
        if ($dueDate !== '') {
            if ($dueDate < $today) {
                $status = 'overdue';
            } elseif ($dueDate === $today) {
                $status = 'today';
            } else {
                $status = 'upcoming';
            }
        }
        $row['due_amount'] = $due;
        $row['status'] = $status;
        $out[] = $row;
    }
    return $out;
}

function whatsapp_due_report_text(PDO $pdo, array $company): string
{
    $shop = (string) ($company['name'] ?? 'Hardware Store');
    $rows = due_sales_rows($pdo);
    $overdue = [];
    $today = [];
    $upcoming = [];
    $open = [];
    $sum = 0.0;
    foreach ($rows as $row) {
        $sum += (float) $row['due_amount'];
        if ($row['status'] === 'overdue') {
            $overdue[] = $row;
        } elseif ($row['status'] === 'today') {
            $today[] = $row;
        } elseif ($row['status'] === 'upcoming') {
            $upcoming[] = $row;
        } else {
            $open[] = $row;
        }
    }

    $line = static function (array $row): string {
        $dueOn = !empty($row['due_date']) ? format_display_date($row['due_date']) : 'date nahi';
        return '- Inv ' . ($row['invoice_no'] ?? '') . '  '
            . ($row['customer_name'] ?? '') . '  '
            . whatsapp_inr((float) $row['due_amount'])
            . '  due ' . $dueOn
            . (!empty($row['mobile']) ? '  ' . $row['mobile'] : '');
    };

    $lines = [
        '*' . $shop . '*',
        'Raat 9 baje Due Report — ' . date('d/m/Y'),
        '',
        '*Kul pending: ' . whatsapp_inr($sum) . '*  (' . count($rows) . ' bills)',
        '',
    ];
    $blocks = [
        ['OVERDUE (date nikal gayi)', $overdue],
        ['AAJ DUE', $today],
        ['Aane wale', array_slice($upcoming, 0, 12)],
        ['Udhaar (due date nahi)', array_slice($open, 0, 12)],
    ];
    foreach ($blocks as [$title, $list]) {
        if ($list === []) {
            continue;
        }
        $lines[] = '*' . $title . '*';
        foreach ($list as $row) {
            $lines[] = $line($row);
        }
        $lines[] = '';
    }

    try {
        $low = $pdo->query(
            'SELECT product_name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC, product_name ASC LIMIT 12'
        )->fetchAll() ?: [];
        if ($low) {
            $lines[] = '*Low stock*';
            foreach ($low as $p) {
                $lines[] = '- ' . ($p['product_name'] ?? '') . '  stock ' . ($p['stock'] ?? '');
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    $lines[] = '';
    $lines[] = 'Yeh auto reminder aapke WhatsApp par hai.';
    return implode("\n", $lines);
}
