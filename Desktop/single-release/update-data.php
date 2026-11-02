<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'تحديث البيانات';
$pageDescription = 'تحديث بيانات الحساب بعد تسجيل الدخول.';
$pageId = 'update-data';
$bodyClass = 'nas-login-screen forgot-password-screen update-data-screen';
$hideSiteFooter = true;
$useNasLayout = true;
$nasBrand = 'sw';
$requiresDemoAuth = true;
$themeColor = '#1a4f86';

try {
    require __DIR__ . '/partials/header.php';
    ?>

    <section class="nas-shell reset-shell">
    <article class="nas-card reset-card">
        <h1 class="reset-card__title" data-i18n="update.title">تحديث البيانات</h1>
        <p class="reset-card__lead" data-i18n="update.lead">يجب تحديث بياناتك من خلال خطوات بسيطة، لتتمكن من استخدام حسابك.</p>
        <p class="reset-card__note" data-i18n="update.note">نجري تحديث بيانات على جميع الحسابات بشكل دوري، لنتمكن من تحديث بيانات السكن وارقام الهواتف والبريد الالكتروني.</p>
        <a class="reset-continue" href="<?= e(app_url('register')) ?>" data-i18n="reset.continue">استمر</a>
    </article>
</section>

    <?php require __DIR__ . '/partials/footer.php'; ?>
<?php } catch (Throwable $e) {
    // Prevent HTTP 500 from killing the page if hosting/session init fails.
    @error_log('[single-window] update-data render failed: ' . $e->getMessage());
    http_response_code(200);
    ?>
    <!doctype html>
    <html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="<?= e($themeColor) ?>">
        <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
        <link rel="stylesheet" href="<?= e(asset_url('css/app.css')) ?>">
    </head>
    <body class="<?= e($bodyClass ?? '') ?>" data-page="update-data">
        <section class="nas-shell reset-shell">
            <article class="nas-card reset-card">
                <h1 class="reset-card__title" data-i18n="update.title">تحديث البيانات</h1>
                <p class="reset-card__lead">حصل خطأ بسيط أثناء تجهيز الصفحة. الرجاء إعادة المحاولة.</p>
                <a class="reset-continue" href="<?= e(app_url('register')) ?>">استمر</a>
            </article>
        </section>
    </body>
    </html>
    <?php
}
