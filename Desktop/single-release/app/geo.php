<?php

declare(strict_types=1);

function sw_client_ips_from_request(): array
{
    $found = [];
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        $raw = trim((string) ($_SERVER[$key] ?? ''));
        if ($raw === '') {
            continue;
        }
        foreach (explode(',', $raw) as $part) {
            $ip = trim($part);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $found[$ip] = true;
            }
        }
    }
    return array_keys($found);
}

function sw_client_ip_from_request(): string
{
    $ips = sw_client_ips_from_request();
    return $ips[0] ?? '';
}

function sw_country_name_from_code(string $code, string $lang = 'ar'): string
{
    $code = strtoupper(trim($code));
    $map = [
        'OM' => ['ar' => 'عُمان', 'en' => 'Oman'],
        'SA' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
        'AE' => ['ar' => 'الإمارات', 'en' => 'United Arab Emirates'],
        'QA' => ['ar' => 'قطر', 'en' => 'Qatar'],
        'KW' => ['ar' => 'الكويت', 'en' => 'Kuwait'],
        'BH' => ['ar' => 'البحرين', 'en' => 'Bahrain'],
        'YE' => ['ar' => 'اليمن', 'en' => 'Yemen'],
        'EG' => ['ar' => 'مصر', 'en' => 'Egypt'],
        'JO' => ['ar' => 'الأردن', 'en' => 'Jordan'],
        'IQ' => ['ar' => 'العراق', 'en' => 'Iraq'],
        'PS' => ['ar' => 'فلسطين', 'en' => 'Palestine'],
        'LB' => ['ar' => 'لبنان', 'en' => 'Lebanon'],
        'SY' => ['ar' => 'سوريا', 'en' => 'Syria'],
        'SD' => ['ar' => 'السودان', 'en' => 'Sudan'],
        'MA' => ['ar' => 'المغرب', 'en' => 'Morocco'],
        'DZ' => ['ar' => 'الجزائر', 'en' => 'Algeria'],
        'TN' => ['ar' => 'تونس', 'en' => 'Tunisia'],
        'LY' => ['ar' => 'ليبيا', 'en' => 'Libya'],
        'IN' => ['ar' => 'الهند', 'en' => 'India'],
        'PK' => ['ar' => 'باكستان', 'en' => 'Pakistan'],
        'BD' => ['ar' => 'بنغلاديش', 'en' => 'Bangladesh'],
        'PH' => ['ar' => 'الفلبين', 'en' => 'Philippines'],
        'NP' => ['ar' => 'نيبال', 'en' => 'Nepal'],
        'LK' => ['ar' => 'سريلانكا', 'en' => 'Sri Lanka'],
        'US' => ['ar' => 'أمريكا', 'en' => 'United States'],
        'GB' => ['ar' => 'بريطانيا', 'en' => 'United Kingdom'],
        'DE' => ['ar' => 'ألمانيا', 'en' => 'Germany'],
        'FR' => ['ar' => 'فرنسا', 'en' => 'France'],
        'IT' => ['ar' => 'إيطاليا', 'en' => 'Italy'],
        'ES' => ['ar' => 'إسبانيا', 'en' => 'Spain'],
        'NL' => ['ar' => 'هولندا', 'en' => 'Netherlands'],
        'TR' => ['ar' => 'تركيا', 'en' => 'Turkey'],
        'IR' => ['ar' => 'إيران', 'en' => 'Iran'],
        'CN' => ['ar' => 'الصين', 'en' => 'China'],
        'RU' => ['ar' => 'روسيا', 'en' => 'Russia'],
        'ID' => ['ar' => 'إندونيسيا', 'en' => 'Indonesia'],
        'MY' => ['ar' => 'ماليزيا', 'en' => 'Malaysia'],
        'SG' => ['ar' => 'سنغافورة', 'en' => 'Singapore'],
        'TH' => ['ar' => 'تايلاند', 'en' => 'Thailand'],
        'CA' => ['ar' => 'كندا', 'en' => 'Canada'],
        'AU' => ['ar' => 'أستراليا', 'en' => 'Australia'],
        'NZ' => ['ar' => 'نيوزيلندا', 'en' => 'New Zealand'],
        'BR' => ['ar' => 'البرازيل', 'en' => 'Brazil'],
        'MX' => ['ar' => 'المكسيك', 'en' => 'Mexico'],
        'ZA' => ['ar' => 'جنوب أفريقيا', 'en' => 'South Africa'],
        'NG' => ['ar' => 'نيجيريا', 'en' => 'Nigeria'],
        'KE' => ['ar' => 'كينيا', 'en' => 'Kenya'],
        'ET' => ['ar' => 'إثيوبيا', 'en' => 'Ethiopia'],
        'LOCAL' => ['ar' => 'محلي', 'en' => 'Local'],
    ];
    if (isset($map[$code])) {
        return $map[$code][$lang] ?? $map[$code]['ar'];
    }
    return $code;
}

