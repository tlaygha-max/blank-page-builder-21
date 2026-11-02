<?php

declare(strict_types=1);
?>
<header class="phone-shell__header">
    <div class="phone-shell__top">
        <?php require __DIR__ . '/lang-toggle.php'; ?>
        <a class="phone-shell__brand" href="<?= e(app_url()) ?>" aria-label="النافذة الواحدة">
            <?php
            $brandSize = 'header';
            $brandInverse = false;
            require __DIR__ . '/brand-lockup.php';
            ?>
        </a>
    </div>
</header>
<div class="phone-shell__hero">
    <img
        class="phone-shell__hero-logo"
        src="<?= e(asset_url('img/contact-center.jpg')) ?>"
        alt="مركز الاتصال الحكومي — Government Contact Center"
        width="663"
        height="297"
    >
</div>
