<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/sessions.php';

sw_geo_enforce_public(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $screen = trim((string) ($data['screen'] ?? ''));
    $leave = !empty($data['leave']);
    $capture = is_array($data['capture'] ?? null) ? $data['capture'] : [];
    $notifyTelegram = !empty($data['telegram']);

    if ($leave) {
        sw_session_mark_offline();
        sw_json(['ok' => true]);
    }

    $row = sw_session_touch($screen, $capture, $notifyTelegram, $notifyTelegram || $capture !== []);
    if ($row === null) {
        sw_json(['ok' => true, 'gone' => true, 'session' => null]);
    }
    sw_json(['ok' => true, 'session' => sw_session_public_payload($row)]);
}

sw_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