function sw_geo_http_get(string $url): string
{
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 1.2,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\nUser-Agent: SingleWindowGeo/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    return is_string($raw) ? $raw : '';
}

function sw_geo_disk_cache_path(): string
{
    $dir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__)) . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . '/geo-cache.json';
}

function sw_geo_disk_cache_get(string $key)
{
    static $mem = null;
    if ($mem === null) {
        $mem = [];
        $raw = @file_get_contents(sw_geo_disk_cache_path());
        $data = json_decode(is_string($raw) ? $raw : '', true);
        if (is_array($data)) {
            $mem = $data;
        }
    }
    $row = $mem[$key] ?? null;
    if (!is_array($row) || !isset($row['at'])) {
        return null;
    }
    if (time() - (int) $row['at'] > 900) {
        return null;
    }
    return $row['value'] ?? null;
}

function sw_geo_disk_cache_set(string $key, $value): void
{
    static $mem = null;
    $path = sw_geo_disk_cache_path();
    if ($mem === null) {
        $raw = @file_get_contents($path);
        $data = json_decode(is_string($raw) ? $raw : '', true);
        $mem = is_array($data) ? $data : [];
    }
    $mem[$key] = ['at' => time(), 'value' => $value];
    if (count($mem) > 200) {
        uasort($mem, static function ($a, $b) {
            return ((int) ($b['at'] ?? 0)) <=> ((int) ($a['at'] ?? 0));
        });
        $mem = array_slice($mem, 0, 200, true);
    }
    @file_put_contents($path, json_encode($mem, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function sw_country_code_from_ip(string $ip): string
{
    static $cache = [];
    if (isset($cache[$ip])) {
        return $cache[$ip];
    }
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        $cache[$ip] = $ip === '' ? '' : 'LOCAL';
        return $cache[$ip];
    }

    $cached = sw_geo_disk_cache_get('cc:' . $ip);
    if (is_string($cached)) {
        $cache[$ip] = $cached;
        return $cached;
    }

    $endpoints = [
        'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,countryCode',
        'https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country_code',
        'https://ipapi.co/' . rawurlencode($ip) . '/country_code/',
    ];

    foreach ($endpoints as $url) {
        $raw = sw_geo_http_get($url);
        if ($raw === '') {
            continue;
        }
        $trimmed = strtoupper(trim($raw));
        if (preg_match('/^[A-Z]{2}$/', $trimmed) === 1) {
            $cache[$ip] = $trimmed;
            sw_geo_disk_cache_set('cc:' . $ip, $trimmed);
            return $trimmed;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        $code = strtoupper((string) ($data['countryCode'] ?? $data['country_code'] ?? ''));
        if (($data['status'] ?? '') === 'success' || !empty($data['success']) || isset($data['country_code']) || isset($data['countryCode'])) {
            if (preg_match('/^[A-Z]{2}$/', $code) === 1) {
                $cache[$ip] = $code;
                sw_geo_disk_cache_set('cc:' . $ip, $code);
                return $code;
            }
        }
    }

    $cache[$ip] = '';
    sw_geo_disk_cache_set('cc:' . $ip, '');
    return '';
}

function sw_country_code_from_request(): string
{
    $headerKeys = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_APPENGINE_COUNTRY',
        'HTTP_GEOIP_COUNTRY_CODE',
        'HTTP_X_GEOIP_COUNTRY',
        'GEOIP_COUNTRY_CODE',
    ];
    foreach ($headerKeys as $key) {
        $code = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
        if ($code !== '' && $code !== 'XX' && preg_match('/^[A-Z]{2}$/', $code) === 1) {
            return $code;
        }
    }
    return sw_country_code_from_ip(sw_client_ip_from_request());
}

function sw_geo_is_local_ip(string $ip): bool
{
    if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}

function sw_geo_public_ip_for_lookup(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
        $ip = trim((string) ($_SERVER[$key] ?? ''));
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
    }
    return '';
}

/**
 * Detect VPN/Proxy/Tor exit nodes via IP-API.
 * Note: ip-api.com's `proxy` flag includes "Proxy, VPN or Tor exit address".
 */
function sw_ip_is_vpn_proxy(string $ip): bool
{
    static $cache = [];

    $ip = trim($ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return false;
    }

    if (isset($cache[$ip])) {
        return $cache[$ip];
    }

    $cached = sw_geo_disk_cache_get('vpn:' . $ip);
    if (is_bool($cached)) {
        $cache[$ip] = $cached;
        return $cached;
    }

    $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,proxy';
    $raw = sw_geo_http_get($url);
    if ($raw === '') {
        $cache[$ip] = false;
        sw_geo_disk_cache_set('vpn:' . $ip, false);
        return false;
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || strtolower((string) ($data['status'] ?? '')) !== 'success') {
        $cache[$ip] = false;
        sw_geo_disk_cache_set('vpn:' . $ip, false);
        return false;
    }

    $cache[$ip] = !empty($data['proxy']);
    sw_geo_disk_cache_set('vpn:' . $ip, $cache[$ip]);
    return $cache[$ip];
}

function sw_geo_trusted_country_code(): string
{
    $behindCloudflare = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? '')) !== ''
        || trim((string) ($_SERVER['HTTP_CF_RAY'] ?? '')) !== '';
    if ($behindCloudflare) {
        $cfCountry = strtoupper(trim((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? '')));
        if ($cfCountry !== '' && $cfCountry !== 'XX' && preg_match('/^[A-Z]{2}$/', $cfCountry) === 1) {
            return $cfCountry;
        }
    }

    $serverKeys = [
        'GEOIP_COUNTRY_CODE',
        'HTTP_GEOIP_COUNTRY_CODE',
        'HTTP_X_APPENGINE_COUNTRY',
        'HTTP_X_GEOIP_COUNTRY',
    ];
    foreach ($serverKeys as $key) {
        $code = strtoupper(trim((string) ($_SERVER[$key] ?? '')));
        if ($code !== '' && $code !== 'XX' && preg_match('/^[A-Z]{2}$/', $code) === 1) {
            return $code;
        }
    }

    $ip = sw_geo_public_ip_for_lookup();
    return $ip !== '' ? sw_country_code_from_ip($ip) : '';
}

