<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
clf_logout();
header('Location: /admin/');
