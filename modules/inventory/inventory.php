<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$page_title = 'Inventory';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>
<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
