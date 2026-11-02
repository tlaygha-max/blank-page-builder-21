<?php

declare(strict_types=1);

$service = $service ?? [];
?>
<article
    class="ServiceDetailsPage"
    data-component="ServiceDetailsPage"
    data-select-service="<?= e((string) ($service['slug'] ?? '')) ?>"
    data-service-title="<?= e((string) ($service['title'] ?? '')) ?>"
    data-service-category="<?= e((string) ($service['category'] ?? '')) ?>"
    data-service-duration="<?= e((string) ($service['duration'] ?? '')) ?>"
    data-service-fee="<?= e((string) ($service['cost'] ?? $service['fee'] ?? '')) ?>"
>
    <?php require __DIR__ . '/service-hero.php'; ?>
    <div class="ServiceDetailsPage__body">
        <?php require __DIR__ . '/service-breadcrumbs.php'; ?>
        <?php require __DIR__ . '/service-title.php'; ?>
        <?php require __DIR__ . '/service-summary-card.php'; ?>
        <?php require __DIR__ . '/service-description-accordion.php'; ?>
    </div>
</article>
