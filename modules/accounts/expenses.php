<?php
// Redirect to accounts
require_once dirname(dirname(__DIR__)).'/config/app.php';
requireLogin();
redirect(APP_URL.'/modules/accounts/index.php');
?>
