<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'نموذج عربي تجريبي لمسار خدمات خيالية، دون حسابات أو بيانات حقيقية.';
$activeNav = $activeNav ?? '';
$bodyClass = $bodyClass ?? '';
$pageId = $pageId ?? 'home';
$showStepper = $showStepper ?? false;
$currentStep = $currentStep ?? 0;
$requiresDemoAuth = $requiresDemoAuth ?? false;
$baseUrl = app_url();
$brandUrl = $requiresDemoAuth ? app_url('portal') : app_url();
$hideSiteFooter = $hideSiteFooter ?? false;
$useNasFooter = $useNasFooter ?? false;
$themeColor = $themeColor ?? '#0b2239';
$htmlLang = $htmlLang ?? 'ar';
$htmlDir = $htmlDir ?? 'rtl';
$bareLayout = $bareLayout ?? false;
$skipLinkLabel = $skipLinkLabel ?? 'انتقل إلى المحتوى';
$recaptchaSiteKey = $recaptchaSiteKey ?? '';
// Image-captcha modal is the bot check. Skip Google reCAPTCHA.js — it was stalling login/register.
$isLoginScreen = $pageId === 'login';
$isServicesScreen = $pageId === 'portal';
$isServicePage = $pageId === 'service';
$useNasLayout = $useNasLayout ?? ($pageId === 'smart-card');
$useAppChrome = !$bareLayout && !$useNasLayout && ($isLoginScreen || $isServicesScreen || $isServicePage);

if (!in_array(($pageId ?? ''), ['control', 'control-login'], true)) {
    require_once APP_ROOT . '/app/sessions.php';
    require_once APP_ROOT . '/app/block.php';
    $GLOBALS['pageId'] = (string) $pageId;
    try {
        if (($pageId ?? '') !== 'geo-blocked') {
            sw_geo_enforce_public();
        }
        if (($pageId ?? '') === 'geo-blocked') {
            // Keep geo-blocked visitors out of the session list.
        } elseif (($pageId ?? '') !== 'blocked') {
            $cookieId = sw_session_cookie_id();
            if ($cookieId !== '' && sw_session_id_is_deleted($cookieId)) {
                $GLOBALS['sw_session_rotated'] = true;
                sw_session_id();
            } else {
                sw_session_touch((string) $pageId);
            }
            sw_block_enforce();
        } else {
            sw_session_ensure();
            sw_block_enforce();
        }
    } catch (Throwable $e) {
        // Some hosting environments may fail DB/session init.
        // For update-data page we still want to render content instead of HTTP 500.
        if (($pageId ?? '') !== 'update-data') {
            throw $e;
        }
    }
}
?>
<!doctype html>
<html lang="<?= e($htmlLang) ?>" dir="<?= e($htmlDir) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="<?= e($themeColor) ?>">
    <?php if (($pageId ?? '') === 'geo-blocked'): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    <script defer src="<?= e(asset_url('js/app.js')) ?>"></script>
    <?php if (!empty($recaptchaSiteKey)): ?>
    <script>
    window.onNasRecaptchaSuccess = window.onNasRecaptchaSuccess || function () {};
    window.onNasRecaptchaExpired = window.onNasRecaptchaExpired || function () {};
    window.onNasRecaptchaLoad = function onNasRecaptchaLoad() {
        window.__nasRecaptchaReady = true;
        document.dispatchEvent(new Event('nas-recaptcha-ready'));
    };
    </script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onNasRecaptchaLoad&render=explicit&hl=<?= e($htmlLang === 'en' ? 'en' : 'ar') ?>" defer></script>
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>" data-page="<?= e($pageId) ?>" data-base-url="<?= e($baseUrl) ?>" data-requires-demo-auth="<?= $requiresDemoAuth ? 'true' : 'false' ?>" data-presence-url="<?= e(app_url('api/presence.php')) ?>" data-live-url="<?= e(app_url('api/live.php')) ?>" data-sessions-url="<?= e(app_url('api/sessions.php')) ?>" data-chat-url="<?= e(app_url('api/chat.php')) ?>"<?= !empty($GLOBALS['sw_session_rotated']) ? ' data-session-reset="1"' : '' ?><?= !empty($recaptchaSiteKey) ? ' data-recaptcha-sitekey="' . e($recaptchaSiteKey) . '"' : '' ?>>
<a class="skip-link" href="#main-content"><?= e($skipLinkLabel) ?></a>
<div class="demo-badge" role="note">
    <span aria-hidden="true">!</span>
    نموذج تجريبي — لا تدخل بيانات حقيقية
</div>

<?php if ($bareLayout): ?>
<?php elseif ($useNasLayout): ?>
<?php require __DIR__ . '/nas-header.php'; ?>
<?php elseif ($useAppChrome): ?>
<?php require __DIR__ . '/site-header.php'; ?>
<?php else: ?>
<header class="site-header">
    <div class="container header-inner">
        <?php require __DIR__ . '/lang-toggle.php'; ?>
        <a class="brand" href="<?= e($brandUrl) ?>" aria-label="النافذة الواحدة - الرئيسية">
            <?php
            $brandSize = 'header';
            $brandInverse = false;
            require __DIR__ . '/brand-lockup.php';
            ?>
        </a>

        <button class="menu-toggle" type="button" aria-controls="main-navigation" aria-expanded="false">
            <span></span><span></span><span></span>
            <span class="sr-only">فتح القائمة</span>
        </button>

        <nav id="main-navigation" class="main-nav" aria-label="التنقل الرئيسي">
            <?php if ($requiresDemoAuth): ?>
                <a class="<?= $activeNav === 'portal' ? 'is-active' : '' ?>" href="<?= e(app_url('portal')) ?>">لوحة الخدمات</a>
                <a href="<?= e(app_url('portal')) ?>#services">الخدمات</a>
                <a href="<?= e(app_url('user-type')) ?>">طلب جديد</a>
                <button class="nav-cta nav-logout-button" type="button" data-demo-logout>خروج من التجربة</button>
            <?php else: ?>
                <a class="<?= $pageId === 'login' ? 'is-active' : '' ?>" href="<?= e(app_url()) ?>">تسجيل الدخول</a>
                <a class="<?= $pageId === 'register' ? 'is-active' : '' ?>" href="<?= e(app_url('register')) ?>">إنشاء ملف تجريبي</a>
                <a class="nav-cta" href="<?= e(app_url('access-help')) ?>">مساعدة الدخول</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php endif; ?>

<?php if ($showStepper): ?>
    <div class="stepper-shell" aria-label="تقدم الطلب">
        <div class="container">
            <ol class="stepper">
                <?php foreach (portal_steps() as $stepNumber => $stepLabel): ?>
                    <?php $stepClass = $currentStep > $stepNumber ? 'is-complete' : ($currentStep === $stepNumber ? 'is-current' : ''); ?>
                    <li class="<?= e($stepClass) ?>" <?= $currentStep === $stepNumber ? 'aria-current="step"' : '' ?>>
                        <span class="step-number"><?= $currentStep > $stepNumber ? '✓' : $stepNumber ?></span>
                        <span class="step-label"><?= e($stepLabel) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
<?php endif; ?>

<main id="main-content">
