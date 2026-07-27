<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status = 'completed' AND DATE(created_at) = :today");
$stmt->execute(['today' => $today]);
$total_sales_today = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total FROM orders WHERE status = 'completed' AND DATE(created_at) = :yesterday");
$stmt->execute(['yesterday' => $yesterday]);
$total_sales_yesterday = $stmt->fetch()['total'];
$sales_trend = $total_sales_yesterday > 0 ? round((($total_sales_today - $total_sales_yesterday) / $total_sales_yesterday) * 100, 1) : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM orders WHERE DATE(created_at) = :today");
$stmt->execute(['today' => $today]);
$orders_today = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM orders WHERE DATE(created_at) = :yesterday");
$stmt->execute(['yesterday' => $yesterday]);
$orders_yesterday = $stmt->fetch()['count'];
$orders_trend = $orders_yesterday > 0 ? round((($orders_today - $orders_yesterday) / $orders_yesterday) * 100, 1) : 0;

$stmt = $pdo->prepare("
    SELECT oi.product_name, SUM(oi.quantity) AS qty
    FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id
    WHERE o.status = 'completed' AND DATE(o.created_at) = :today
    GROUP BY oi.product_name
    ORDER BY qty DESC
    LIMIT 5
");
$stmt->execute(['today' => $today]);
$best_sellers = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT ingredient_id, name, stock_quantity, unit, min_stock_level FROM ingredients WHERE stock_quantity <= min_stock_level ORDER BY stock_quantity ASC LIMIT 5");
$stmt->execute();
$low_stock = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM orders WHERE status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetch()['count'];

$stmt = $pdo->prepare("
    SELECT t.transaction_id, t.amount, t.payment_method, t.created_at, o.order_id, o.customer_name
    FROM transactions t
    JOIN orders o ON o.order_id = t.order_id
    ORDER BY t.created_at DESC
    LIMIT 8
");
$stmt->execute();
$recent_transactions = $stmt->fetchAll();

$page_title = 'Dashboard';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Dashboard</h1>
        <p>Overview of your coffee shop performance for today</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card stat-sales">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Total Sales Today</span>
            <span class="stat-value">₱<?= number_format($total_sales_today, 2) ?></span>
            <?php if ($sales_trend != 0): ?>
                <span class="stat-trend <?= $sales_trend >= 0 ? 'up' : 'down' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <?= $sales_trend >= 0 ? '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>' : '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>' ?>
                    </svg>
                    <?= abs($sales_trend) ?>% vs yesterday
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card stat-orders">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Orders Today</span>
            <span class="stat-value"><?= $orders_today ?></span>
            <?php if ($orders_trend != 0): ?>
                <span class="stat-trend <?= $orders_trend >= 0 ? 'up' : 'down' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <?= $orders_trend >= 0 ? '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>' : '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/>' ?>
                    </svg>
                    <?= abs($orders_trend) ?>% vs yesterday
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card stat-pending">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Pending Orders</span>
            <span class="stat-value"><?= $pending_orders ?></span>
            <span class="stat-trend up" style="visibility: hidden;">-</span>
        </div>
    </div>

    <div class="stat-card stat-stock">
        <div class="stat-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        </div>
        <div class="stat-info">
            <span class="stat-label">Low Stock Items</span>
            <span class="stat-value"><?= count($low_stock) ?></span>
            <span class="stat-trend down" style="visibility: hidden;">-</span>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <h2>Best Selling Products Today</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($best_sellers)): ?>
                <p class="empty-state">No completed sales yet today</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Product</th><th>Sold</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($best_sellers as $i => $item): ?>
                            <tr>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:8px;">
                                        <span style="width:22px;height:22px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;background:<?= $i === 0 ? 'linear-gradient(135deg, var(--coffee-gold), var(--coffee-latte))' : 'var(--border-light)' ?>;color:<?= $i === 0 ? '#fff' : 'var(--text-muted)' ?>;"><?= $i + 1 ?></span>
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </span>
                                </td>
                                <td><strong><?= (int)$item['qty'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </span>
                <h2>Low Stock Ingredients</h2>
            </div>
            <a href="<?= BASE_URL ?>/modules/inventory/inventory.php" class="card-header-action">Manage</a>
        </div>
        <div class="card-body">
            <?php if (empty($low_stock)): ?>
                <p class="empty-state">All ingredients are well-stocked</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Ingredient</th><th>Stock</th><th>Min</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($low_stock as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td class="text-danger"><?= (float)$item['stock_quantity'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                                <td><?= (float)$item['min_stock_level'] ?> <?= htmlspecialchars($item['unit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card card-full">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </span>
                <h2>Recent Transactions</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($recent_transactions)): ?>
                <p class="empty-state">No transactions yet</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_transactions as $t): ?>
                            <tr>
                                <td><strong>#<?= $t['order_id'] ?></strong></td>
                                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                                <td><strong>₱<?= number_format($t['amount'], 2) ?></strong></td>
                                <td><span class="badge badge-<?= $t['payment_method'] ?>"><?= ucfirst($t['payment_method']) ?></span></td>
                                <td class="text-muted"><?= date('h:i A', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
