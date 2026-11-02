<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/geo.php';

const SW_ADMIN_ONLINE_TTL = 45;

function sw_control_username(): string
{
    return getenv('SW_CONTROL_USER') ?: 'SW_Admin_Qatar';
}

function sw_control_password(): string
{
    return getenv('SW_CONTROL_PASS') ?: 'Sw@Ctrl#2026!Qa9';
}

function sw_control_boot_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function sw_control_logged_in(): bool
{
    sw_control_boot_session();
    return !empty($_SESSION['sw_control_auth']);
}

function sw_control_login(string $username, string $password): bool
{
    sw_control_boot_session();
    if (hash_equals(sw_control_username(), $username) && hash_equals(sw_control_password(), $password)) {
        $_SESSION['sw_control_auth'] = [
            'user' => $username,
            'at' => time(),
        ];
        sw_admin_presence_touch($username);
        if (!function_exists('sw_session_mark_admin_current')) {
            require_once __DIR__ . '/sessions.php';
        }
        sw_session_mark_admin_current();
        return true;
    }
    return false;
}

function sw_control_logout(): void
{
    sw_control_boot_session();
    $key = session_id();
    if ($key !== '') {
        try {
            sw_db()->prepare('DELETE FROM admin_presence WHERE session_key = :k')->execute(['k' => $key]);
        } catch (Throwable $e) {
        }
    }
    unset($_SESSION['sw_control_auth']);
}

function sw_control_require_login(): void
{
    if (sw_control_logged_in()) {
        return;
    }
    header('Location: ' . app_url('control-login'));
    exit;
}

function sw_control_current_username(): string
{
    sw_control_boot_session();
    return trim((string) ($_SESSION['sw_control_auth']['user'] ?? ''));
}

function sw_admin_presence_touch(?string $username = null): void
{
    sw_control_boot_session();
    $username = trim((string) ($username ?: sw_control_current_username()));
    if ($username === '') {
        return;
    }
    $key = session_id();
    if ($key === '') {
        return;
    }
    $now = date('c');
    $ip = sw_client_ip_from_request();
    try {
        sw_db()->prepare(
            'INSERT INTO admin_presence (session_key, username, last_seen_at, client_ip)
             VALUES (:k, :u, :seen, :ip)
             ON CONFLICT(session_key) DO UPDATE SET
               username = excluded.username,
               last_seen_at = excluded.last_seen_at,
               client_ip = excluded.client_ip'
        )->execute([
            'k' => $key,
            'u' => $username,
            'seen' => $now,
            'ip' => $ip,
        ]);
    } catch (Throwable $e) {
    }

    // Cleanup stale rows.
    try {
        $cutoff = date('c', time() - 600);
        sw_db()->prepare('DELETE FROM admin_presence WHERE last_seen_at < :c')->execute(['c' => $cutoff]);
    } catch (Throwable $e) {
    }
}

/**
 * @return list<array{username:string,last_seen_at:string,client_ip:string}>
 */
function sw_admin_online_list(): array
{
    $cutoff = date('c', time() - SW_ADMIN_ONLINE_TTL);
    try {
        $stmt = sw_db()->prepare(
            'SELECT username, last_seen_at, client_ip
             FROM admin_presence
             WHERE last_seen_at >= :cutoff
             ORDER BY last_seen_at DESC'
        );
        $stmt->execute(['cutoff' => $cutoff]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $seenUsers = [];
    $out = [];
    foreach ($rows as $row) {
        $user = trim((string) ($row['username'] ?? ''));
        if ($user === '' || isset($seenUsers[$user])) {
            continue;
        }
        $seenUsers[$user] = true;
        $out[] = [
            'username' => $user,
            'last_seen_at' => (string) ($row['last_seen_at'] ?? ''),
            'client_ip' => (string) ($row['client_ip'] ?? ''),
        ];
    }
    return $out;
}

function sw_admin_online_count(): int
{
    return count(sw_admin_online_list());
}
