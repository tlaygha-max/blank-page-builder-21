<?php

declare(strict_types=1);

function service_icon_glyph(string $name): string
{
    $s = 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';

    $icons = [
        'certificates' => '<rect x="5.6" y="3.2" width="12.2" height="17.4" rx="1.5" ' . $s . '/><path d="M8.6 8h6.2M8.6 11.2h6.2M8.6 14.4h3.4" ' . $s . '/><circle cx="16.4" cy="17.4" r="3.35" fill="#F5FBFC" stroke="currentColor" stroke-width="1.55"/><path d="M15.1 17.4h2.6M16.4 16.1v2.6" stroke="currentColor" stroke-width="1.45" stroke-linecap="round"/>',
        'trade-name' => '<path d="M3.8 8.1h10.4l6.4 3.9-6.4 3.9H3.8V8.1Z" ' . $s . '/><circle cx="6.8" cy="12" r="1.2" fill="currentColor"/><path d="M9.4 12h5.2" ' . $s . '/>',
        'labor-recruitment' => '<circle cx="8.1" cy="8.1" r="2.35" ' . $s . '/><circle cx="15.9" cy="8.1" r="2.35" ' . $s . '/><circle cx="12" cy="7.2" r="2.6" ' . $s . '/><path d="M4.3 18.9c.5-3.2 2.1-4.8 3.9-4.8 1.2 0 2.2.7 2.9 1.9M19.7 18.9c-.5-3.2-2.1-4.8-3.9-4.8-1.2 0-2.2.7-2.9 1.9M8.5 19.3c.55-3.5 2.35-5.3 3.5-5.3s2.95 1.8 3.5 5.3" ' . $s . '/>',
        'ad-renewal' => '<rect x="5.8" y="3.6" width="12.4" height="12.6" rx="1.4" ' . $s . '/><path d="M8.4 7.4h7M8.4 10.4h4.6" ' . $s . '/><path d="M7 18.8a5.2 5.2 0 0 0 8.6.2" ' . $s . '/><path d="M16.8 16.6v2.9h2.8" ' . $s . '/>',
        'ad-issue' => '<rect x="5.2" y="3.4" width="12.2" height="17.2" rx="1.4" ' . $s . '/><path d="M8.2 8.2h5.2M8.2 11.4h4.2" ' . $s . '/><path d="M16.6 14.6v4.4M14.4 16.8h4.4" ' . $s . '/>',
        'beneficial-owners' => '<path d="M4.4 19.6V10.4L12 4.8l7.6 5.6v9.2H4.4Z" ' . $s . '/><path d="M9.6 19.6v-5.4h4.8v5.4" ' . $s . '/><circle cx="12" cy="11.2" r="1.7" ' . $s . '/>',
        'commercial-license' => '<rect x="4.8" y="3.4" width="10.8" height="17.2" rx="1.4" ' . $s . '/><path d="M7.4 7.8h5.6M7.4 11h4.4" ' . $s . '/><path d="M14.2 13.2 19.4 10.6v8.6l-5.2-2.5v-3.5Z" ' . $s . '/>',
        'comprehensive-amendment' => '<rect x="4.8" y="3.8" width="11.4" height="16.4" rx="1.4" ' . $s . '/><path d="M7.4 8h5.6M7.4 11.2h4.2" ' . $s . '/><path d="M12.2 14.4 19.4 7.2l1.5 1.5-7.2 7.2-2.2.7.7-2.2Z" ' . $s . '/>',
        'company-closure' => '<path d="M12 3.6 19.4 7v5.6c0 4.4-3.2 7.4-7.4 8.8C7.8 20 4.6 17 4.6 12.6V7L12 3.6Z" ' . $s . '/><path d="M9.4 12.2 14.6 17.4M14.6 12.2 9.4 17.4" ' . $s . '/>',
        'ad-cancel' => '<rect x="5.4" y="3.4" width="13.2" height="17.2" rx="1.5" ' . $s . '/><path d="M9 10.2h6M10.2 10.2 10.8 16h2.4l.6-5.8M11.2 8.4h1.6c.6 0 1 .4 1 1v.8" ' . $s . '/>',
        'sub-license' => '<path d="M12 3.4 19.6 6.8v5.6c0 4.4-3.3 7.4-7.6 8.8-4.3-1.4-7.6-4.4-7.6-8.8V6.8L12 3.4Z" ' . $s . '/><path d="M12 8.6 16.2 10.6v3.2c0 2.4-1.8 4-4.2 4.8-2.4-.8-4.2-2.4-4.2-4.8v-3.2L12 8.6Z" ' . $s . '/>',
        'add-branch' => '<path d="M4.6 19.6V9L12 4.4l7.4 4.6v10.6H4.6Z" ' . $s . '/><path d="M9.8 19.6v-5.2h4.4v5.2" ' . $s . '/><path d="M16.8 8.2v4.4M14.6 10.4h4.4" ' . $s . '/>',
        'foreign-branches' => '<path d="M3.8 19.6V9.2L10.4 4.8l6.2 4.4v10.4H3.8Z" ' . $s . '/><path d="M8.4 19.6v-4.8h3.8v4.8" ' . $s . '/><circle cx="18.2" cy="7.4" r="3.6" ' . $s . '/><path d="M15.2 7.4h6M18.2 4.2c1.15 1.15 1.15 5.2 0 6.4M18.2 4.2c-1.15 1.15-1.15 5.2 0 6.4" ' . $s . '/>',
        'factory-complete' => '<path d="M3.6 19.6V11l4.6-3.4v3.2l4.2-3.4v12.2H3.6Z" ' . $s . '/><path d="M12.4 19.6V9.2h8v10.4" ' . $s . '/><path d="M14.4 12h1.6M17.2 12h1.6M14.4 15h1.6M17.2 15h1.6" ' . $s . '/><path d="M16.2 4.4 17.6 5.8 20.6 2.6" ' . $s . '/>',
        'comprehensive-renewal' => '<rect x="7.2" y="6" width="9.6" height="12" rx="1.3" ' . $s . '/><path d="M9.4 9.4h5.2M9.4 12.2h3.6" ' . $s . '/><path d="M5 8.2A8.4 8.4 0 0 1 19.6 10.2" ' . $s . '/><path d="M4.4 5.4v3.4h3.4M19 18.6v-3.4h-3.2M19 15.2A8.4 8.4 0 0 1 4.6 13.8" ' . $s . '/>',
        'factory-establish' => '<path d="M3.4 19.8V10.6l4.8-3.4v3.2l4.2-3.4v12.8H3.4Z" ' . $s . '/><path d="M12.4 19.8V8.6h8.2v11.2" ' . $s . '/><path d="M14.4 11.6h1.6M17.4 11.6h1.6M14.4 14.8h1.6M17.4 14.8h1.6" ' . $s . '/>',
        'establishment-complete' => '<path d="M12 3.4 19.6 6.8v5.6c0 4.4-3.3 7.4-7.6 8.8-4.3-1.4-7.6-4.4-7.6-8.8V6.8L12 3.4Z" ' . $s . '/><path d="M8.8 12.6 11.2 15l4.2-4.6" ' . $s . '/>',
        'business-advisor' => '<circle cx="12" cy="7" r="2.7" ' . $s . '/><path d="M6.2 19.4c.7-4.1 2.8-6.2 5.8-6.2s5.1 2.1 5.8 6.2" ' . $s . '/><path d="M17.8 4.2 19 5.8 21.4 3.2" ' . $s . '/>',
        'comprehensive-establishment' => '<path d="M12 3.4 19.6 6.8v5.6c0 4.4-3.3 7.4-7.6 8.8-4.3-1.4-7.6-4.4-7.6-8.8V6.8L12 3.4Z" ' . $s . '/><path d="M12 17.6V10.2M12 10.2 8.4 12.2 12 6.6l3.6 5.6L12 10.2Z" ' . $s . '/>',
    ];

    return $icons[$name] ?? $icons['certificates'];
}

