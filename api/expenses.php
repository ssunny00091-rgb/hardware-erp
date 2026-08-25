<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$pdo   = db();
$month = $_GET['month'] ?? date('Y-m');
$from  = $_GET['from'] ?? null;
$to    = $_GET['to'] ?? null;
$date  = $_GET['date'] ?? null;

function expense_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            expense_json_response(['error' => 'ID required'], 400);
        }
        $pdo->prepare('DELETE FROM expenses WHERE id = :id')->execute(['id' => $id]);
        expense_json_response(['ok' => true]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = read_json_body();
        $expDate   = trim((string) ($body['expense_date'] ?? date('Y-m-d')));
        $category  = trim((string) ($body['category'] ?? 'General'));
        $desc      = trim((string) ($body['description'] ?? ''));
        $amount    = (float) ($body['amount'] ?? 0);
        $payMode   = trim((string) ($body['payment_mode'] ?? 'Cash'));

        if ($amount <= 0) {
            expense_json_response(['error' => 'Amount 0 se zyada hona chahiye'], 400);
        }
        if ($expDate === '') {
            $expDate = date('Y-m-d');
        }

        $ins = $pdo->prepare(
            'INSERT INTO expenses (expense_date, category, description, amount, payment_mode) VALUES (:d, :cat, :desc, :amt, :pm)'
        );
        $ins->execute([
            'd'    => $expDate,
            'cat'  => $category,
            'desc' => $desc,
            'amt'  => $amount,
            'pm'   => $payMode,
        ]);

        expense_json_response([
            'ok'   => true,
            'id'   => (int) $pdo->lastInsertId(),
            'expense_date' => $expDate,
            'category' => $category,
            'description' => $desc,
            'amount' => $amount,
            'payment_mode' => $payMode,
        ]);
    }

    // GET — list with date filters
    $where  = [];
    $params = [];

    if ($date !== null && $date !== '') {
        $where[] = 'expense_date = :date';
        $params['date'] = $date;
    } elseif ($from !== null && $from !== '' && $to !== null && $to !== '') {
        $where[] = 'expense_date BETWEEN :from AND :to';
        $params['from'] = $from;
        $params['to']   = $to;
    } elseif ($month !== '' && $month !== 'all') {
        $where[] = 'expense_date LIKE :month';
        $params['month'] = $month . '%';
    }

    $sql = 'SELECT id, expense_date, category, description, amount, payment_mode, created_at FROM expenses';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY expense_date DESC, id DESC LIMIT 300';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $total = 0.0;
    foreach ($rows as $r) {
        $total += (float) $r['amount'];
    }

    expense_json_response([
        'expenses' => $rows,
        'total'    => round($total, 2),
        'count'    => count($rows),
    ]);

} catch (Throwable $e) {
    expense_json_response(['error' => $e->getMessage()], 500);
}
