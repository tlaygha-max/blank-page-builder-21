<?php

declare(strict_types=1);

$services = $services ?? services_catalog();
?>
<section class="ServicesScreen" data-component="ServicesScreen" aria-labelledby="services-screen-title">
    <header class="ServicesScreen__intro">
        <h1 class="ServicesScreen__title" id="services-screen-title">الخدمات</h1>
        <p class="ServicesScreen__lead">اختر الخدمة المطلوبة للاطلاع على المدة والتكلفة التقديرية وبدء الطلب.</p>
    </header>
    <div class="ServicesScreen__list">
        <?php foreach ($services as $code => $service): ?>
            <?php require __DIR__ . '/service-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
