<?php

declare(strict_types=1);

$code = $code ?? '';
$service = $service ?? [];
$href = service_url((string) $code);
?>
<a
    class="ServiceCard"
    href="<?= e($href) ?>"
    data-select-service="<?= e((string) $code) ?>"
    data-service-title="<?= e((string) ($service['title'] ?? '')) ?>"
    data-service-category="<?= e((string) ($service['category'] ?? '')) ?>"
    data-service-duration="<?= e((string) ($service['duration'] ?? '')) ?>"
    data-service-fee="<?= e((string) ($service['cost'] ?? $service['fee'] ?? '')) ?>"
>
    <?= service_card_icon((string) ($service['icon'] ?? 'certificates')) ?>
    <h2 class="ServiceCard__title"><?= e((string) ($service['title'] ?? '')) ?></h2>
    <p class="ServiceCard__desc"><?= e((string) ($service['excerpt'] ?? $service['title'] ?? '')) ?></p>
    <p class="ServiceCard__meta">
        <span><?= e((string) ($service['duration'] ?? '')) ?></span>
        <span><?= e((string) ($service['cost'] ?? $service['fee'] ?? '')) ?></span>
    </p>
</a>
