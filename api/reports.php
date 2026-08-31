<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/reports.php';

try {
    $pdo = db();
    json_response(reports_build($pdo, $_GET));
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
