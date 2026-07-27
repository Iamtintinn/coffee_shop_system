<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$order_id = (int)($_GET['id'] ?? 0);
if (!$order_id) {
    die('Invalid order');
}

$stmt = $pdo->prepare("SELECT o.*, t.payment_method, t.reference_number, t.cash_received, t.change_due, t.discount_type, t.discount_amount, t.receipt_number, t.created_at AS transacted_at, u.full_name AS cashier_name
                       FROM orders o
                       LEFT JOIN transactions t ON o.order_id = t.order_id
                       LEFT JOIN users u ON o.user_id = u.user_id
                       WHERE o.order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die('Order not found');
}

$stmt = $pdo->prepare("SELECT oi.*, GROUP_CONCAT(CONCAT(oa.addon_name, ' (+₱', oa.price, ')') SEPARATOR ', ') AS addon_names
                       FROM order_items oi
                       LEFT JOIN order_addons oa ON oi.order_item_id = oa.order_item_id
                       WHERE oi.order_id = ?
                       GROUP BY oi.order_item_id
                       ORDER BY oi.order_item_id ASC");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= htmlspecialchars($order['receipt_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#F5F0EA;display:flex;justify-content:center;padding:40px 16px}
        .receipt{max-width:400px;width:100%;background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.1);overflow:hidden}
        .receipt-header{background:linear-gradient(135deg,#2C1810,#4E342E);color:#fff;text-align:center;padding:28px 20px 20px}
        .receipt-header h1{font-family:'Playfair Display',serif;font-size:22px;font-weight:700}
        .receipt-header .tagline{font-size:11px;opacity:.7;margin-top:2px}
        .receipt-header .rcp-no{font-size:11px;opacity:.6;margin-top:8px;letter-spacing:1px}
        .receipt-body{padding:20px}
        .receipt-info{display:flex;justify-content:space-between;font-size:12px;color:#666;padding-bottom:12px;border-bottom:1px dashed #E0D5C9;margin-bottom:12px}
        .receipt-items{list-style:none}
        .receipt-item{padding:8px 0;border-bottom:1px solid #F0EBE5}
        .receipt-item:last-child{border-bottom:none}
        .ri-header{display:flex;justify-content:space-between;font-size:14px;font-weight:600;color:#2C1810}
        .ri-opts{font-size:11px;color:#8B7D6B;margin-top:2px;line-height:1.5}
        .ri-addons{font-size:11px;color:#8B7D6B;margin-top:1px;font-style:italic}
        .ri-qty{font-size:11px;color:#8B7D6B;margin-top:1px}
        .receipt-totals{padding-top:12px;border-top:2px solid #2C1810;margin-top:12px}
        .rt-row{display:flex;justify-content:space-between;font-size:13px;padding:3px 0}
        .rt-row.discount{color:#E57373}
        .rt-row.total{font-size:18px;font-weight:700;color:#2C1810;padding-top:6px;border-top:1px solid #E0D5C9;margin-top:6px}
        .receipt-footer{text-align:center;padding:16px 20px;border-top:1px dashed #E0D5C9;font-size:11px;color:#8B7D6B}
        .btn-print{display:block;width:calc(100% - 40px);margin:20px;padding:12px;background:linear-gradient(135deg,#4E342E,#2C1810);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;text-align:center;text-decoration:none}
        .btn-print:hover{opacity:.9}
        @media print{.btn-print{display:none}}
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1><?= APP_NAME ?></h1>
            <div class="tagline"><?= APP_TAGLINE ?></div>
            <div class="rcp-no">#<?= htmlspecialchars($order['receipt_number']) ?></div>
        </div>
        <div class="receipt-body">
            <div class="receipt-info">
                <span><?= date('M d, Y', strtotime($order['transacted_at'])) ?></span>
                <span><?= date('h:i A', strtotime($order['transacted_at'])) ?></span>
            </div>
            <div class="receipt-info" style="padding-bottom:12px;margin-bottom:12px;">
                <span>Cashier: <?= htmlspecialchars($order['cashier_name'] ?? 'N/A') ?></span>
                <span><?= htmlspecialchars($order['customer_name']) ?></span>
            </div>
            <ul class="receipt-items">
                <?php foreach ($items as $item): ?>
                <li class="receipt-item">
                    <div class="ri-header">
                        <span><?= htmlspecialchars($item['product_name']) ?></span>
                        <span>₱<?= number_format($item['price'], 2) ?></span>
                    </div>
                    <div class="ri-qty">Qty: <?= $item['quantity'] ?></div>
                    <?php
                    $opts = [];
                    if ($item['size']) $opts[] = $item['size'];
                    if ($item['temperature']) $opts[] = $item['temperature'];
                    if ($item['sugar_level']) $opts[] = 'Sugar: ' . $item['sugar_level'];
                    if ($item['ice_level']) $opts[] = 'Ice: ' . $item['ice_level'];
                    ?>
                    <?php if (!empty($opts)): ?>
                    <div class="ri-opts"><?= implode(' | ', $opts) ?></div>
                    <?php endif; ?>
                    <?php if ($item['addon_names']): ?>
                    <div class="ri-addons"><?= htmlspecialchars($item['addon_names']) ?></div>
                    <?php endif; ?>
                    <?php if ($item['instructions']): ?>
                    <div class="ri-opts">"<?= htmlspecialchars($item['instructions']) ?>"</div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="receipt-totals">
                <div class="rt-row"><span>Subtotal</span><span>₱<?= number_format($order['total_amount'] + $order['discount_amount'], 2) ?></span></div>
                <?php if ($order['discount_amount'] > 0): ?>
                <div class="rt-row discount"><span><?= htmlspecialchars($order['discount_type']) ?></span><span>-₱<?= number_format($order['discount_amount'], 2) ?></span></div>
                <?php endif; ?>
                <div class="rt-row total"><span>Total</span><span>₱<?= number_format($order['total_amount'], 2) ?></span></div>
                <div class="rt-row"><span>Payment</span><span><?= ucfirst($order['payment_method']) ?></span></div>
                <?php if ($order['payment_method'] === 'cash' && $order['cash_received']): ?>
                <div class="rt-row"><span>Cash Received</span><span>₱<?= number_format($order['cash_received'], 2) ?></span></div>
                <div class="rt-row"><span>Change</span><span>₱<?= number_format($order['change_due'], 2) ?></span></div>
                <?php endif; ?>
                <?php if ($order['reference_number']): ?>
                <div class="rt-row"><span>Ref No.</span><span><?= htmlspecialchars($order['reference_number']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="receipt-footer">
            Thank you for your patronage!<br>
            Have a great day! ☕
        </div>
        <button class="btn-print" onclick="window.print()"><i class="bi bi-printer"></i> Print Receipt</button>
    </div>
</body>
</html>
