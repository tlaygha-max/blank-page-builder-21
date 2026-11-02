<?php

declare(strict_types=1);

if (defined('APP_NAME')) {
    return;
}

const APP_NAME = 'بوابة الخدمات الموحدة';
const APP_ROOT = __DIR__ . '/..';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
define('APP_BASE_URL', $scriptDirectory === '' || $scriptDirectory === '/' ? '' : $scriptDirectory);

function app_url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    $base = APP_BASE_URL;

    if ($cleanPath === '') {
        return $base !== '' ? $base . '/' : '/';
    }

    return ($base !== '' ? $base : '') . '/' . $cleanPath;
}

function asset_url(string $path): string
{
    return app_url('assets/' . ltrim($path, '/'));
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require APP_ROOT . '/app/services-data.php';
require APP_ROOT . '/app/service-icons.php';

function portal_steps(): array
{
    return [
        1 => 'نوع المستخدم',
        2 => 'بيانات الطلب',
        3 => 'تفاصيل الخدمة',
        4 => 'المراجعة',
    ];
}
