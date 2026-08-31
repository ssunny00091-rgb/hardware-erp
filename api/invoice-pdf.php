<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$dir = APP_ROOT . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'invoices';

function safe_pdf_name(string $raw): string
{
    $base = basename($raw);
    $clean = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? '';
    if ($clean === '' || !str_ends_with(strtolower($clean), '.pdf')) {
        $clean .= '.pdf';
    }
    return substr($clean, 0, 120);
}

try {
    if ($method === 'POST') {
        $name = safe_pdf_name((string) ($_GET['name'] ?? 'Invoice.pdf'));
        $raw = file_get_contents('php://input');
        if ($raw === false || strlen($raw) < 100) {
            json_response(['error' => 'PDF data nahi mila'], 422);
        }
        if (strlen($raw) > 8 * 1024 * 1024) {
            json_response(['error' => 'PDF bahut badi hai'], 413);
        }
        if (!str_starts_with($raw, '%PDF')) {
            json_response(['error' => 'Yeh valid PDF file nahi hai'], 422);
        }
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            json_response(['error' => 'data/invoices folder nahi ban paya — writable karo'], 500);
        }
        if (@file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $raw) === false) {
            json_response(['error' => 'PDF save nahi hui — folder writable nahi'], 500);
        }
        json_response([
            'ok' => true,
            'url' => app_url('api/invoice-pdf.php?f=' . rawurlencode($name)),
            'name' => $name,
        ]);
    }

    if ($method === 'GET') {
        $name = safe_pdf_name((string) ($_GET['f'] ?? ''));
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo 'PDF not found';
            exit;
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }

    json_response(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
