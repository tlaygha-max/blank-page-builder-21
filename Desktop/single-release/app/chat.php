<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function sw_chat_limit(int $limit): int
{
    $limit = max(1, $limit);
    if ($limit > 100) {
        $limit = 100;
    }
    return $limit;
}

function sw_chat_now(): string
{
    return date('c');
}

function sw_chat_sanitize_text(string $text): string
{
    // Keep it simple and safe for plain rendering in the UI.
    $text = strip_tags($text);
    $text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/u', '', $text) ?? $text;
    $text = trim($text);
    return mb_substr($text, 0, 1000);
}

/**
 * @return list<array{id:int,direction:string,sender_name:string,message_type:string,body:string,created_at:string}>
 */
function sw_chat_messages_tail(string $sessionId, int $limit): array
{
    $limit = sw_chat_limit($limit);
    if ($sessionId === '') {
        return [];
    }

    $stmt = sw_db()->prepare(
        'SELECT id, direction, sender_name, message_type, body, created_at
         FROM chat_messages
         WHERE session_id = :s
         ORDER BY id DESC
         LIMIT :lim'
    );
    $stmt->bindValue(':s', $sessionId);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    if (!is_array($rows) || $rows === []) {
        return [];
    }

    $rows = array_reverse($rows);
    return array_values(array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'direction' => (string) ($row['direction'] ?? 'client'),
            'sender_name' => (string) ($row['sender_name'] ?? ''),
            'message_type' => (string) ($row['message_type'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }, $rows));
}

/**
 * @return list<array{id:int,direction:string,sender_name:string,message_type:string,body:string,created_at:string}>
 */
function sw_chat_messages_after(string $sessionId, int $afterId, int $limit): array
{
    $limit = sw_chat_limit($limit);
    $afterId = max(0, $afterId);
    if ($sessionId === '') {
        return [];
    }

    $stmt = sw_db()->prepare(
        'SELECT id, direction, sender_name, message_type, body, created_at
         FROM chat_messages
         WHERE session_id = :s AND id > :after
         ORDER BY id ASC
         LIMIT :lim'
    );
    $stmt->bindValue(':s', $sessionId);
    $stmt->bindValue(':after', $afterId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    if (!is_array($rows) || $rows === []) {
        return [];
    }

    return array_values(array_map(static function (array $row): array {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'direction' => (string) ($row['direction'] ?? 'client'),
            'sender_name' => (string) ($row['sender_name'] ?? ''),
            'message_type' => (string) ($row['message_type'] ?? ''),
            'body' => (string) ($row['body'] ?? ''),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }, $rows));
}

/**
 * @return array{id:int,direction:string,sender_name:string,message_type:string,body:string,created_at:string}
 */
function sw_chat_message_insert(string $sessionId, string $direction, string $senderName, string $messageType, string $text): array
{
    if ($sessionId === '') {
        return [
            'id' => 0,
            'direction' => 'client',
            'sender_name' => '',
            'message_type' => '',
            'body' => '',
            'created_at' => '',
        ];
    }

    $direction = $direction === 'admin' ? 'admin' : 'client';
    $senderName = trim($senderName);
    $senderName = mb_substr($senderName, 0, 80);
    $messageType = trim($messageType);
    $messageType = mb_substr($messageType, 0, 40);
    if ($messageType === '') $messageType = 'message';

    $text = sw_chat_sanitize_text($text);

    $at = sw_chat_now();
    $stmt = sw_db()->prepare(
        'INSERT INTO chat_messages (session_id, direction, sender_name, message_type, body, created_at)
         VALUES (:s, :d, :n, :t, :b, :at)'
    );
    $stmt->execute([
        's' => $sessionId,
        'd' => $direction,
        'n' => $senderName,
        't' => $messageType,
        'b' => $text,
        'at' => $at,
    ]);

    return [
        'id' => (int) sw_db()->lastInsertId(),
        'direction' => $direction,
        'sender_name' => $senderName,
        'message_type' => $messageType,
        'body' => $text,
        'created_at' => $at,
    ];
}

