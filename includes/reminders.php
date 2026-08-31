<?php

declare(strict_types=1);

function ensure_reminders_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS due_reminders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            party_id INT UNSIGNED NULL,
            party_name VARCHAR(255) NOT NULL,
            party_type VARCHAR(30) NOT NULL DEFAULT "customer",
            mobile VARCHAR(20) DEFAULT "",
            remind_on DATE NOT NULL,
            amount DECIMAL(12,2) NULL,
            note VARCHAR(255) DEFAULT "",
            status VARCHAR(20) NOT NULL DEFAULT "pending",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME NULL,
            KEY idx_remind_on (remind_on, status),
            KEY idx_remind_party (party_id)
        ) ENGINE=InnoDB'
    );
}

function reminder_list(PDO $pdo, string $scope = 'open'): array
{
    ensure_reminders_schema($pdo);
    $today = date('Y-m-d');
    if ($scope === 'today') {
        $sql = 'SELECT * FROM due_reminders WHERE status = "pending" AND remind_on <= :d ORDER BY remind_on ASC, id ASC LIMIT 80';
        $st = $pdo->prepare($sql);
        $st->execute(['d' => $today]);
    } elseif ($scope === 'upcoming') {
        $sql = 'SELECT * FROM due_reminders WHERE status = "pending" AND remind_on > :d ORDER BY remind_on ASC, id ASC LIMIT 80';
        $st = $pdo->prepare($sql);
        $st->execute(['d' => $today]);
    } elseif ($scope === 'all') {
        $st = $pdo->query('SELECT * FROM due_reminders ORDER BY remind_on DESC, id DESC LIMIT 80');
    } else {
        $st = $pdo->query('SELECT * FROM due_reminders WHERE status = "pending" ORDER BY remind_on ASC, id ASC LIMIT 80');
    }
    $rows = $st ? ($st->fetchAll() ?: []) : [];
    foreach ($rows as &$row) {
        $on = (string) ($row['remind_on'] ?? '');
        if ($on !== '' && $on < $today && ($row['status'] ?? '') === 'pending') {
            $row['when'] = 'overdue';
        } elseif ($on === $today) {
            $row['when'] = 'today';
        } else {
            $row['when'] = 'upcoming';
        }
        $row['date'] = format_display_date($on);
    }
    unset($row);
    return $rows;
}

function reminder_pick_party(array $matches): array
{
    if (function_exists('assistant_pick_party')) {
        return assistant_pick_party($matches);
    }
    if ($matches === []) {
        return ['party' => null, 'confident' => false, 'nearby' => []];
    }
    return [
        'party' => $matches[0],
        'confident' => count($matches) === 1,
        'nearby' => $matches,
    ];
}

function reminder_save(PDO $pdo, array $args): array
{
    ensure_reminders_schema($pdo);
    $name = trim((string) ($args['name'] ?? $args['party_name'] ?? ''));
    $type = normalize_party_type((string) ($args['type'] ?? $args['party_type'] ?? 'customer'));
    $dateRaw = (string) ($args['remind_on'] ?? $args['date'] ?? $args['due_date'] ?? '');
    $date = parse_sale_date($dateRaw);
    $amount = $args['amount'] ?? $args['due'] ?? null;
    $amountVal = ($amount === null || $amount === '') ? null : (float) $amount;
    $note = trim((string) ($args['note'] ?? $args['notes'] ?? ''));
    $mobile = trim((string) ($args['mobile'] ?? ''));
    $partyId = (int) ($args['party_id'] ?? 0);

    if ($name === '' && $partyId <= 0) {
        return ['error' => 'Kis customer/supplier ka reminder? Naam bolo.'];
    }

    if ($partyId <= 0 && $name !== '') {
        $matches = function_exists('search_parties_fuzzy')
            ? search_parties_fuzzy($pdo, $name, $type, 8)
            : [];
        if ($matches === [] && $type !== '' && function_exists('search_parties_fuzzy')) {
            $matches = search_parties_fuzzy($pdo, $name, '', 8);
        }
        $pick = reminder_pick_party($matches);
        if ($pick['party'] && !empty($pick['confident'])) {
            $partyId = (int) $pick['party']['id'];
            $name = (string) $pick['party']['name'];
            $type = normalize_party_type((string) ($pick['party']['type'] ?? $type));
            if ($mobile === '') {
                $mobile = trim((string) ($pick['party']['mobile'] ?? ''));
            }
        } elseif ($pick['party'] && !$pick['confident'] && empty($args['force'])) {
            return [
                'need_pick' => true,
                'query' => $name,
                'nearby' => $matches,
                'note' => 'Kaun sa naam? Confirm karo, phir reminder lagaoonga.',
            ];
        }
    }

    if ($partyId > 0) {
        $st = $pdo->prepare('SELECT id, name, type, mobile FROM parties WHERE id = :id');
        $st->execute(['id' => $partyId]);
        $p = $st->fetch();
        if ($p) {
            $name = (string) $p['name'];
            $type = normalize_party_type((string) $p['type']);
            if ($mobile === '') {
                $mobile = trim((string) ($p['mobile'] ?? ''));
            }
        }
    }

    if ($name === '') {
        return ['error' => 'Naam nahi mila'];
    }

    $pdo->prepare(
        'INSERT INTO due_reminders (party_id, party_name, party_type, mobile, remind_on, amount, note, status)
         VALUES (:party_id, :party_name, :party_type, :mobile, :remind_on, :amount, :note, "pending")'
    )->execute([
        'party_id' => $partyId > 0 ? $partyId : null,
        'party_name' => $name,
        'party_type' => $type,
        'mobile' => $mobile,
        'remind_on' => $date,
        'amount' => $amountVal,
        'note' => $note,
    ]);
    $id = (int) $pdo->lastInsertId();
    return [
        'ok' => true,
        'id' => $id,
        'party_id' => $partyId,
        'name' => $name,
        'type' => $type,
        'mobile' => $mobile,
        'remind_on' => $date,
        'date' => format_display_date($date),
        'amount' => $amountVal,
        'note' => $note,
        'message' => $name . ' ka reminder ' . format_display_date($date) . ' ko lag gaya. Us din raat 9 baje aapke WhatsApp par aayega, aur software kholte hi dikhega.',
    ];
}

