<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'الدخول بالبطاقة الذكية';
$pageDescription = 'مصادقة تجريبية باسم المستخدم وكلمة المرور.';
$pageId = 'smart-card';
$bodyClass = 'nas-login-screen';
$hideSiteFooter = true;
$useNasLayout = true;
$themeColor = '#1a4f86';

require __DIR__ . '/partials/header.php';
?>

<section class="nas-shell">
    <article class="nas-card">
        <h1 class="nas-card__title">المصادقة مع اسم المستخدم وكلمة المرور</h1>
        <p class="nas-card__kicker">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M12 12a4.2 4.2 0 1 0-4.2-4.2A4.2 4.2 0 0 0 12 12Zm0 2.1c-3.9 0-7 1.9-7 4.2V21h14v-2.7c0-2.3-3.1-4.2-7-4.2Z"/>
            </svg>
            <span>الدخول بواسطة إسم المُستخدم</span>
        </p>

        <form class="nas-form" data-nas-login-form novalidate autocomplete="off">
            <label class="nas-field" for="nas-username">اسم المستخدم</label>
            <input id="nas-username" name="nasUsername" type="text" inputmode="text" autocapitalize="none" spellcheck="false" autocomplete="off" data-1p-ignore placeholder="اسم المستخدم">

            <label class="nas-field" for="nas-password">كلمة المرور</label>
            <input id="nas-password" name="nasPassword" type="password" autocomplete="new-password" data-1p-ignore placeholder="كلمة المرور">

            <p class="nas-error" data-nas-error aria-live="polite"></p>

            <div class="nas-actions">
                <button class="nas-continue" type="submit">استمر</button>
                <a class="nas-cancel" href="<?= e(app_url()) ?>">إلغاء</a>
            </div>
        </form>

        <p class="nas-links">
            <a href="<?= e(app_url('forgot-password')) ?>">هل نسيت كلمة المرور؟</a>
            <a href="<?= e(app_url('register')) ?>">إنشاء حساب جديد</a>
        </p>
    </article>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
