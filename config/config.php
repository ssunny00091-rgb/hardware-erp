<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($GLOBALS['APP_CONFIG']) || !is_array($GLOBALS['APP_CONFIG'])) {
    $GLOBALS['APP_CONFIG'] = [
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
            'whatsapp' => getenv('OWNER_WHATSAPP') ?: '9831046765',
        ],
        'openrouter' => [
            'api_key' => (string) (getenv('OPENROUTER_API_KEY') ?: ''),
            'model' => (string) (getenv('OPENROUTER_MODEL') ?: 'google/gemini-2.5-flash'),
        ],
    ];
}

return $GLOBALS['APP_CONFIG'];
