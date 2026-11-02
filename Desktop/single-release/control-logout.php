<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/control-auth.php';

sw_control_logout();
header('Location: ' . app_url('control-login'));
exit;
