<?php
require_once __DIR__ . '/config/app.php';
if (isLoggedIn()) redirect(APP_URL . '/dashboard.php');
redirect(APP_URL . '/auth/login.php');
?>
