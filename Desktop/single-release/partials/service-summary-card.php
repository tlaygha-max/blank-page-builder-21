<?php

declare(strict_types=1);

$service = $service ?? [];
$slug = (string) ($service['slug'] ?? '');
$startUrl = service_start_url($slug);
?>
<article class="ServiceSummaryCard" data-component="ServiceSummaryCard">
    <div class="ServiceSummaryCard__row">
        <p class="ServiceSummaryCard__label">المدة الزمنية (تقريباً)</p>
        <div class="ServiceSummaryCard__value">
            <?= service_duration_icon() ?>
            <strong><?= e((string) ($service['duration'] ?? '')) ?></strong>
        </div>
    </div>
    <hr class="ServiceSummaryCard__rule">
    <div class="ServiceSummaryCard__row">
        <p class="ServiceSummaryCard__label">التكلفة (تقريباً)</p>
        <div class="ServiceSummaryCard__value">
            <?= service_cost_icon() ?>
            <strong><?= e((string) ($service['cost'] ?? '')) ?></strong>
        </div>
    </div>
    <hr class="ServiceSummaryCard__rule">
    <a class="ServiceSummaryCard__cta" href="<?= e($startUrl) ?>" data-start-service="<?= e($slug) ?>">ابدأ الخدمة</a>
</article>
