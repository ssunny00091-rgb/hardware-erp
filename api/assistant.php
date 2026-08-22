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
            'env_path' => $cfg['env_path'] ?? '',
            'curl' => function_exists('curl_init'),
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

    if (isset($payload['openrouter_api_key']) || !empty($payload['test_openrouter'])) {
        $current = openrouter_config();
        $key = isset($payload['openrouter_api_key'])
            ? normalize_openrouter_key((string) $payload['openrouter_api_key'])
            : $current['api_key'];
        $model = trim((string) ($payload['openrouter_model'] ?? ''));
        if (isset($payload['openrouter_api_key'])) {
            $save = ['OPENROUTER_API_KEY' => $key];
            if ($model !== '') {
                $save['OPENROUTER_MODEL'] = $model;
            }
            save_env_file($save);
        }
        $test = $key !== ''
            ? openrouter_test_key($key)
            : ['ok' => false, 'error' => 'Key khali hai'];
        json_response([
            'ok' => true,
            'saved' => isset($payload['openrouter_api_key']),
            'configured' => $key !== '',
            'model' => $model !== '' ? $model : $current['model'],
            'test' => $test,
        ]);
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
