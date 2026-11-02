<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'محظور عبر VPN';
$pageDescription = 'تم حظر الوصول من شبكات VPN/Proxy أو Tor.';
$pageId = 'geo-blocked';
$bodyClass = 'page-blocked page-geo-blocked';
$hideSiteFooter = true;
$bareLayout = true;
$themeColor = '#10233a';

require __DIR__ . '/partials/header.php';
?>
<section class="blocked-screen blocked-screen--geo">
    <article class="blocked-screen__card">
        <div class="blocked-screen__icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" focusable="false">
                <path fill="currentColor" d="M24 6.5A8.5 8.5 0 0 0 15.5 15v4.2H12A3.5 3.5 0 0 0 8.5 22.7v14.8A3.5 3.5 0 0 0 12 41h24a3.5 3.5 0 0 0 3.5-3.5V22.7A3.5 3.5 0 0 0 36 19.2h-3.5V15A8.5 8.5 0 0 0 24 6.5Zm0 3.4A5.1 5.1 0 0 1 29.1 15v4.2H18.9V15A5.1 5.1 0 0 1 24 9.9Z"/>
            </svg>
        </div>
        <h1>تم حظر الدخول عبر VPN</h1>
        <p>لا يمكن فتح الموقع لأن عنوانك يبدو ضمن شبكة VPN/Proxy أو Tor. أوقف الـVPN/الـProxy وأعد المحاولة.</p>
    </article>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
