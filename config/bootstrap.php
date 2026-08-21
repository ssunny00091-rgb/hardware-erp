<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function parse_env_line(string $line): ?array
{
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        return null;
    }
    [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
    $key = trim($key);
    $value = trim($value);
    if ($key === '') {
        return null;
    }
    return [$key, $value];
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

function save_env_file(array $values): void
{
    $merged = read_env_file();
    foreach ($values as $key => $value) {
        $merged[(string) $key] = str_replace(["\r", "\n"], '', (string) $value);
    }
    $lines = [];
    foreach ($merged as $key => $value) {
        $lines[] = $key . '=' . $value;
    }
    $target = APP_ROOT . '/.env';
    if (file_put_contents($target, implode("\n", $lines) . "\n") === false) {
        throw new RuntimeException('.env file nahi likh paye. Folder writable hona chahiye.');
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
