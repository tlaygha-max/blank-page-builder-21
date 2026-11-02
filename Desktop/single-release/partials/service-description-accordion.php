<?php

declare(strict_types=1);

$service = $service ?? [];
$slug = (string) ($service['slug'] ?? 'service');
$panelId = 'service-description-' . preg_replace('/[^a-z0-9-]/i', '', $slug);
$descriptionHtml = service_description_html($service['description'] ?? []);
?>
<section class="ServiceDescriptionAccordion" data-component="ServiceDescriptionAccordion">
    <h2 class="ServiceDescriptionAccordion__heading">
        <button
            class="ServiceDescriptionAccordion__toggle"
            type="button"
            id="<?= e($panelId) ?>-trigger"
            aria-expanded="true"
            aria-controls="<?= e($panelId) ?>"
            data-service-accordion
        >
            <span>وصف الخدمة</span>
            <svg class="ServiceDescriptionAccordion__chevron" viewBox="0 0 14 8" aria-hidden="true" focusable="false">
                <path d="M1 6.4 7 1.6 13 6.4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </h2>
    <div class="ServiceDescriptionAccordion__panel" id="<?= e($panelId) ?>" role="region" aria-labelledby="<?= e($panelId) ?>-trigger">
        <div class="ServiceDescriptionAccordion__body">
            <?php if ($descriptionHtml !== ''): ?>
                <?= $descriptionHtml ?>
            <?php else: ?>
                <p class="ServiceDescriptionAccordion__p">لا يتوفر وصف تفصيلي لهذه الخدمة حالياً.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
