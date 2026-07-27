<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
$page_title = 'POS';
$body_class = 'pos-page';
require_once __DIR__ . '/../../includes/header.php';
?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
