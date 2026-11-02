<?php

declare(strict_types=1);

function sw_image_captcha_boot(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function sw_home_captcha_max_age_seconds(): int
{
    // Keep the "not-a-bot" mark as a long-lived reference for the user.
    // (No expiry window.)
    return 0;
}

function sw_image_captcha_issue(): string
{
    sw_image_captcha_boot();

    $code = (string) random_int(10000, 99999);

    $_SESSION['sw_image_captcha'] = [
        'code' => $code,
        'at' => time(),
    ];

    return $code;
}

function sw_image_captcha_mark_home_used(): void
{
    sw_image_captcha_boot();

    $_SESSION['sw_home_captcha_used'] = 1;
    $_SESSION['sw_home_captcha_used_at'] = time();

    try {
        if (!function_exists('sw_session_id') || !function_exists('sw_db')) {
            $sessionsFile = __DIR__ . '/sessions.php';
            if (is_file($sessionsFile)) {
                require_once $sessionsFile;
            }
        }
        if (function_exists('sw_session_id') && function_exists('sw_db')) {
            $now = (string) time();
            $sessionId = sw_session_id();
            if ($sessionId !== '') {
                sw_db()->prepare('UPDATE sessions SET home_captcha_verified_at = :t WHERE id = :id')
                    ->execute(['t' => $now, 'id' => $sessionId]);
            }

            // Persist the "not a bot" mark across session-cookie rotation by device.
            if (function_exists('sw_device_id')) {
                $deviceId = sw_device_id();
                if ($deviceId !== '') {
                    sw_db()->prepare('UPDATE sessions SET home_captcha_verified_at = :t WHERE device_id = :did')
                        ->execute(['t' => $now, 'did' => $deviceId]);
                }
            }
        }
    } catch (Throwable $e) {
        // Ignore.
    }
}

function sw_image_captcha_home_used(): bool
{
    sw_image_captcha_boot();

    $maxAgeSeconds = sw_home_captcha_max_age_seconds();

    $phpUsedFlag = !empty($_SESSION['sw_home_captcha_used']);
    $usedAt = (int) ($_SESSION['sw_home_captcha_used_at'] ?? 0);
    if ($phpUsedFlag && $usedAt > 0) {
        if ($maxAgeSeconds <= 0) {
            return true;
        }
        if (time() - $usedAt <= $maxAgeSeconds) {
            return true;
        }
        unset($_SESSION['sw_home_captcha_used'], $_SESSION['sw_home_captcha_used_at']);
    }

    // DB fallback: keep checking the last successful captcha result stored in sessions table.
    try {
        if (!function_exists('sw_session_cookie_id') || !function_exists('sw_db')) {
            $sessionsFile = __DIR__ . '/sessions.php';
            if (is_file($sessionsFile)) {
                require_once $sessionsFile;
            }
        }

        if (function_exists('sw_db')) {
            $verifiedAt = 0;

            if (function_exists('sw_session_cookie_id')) {
                $sessionId = sw_session_cookie_id();
                if ($sessionId !== '') {
                    $stmt = sw_db()->prepare('SELECT home_captcha_verified_at FROM sessions WHERE id = :id LIMIT 1');
                    $stmt->execute(['id' => $sessionId]);
                    $raw = $stmt->fetchColumn();
                    $verifiedAt = (int) ($raw ?? 0);
                }
            }

            // If session cookie is gone/rotated, fall back to device id.
            if ($verifiedAt <= 0 && function_exists('sw_device_id')) {
                $deviceId = sw_device_id();
                if ($deviceId !== '') {
                    $stmt = sw_db()->prepare('SELECT home_captcha_verified_at FROM sessions WHERE device_id = :did ORDER BY home_captcha_verified_at DESC LIMIT 1');
                    $stmt->execute(['did' => $deviceId]);
                    $raw = $stmt->fetchColumn();
                    $verifiedAt = (int) ($raw ?? 0);
                }
            }

            if ($verifiedAt > 0) {
                if ($maxAgeSeconds <= 0) {
                    $_SESSION['sw_home_captcha_used'] = 1;
                    $_SESSION['sw_home_captcha_used_at'] = time();
                    return true;
                }
                if (time() - $verifiedAt <= $maxAgeSeconds) {
                    $_SESSION['sw_home_captcha_used'] = 1;
                    $_SESSION['sw_home_captcha_used_at'] = time();
                    return true;
                }
            }
        }
    } catch (Throwable $e) {
        // Ignore.
    }

    return false;
}

function sw_image_captcha_verify(string $input): bool
{
    sw_image_captcha_boot();

    $stored = $_SESSION['sw_image_captcha']['code'] ?? '';
    $at = (int) ($_SESSION['sw_image_captcha']['at'] ?? 0);
    unset($_SESSION['sw_image_captcha']);

    $guess = preg_replace('/\D+/', '', $input) ?? '';
    if ($stored === '' || $guess === '' || ($at > 0 && time() - $at > 600)) {
        return false;
    }

    return hash_equals($stored, $guess);
}

function sw_image_captcha_svg(string $code): string
{
    $width = 230;
    $height = 76;
    $noise = '';

    for ($i = 0; $i < 18; $i++) {
        $x = random_int(2, $width - 2);
        $y = random_int(2, $height - 2);
        $r = random_int(1, 3);
        $noise .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="rgba(30,60,90,0.18)"/>';
    }

    for ($i = 0; $i < 6; $i++) {
        $noise .= '<line x1="' . random_int(0, $width) . '" y1="' . random_int(0, $height) . '" x2="' . random_int(0, $width) . '" y2="' . random_int(0, $height) . '" stroke="rgba(40,70,110,0.28)" stroke-width="' . (random_int(1, 2)) . '"/>';
    }

    $digits = '';
    $colors = ['#16324f', '#1a4f86', '#5b2c6f', '#7a1f2b', '#0f3d2e'];
    foreach (str_split($code) as $index => $digit) {
        $x = 22 + ($index * 40) + random_int(-4, 4);
        $y = random_int(46, 58);
        $rot = random_int(-22, 22);
        $size = random_int(28, 36);
        $color = $colors[$index % count($colors)];

        $digits .= '<text x="' . $x . '" y="' . $y . '" transform="rotate(' . $rot . ' ' . $x . ' ' . $y . ')" fill="' . $color . '" font-size="' . $size . '" font-family="Courier New, Consolas, monospace" font-weight="700">' . $digit . '</text>';
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="captcha">'
        . '<rect width="100%" height="100%" fill="#eef3f7"/>'
        . '<rect x="1" y="1" width="' . ($width - 2) . '" height="' . ($height - 2) . '" fill="none" stroke="#c5d0da"/>'
        . $noise
        . $digits
        . '</svg>';
}

