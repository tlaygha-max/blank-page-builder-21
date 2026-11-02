<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

$pageTitle = 'الصفحة غير موجودة';
$pageDescription = 'تعذر العثور على الصفحة المطلوبة.';
$pageId = 'not-found';
require __DIR__ . '/partials/header.php';
?>
<section class="flow-section error-section">
    <div class="container success-container">
        <span class="error-code">404</span>
        <h1>هذه الصفحة غير موجودة</h1>
        <p>قد يكون الرابط قديمًا، أو أن المسار لم يعد جزءًا من التجربة الآمنة.</p>
        <a class="button button--primary" href="<?= e(app_url()) ?>">العودة للرئيسية</a>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
