<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$serviceCode = '';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#/service/([A-Za-z0-9-]+)/?$#', $requestPath, $matches) === 1) {
    $serviceCode = $matches[1];
} elseif (isset($_GET['code']) && is_string($_GET['code'])) {
    $serviceCode = $_GET['code'];
}

$service = find_service($serviceCode);

if (!$service) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$pageTitle = $service['title'];
$pageDescription = $service['excerpt'];
$pageId = 'service';
$bodyClass = 'service-page service-details-screen';
$activeNav = 'portal';
$requiresDemoAuth = false;
$hideSiteFooter = true;
$themeColor = '#1763A1';

require __DIR__ . '/partials/header.php';
require __DIR__ . '/partials/service-details-page.php';
require __DIR__ . '/partials/footer.php';
