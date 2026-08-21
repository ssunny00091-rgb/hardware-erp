<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/assistant.php';

try {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $cfg = openrouter_config();

    if ($method === 'GET') {
        json_response([
            'configured' => $cfg['api_key'] !== '',
            'model' => $cfg['model'],
        ]);
    }

    if ($method !== 'POST') {
        json_response(['error' => 'Method not allowed'], 405);
    }

    $payload = read_json_body();
    if ($payload === [] && isset($_POST['payload'])) {
        $decoded = json_decode((string) $_POST['payload'], true);
        $payload = is_array($decoded) ? $decoded : [];
    }

    if (isset($payload['openrouter_api_key'])) {
        $key = trim((string) $payload['openrouter_api_key']);
        $model = trim((string) ($payload['openrouter_model'] ?? ''));
        $save = ['OPENROUTER_API_KEY' => $key];
        if ($model !== '') {
            $save['OPENROUTER_MODEL'] = $model;
        }
        save_env_file($save);
        json_response(['ok' => true, 'configured' => $key !== '']);
    }

    $pdo = db();
    $text = trim((string) ($payload['message'] ?? $payload['text'] ?? ''));
    $history = is_array($payload['history'] ?? null) ? $payload['history'] : [];
    $files = assistant_collect_uploads();
    $out = assistant_chat($pdo, $text, $history, $files);
    json_response(['ok' => true] + $out);
} catch (InvalidArgumentException $e) {
    json_response(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
