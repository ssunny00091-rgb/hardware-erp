<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        if (isset($_GET['next_invoice'])) {
            json_response(['invoice_no' => peek_next_invoice_number($pdo)]);
        }

        $id = (int) ($_GET['id'] ?? 0);

        if ($id > 0) {
            $sale = load_sale_full($pdo, $id);
            if (!$sale) {
                json_response(['error' => 'Sale not found'], 404);
            }
            json_response(['sale' => $sale]);
        }

        $date = trim((string) ($_GET['date'] ?? ''));
        $from = trim((string) ($_GET['from'] ?? ''));
        $to = trim((string) ($_GET['to'] ?? ''));
        $valid = static fn (string $v): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
        if (!$valid($date)) {
            $date = '';
        }
        if (!$valid($from)) {
            $from = '';
        }
        if (!$valid($to)) {
            $to = '';
        }

        try {
            $where = [];
            $params = [];
            if ($date !== '') {
                $where[] = 'COALESCE(sale_date, DATE(created_at)) = :date';
                $params['date'] = $date;
            }
            if ($from !== '') {
                $where[] = 'COALESCE(sale_date, DATE(created_at)) >= :from';
                $params['from'] = $from;
            }
            if ($to !== '') {
                $where[] = 'COALESCE(sale_date, DATE(created_at)) <= :to';
                $params['to'] = $to;
            }
            $sql = 'SELECT id, invoice_no, customer_name, mobile, total, received, ref_type, ref_name, sale_date, due_date, created_at
                 FROM sales';
            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY COALESCE(sale_date, DATE(created_at)) DESC, id DESC LIMIT 500';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_response(['sales' => $stmt->fetchAll()]);
        } catch (Throwable $e) {
            $where = [];
            $params = [];
            if ($date !== '') {
                $where[] = 'DATE(created_at) = :date';
                $params['date'] = $date;
            }
            if ($from !== '') {
                $where[] = 'DATE(created_at) >= :from';
                $params['from'] = $from;
            }
            if ($to !== '') {
                $where[] = 'DATE(created_at) <= :to';
                $params['to'] = $to;
            }
            $sql = 'SELECT id, invoice_no, customer_name, mobile, total, created_at FROM sales';
            if ($where !== []) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY created_at DESC, id DESC LIMIT 500';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_response(['sales' => $stmt->fetchAll()]);
        }
    }

    if ($method === 'POST' || $method === 'PUT') {
        $body = read_json_body();
        $data = sale_payload($body);
        $editId = (int) ($body['id'] ?? $_GET['id'] ?? 0);

        $pdo->beginTransaction();
        if ($method === 'PUT' || $editId > 0) {
            if ($editId <= 0) {
                json_response(['error' => 'Invalid sale id'], 422);
            }
            $existing = $pdo->prepare('SELECT id, invoice_no, created_at FROM sales WHERE id = :id');
            $existing->execute(['id' => $editId]);
            $row = $existing->fetch();
            if (!$row) {
                json_response(['error' => 'Sale not found'], 404);
            }
            restore_sale_stock($pdo, $editId);
            $pdo->prepare('DELETE FROM sale_items WHERE sale_id = :id')->execute(['id' => $editId]);
            $saved = persist_sale_header(
                $pdo,
                $data,
                $editId,
                (string) $row['invoice_no'],
                $data['saleDate']
            );
        } else {
            $saved = persist_sale_header($pdo, $data, null, null, $data['saleDate']);
        }
        $pdo->commit();
        json_response(['ok' => true] + $saved);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            json_response(['error' => 'Invalid sale id'], 422);
        }

        $pdo->beginTransaction();
        restore_sale_stock($pdo, $id);
        delete_sale_ledgers($pdo, $id);
        $pdo->prepare('DELETE FROM sales WHERE id = :id')->execute(['id' => $id]);
        $pdo->commit();
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => $e->getMessage()], 500);
}
