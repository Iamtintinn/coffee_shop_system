<?php
/**
 * Placeholder generator for missing modules
 * Run: /Applications/XAMPP/xamppfiles/bin/php scripts/generate_placeholders.php
 */
$base = __DIR__ . '/..';

$placeholder = <<<'PHP'
<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$page_title = 'TITLE';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>
<div class="page-header">
    <div class="page-header-left">
        <h1>TITLE</h1>
        <p>DESC</p>
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
PHP;

$redirect = <<<'PHP'
<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
redirect(BASE_URL . '/modules/PATH');
PHP;

$pages = [
    'modules/pos/pos.php'                            => ['Point of Sale', 'Process customer orders'],
    'modules/orders/orders.php'                      => ['Orders', 'Manage customer orders'],
    'modules/customers/customers.php'                => ['Customers', 'Manage customer information'],
    'modules/discounts/discounts.php'                => ['Discounts', 'Manage discounts and promotions'],
    'modules/discounts/promo_codes.php'              => ['Promo Codes', 'Manage promo codes'],
    'modules/reports/daily_sales.php'                => ['Daily Sales Report', 'View daily sales data'],
    'modules/reports/monthly_sales.php'              => ['Monthly Sales Report', 'View monthly sales data'],
    'modules/reports/inventory_report.php'           => ['Inventory Report', 'View inventory reports'],
    'modules/reports/export.php'                     => ['Export Reports', 'Export data reports'],
    'modules/users/users.php'                        => ['Users', 'Manage system users'],
    'modules/users/add_user.php'                     => ['Add User', 'Create a new user'],
    'modules/users/edit_user.php'                    => ['Edit User', 'Edit user information'],
    'modules/users/roles.php'                        => ['Roles & Permissions', 'Manage user roles'],
    'modules/settings/settings.php'                  => ['Settings', 'System configuration'],
    'modules/settings/backup.php'                    => ['Backup & Restore', 'Database backup tools'],
    'modules/settings/activity_logs.php'             => ['Activity Logs', 'View system activity'],
    'modules/settings/profile.php'                   => ['My Profile', 'Edit your profile'],
    'modules/orders/pending.php'                     => ['Pending Orders', 'View pending orders'],
    'modules/orders/preparing.php'                   => ['Preparing Orders', 'Orders being prepared'],
    'modules/orders/ready.php'                       => ['Ready Orders', 'Orders ready for pickup'],
    'modules/orders/completed.php'                   => ['Completed Orders', 'Completed orders'],
    'modules/customers/add_customer.php'             => ['Add Customer', 'Register a new customer'],
    'modules/customers/customer_history.php'         => ['Customer History', 'View customer purchase history'],
    'modules/products/product_details.php'           => ['Product Details', 'View product information'],
    'modules/authentication/forgot_password.php'     => ['Forgot Password', 'Reset your password'],
];

$redirects = [
    'modules/discounts/promo_codes.php'              => 'discounts/discounts.php',
    'modules/users/add_user.php'                     => 'users/users.php',
    'modules/users/edit_user.php'                    => 'users/users.php',
    'modules/users/roles.php'                        => 'users/users.php',
    'modules/orders/pending.php'                     => 'orders/orders.php',
    'modules/orders/preparing.php'                   => 'orders/orders.php',
    'modules/orders/ready.php'                       => 'orders/orders.php',
    'modules/orders/completed.php'                   => 'orders/orders.php',
    'modules/customers/add_customer.php'             => 'customers/customers.php',
    'modules/customers/customer_history.php'         => 'customers/customers.php',
    'modules/products/product_details.php'           => 'products/products.php',
    'modules/authentication/forgot_password.php'     => 'authentication/login.php',
];

foreach ($pages as $file => $info) {
    $path = $base . '/' . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    if (isset($redirects[$file])) {
        $content = str_replace('PATH', $redirects[$file], $redirect);
    } else {
        $content = str_replace(['TITLE', 'DESC'], $info, $placeholder);
    }
    file_put_contents($path, $content);
    echo "Created: $file\n";
}

echo "\nDone! " . count($pages) . " files created.";
