<?php
require_once dirname(__DIR__) . '/config/app.php';
if (isLoggedIn()) logActivity('logout', 'auth', 'User logged out');
session_destroy();
redirect(APP_URL . '/auth/login.php');
?>
