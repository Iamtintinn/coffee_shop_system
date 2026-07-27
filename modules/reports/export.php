<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$page_title = 'Export Reports';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1>Export Reports</h1>
        <p>Export data reports</p>
        <div class="page-header-accent"></div>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="empty-state" style="padding:60px 0;">
            <span style="font-size:48px;display:block;margin-bottom:16px;">☕</span>
            <h3 style="font-size:18px;color:var(--coffee-dark);margin-bottom:8px;">Coming Soon</h3>
            <p style="color:var(--text-muted);">This module is under development</p>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>