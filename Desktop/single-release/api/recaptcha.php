<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/sessions.php';
require dirname(__DIR__) . '/app/recaptcha.php';
require dirname(__DIR__) . '/app/image-captcha.php';

sw_geo_enforce_public(true);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    sw_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$token = (string) ($data['token'] ?? $data['code'] ?? '');
$action = (string) ($data['action'] ?? '');
if (trim($token) === '') {
    sw_json(['ok' => false, 'error' => 'missing_token'], 400);
}

$cfg = sw_recaptcha_config();
$secret = trim((string) ($cfg['secret_key'] ?? ''));
if ($secret === '') {
    sw_json(['ok' => false, 'error' => 'missing_secret_key'], 500);
}

$verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
$payload = http_build_query([
    'secret' => $secret,
    'response' => $token,
    'remoteip' => sw_client_ip(),
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $payload,
        'timeout' => 7,
    ],
]);

$rawRes = @file_get_contents($verifyUrl, false, $context);
$json = is_string($rawRes) ? json_decode($rawRes, true) : null;

$minScoreRaw = getenv('RECAPTCHA_V3_MIN_SCORE');
$minScore = ($minScoreRaw !== false && $minScoreRaw !== '') ? (float) $minScoreRaw : 0.6;

$score = null;
$responseAction = '';

$ok = is_array($json) && !empty($json['success']);
$errorCode = '';

if ($ok) {
    $score = isset($json['score']) ? (float) $json['score'] : null;
    $responseAction = (string) ($json['action'] ?? '');

    // reCAPTCHA v3: reject low scores.
    if ($score !== null && $score < $minScore) {
        $ok = false;
        $errorCode = 'low_score';
    }

    // Reject if client expected action doesn't match.
    if ($ok && $action !== '' && $responseAction !== '' && $responseAction !== $action) {
        $ok = false;
        $errorCode = 'action_mismatch';
    }
}

if ($ok) {
    // Reuse the existing session flag used by router anti-skip.
    sw_image_captcha_mark_home_used();
} else {
    if ($errorCode === '' && is_array($json)) {
        $errorCodes = $json['error-codes'] ?? [];
        if (is_array($errorCodes) && isset($errorCodes[0])) {
            $errorCode = (string) $errorCodes[0];
        }
    }
    if ($errorCode === '') {
        $errorCode = 'verification_failed';
    }
}

sw_json(['ok' => $ok, 'error' => $ok ? '' : $errorCode, 'score' => $score, 'action' => $responseAction]);

