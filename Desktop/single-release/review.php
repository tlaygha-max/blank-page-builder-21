<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'مراجعة الطلب';
$pageDescription = 'راجع الطلب التجريبي قبل إنشاء رقم مرجعي وهمي.';
$pageId = 'review';
$showStepper = true;
$currentStep = 4;
$requiresDemoAuth = true;

require __DIR__ . '/partials/header.php';
?>

<section class="flow-section">
    <div class="container flow-container">
        <div class="flow-heading">
            <span class="flow-kicker">الخطوة الرابعة</span>
            <h1>راجع الطلب قبل الإكمال</h1>
            <p>تأكد أن جميع المعلومات وهمية. لن تُرسل هذه البيانات إلى أي خادم أو جهة.</p>
        </div>

        <div class="review-card" data-review-card>
            <div class="review-card__header">
                <div><span class="mini-label">ملخص الطلب</span><h2 data-review-service>...</h2></div>
                <a href="<?= e(app_url('request')) ?>">تعديل البيانات</a>
            </div>
            <dl class="review-list">
                <div><dt>نوع المستخدم</dt><dd data-review-user-type>...</dd></div>
                <div><dt>الاسم التجريبي</dt><dd data-review-name>...</dd></div>
                <div><dt>المدينة</dt><dd data-review-city>...</dd></div>
                <div><dt>الموعد</dt><dd data-review-appointment>...</dd></div>
                <div><dt>الرسوم الوهمية</dt><dd data-review-fee>...</dd></div>
                <div class="review-notes"><dt>الملاحظات</dt><dd data-review-notes>لا توجد ملاحظات</dd></div>
            </dl>
        </div>

        <label class="confirm-box">
            <input type="checkbox" data-demo-confirm>
            <span><strong>أؤكد أنني استخدمت بيانات تجريبية فقط.</strong><small>هذا التأكيد جزء من النموذج التعليمي ولا يمثل توقيعًا أو موافقة رسمية.</small></span>
        </label>
        <p class="selection-hint selection-hint--error" data-confirm-error aria-live="polite"></p>

        <div class="flow-actions">
            <a class="button button--ghost" href="<?= e(app_url('details')) ?>">السابق</a>
            <button class="button button--primary" type="button" data-submit-demo>إكمال الطلب التجريبي <span aria-hidden="true">←</span></button>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
