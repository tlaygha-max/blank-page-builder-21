<?php

declare(strict_types=1);

$brandSize = $brandSize ?? 'md';
$brandInverse = !empty($brandInverse);
$brandSrc = $brandInverse ? 'img/single-window-white.png' : 'img/single-window.png';
?>
<img
    class="sw-lockup sw-lockup--<?= e($brandSize) ?>"
    src="<?= e(asset_url($brandSrc)) ?>"
    alt="النافذة الواحدة"
    width="218"
    height="72"
>
