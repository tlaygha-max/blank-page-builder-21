<?php

declare(strict_types=1);

$captchaInputId = $captchaInputId ?? 'captcha-code';
$showCaptchaHeading = $showCaptchaHeading ?? false;
$captchaExtraClass = $captchaExtraClass ?? '';
?>

<?php if ($showCaptchaHeading): ?>
<p class="nas-field" id="<?= e($captchaInputId) ?>-label" data-nas-captcha-heading>التحقق</p>
<?php endif; ?>

<div
    class="nas-captcha<?= $captchaExtraClass !== '' ? ' ' . e($captchaExtraClass) : '' ?>"
    data-nas-captcha
    data-captcha-url="<?= e(app_url('api/captcha.php')) ?>"
>
    <div class="nas-captcha__row">
        <img
            class="nas-captcha__image"
            data-nas-captcha-image
            src="<?= e(app_url('api/captcha.php')) ?>?t=0"
            alt=""
        />
        <button
            class="nas-captcha__refresh"
            type="button"
            data-nas-captcha-refresh
            aria-label="تغيير التحقق"
        >↻</button>
    </div>

    <label
        class="nas-field nas-captcha__prompt"
        for="<?= e($captchaInputId) ?>"
        data-nas-captcha-prompt
    >يرجى إدخال رمز التحقق</label>

    <input
        id="<?= e($captchaInputId) ?>"
        name="captchaCode"
        type="text"
        inputmode="numeric"
        maxlength="5"
        autocomplete="off"
        data-nas-captcha-input
        placeholder=""
    >
</div>

