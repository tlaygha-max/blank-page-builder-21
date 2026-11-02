<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'بيانات الطلب التجريبي';
$pageDescription = 'نموذج طلب تجريبي لا يطلب بيانات حساسة.';
$pageId = 'request';
$showStepper = true;
$currentStep = 2;
$requiresDemoAuth = true;
$services = services_catalog();

require __DIR__ . '/partials/header.php';
?>

<section class="flow-section">
    <div class="container form-layout">
        <div class="flow-heading flow-heading--aligned">
            <span class="flow-kicker">الخطوة الثانية</span>
            <h1>بيانات بسيطة للتجربة</h1>
            <p>استخدم اسمًا وهميًا فقط. لا تكتب اسمك الحقيقي أو أي معلومات شخصية في الملاحظات.</p>
        </div>

        <aside class="safety-card" aria-label="تنبيه الخصوصية">
            <span aria-hidden="true">!</span>
            <div><strong>هذه ليست معاملة حقيقية</strong><p>لا بريد إلكتروني، لا رقم هاتف، لا رقم هوية، ولا بيانات دفع.</p></div>
        </aside>

        <form class="request-form" data-request-form novalidate autocomplete="off">
            <div class="field-grid">
                <div class="field">
                    <label for="demo-name">الاسم التجريبي <span>*</span></label>
                    <input id="demo-name" name="demoName" type="text" minlength="3" maxlength="60" placeholder="مثال: زائر السرو" aria-describedby="demo-name-help demo-name-error">
                    <small id="demo-name-help">اكتب اسمًا وهميًا من 3 أحرف على الأقل.</small>
                    <p class="field-error" id="demo-name-error" aria-live="polite"></p>
                </div>

                <div class="field">
                    <label for="service-type">نوع الخدمة <span>*</span></label>
                    <select id="service-type" name="serviceCode" aria-describedby="service-type-error">
                        <option value="">اختر خدمة خيالية</option>
                        <?php foreach ($services as $code => $service): ?>
                            <option value="<?= e($code) ?>"><?= e($service['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="field-error" id="service-type-error" aria-live="polite"></p>
                </div>

                <div class="field">
                    <label for="city">المدينة التجريبية <span>*</span></label>
                    <select id="city" name="city" aria-describedby="city-error">
                        <option value="">اختر مدينة خيالية</option>
                        <option value="مدينة السرو">مدينة السرو</option>
                        <option value="مدينة الميناء">مدينة الميناء</option>
                        <option value="مدينة الواحة">مدينة الواحة</option>
                        <option value="مدينة التلال">مدينة التلال</option>
                    </select>
                    <p class="field-error" id="city-error" aria-live="polite"></p>
                </div>

                <div class="field">
                    <label for="appointment">الموعد التجريبي <span>*</span></label>
                    <input id="appointment" name="appointment" type="date" aria-describedby="appointment-error">
                    <p class="field-error" id="appointment-error" aria-live="polite"></p>
                </div>

                <div class="field field--full">
                    <div class="label-row"><label for="notes">ملاحظات اختيارية</label><span data-notes-count>0 / 300</span></div>
                    <textarea id="notes" name="notes" rows="5" maxlength="300" placeholder="اكتب وصفًا خياليًا مختصرًا، دون أي بيانات حقيقية." aria-describedby="notes-help notes-error"></textarea>
                    <small id="notes-help">لا تضف رقم هاتف أو بريدًا إلكترونيًا أو عنوانًا حقيقيًا.</small>
                    <p class="field-error" id="notes-error" aria-live="polite"></p>
                </div>
            </div>

            <div class="flow-actions form-actions">
                <a class="button button--ghost" href="<?= e(app_url('user-type')) ?>">السابق</a>
                <button class="button button--primary" type="submit">عرض التفاصيل <span aria-hidden="true">←</span></button>
            </div>
        </form>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
