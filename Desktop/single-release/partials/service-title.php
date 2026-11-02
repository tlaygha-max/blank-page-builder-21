<?php

declare(strict_types=1);

$service = $service ?? [];
$title = (string) ($service['title'] ?? '');
?>
<div class="ServiceTitle" data-component="ServiceTitle">
    <span class="ServiceTitle__accent" aria-hidden="true"></span>
    <h2 class="ServiceTitle__text"><?= e($title) ?></h2>
</div>
