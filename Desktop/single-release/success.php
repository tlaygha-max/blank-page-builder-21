<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'اكتملت التجربة';
$pageDescription = 'صفحة نجاح برقم مرجعي تجريبي وهمي.';
$pageId = 'success';
$showStepper = true;
$currentStep = 5;
$bodyClass = 'success-page';
$requiresDemoAuth = true;

require __DIR__ . '/partials/header.php';
?>

<section class="flow-section success-section">
    <div class="container success-container">
        <div class="success-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M9.4 16.6 5.8 13l-1.4 1.4 5 5 10-10-1.4-1.4Z"/></svg>
        </div>
        <span class="flow-kicker">اكتملت التجربة</span>
        <h1>تم إنشاء طلبك الوهمي بنجاح</h1>
        <p>هذا التأكيد للعرض فقط. لم تُرسل أي بيانات، وتم مسح مسودة الطلب من هذا التبويب.</p>

        <div class="reference-card">
            <span>الرقم المرجعي التجريبي</span>
            <strong data-reference-number>...</strong>
            <small>لا يمكن استخدام هذا الرقم في أي معاملة حقيقية.</small>
        </div>

        <div class="success-note"><span aria-hidden="true">i</span> انتهت الرحلة هنا، ولا توجد متابعة أو رسائل أو إشعارات لاحقة.</div>

        <div class="success-actions">
            <a class="button button--primary" href="<?= e(app_url()) ?>" data-new-demo>بدء تجربة جديدة</a>
            <a class="button button--ghost" href="<?= e(app_url()) ?>#services">العودة للخدمات</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
