<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'اختيار نوع المستخدم';
$pageDescription = 'اختر صفتك في المسار التجريبي دون إنشاء حساب.';
$pageId = 'user-type';
$showStepper = true;
$currentStep = 1;
$requiresDemoAuth = true;

require __DIR__ . '/partials/header.php';
?>

<section class="flow-section">
    <div class="container flow-container">
        <div class="flow-heading">
            <span class="flow-kicker">الخطوة الأولى</span>
            <h1>كيف تريد متابعة التجربة؟</h1>
            <p>لا ينشئ هذا الاختيار حسابًا، ويُستخدم فقط لتخصيص ملخص الطلب داخل هذا التبويب.</p>
        </div>

        <div class="choice-grid" role="radiogroup" aria-label="نوع المستخدم">
            <button class="choice-card" type="button" role="radio" aria-checked="false" data-user-type="individual">
                <span class="choice-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M12 12a4.2 4.2 0 1 0-4.2-4.2A4.2 4.2 0 0 0 12 12Zm0 2.1c-3.9 0-7 1.9-7 4.2V21h14v-2.7c0-2.3-3.1-4.2-7-4.2Z"/></svg>
                </span>
                <span class="choice-content"><strong>فرد</strong><small>طلب تجريبي باسم شخصي وهمي</small></span>
                <span class="choice-check" aria-hidden="true">✓</span>
            </button>
            <button class="choice-card" type="button" role="radio" aria-checked="false" data-user-type="organization">
                <span class="choice-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M4 20V8.8L12 4l8 4.8V20h-6.2v-5.2H10.2V20H4Zm2.3-2.2h1.6v-3h7.2v3h1.6V10l-6.2-3.6L6.3 10v7.8Z"/></svg>
                </span>
                <span class="choice-content"><strong>منشأة</strong><small>تجربة المسار باسم منشأة افتراضية</small></span>
                <span class="choice-check" aria-hidden="true">✓</span>
            </button>
            <button class="choice-card" type="button" role="radio" aria-checked="false" data-user-type="visitor">
                <span class="choice-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M12 3.2A8.8 8.8 0 1 0 20.8 12 8.8 8.8 0 0 0 12 3.2Zm0 2.1a6.6 6.6 0 0 1 6.2 4.4H13.8V5.4A6.5 6.5 0 0 1 12 5.3Zm-2.2.3v4.4H5.6A6.6 6.6 0 0 1 9.8 5.6Zm0 6.6v4.4a6.6 6.6 0 0 1-4.2-4.4Zm2.2 4.7a6.5 6.5 0 0 1 1.8-.2 6.5 6.5 0 0 1 1.8.2v-4.5h4.4A6.6 6.6 0 0 1 12 18.7Zm2.2-6.5V7.8h4.2A6.6 6.6 0 0 1 18.4 12Z"/></svg>
                </span>
                <span class="choice-content"><strong>زائر</strong><small>استعراض الخطوات دون حساب أو تسجيل</small></span>
                <span class="choice-check" aria-hidden="true">✓</span>
            </button>
        </div>

        <p class="selection-hint" id="type-selection-hint" aria-live="polite">اختر أحد الأنواع للمتابعة.</p>

        <div class="flow-actions">
            <a class="button button--ghost" href="<?= e(app_url()) ?>">العودة للرئيسية</a>
            <button class="button button--primary" type="button" data-user-type-next disabled>متابعة <span aria-hidden="true">←</span></button>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
