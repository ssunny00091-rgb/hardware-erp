<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['error' => 'POST required'], 405);
    }

    $action = trim((string) ($_GET['action'] ?? ''));

    if ($action === 'upload') {
        if (empty($_FILES['qr']) || !is_uploaded_file($_FILES['qr']['tmp_name'])) {
            json_response(['error' => 'QR image file bhejo (field: qr)'], 400);
        }
        $file = $_FILES['qr'];
        $size = (int) $file['size'];
        if ($size <= 0 || $size > 4 * 1024 * 1024) {
            json_response(['error' => 'Image chhoti rakho (max 4MB)'], 413);
        }
        $tmp = (string) $file['tmp_name'];
        $info = @getimagesize($tmp);
        if ($info === false) {
            json_response(['error' => 'Yeh valid image nahi hai'], 422);
        }
        $mime = strtolower((string) $info['mime']);
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp', 'image/gif'], true)) {
            json_response(['error' => 'PNG / JPG / WEBP image bhejo'], 422);
        }
        $ext = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ][$mime];

        $dir = APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'qr';
        if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
            json_response(['error' => 'assets/qr folder nahi bana'], 500);
        }
        $target = $dir . DIRECTORY_SEPARATOR . 'payment-qr.' . $ext;
        if (!@move_uploaded_file($tmp, $target)) {
            json_response(['error' => 'QR image save nahi hui'], 500);
        }

        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $old) {
            if ($old !== $ext && is_file($dir . DIRECTORY_SEPARATOR . 'payment-qr.' . $old)) {
                @unlink($dir . DIRECTORY_SEPARATOR . 'payment-qr.' . $old);
            }
        }

        write_app_settings(['payment_qr_file' => 'assets/qr/payment-qr.' . $ext]);
        json_response(['ok' => true, 'url' => payment_qr_url()]);
    }

    if ($action === 'remove') {
        $dir = APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'qr';
        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $old) {
            $f = $dir . DIRECTORY_SEPARATOR . 'payment-qr.' . $old;
            if (is_file($f)) {
                @unlink($f);
            }
        }
        $settings = read_app_settings();
        unset($settings['payment_qr_file']);
        write_app_settings($settings);
        json_response(['ok' => true, 'url' => '']);
    }

    json_response(['error' => 'Unknown action'], 400);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
