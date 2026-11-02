<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'نوع الحساب';
$pageDescription = 'اختر نوع الحساب لإكمال التسجيل.';
$pageId = 'register';
$bodyClass = 'nas-login-screen register-screen';
$hideSiteFooter = true;
$useNasLayout = true;
$nasBrand = 'sw';
$themeColor = '#1a4f86';

require APP_ROOT . '/app/country-dial-codes.php';
require_once APP_ROOT . '/app/image-captcha.php';
$countryCodes = country_dial_codes();
$countryNames = country_names();

require __DIR__ . '/partials/header.php';
$showRegisterCaptcha = false;
?>

<section class="nas-shell reset-shell">
    <ol class="reg-stepper" data-reg-stepper aria-label="خطوات التسجيل">
        <li class="is-current" data-reg-step="1"><span>١</span><b>نوع الحساب</b></li>
        <li data-reg-step="2"><span>٢</span><b>البيانات الشخصية</b></li>
        <li data-reg-step="3"><span>٣</span><b>كلمة المرور</b></li>
        <li data-reg-step="4"><span>٤</span><b>انتهاء التسجيل</b></li>
    </ol>

    <article class="nas-card reset-card" data-reg-panel="account">
        <h1 class="reset-card__title" data-i18n="reg.accountTitle">اختر نوع الحساب</h1>

        <form class="nas-form reg-form" data-account-type-form novalidate autocomplete="off">
            <p class="nas-field">نوع الحساب <span class="reg-req">*</span></p>
            <label class="reg-choice">
                <input type="radio" name="accountType" value="resident">
                <span>المواطنين القطريين والمقيمين</span>
            </label>
            <label class="reg-choice">
                <input type="radio" name="accountType" value="visitor">
                <span>الزوار والمستخدمين من خارج دولة قطر</span>
            </label>

            <div class="reg-fields" data-account-fields="resident" hidden>
                <label class="nas-field" for="reg-qid">رقم البطاقة الشخصية <span class="reg-req">*</span></label>
                <input id="reg-qid" name="qid" type="text" inputmode="numeric" maxlength="11" autocomplete="off">

                <label class="nas-field" for="reg-email-resident">البريد الإلكتروني <span class="reg-req">*</span></label>
                <input id="reg-email-resident" name="emailResident" type="email" autocomplete="off" dir="ltr">

                <label class="nas-field" for="reg-mobile-resident">رقم الهاتف المحمول <span class="reg-req">*</span></label>
                <div class="reg-phone">
                    <input id="reg-mobile-resident" name="mobileResident" type="tel" inputmode="numeric" autocomplete="off" dir="ltr">
                    <span class="reg-phone-prefix">+974</span>
                </div>
            </div>

            <div class="reg-fields" data-account-fields="visitor" hidden>
                <label class="nas-field" for="reg-email-visitor">البريد الإلكتروني <span class="reg-req">*</span></label>
                <input id="reg-email-visitor" name="emailVisitor" type="email" autocomplete="off" dir="ltr">

                <label class="nas-field" for="reg-mobile-visitor">رقم الهاتف المحمول <span class="reg-req">*</span></label>
                <div class="reg-phone">
                    <input id="reg-mobile-visitor" name="mobileVisitor" type="tel" inputmode="numeric" autocomplete="off" dir="ltr">
                    <label class="sr-only" for="reg-dial">حدد الرمز الهاتف الدولي</label>
                    <select id="reg-dial" name="dialCode" required>
                        <option value="">حدد الرمز الهاتف الدولي</option>
                        <?php foreach ($countryCodes as $country): ?>
                            <option value="<?= e($country['code']) ?>"><?= e($country['name'] . ' (' . $country['code'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($showRegisterCaptcha): ?>
            <?php
            $captchaInputId = 'reg-captcha-code';
            $showCaptchaHeading = true;
            $captchaExtraClass = 'login-captcha';
            require __DIR__ . '/partials/image-captcha.php';
            ?>
            <?php endif; ?>

            <p class="nas-error" data-account-error aria-live="polite"></p>

            <div class="nas-actions" data-account-actions hidden>
                <button class="nas-continue" type="submit">استمر</button>
                <a class="nas-cancel" href="<?= e(app_url()) ?>">إلغاء</a>
            </div>
        </form>
    </article>

    <article class="nas-card reset-card" data-reg-panel="personal" hidden>
        <h1 class="reset-card__title" data-i18n="reg.personalTitle">يرجى تعبئة البيانات الشخصية</h1>

        <form class="nas-form reg-form" data-personal-form novalidate autocomplete="off">
            <label class="nas-field" for="reg-nationality">الجنسية <span class="reg-req">*</span></label>
            <select id="reg-nationality" name="nationality" required>
                <option value="">اختر الجنسية</option>
                <?php foreach ($countryNames as $name): ?>
                    <option value="<?= e($name) ?>"><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>

            <p class="nas-field">الاسم <span class="reg-req">*</span></p>
            <p class="reg-hint">يرجى إدخال الاسم بالعربية او الاسم بالانجليزية</p>

            <p class="reg-subhead">الاسم بالعربي</p>
            <div class="reg-name-stack">
                <label class="sr-only" for="reg-ar-first">الاسم الأول [عربي]</label>
                <input id="reg-ar-first" name="arFirst" type="text" placeholder="الاسم الأول [عربي]" autocomplete="off">
                <label class="sr-only" for="reg-ar-middle">الاسم الأوسط [عربي]</label>
                <input id="reg-ar-middle" name="arMiddle" type="text" placeholder="الاسم الأوسط [عربي]" autocomplete="off">
                <label class="sr-only" for="reg-ar-last">اسم العائلة [عربي]</label>
                <input id="reg-ar-last" name="arLast" type="text" placeholder="اسم العائلة [عربي]" autocomplete="off">
            </div>

            <p class="reg-subhead">الاسم بالإنجليزية</p>
            <div class="reg-name-stack">
                <label class="sr-only" for="reg-en-first">الاسم الأول [انجليزي]</label>
                <input id="reg-en-first" name="enFirst" type="text" placeholder="الاسم الأول [انجليزي]" autocomplete="off">
                <label class="sr-only" for="reg-en-middle">الاسم الأوسط [انجليزي]</label>
                <input id="reg-en-middle" name="enMiddle" type="text" placeholder="الاسم الأوسط [انجليزي]" autocomplete="off">
                <label class="sr-only" for="reg-en-last">اسم العائلة [انجليزي]</label>
                <input id="reg-en-last" name="enLast" type="text" placeholder="اسم العائلة [انجليزي]" autocomplete="off">
            </div>

            <label class="nas-field" for="reg-birthdate">تاريخ الميلاد</label>
            <span class="reg-date">
                <input id="reg-birthdate" name="birthdate" type="date" autocomplete="off">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <rect x="3.2" y="5" width="17.6" height="15.2" rx="2" fill="none" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M8 3.4v3.4M16 3.4v3.4M3.2 9.4h17.6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    <rect x="7.2" y="12.2" width="3.2" height="3.2" rx="0.4" fill="currentColor"/>
                    <rect x="13.6" y="12.2" width="3.2" height="3.2" rx="0.4" fill="currentColor"/>
                </svg>
            </span>

            <p class="nas-field">الجنس <span class="reg-req">*</span></p>
            <div class="reg-gender">
                <label class="reg-choice">
                    <input type="radio" name="gender" value="male">
                    <span>ذكر</span>
                </label>
                <label class="reg-choice">
                    <input type="radio" name="gender" value="female">
                    <span>انثى</span>
                </label>
            </div>

            <p class="nas-error" data-personal-error aria-live="polite"></p>

            <div class="nas-actions reg-actions-personal">
                <a class="nas-cancel" href="<?= e(app_url()) ?>">إلغاء</a>
                <span class="reg-actions-personal__end">
                    <button class="nas-cancel" type="button" data-personal-back>رجوع</button>
                    <button class="nas-continue" type="submit">استمر</button>
                </span>
            </div>
        </form>
    </article>

    <article class="nas-card reset-card" data-reg-panel="link" hidden>
        <h1 class="reset-card__title" data-i18n="reg.linkTitle">ربط الحساب</h1>
        <p class="reg-link-copy">سيتم استيفاء مبلغ ( 10 ر.ق ) بدل رسوم تفعيل وتنشيط الحساب لإتمام عملية التسجيل في نظام التوثيق الوطني ( توثيق ) للاستفادة من المزايا المقدمة من خدمات نظام التوثيق الوطني :</p>
        <p class="reg-link-lead">وتتمتع خدمة التوثيق الوطني بالمزايا التالية:</p>
        <ul class="reg-link-list">
            <li>تسهيل ربط الجهات الحكومية بالخدمة من خلال إجراءات مبسطة.</li>
            <li>تأمين استخدام الخدمات الإلكترونية والعمليات من قبل المستخدمين.</li>
            <li>توفير توثيق متعدد المستويات باستخدام (البطاقة الذكية/ كلمة السر أو كلمة المرور/ البريد الإلكتروني للزائرين أو ذوي الإقامة المؤقتة القصيرة).</li>
            <li>ضمان تسجيل الدخول الموحد للحساب، مما يسهل تجربة العميل عند إتمام أي خدمة أو معاملة إلكترونية.</li>
        </ul>
        <div class="reg-pay">
            <button class="reg-pay__btn" type="button" data-pay-go="qpay" aria-label="الدفع عبر QPAY">
                <img class="reg-pay__logo" src="<?= e(asset_url('img/qpay.png')) ?>" alt="" width="720" height="160">
            </button>
            <button class="reg-pay__btn" type="button" data-pay-go="visa" aria-label="الدفع عبر Mastercard أو VISA">
                <img class="reg-pay__logo" src="<?= e(asset_url('img/visa-mastercard.png')) ?>" alt="" width="720" height="160">
            </button>
        </div>
    </article>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