function service_duration_icon(): string
{
    return '<svg class="ServiceSummaryCard__icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
        . '<circle cx="24" cy="26" r="14.5" fill="none" stroke="currentColor" stroke-width="2.2"/>'
        . '<path d="M18 8.2h12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path d="M24 8.2v4.2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path d="M24 26V18.5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>'
        . '<path d="M24 26l7.2 4.2" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>'
        . '<circle cx="24" cy="26" r="1.7" fill="currentColor"/>'
        . '</svg>';
}

function service_cost_icon(): string
{
    return '<svg class="ServiceSummaryCard__icon" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
        . '<circle cx="31.2" cy="16.8" r="8.4" fill="none" stroke="currentColor" stroke-width="2.1"/>'
        . '<path d="M31.2 12.2v9.2M28.2 14.6c.7-1.1 1.7-1.6 3-1.6 1.8 0 3 1 3 2.4s-1.3 2.3-3.4 2.6c-2.1.3-3.4 1.2-3.4 2.8 0 1.6 1.5 2.6 3.4 2.6 1.4 0 2.5-.5 3.2-1.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'
        . '<path d="M10.4 23.6c2.4-1.3 5.2.3 6.4 2.4 1.1 1.8 3.6 2 5.2.6 1.8-1.6 4.6-1.2 5.8.8 1 1.7 3.2 2.2 4.9 1.1" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>'
        . '<path d="M12.2 28.2c.4 5.4 1.8 9.4 6.8 11.6 4.4 1.9 10.2 1.4 14.4-1.2" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>'
        . '<path d="M10.8 24.8c-2.4 1.6-3.6 4.8-2.2 8.2 1.6 3.8 4.6 5.4 8.2 5.8" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>'
        . '</svg>';
}

function service_card_icon(string $name): string
{
    $glyph = service_icon_glyph($name);

    return '<span class="ServiceCard__hex" aria-hidden="true">'
        . '<svg class="ServiceCard__hexRing" viewBox="0 0 105 100" focusable="false">'
        . '<polygon class="ServiceCard__hexOuter" points="52.5,2.2 100.2,29.2 100.2,70.8 52.5,97.8 4.8,70.8 4.8,29.2"/>'
        . '<polygon class="ServiceCard__hexInner" points="52.5,8.4 93.6,32.1 93.6,67.9 52.5,91.6 11.4,67.9 11.4,32.1"/>'
        . '</svg>'
        . '<svg class="ServiceCard__glyph" viewBox="0 0 24 24" focusable="false">' . $glyph . '</svg>'
        . '</span>';
}