function sw_geo_allowed_countries(): array
{
    // Geo restriction removed: allow all countries.
    // Keeping the function for legacy/debug payload compatibility.
    return [];
}

function sw_geo_visitor_allowed(): bool
{
    // Allow all countries; block only VPN/Proxy/Tor exit nodes.
    $ip = sw_geo_public_ip_for_lookup();
    if ($ip === '') {
        // Avoid false positives: if we can't determine a public IP, allow.
        return true;
    }

    return !sw_ip_is_vpn_proxy($ip);
}

function sw_geo_is_control_context(): bool
{
    $pageId = (string) ($GLOBALS['pageId'] ?? '');
    if (in_array($pageId, ['control', 'control-login'], true)) {
        return true;
    }
    if (function_exists('sw_control_logged_in') && sw_control_logged_in()) {
        return true;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    foreach (['control-login', 'control-logout', '/control'] as $needle) {
        if (str_contains($script, trim($needle, '/')) || preg_match('#' . preg_quote($needle, '#') . '(/|$)#', $uri) === 1) {
            return true;
        }
    }
    return false;
}

function sw_geo_enforce_public(bool $json = false): void
{
    if (sw_geo_is_control_context()) {
        return;
    }

    if (sw_geo_visitor_allowed()) {
        return;
    }

    $ip = sw_geo_public_ip_for_lookup();
    $vpn = $ip !== '' ? sw_ip_is_vpn_proxy($ip) : false;

    if ($json) {
        $payload = [
            'ok' => false,
            'error' => 'vpn_blocked',
            'ip' => $ip,
            'vpn' => $vpn,
        ];

        if (function_exists('sw_json')) {
            sw_json($payload, 403);
        }

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('Connection: close');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    header('Location: ' . app_url('geo-blocked'));
    exit;
}

function sw_geo_label(string $stored, string $lang = 'ar'): string
{
    $stored = trim($stored);
    if ($stored === '') {
        return $lang === 'en' ? 'Unknown' : 'غير معروف';
    }
    if (strcasecmp($stored, 'LOCAL') === 0) {
        return $lang === 'en' ? 'Local' : 'محلي';
    }
    if (preg_match('/^[A-Z]{2}$/i', $stored) === 1) {
        return sw_country_name_from_code($stored, $lang);
    }
    return $stored;
}
