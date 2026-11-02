<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'الخدمات';
$pageDescription = 'قائمة خدمات النافذة الواحدة.';
$activeNav = 'portal';
$pageId = 'portal';
$bodyClass = 'auth-page services-screen';
$hideSiteFooter = true;
$themeColor = '#1763A1';
$requiresDemoAuth = false;
$services = services_catalog();

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/services-screen.php';
require __DIR__ . '/partials/footer.php';
