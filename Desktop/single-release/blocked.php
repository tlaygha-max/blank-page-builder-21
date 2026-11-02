<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'تم حظر الدخول';
$pageDescription = 'لا يمكنك متابعة استخدام الموقع من هذا الجهاز أو العنوان الشبكي.';
$pageId = 'blocked';
$bodyClass = 'page-blocked';
$hideSiteFooter = true;
$bareLayout = true;
$themeColor = '#10233a';

require __DIR__ . '/partials/header.php';
?>
<section class="blocked-screen">
    <article class="blocked-screen__card">
        <div class="blocked-screen__icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" focusable="false">
                <circle cx="24" cy="24" r="20" fill="none" stroke="currentColor" stroke-width="3"/>
                <path d="M14 14 34 34" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
            </svg>
        </div>
        <h1>تم حظر دخولك على الموقع</h1>
        <p>لا يمكنك متابعة استخدام الموقع من هذا الجهاز أو العنوان الشبكي المرتبط بجلستك.</p>
    </article>
</section>
<script>
(function () {
    history.pushState(null, '', location.href);
    window.addEventListener('popstate', function () {
        history.pushState(null, '', location.href);
    });
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (a) {
            e.preventDefault();
        }
    }, true);
})();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
