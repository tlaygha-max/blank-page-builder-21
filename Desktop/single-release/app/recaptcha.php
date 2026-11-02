<?php

declare(strict_types=1);

function sw_recaptcha_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $siteKey = trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: ''));
    $secret = trim((string) (getenv('RECAPTCHA_SECRET_KEY') ?: ''));

    foreach ([
        APP_ROOT . '/storage/recaptcha.json',
        APP_ROOT . '/recaptcha.json',
    ] as $file) {
        if (!is_file($file)) {
            continue;
        }
        $raw = @file_get_contents($file);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($json)) {
            continue;
        }
        if ($siteKey === '') {
            $siteKey = trim((string) ($json['site_key'] ?? ''));
        }
        if ($secret === '') {
            $secret = trim((string) ($json['secret_key'] ?? ''));
        }
        if ($siteKey !== '') {
            break;
        }
    }

    if ($siteKey === '') {
        // Official Google test key — widget works, but image challenges stay off.
        $siteKey = '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI';
    }

    $cfg = [
        'site_key' => $siteKey,
        'secret_key' => $secret,
    ];
    return $cfg;
}

function sw_recaptcha_site_key(): string
{
    return sw_recaptcha_config()['site_key'];
}
