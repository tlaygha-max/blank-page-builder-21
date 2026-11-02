<?php

declare(strict_types=1);

$qdiSize = $qdiSize ?? 'header';
$qdiInverse = $qdiInverse ?? false;
$qdiSrc = $qdiInverse ? 'img/qdi-white.png' : 'img/qdi.png';
?>
<img
    class="qdi-lockup qdi-lockup--<?= e($qdiSize) ?>"
    src="<?= e(asset_url($qdiSrc)) ?>"
    alt="هوية قطر الرقمية"
    width="615"
    height="240"
>