function reminder_set_status(PDO $pdo, int $id, string $status): array
{
    ensure_reminders_schema($pdo);
    if ($id <= 0) {
        return ['error' => 'Reminder id chahiye'];
    }
    $status = in_array($status, ['pending', 'done', 'cancelled'], true) ? $status : 'done';
    $st = $pdo->prepare('UPDATE due_reminders SET status = :s, sent_at = CASE WHEN :s2 = "done" THEN NOW() ELSE sent_at END WHERE id = :id');
    $st->execute(['s' => $status, 's2' => $status, 'id' => $id]);
    return ['ok' => true, 'id' => $id, 'status' => $status];
}

function assistant_tool_set_reminder(PDO $pdo, array $args): array
{
    return reminder_save($pdo, $args);
}

function assistant_tool_list_reminders(PDO $pdo, array $args): array
{
    $scope = strtolower(trim((string) ($args['scope'] ?? 'open')));
    if (!in_array($scope, ['open', 'today', 'upcoming', 'all'], true)) {
        $scope = 'open';
    }
    $rows = reminder_list($pdo, $scope);
    return [
        'ok' => true,
        'scope' => $scope,
        'reminders' => $rows,
        'count' => count($rows),
    ];
}

function assistant_tool_cancel_reminder(PDO $pdo, array $args): array
{
    $id = (int) ($args['id'] ?? $args['reminder_id'] ?? 0);
    if ($id <= 0) {
        $name = trim((string) ($args['name'] ?? ''));
        if ($name === '') {
            return ['error' => 'Kaun sa reminder cancel? id ya naam do.'];
        }
        $rows = reminder_list($pdo, 'open');
        $hit = null;
        foreach ($rows as $row) {
            if (stripos((string) $row['party_name'], $name) !== false) {
                $hit = $row;
                break;
            }
        }
        if (!$hit) {
            return ['error' => 'Is naam ka pending reminder nahi mila', 'nearby' => $rows];
        }
        $id = (int) $hit['id'];
    }
    return reminder_set_status($pdo, $id, 'cancelled');
}

function reminder_whatsapp_block(PDO $pdo): string
{
    $rows = reminder_list($pdo, 'today');
    if ($rows === []) {
        return '';
    }
    $lines = ['', '*Aaj ke reminders (chat se lage hue)*'];
    foreach ($rows as $row) {
        $amt = isset($row['amount']) && $row['amount'] !== null && $row['amount'] !== ''
            ? '  ' . whatsapp_inr((float) $row['amount'])
            : '';
        $note = trim((string) ($row['note'] ?? ''));
        $when = (string) ($row['when'] ?? '');
        $tag = $when === 'overdue' ? 'OVERDUE' : 'AAJ';
        $bit = '- [' . $tag . '] ' . ($row['party_name'] ?? '')
            . ' (' . ($row['party_type'] ?? '') . ')'
            . $amt
            . '  ' . ($row['date'] ?? '');
        if (!empty($row['mobile'])) {
            $bit .= '  ' . $row['mobile'];
        }
        if ($note !== '') {
            $bit .= ' — ' . $note;
        }
        $lines[] = $bit;
    }
    return implode("\n", $lines);
}

function reminder_banner_rows(PDO $pdo): array
{
    try {
        return reminder_list($pdo, 'today');
    } catch (Throwable $e) {
        return [];
    }
}
