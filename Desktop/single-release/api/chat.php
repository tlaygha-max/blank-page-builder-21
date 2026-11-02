<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/control-auth.php';
require dirname(__DIR__) . '/app/sessions.php';
require dirname(__DIR__) . '/app/chat.php';

sw_geo_enforce_public(true);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function sw_chat_api_limit(?string $raw): int
{
    $n = (int) (trim((string) $raw) ?: 50);
    return max(1, min(100, $n));
}

if ($method === 'GET') {
    $isAdmin = sw_control_logged_in();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $afterRaw = $_GET['after'] ?? null;
    $limit = sw_chat_api_limit((string) ($_GET['limit'] ?? '50'));

    $after = null;
    if ($afterRaw !== null && $afterRaw !== '') {
        $after = (int) $afterRaw;
        $after = max(0, $after);
    }

    $sessionId = '';
    if ($isAdmin) {
        $sessionId = trim((string) ($_GET['session'] ?? ''));
        if ($sessionId === '') {
            // If an admin is logged in but the caller didn't specify `?session=...`,
            // fall back to client mode so the client chat widget doesn't break.
            $row = sw_session_existing();
            if ($row === null) {
                sw_json([
                    'ok' => true,
                    'session' => '',
                    'messages' => [],
                    'lastId' => 0,
                ]);
            }
            $sessionId = (string) ($row['id'] ?? '');
        }
    } else {
        $row = sw_session_existing();
        if ($row === null) {
            sw_json([
                'ok' => true,
                'session' => '',
                'messages' => [],
                'lastId' => 0,
            ]);
        }
        $sessionId = (string) ($row['id'] ?? '');
    }

    $messages = $after === null
        ? sw_chat_messages_tail($sessionId, $limit)
        : sw_chat_messages_after($sessionId, $after, $limit);

    $lastId = 0;
    if ($messages !== []) {
        $last = $messages[count($messages) - 1];
        $lastId = (int) ($last['id'] ?? 0);
    } elseif ($after !== null) {
        $lastId = (int) $after;
    }

    sw_json([
        'ok' => true,
        'session' => $sessionId,
        'messages' => $messages,
        'lastId' => $lastId,
    ]);
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $text = trim((string) ($data['text'] ?? ''));
    if ($text === '') {
        sw_json(['ok' => false, 'error' => 'missing_text'], 400);
    }

    $messageType = trim((string) ($data['message_type'] ?? ''));
    if ($messageType === '') {
        $messageType = 'message';
    }

    // Normalize to known values.
    if (
        $messageType !== 'confirm' &&
        $messageType !== 'alert' &&
        $messageType !== 'reject' &&
        $messageType !== 'important' &&
        $messageType !== 'message'
    ) {
        $messageType = 'message';
    }

    $isAdmin = sw_control_logged_in();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    $direction = 'client';
    $senderName = 'عميل';
    $sessionId = '';

    if ($isAdmin) {
        $sessionId = trim((string) ($data['session_id'] ?? $data['session'] ?? $data['id'] ?? ''));
        if ($sessionId === '') {
            // If Admin is logged in but the caller didn't provide `session_id`,
            // this request is likely coming from the client chat widget.
            // Fall back to client mode so messages get stored as `direction=client`.
            $row = sw_session_existing();
            if ($row !== null) {
                $senderName = (string) ($row['customer_name'] ?? '');
                if ($senderName === '') {
                    $senderName = (string) ($row['login_username'] ?? '');
                }
                $sessionId = (string) ($row['id'] ?? '');
            } else {
                if (trim($senderName) === '') $senderName = 'زائر تجريبي';
                $sessionId = sw_session_id();
            }
            $direction = 'client';
            // Keep $senderName as computed above.
        } else {
            $direction = 'admin';
            $senderType = trim((string) ($data['sender_type'] ?? ''));
            if ($senderType === '') {
                $senderType = 'admin-general';
            }

            // Only allow known values to avoid trusting arbitrary client input.
            if (
                $senderType === 'customer-service' ||
                $senderType === 'خدمة العملاء'
            ) {
                $senderName = 'خدمة العملاء';
            } else {
                $senderName = 'الادارة العامة';
            }
        }
    } else {
        $direction = 'client';
        $row = sw_session_existing();
        if ($row !== null) {
            $senderName = (string) ($row['customer_name'] ?? '');
            if ($senderName === '') {
                $senderName = (string) ($row['login_username'] ?? '');
            }
        }
        if (trim($senderName) === '') {
            $senderName = 'زائر تجريبي';
        }

        $sessionId = $row !== null ? (string) ($row['id'] ?? '') : sw_session_id();
    }

    $message = sw_chat_message_insert($sessionId, $direction, $senderName, $messageType, $text);
    if (!empty($message['body']) && $message['body'] !== '') {
        sw_json(['ok' => true, 'message' => $message]);
    }

    sw_json(['ok' => false, 'error' => 'empty_message'], 400);
}

sw_json(['ok' => false, 'error' => 'method_not_allowed'], 405);

