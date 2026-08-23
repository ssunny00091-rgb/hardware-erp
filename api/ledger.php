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
            $row = load_party_ledger($pdo, $partyId);
            if (!$row) {
                json_response(['error' => 'Party not found'], 404);
            }
            json_response($row);
        }

        $type = normalize_party_type((string) ($_GET['type'] ?? 'customer'));
$search = trim((string) ($_GET['search'] ?? ''));

$sql = 'SELECT p.id, p.name, p.mobile, p.address, p.type,
            COALESCE(SUM(l.debit), 0) AS debit,
            COALESCE(SUM(l.credit), 0) AS credit,
            COALESCE(SUM(l.debit), 0) - COALESCE(SUM(l.credit), 0) AS balance
        FROM parties p
        LEFT JOIN ledger_entries l ON l.party_id = p.id
        WHERE p.type = :type
          AND p.deleted_at IS NULL';

$params = ['type' => $type];

if ($search !== '') {
    $sql .= ' AND (p.name LIKE :search_name OR p.mobile LIKE :search_mobile)';
$params['search_name'] = '%' . $search . '%';
$params['search_mobile'] = '%' . $search . '%';
}

$sql .= ' GROUP BY p.id, p.name, p.mobile, p.address, p.type
          ORDER BY p.name ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} catch (Throwable $e) {
    $fallbackSql = 'SELECT p.id, p.name, p.mobile, p.address, p.type,
                           COALESCE(SUM(l.debit), 0) AS debit,
                           COALESCE(SUM(l.credit), 0) AS credit,
                           COALESCE(SUM(l.debit), 0) - COALESCE(SUM(l.credit), 0) AS balance
                    FROM parties p
                    LEFT JOIN ledger_entries l ON l.party_id = p.id
                    WHERE p.type = :type';

    $fallbackParams = ['type' => $type];

    if ($search !== '') {
        $fallbackSql .= ' AND (p.name LIKE :search_name OR p.mobile LIKE :search_mobile)';
$fallbackParams['search_name'] = '%' . $search . '%';
$fallbackParams['search_mobile'] = '%' . $search . '%';
    }

    $fallbackSql .= ' GROUP BY p.id, p.name, p.mobile, p.address, p.type
                      ORDER BY p.name ASC';

    $stmt = $pdo->prepare($fallbackSql);
    $stmt->execute($fallbackParams);
}

json_response([
    'type' => $type,
    'search' => $search,
    'parties' => $stmt->fetchAll()
]);

    }

    if ($method === 'POST') {
        $body = read_json_body();
        $partyId = (int) ($body['party_id'] ?? 0);
        $amount = (float) ($body['amount'] ?? 0);
        $notes = trim((string) ($body['notes'] ?? ''));
        $date = parse_sale_date($body['entry_date'] ?? '');
        if ($partyId <= 0 || $amount <= 0) {
            json_response(['error' => 'Party and amount required'], 422);
        }
        if (!party_is_live($pdo, $partyId)) {
            json_response(['error' => 'Party not found'], 404);
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

    if ($method === 'PUT') {
        $body = read_json_body();
        $entryId = (int) ($body['id'] ?? $body['entry_id'] ?? $_GET['id'] ?? 0);
        $partyId = (int) ($body['party_id'] ?? 0);

        if ($entryId > 0) {
            update_ledger_entry($pdo, $entryId, $body);
            json_response(['ok' => true]);
        }

        if ($partyId > 0) {
            update_party_details(
                $pdo,
                $partyId,
                (string) ($body['name'] ?? ''),
                (string) ($body['mobile'] ?? ''),
                (string) ($body['address'] ?? '')
            );
            json_response(['ok' => true]);
        }

        json_response(['error' => 'Specify party_id or entry id'], 422);
    }

    if ($method === 'DELETE') {
        $body = read_json_body();
        $entryId = (int) ($_GET['id'] ?? $body['id'] ?? $body['entry_id'] ?? 0);
        $partyId = (int) ($_GET['party_id'] ?? $body['party_id'] ?? 0);

        if ($entryId > 0) {
            $row = $pdo->prepare('SELECT * FROM ledger_entries WHERE id = :id');
            $row->execute(['id' => $entryId]);
            $entry = $row->fetch();
            if (!$entry) {
                json_response(['error' => 'Entry not found'], 404);
            }
            $pdo->prepare('DELETE FROM ledger_entries WHERE id = :id')->execute(['id' => $entryId]);
            json_response(['ok' => true]);
        }

        if ($partyId > 0) {
            delete_party_record($pdo, $partyId);
            json_response(['ok' => true]);
        }

        json_response(['error' => 'Specify entry id or party_id'], 422);
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (InvalidArgumentException $e) {
    json_response(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
