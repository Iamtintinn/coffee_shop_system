<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/modules/dashboard/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/modules/authentication/login.php');
}
exit;
