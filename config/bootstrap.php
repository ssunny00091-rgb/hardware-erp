<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function strip_env_quotes(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
    $value = trim($value);
    $len = strlen($value);
    if ($len >= 2) {
        $a = $value[0];
        $b = $value[$len - 1];
        if (($a === '"' && $b === '"') || ($a === "'" && $b === "'")) {
            return stripcslashes(substr($value, 1, -1));
        }
    }
    return $value;
}

function parse_env_line(string $line): ?array
{
    $line = trim($line);
    $line = preg_replace('/^\xEF\xBB\xBF/u', '', $line) ?? $line;
    if ($line === '' || str_starts_with($line, '#')) {
        return null;
    }
    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
    $key = trim($key);
    $value = strip_env_quotes($value);
    if ($key === '') {
        return null;
    }
    return [$key, $value];
}

function normalize_openrouter_key(string $key): string
{
    $key = strip_env_quotes($key);
    $key = preg_replace('/^Bearer\s+/i', '', $key) ?? $key;
    $key = preg_replace('/[\s\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]+/u', '', $key) ?? $key;
    return trim($key);
}

function read_env_file(?string $path = null): array
{
    $path = $path ?? APP_ROOT . '/.env';
    $out = [];
    if (!is_readable($path)) {
        return $out;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $parsed = parse_env_line($line);
        if ($parsed) {
            $out[$parsed[0]] = $parsed[1];
        }
    }
    return $out;
}

function load_env_file(?string $path = null): void
{
    foreach (read_env_file($path) as $key => $value) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function settings_json_path(): string
{
    return APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'app-settings.json';
}

function read_app_settings(): array
{
    $path = settings_json_path();
    if (!is_readable($path)) {
        return [];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function write_app_settings(array $values): void
{
    $dir = APP_ROOT . DIRECTORY_SEPARATOR . 'data';
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('data folder nahi bani: ' . $dir . ' — folder writable karo.');
    }
    if (!is_writable($dir)) {
        @chmod($dir, 0777);
    }
    $merged = read_app_settings();
    foreach ($values as $key => $value) {
        $merged[(string) $key] = str_replace(["\r", "\n"], '', (string) $value);
    }
    $path = settings_json_path();
    $json = json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Settings encode nahi hui');
    }
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $json . "\n") === false) {
        if (@file_put_contents($path, $json . "\n") === false) {
            throw new RuntimeException('Key file nahi likhi: ' . $path);
        }
        return;
    }
    if (!@rename($tmp, $path)) {
        @unlink($path);
        if (!@rename($tmp, $path) && @file_put_contents($path, $json . "\n") === false) {
            throw new RuntimeException('Key file nahi likhi: ' . $path);
        }
        @unlink($tmp);
    }
}

function save_env_file(array $values): void
{
    $merged = read_env_file();
    foreach ($values as $key => $value) {
        $v = str_replace(["\r", "\n"], '', (string) $value);
        if ($key === 'OPENROUTER_API_KEY') {
            $v = normalize_openrouter_key($v);
        }
        $merged[(string) $key] = $v;
    }
    $lines = [];
    foreach ($merged as $key => $value) {
        $lines[] = $key . '=' . $value;
    }
    $target = APP_ROOT . DIRECTORY_SEPARATOR . '.env';
    $dir = APP_ROOT;
    if (is_file($target) && !is_writable($target)) {
        @chmod($target, 0666);
    }
    if (!is_file($target) && !is_writable($dir)) {
        @chmod($dir, 0777);
    }
    $ok = @file_put_contents($target, implode("\n", $lines) . "\n");
    if ($ok === false) {
        throw new RuntimeException('.env nahi likh paye: ' . $target);
    }
    load_env_file();
}

function app_base_url(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    if (basename($dir) === 'api') {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        return '';
    }
    return rtrim($dir, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', app_base_url());
}

function app_url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function payment_qr_url(): string
{
    $settings = read_app_settings();
    $rel = trim((string) ($settings['payment_qr_file'] ?? 'assets/qr/payment-qr.png'));
    $rel = ltrim($rel, '/');
    $abs = APP_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (is_file($abs)) {
        $stamp = (string) @filemtime($abs);
        return app_url($rel) . '?v=' . $stamp;
    }
    $fallback = APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'qr' . DIRECTORY_SEPARATOR . 'payment-qr.png';
    if (is_file($fallback)) {
        return app_url('assets/qr/payment-qr.png') . '?v=' . (string) @filemtime($fallback);
    }
    return '';
}

function has_payment_qr(): bool
{
    return payment_qr_url() !== '';
}

function payment_upi_id(): string
{
    $settings = read_app_settings();
    return trim((string) ($settings['payment_upi'] ?? ''));
}

function upi_payment_string(string $upi, string $name, float $amount = 0.0, string $note = ''): string
{
    $upi = trim($upi);
    if ($upi === '') {
        return '';
    }
    $params = ['pa=' . rawurlencode($upi)];
    if ($name !== '') {
        $params[] = 'pn=' . rawurlencode($name);
    }
    if ($amount > 0) {
        $params[] = 'am=' . number_format($amount, 2, '.', '');
    }
    $params[] = 'cu=INR';
    if ($note !== '') {
        $params[] = 'tn=' . rawurlencode($note);
    }
    return 'upi://pay?' . implode('&', $params);
}

function run_sql_file(PDO $pdo, string $file): void
{
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('SQL file nahi mili: ' . $file);
    }

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
        if (
            $statement === ''
            || str_starts_with($statement, '--')
            || stripos($statement, 'CREATE DATABASE') === 0
            || stripos($statement, 'USE ') === 0
        ) {
            continue;
        }
        $pdo->exec($statement);
    }
}

function mysql_server_pdo(array $db): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;charset=%s',
        $db['host'],
        $db['port'],
        $db['charset'] ?? 'utf8mb4'
    );

    return new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

load_env_file();
