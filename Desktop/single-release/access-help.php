<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'استعادة الوصول التجريبي';
$pageDescription = 'مساعدة للوصول إلى العرض دون استعادة حساب أو إرسال رمز.';
$pageId = 'access-help';
$bodyClass = 'auth-page';

require __DIR__ . '/partials/header.php';
?>

<section class="auth-section">
    <div class="container help-card">
        <div class="help-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M12 2.5A9.5 9.5 0 1 0 21.5 12 9.5 9.5 0 0 0 12 2.5Zm0 14.2a1.2 1.2 0 1 1-1.2 1.2 1.2 1.2 0 0 1 1.2-1.2Zm1.3-3.4-.4.2a1 1 0 0 0-.6.9v.3h-1.6v-.4a2.6 2.6 0 0 1 1.5-2.4l.5-.3a1.5 1.5 0 1 0-2.2-1.4H8.9A3.2 3.2 0 0 1 12 6.8a3.1 3.1 0 0 1 1.3 5.9Z"/></svg>
        </div>
        <span class="flow-kicker">مساعدة الدخول</span>
        <h1>لا يوجد حساب حقيقي لاستعادته</h1>
        <p>هذا نموذج تجريبي، لذلك لا نرسل بريدًا أو رسالة أو OTP. استخدم دائمًا بيانات العرض الثابتة التالية:</p>

        <div class="demo-credentials demo-credentials--large">
            <div><span>اسم المستخدم</span><strong>demo</strong></div>
            <div><span>رمز الدخول</span><strong>2468</strong></div>
        </div>

        <div class="safety-inline"><span>!</span> لا تدخل بريدك أو رقم هاتفك أو كلمة مرور تستخدمها في أي مكان آخر.</div>

        <div class="success-actions">
            <a class="button button--primary" href="<?= e(app_url('?fill=1')) ?>">استخدام بيانات العرض</a>
            <a class="button button--ghost" href="<?= e(app_url('register')) ?>">إنشاء ملف تجريبي</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
