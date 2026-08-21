<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function load_env_file(?string $path = null): void
{
    $path = $path ?? APP_ROOT . '/.env';
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($key === '') {
            continue;
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function save_env_file(array $values): void
{
    $lines = [];
    foreach ($values as $key => $value) {
        $lines[] = $key . '=' . str_replace(["\r", "\n"], '', (string) $value);
    }

    $target = APP_ROOT . '/.env';
    if (file_put_contents($target, implode("\n", $lines) . "\n") === false) {
        throw new RuntimeException('.env file nahi likh paye. Folder writable hona chahiye.');
    }
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
