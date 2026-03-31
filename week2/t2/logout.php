<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

unset($_SESSION[T2_SESSION_USER], $_SESSION[T2_SESSION_CSRF]);

header('Location: login.php');
exit;
