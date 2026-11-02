<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/sessions.php';
require dirname(__DIR__) . '/app/image-captcha.php';

sw_geo_enforce_public(true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    $code = sw_image_captcha_issue();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    header('Content-Type: image/svg+xml; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    echo sw_image_captcha_svg($code);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $ok = sw_image_captcha_verify((string) ($data['code'] ?? ''));

    if ($ok) {
        sw_image_captcha_mark_home_used();
    }

    sw_json(['ok' => $ok, 'error' => $ok ? '' : 'mismatch']);
}

sw_json(['ok' => false, 'error' => 'method_not_allowed'], 405);

