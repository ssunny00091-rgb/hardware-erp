<?php

declare(strict_types=1);

$envFile = dirname(__DIR__) . '/.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'hardware_erp',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],
    'company' => [
        'name' => 'SATYANARAYAN HARDWARE STORES',
        'address_line1' => 'Main Road, Jayanagar, PIN - 847226',
        'address_line2' => 'Second Branch - Near Anumandal Hospital, Jayanagar',
        'mobile' => '9431875263, 9831046765',
        'email' => 'sunnynayak01@gmail.com',
        'gst' => '10ADTPN8807A1ZP',
    ],
];
