<?php

declare(strict_types=1);

$service = $service ?? [];
$title = (string) ($service['title'] ?? '');
?>
<section class="ServiceHero" data-component="ServiceHero" aria-label="<?= e($title) ?>">
    <h1 class="ServiceHero__title"><?= e($title) ?></h1>
</section>
