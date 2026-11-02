<?php

declare(strict_types=1);

$isServicesScreen = $isServicesScreen ?? false;
$isServicePage = $isServicePage ?? false;
$headerBrandUrl = ($isServicesScreen || $isServicePage) ? app_url('portal') : app_url();
?>
<header class="login-header SiteHeader" data-component="SiteHeader">
    <div class="login-header__top">
        <?php require __DIR__ . '/lang-toggle.php'; ?>
        <span class="login-header__rule" aria-hidden="true"></span>
        <a class="login-header__login" href="<?= e(app_url()) ?>" data-i18n-nav-login>تسجيل الدخول</a>
    </div>
    <div class="login-header__bar">
        <a class="login-header__brand" href="<?= e($headerBrandUrl) ?>" aria-label="النافذة الواحدة">
            <?php $brandSize = 'header'; require __DIR__ . '/brand-lockup.php'; ?>
        </a>
        <a class="login-header__services<?= $isServicesScreen ? ' is-current' : '' ?>" href="<?= e(app_url('portal')) ?>"<?= $isServicesScreen ? ' aria-current="page"' : '' ?>>
            <svg class="login-header__services-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M4 4h6.5v6.5H4V4Zm9.5 0H20v6.5h-6.5V4ZM4 13.5H10.5V20H4v-6.5Zm9.5 0H20V20h-6.5v-6.5Z"/>
            </svg>
            <span data-i18n-nav-services>الخدمات</span>
        </a>
    </div>
</header>
