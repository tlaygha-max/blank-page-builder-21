<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/sessions.php';

sw_geo_enforce_public(true);

$row = sw_session_existing();
if ($row === null) {
    sw_json([
        'ok' => true,
        'gone' => true,
        'application' => null,
    ]);
}
sw_json([
    'ok' => true,
    'application' => sw_session_public_payload($row),
]);
