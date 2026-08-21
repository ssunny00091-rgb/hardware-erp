<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = db();
    backfill_ledgers($pdo);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $partyId = (int) ($_GET['party_id'] ?? 0);
        if ($partyId > 0) {
            $party = $pdo->prepare('SELECT * FROM parties WHERE id = :id');
            $party->execute(['id' => $partyId]);
            $row = $party->fetch();
            if (!$row) {
                json_response(['error' => 'Party not found'], 404);
            }
            $entries = $pdo->prepare(
                'SELECT id, entry_date, particulars, ref_no, debit, credit, sale_id, purchase_id
                 FROM ledger_entries
                 WHERE party_id = :id
                 ORDER BY entry_date ASC, id ASC'
            );
            $entries->execute(['id' => $partyId]);
            $list = $entries->fetchAll();
            $balance = 0.0;
            foreach ($list as &$entry) {
                $balance += (float) $entry['debit'] - (float) $entry['credit'];
                $entry['balance'] = $balance;
            }
            unset($entry);
            json_response([
                'party' => $row,
                'entries' => $list,
                'debit' => array_sum(array_map(static fn ($e) => (float) $e['debit'], $list)),
                'credit' => array_sum(array_map(static fn ($e) => (float) $e['credit'], $list)),
                'balance' => $balance,
            ]);
        }

        $type = normalize_party_type((string) ($_GET['type'] ?? 'customer'));
        $stmt = $pdo->prepare(
            'SELECT p.id, p.name, p.mobile, p.type,
                    COALESCE(SUM(l.debit), 0) AS debit,
                    COALESCE(SUM(l.credit), 0) AS credit,
                    COALESCE(SUM(l.debit), 0) - COALESCE(SUM(l.credit), 0) AS balance
             FROM parties p
             LEFT JOIN ledger_entries l ON l.party_id = p.id
             WHERE p.type = :type
             GROUP BY p.id, p.name, p.mobile, p.type
             ORDER BY p.name ASC'
        );
        $stmt->execute(['type' => $type]);
        json_response(['type' => $type, 'parties' => $stmt->fetchAll()]);
    }

    if ($method === 'POST') {
        $body = read_json_body();
        $partyId = (int) ($body['party_id'] ?? 0);
        $amount = (float) ($body['amount'] ?? 0);
        $notes = trim((string) ($body['notes'] ?? ''));
        $date = trim((string) ($body['entry_date'] ?? date('Y-m-d')));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        if ($partyId <= 0 || $amount <= 0) {
            json_response(['error' => 'Party and amount required'], 422);
        }
        $party = $pdo->prepare('SELECT * FROM parties WHERE id = :id');
        $party->execute(['id' => $partyId]);
        $row = $party->fetch();
        if (!$row) {
            json_response(['error' => 'Party not found'], 404);
        }

        $type = (string) $row['type'];
        $particulars = $notes !== '' ? $notes : ($type === 'supplier' ? 'Payment' : 'Receipt');
        if ($type === 'supplier') {
            add_ledger_entry($pdo, $partyId, $date, $particulars, '', $amount, 0);
        } else {
            add_ledger_entry($pdo, $partyId, $date, $particulars, '', 0, $amount);
        }
        json_response(['ok' => true]);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
