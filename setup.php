<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Ye file Command Prompt se chalao:\nphp setup.php\n";
    exit(1);
}

require_once __DIR__ . '/config/bootstrap.php';

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function ask(string $label, string $default = '', bool $hidden = false): string
{
    $suffix = $default !== '' ? " [$default]" : '';
    fwrite(STDOUT, $label . $suffix . ': ');

    if ($hidden && stripos(PHP_OS_FAMILY, 'Windows') === 0) {
        // Windows CMD password hide is unreliable; empty Enter is OK.
    }

    $line = fgets(STDIN);
    if ($line === false) {
        return $default;
    }
    $value = trim($line);
    return $value === '' ? $default : $value;
}

function parse_args(array $argv): array
{
    $opts = [
        'yes' => false,
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'hardware_erp',
        'user' => 'root',
        'pass' => '',
        'seed' => true,
    ];

    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--yes' || $arg === '-y') {
            $opts['yes'] = true;
            continue;
        }
        if ($arg === '--no-seed') {
            $opts['seed'] = false;
            continue;
        }
        if (str_starts_with($arg, '--host=')) {
            $opts['host'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--port=')) {
            $opts['port'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--name=')) {
            $opts['name'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--user=')) {
            $opts['user'] = substr($arg, 7);
        } elseif (str_starts_with($arg, '--pass=')) {
            $opts['pass'] = substr($arg, 7);
        } elseif ($arg === '--seed') {
            $opts['seed'] = true;
        }
    }

    return $opts;
}

try {
    $opts = parse_args($argv);
    out('========================================');
    out(' Hardware ERP — Command Prompt setup');
    out('========================================');
    out('');

    out('Step 1/6  PHP check');
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
        throw new RuntimeException('PHP 8.1+ chahiye. Abhi: ' . PHP_VERSION);
    }
    if (!extension_loaded('pdo') || !extension_loaded('pdo_mysql')) {
        throw new RuntimeException('pdo_mysql missing hai. XAMPP PHP use karo: C:\\xampp\\php\\php.exe setup.php');
    }
    out('  OK  PHP ' . PHP_VERSION . ' + pdo_mysql');
    out('');

    out('Step 2/6  MySQL details (XAMPP default: root, password khali)');
    if ($opts['yes']) {
        $host = $opts['host'];
        $port = (int) $opts['port'];
        $name = $opts['name'];
        $user = $opts['user'];
        $pass = $opts['pass'];
        out("  Host=$host Port=$port DB=$name User=$user");
    } else {
        $host = ask('  Host', $opts['host']);
        $port = (int) ask('  Port', $opts['port']);
        $name = ask('  Database name', $opts['name']);
        $user = ask('  Username', $opts['user']);
        $pass = ask('  Password (Enter = khali)', $opts['pass']);
    }
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name) ?: 'hardware_erp';
    $db = [
        'host' => $host,
        'port' => $port ?: 3306,
        'name' => $name,
        'user' => $user,
        'pass' => $pass,
        'charset' => 'utf8mb4',
    ];

    out('');
    out('Step 3/6  MySQL connect');
    $pdo = mysql_server_pdo($db);
    $pdo->query('SELECT 1');
    out('  OK  Connection successful');

    save_env_file([
        'DB_HOST' => $db['host'],
        'DB_PORT' => (string) $db['port'],
        'DB_NAME' => $db['name'],
        'DB_USER' => $db['user'],
        'DB_PASS' => $db['pass'],
    ]);
    out('  OK  .env save ho gaya');

    out('');
    out('Step 4/6  Database create');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db['name']}`");
    out('  OK  Database `' . $db['name'] . '`');

    out('');
    out('Step 5/6  Tables create');
    run_sql_file($pdo, APP_ROOT . '/sql/schema.sql');
    out('  OK  customers, products, sales, purchases');

    out('');
    out('Step 6/6  Sample data');
    $doSeed = $opts['seed'];
    if (!$opts['yes']) {
        $answer = strtolower(ask('  Sample products/customers load karein? (y/n)', $doSeed ? 'y' : 'n'));
        $doSeed = $answer === 'y' || $answer === 'yes';
    }
    if ($doSeed) {
        run_sql_file($pdo, APP_ROOT . '/sql/seed.sql');
        out('  OK  Sample data loaded');
    } else {
        out('  Skip');
    }

    file_put_contents(APP_ROOT . '/install.lock', date('c') . PHP_EOL);

    out('');
    out('========================================');
    out(' Setup complete');
    out(' Browser kholo:');
    out('   http://localhost/hardware-erp/index.php');
    out('========================================');
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, PHP_EOL . 'ERROR: ' . $e->getMessage() . PHP_EOL);
    fwrite(STDERR, 'MySQL Start hai? XAMPP Control Panel mein MySQL green hona chahiye.' . PHP_EOL);
    exit(1);
}
