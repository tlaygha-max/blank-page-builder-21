<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$pageTitle = 'تسجيل الدخول';
$pageDescription = 'تسجيل الدخول إلى بوابة الخدمات الموحدة.';
$pageId = 'login';
$bodyClass = 'auth-page login-screen';
$hideSiteFooter = true;
$useNasFooter = true;
$nasBrand = 'sw';
$themeColor = '#00326d';
$loginTab = isset($_GET['tab']) && $_GET['tab'] === 'identity' ? 'identity' : 'verify';

require __DIR__ . '/partials/header.php';
?>

<section class="login-hero">
    <button class="login-hero__arrow login-hero__arrow--prev" type="button" data-login-promo-prev aria-label="السابق"></button>
    <button class="login-hero__arrow login-hero__arrow--next" type="button" data-login-promo-next aria-label="التالي"></button>

    <div class="login-hero__inner">
        <aside class="login-promo" aria-label="عرض الخدمات">
            <div class="login-promo__slide is-active" data-login-promo>
                <h2 class="login-promo__title"><span class="login-promo__accent">مستشار تأسيس الأعمال</span></h2>
                <p class="login-promo__text">احصل على إرشاد سريع ومخصص حول المتطلبات والرسوم والمستندات والمدة المتوقعة قبل تقديم طلب التأسيس الرسمي.</p>
            </div>
            <div class="login-promo__slide" data-login-promo hidden>
                <h2 class="login-promo__title"><span class="login-promo__accent">إبدا</span> عملك</h2>
                <p class="login-promo__text">حيث يمكنك تأسيس العمل التجاري بكل سرعة وسهولة وأمان..</p>
            </div>
            <div class="login-promo__slide" data-login-promo hidden>
                <h2 class="login-promo__title"><span class="login-promo__accent">رخص</span> عملك</h2>
                <p class="login-promo__text">تقدم بطلب الحصول على ترخيصك التجاري بعد إتمام حجز اسمك التجاري وتأسيس شركتك.</p>
            </div>
            <div class="login-promo__slide" data-login-promo hidden>
                <h2 class="login-promo__title"><span class="login-promo__accent">تجديد</span> الشركة</h2>
                <p class="login-promo__text">تجديد شركتك (السجل التجاري للشركة الرئيسية وفروعها، والترخيص التجاري)</p>
            </div>
            <div class="login-promo__dots" data-login-promo-dots>
                <button class="is-active" type="button" aria-label="الشريحة 1"></button>
                <button type="button" aria-label="الشريحة 2"></button>
                <button type="button" aria-label="الشريحة 3"></button>
                <button type="button" aria-label="الشريحة 4"></button>
            </div>
        </aside>

        <div class="login-card" id="login-card">
            <div class="login-tabs" role="tablist" aria-label="طريقة الدخول">
                <button
                    class="login-tab<?= $loginTab === 'verify' ? ' is-active' : '' ?>"
                    type="button"
                    role="tab"
                    id="login-tab-verify"
                    aria-selected="<?= $loginTab === 'verify' ? 'true' : 'false' ?>"
                    aria-controls="login-panel-verify"
                    data-login-tab="verify"
                >
                    <img class="login-tab__logo login-tab__logo--tawtheeq" src="<?= e(asset_url('img/tawtheeq.png')) ?>" alt="" width="148" height="48">
                    <span class="sr-only">توثيق Tawtheeq</span>
                </button>
                <button
                    class="login-tab<?= $loginTab === 'identity' ? ' is-active' : '' ?>"
                    type="button"
                    role="tab"
                    id="login-tab-identity"
                    aria-selected="<?= $loginTab === 'identity' ? 'true' : 'false' ?>"
                    aria-controls="login-panel-identity"
                    data-login-tab="identity"
                >
                    <?php
                    $qdiSize = 'tab';
                    $qdiInverse = false;
                    require __DIR__ . '/partials/qdi-lockup.php';
                    ?>
                    <span class="sr-only">هوية قطر الرقمية</span>
                </button>
            </div>

            <div id="login-panel-verify" role="tabpanel" aria-labelledby="login-tab-verify" data-login-panel="verify"<?= $loginTab === 'identity' ? ' hidden inert' : '' ?>>
                <h2 class="login-title">تسجيل الدخول عن طريق نظام توثيق</h2>
                <p class="login-subtitle">الرجاء إدخال المعلومات المطلوبة</p>

                <form class="login-form" data-demo-login-form novalidate autocomplete="off">
                    <label class="login-field">
                        <span class="sr-only">اسم المستخدم</span>
                        <span class="login-field__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2.25c-4.2 0-7.5 2.14-7.5 4.5V21h15v-2.25c0-2.36-3.3-4.5-7.5-4.5Z"/></svg>
                        </span>
                        <input id="demo-username" name="demoUsername" type="text" inputmode="text" autocapitalize="none" spellcheck="false" autocomplete="off" data-1p-ignore placeholder="ادخل اسم المستخدم" aria-describedby="demo-login-error" required>
                    </label>

                    <label class="login-field">
                        <span class="sr-only">كلمة المرور</span>
                        <span class="login-field__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path fill="currentColor" d="M17 9h-1V7A4 4 0 0 0 8 7v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2h-4Zm2 8.7A1.7 1.7 0 1 1 13.7 14 1.7 1.7 0 0 1 12 15.7Z"/></svg>
                        </span>
                        <input id="demo-access-code" name="demoAccessCode" type="password" autocomplete="new-password" data-1p-ignore placeholder="ادخل 6 أحرف على الأقل" aria-describedby="demo-login-error" required minlength="6">
                    </label>

                    <p class="login-forgot">
                        <a href="<?= e(app_url('forgot-password')) ?>" data-i18n-forgot>نسيت كلمة المرور؟</a>
                    </p>

                    <p class="login-error" id="demo-login-error" data-demo-login-error aria-live="polite"></p>

                    <button class="login-submit" type="submit" data-i18n-submit>تسجيل الدخول</button>
                </form>

                <p class="login-register">
                    <span data-i18n-no-account>لا تمتلك حساب حتى الآن؟</span>
                    <a href="<?= e(app_url('register')) ?>" data-i18n-register>تسجيل الحساب</a>
                </p>

                <div class="login-divider" data-i18n-or>أو</div>

                <div class="login-alt-actions">
                    <a class="login-smart" href="<?= e(app_url('smart-card')) ?>">
                        <svg viewBox="0 0 32 24" aria-hidden="true" focusable="false">
                            <rect x="1.2" y="4" width="22" height="16" rx="2.2" fill="none" stroke="currentColor" stroke-width="1.8"/>
                            <rect x="4" y="8" width="6.5" height="5" rx="0.8" fill="currentColor"/>
                            <path d="M13 10h8M13 13.5h5.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round"/>
                            <path d="M24.5 8.5v7l6-3.5-6-3.5Z" fill="currentColor"/>
                        </svg>
                        <span data-i18n-smart>الدخول بالبطاقة الذكية</span>
                    </a>

                    <a class="login-smart login-card__services" href="<?= e(app_url('portal')) ?>">
                        <svg class="login-header__services-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M4 4h6.5v6.5H4V4Zm9.5 0H20v6.5h-6.5V4ZM4 13.5H10.5V20H4v-6.5Zm9.5 0H20V20h-6.5v-6.5Z"/>
                        </svg>
                        <span data-i18n-nav-services>الخدمات</span>
                    </a>
                </div>
            </div>

            <div id="login-panel-identity" role="tabpanel" aria-labelledby="login-tab-identity" data-login-panel="identity"<?= $loginTab === 'verify' ? ' hidden inert' : '' ?>>
                <p class="login-identity-note" data-identity-note>
                    تسجيل الدخول بواسطة البطاقة الرقمية غير متاح حاليا، يرجى تسجيل الدخول بواسطة نظام توثيق من تبويب توثيق بالاعلى.
                </p>
            </div>
        </div>
    </div>

    <div class="login-skyline" aria-hidden="true">
        <svg viewBox="0 0 640 140" preserveAspectRatio="xMidYMax meet">
            <g fill="#0a2a52">
                <rect x="8" y="78" width="18" height="62"/>
                <rect x="30" y="58" width="14" height="82"/>
                <rect x="48" y="70" width="22" height="70"/>
                <rect x="78" y="42" width="16" height="98"/>
                <rect x="98" y="62" width="20" height="78"/>
                <path d="M128 140V36c18-22 38-22 56 0v104Z"/>
                <rect x="192" y="50" width="18" height="90"/>
                <rect x="214" y="28" width="12" height="112"/>
                <rect x="230" y="64" width="26" height="76"/>
                <rect x="262" y="44" width="14" height="96"/>
                <rect x="282" y="18" width="10" height="122"/>
                <rect x="296" y="52" width="22" height="88"/>
                <path d="M328 140V48c22-28 52-28 74 0v92Z"/>
                <rect x="410" y="36" width="16" height="104"/>
                <rect x="430" y="58" width="24" height="82"/>
                <rect x="458" y="24" width="11" height="116"/>
                <rect x="474" y="68" width="28" height="72"/>
                <rect x="508" y="46" width="18" height="94"/>
                <rect x="530" y="32" width="14" height="108"/>
                <rect x="548" y="60" width="22" height="80"/>
                <rect x="576" y="50" width="16" height="90"/>
                <rect x="598" y="72" width="28" height="68"/>
            </g>
        </svg>
    </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
