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
            'settings_path' => $cfg['settings_path'] ?? '',
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
    foreach (['openrouter_api_key', 'openrouter_model', 'test_openrouter', 'skip_test'] as $field) {
        if (!isset($payload[$field]) && isset($_POST[$field])) {
            $payload[$field] = $_POST[$field];
        }
    }

    $providedKey = trim((string) ($payload['openrouter_api_key'] ?? ''));
    $hasNewKey = $providedKey !== '';
    if ($hasNewKey || !empty($payload['test_openrouter'])) {
        $current = openrouter_config();
        $key = $hasNewKey ? normalize_openrouter_key($providedKey) : $current['api_key'];
        $model = trim((string) ($payload['openrouter_model'] ?? ''));
        $saved = ['ok' => false, 'saved_to' => [], 'errors' => []];
        if ($hasNewKey) {
            $saved = persist_openrouter_key($key, $model);
        }
        $wantTest = !empty($payload['test_openrouter']) && empty($payload['skip_test']);
        $test = null;
        if ($wantTest && $key !== '') {
            $test = openrouter_test_key($key);
        }
        $fresh = openrouter_config();
        json_response([
            'ok' => true,
            'saved' => !empty($saved['ok']),
            'saved_to' => $saved['saved_to'] ?? [],
            'save_notes' => $saved['errors'] ?? [],
            'configured' => $fresh['api_key'] !== '',
            'model' => $fresh['model'],
            'settings_path' => $fresh['settings_path'] ?? '',
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
