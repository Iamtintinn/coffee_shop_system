<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$page_title = $page_title ?? 'Dashboard';
$body_class = 'dashboard-page';
require_once __DIR__ . '/header.php';
?>
<div class="dashboard-layout">
    <?php require_once __DIR__ . '/navbar.php'; ?>
    <div class="dashboard-body">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <main class="dashboard-content">
