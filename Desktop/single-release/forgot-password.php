<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'اعادة تعيين';
$pageDescription = 'اعادة تعيين كلمة المرور أو رقم الهاتف بعد مصادقة البيانات.';
$pageId = 'forgot-password';
$bodyClass = 'nas-login-screen forgot-password-screen';
$hideSiteFooter = true;
$useNasLayout = true;
$nasBrand = 'sw';
$themeColor = '#1a4f86';

require __DIR__ . '/partials/header.php';
?>

<section class="nas-shell reset-shell">
    <article class="nas-card reset-card">
        <h1 class="reset-card__title" data-i18n="reset.title">اعادة تعيين</h1>
        <p class="reset-card__lead" data-i18n="reset.lead">في حال اردت تغيير رقم الهاتف أو كلمة المرور، يجب مصادقة بياناتك بخطوات بسيطة.</p>
        <p class="reset-card__note" data-i18n="reset.note">في حال نسيان كلمة المرور، او لم يعد لديك وصول لرقم هاتفك يجب تحديث البيانات لتغيير البيانات المراد تعديلها.</p>
        <a class="reset-continue" href="<?= e(app_url('register')) ?>" data-i18n="reset.continue">استمر</a>
    </article>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
