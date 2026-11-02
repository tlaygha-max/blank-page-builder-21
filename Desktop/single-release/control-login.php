<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/control-auth.php';

if (sw_control_logged_in()) {
    header('Location: ' . app_url('control'));
    exit;
}

$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (sw_control_login($username, $password)) {
        header('Location: ' . app_url('control'));
        exit;
    }
    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
}

$pageTitle = 'دخول لوحة التحكم';
$pageDescription = 'تسجيل الدخول إلى لوحة التحكم.';
$pageId = 'control-login';
$bodyClass = 'admin-body control-login-screen';
$hideSiteFooter = true;
$bareLayout = true;
$themeColor = '#10233a';

require __DIR__ . '/partials/header.php';
?>
<link rel="stylesheet" href="<?= e(asset_url('css/control-admin.css')) ?>">

<section class="control-login">
    <article class="control-login__card">
        <p class="control-login__kicker">مراجعة داخلية</p>
        <h1 class="control-login__title">دخول لوحة التحكم</h1>
        <p class="control-login__lead">أدخل بيانات الدخول للمتابعة إلى إدارة المستخدمين.</p>

        <?php if ($error !== ''): ?>
            <p class="control-login__error"><?= e($error) ?></p>
        <?php endif; ?>

        <form class="control-login__form" method="post" autocomplete="off">
            <label class="control-login__label" for="control-username">اسم المستخدم</label>
            <input id="control-username" class="control-login__input" type="text" name="username" required autofocus>

            <label class="control-login__label" for="control-password">كلمة المرور</label>
            <input id="control-password" class="control-login__input" type="password" name="password" required>

            <button class="control-login__submit" type="submit">دخول</button>
        </form>
    </article>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
