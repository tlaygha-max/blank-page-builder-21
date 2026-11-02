<?php

declare(strict_types=1);

$service = $service ?? [];
$title = (string) ($service['title'] ?? '');
?>
<nav class="ServiceBreadcrumbs" data-component="ServiceBreadcrumbs" aria-label="مسار الصفحة">
    <ol class="ServiceBreadcrumbs__list">
        <li><a class="ServiceBreadcrumbs__home" href="<?= e(app_url('portal')) ?>">الخدمات</a></li>
        <li class="ServiceBreadcrumbs__current" aria-current="page"><span class="ServiceBreadcrumbs__sep" aria-hidden="true">/</span><?= e($title) ?></li>
    </ol>
</nav>
