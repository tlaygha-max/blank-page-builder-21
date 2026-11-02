<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'تفاصيل الخدمة';
$pageDescription = 'تفاصيل ورسوم تقديرية وهمية للخدمة المختارة.';
$pageId = 'details';
$showStepper = true;
$currentStep = 3;
$requiresDemoAuth = true;

require __DIR__ . '/partials/header.php';
?>

<section class="flow-section">
    <div class="container flow-container">
        <div class="flow-heading">
            <span class="flow-kicker">الخطوة الثالثة</span>
            <h1>تفاصيل الخدمة المختارة</h1>
            <p>القيم أدناه تقديرية ووهمية بالكامل، ولا تمثل سعرًا أو مدة حقيقية.</p>
        </div>

        <div class="details-grid" data-service-details>
            <article class="details-main">
                <div class="details-title-row">
                    <span class="service-icon service-icon--large" data-detail-icon aria-hidden="true">✦</span>
                    <div><span class="category-label" data-detail-category>خدمة تجريبية</span><h2 data-detail-title>...</h2></div>
                </div>
                <p class="details-description" data-detail-description>...</p>
                <h3>ما الذي تتضمنه التجربة؟</h3>
                <ul class="feature-list">
                    <li><span>✓</span> مراجعة معلومات الطلب قبل الإكمال</li>
                    <li><span>✓</span> رسوم ومدة موضحتان كقيم وهمية</li>
                    <li><span>✓</span> رقم مرجعي تجريبي عند النجاح</li>
                </ul>
            </article>

            <aside class="estimate-card">
                <span class="mini-label">الملخص التقديري</span>
                <div class="estimate-row"><span>الرسوم</span><strong data-detail-fee>...</strong></div>
                <div class="estimate-row"><span>المدة</span><strong data-detail-duration>...</strong></div>
                <div class="estimate-row"><span>المدينة</span><strong data-detail-city>...</strong></div>
                <div class="estimate-disclaimer"><span aria-hidden="true">!</span> لا يوجد دفع أو حجز فعلي في هذا النموذج.</div>
            </aside>
        </div>

        <div class="flow-actions">
            <a class="button button--ghost" href="<?= e(app_url('request')) ?>">تعديل البيانات</a>
            <button class="button button--primary" type="button" data-details-next>الانتقال للمراجعة <span aria-hidden="true">←</span></button>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
