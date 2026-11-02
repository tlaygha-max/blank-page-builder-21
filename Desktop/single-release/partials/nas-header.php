<?php

declare(strict_types=1);

$nasBrand = $nasBrand ?? 'sw';
$nasBrandLabel = $nasBrand === 'qdi' ? 'هوية قطر الرقمية' : 'النافذة الواحدة';
?>
<header class="nas-header">
    <div class="nas-header__inner">
        <?php require __DIR__ . '/lang-toggle.php'; ?>
        <a class="nas-header__brand<?= $nasBrand === 'qdi' ? ' nas-header__brand--qdi' : '' ?>" href="<?= e(app_url()) ?>" aria-label="<?= e($nasBrandLabel) ?>">
            <?php if ($nasBrand === 'qdi'): ?>
                <?php
                $qdiSize = 'header';
                $qdiInverse = false;
                require __DIR__ . '/qdi-lockup.php';
                ?>
            <?php else: ?>
                <?php
                $brandSize = 'header';
                require __DIR__ . '/brand-lockup.php';
                ?>
            <?php endif; ?>
        </a>
    </div>
</header>
