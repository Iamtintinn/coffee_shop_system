<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) {
    echo 'No order specified.';
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name AS cashier_name,
           t.receipt_number, t.payment_method, t.cash_received, t.change_due,
           t.discount_type, t.discount_amount, t.amount
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN transactions t ON o.order_id = t.order_id
    WHERE o.order_id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    echo 'Order not found.';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

foreach ($items as &$item) {
    $stmt = $pdo->prepare("SELECT * FROM order_addons WHERE order_item_id = ?");
    $stmt->execute([$item['order_item_id']]);
    $item['addons'] = $stmt->fetchAll();
}
unset($item);

$queueNum = str_pad($orderId, 3, '0', STR_PAD_LEFT);
$shopName = 'Brew & Bean';
$shopAddress = '123 Coffee Lane, Groundsville';
$shopContact = '(02) 1234-5678';
$grandTotal = number_format((float)$order['total_amount'], 2);
$subtotal = number_format((float)$order['total_amount'], 2);
$discountAmount = (float)($order['discount_amount'] ?? 0);
$discountLabel = $order['discount_type'] ? ucfirst($order['discount_type']) : '';
$vatExclusive = (float)$order['total_amount'] / 1.12;
$vat = (float)$order['total_amount'] - $vatExclusive;
$cashReceived = $order['cash_received'] ? number_format((float)$order['cash_received'], 2) : '—';
$changeDue = $order['change_due'] ? number_format((float)$order['change_due'], 2) : '—';
$pmtMethod = $order['payment_method'] ? ucfirst($order['payment_method']) : 'Cash';
$receiptNum = $order['receipt_number'] ?? '—';
$dt = date('M d, Y h:i A', strtotime($order['created_at'] ?? 'now'));
$customer = $order['customer_name'] ?: 'Walk-in';
$cashier = $order['cashier_name'] ?: 'Unknown';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt #<?php echo $receiptNum; ?></title>
<style>
@page {
    size: 80mm auto;
    margin: 0;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Courier New', 'Courier', monospace;
    font-size: 12px;
    color: #000;
    background: #fff;
    width: 72mm;
    margin: 0 auto;
    padding: 8mm 4mm;
    line-height: 1.4;
}
.header {
    text-align: center;
    margin-bottom: 8px;
}
.logo {
    font-size: 32px;
    margin-bottom: 2px;
}
.shop-name {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.shop-info {
    font-size: 10px;
    color: #555;
}
.divider {
    border: none;
    border-top: 1px dashed #333;
    margin: 8px 0;
}
.queue-section {
    text-align: center;
    margin: 10px 0;
}
.queue-label {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: #333;
}
.queue-number {
    font-size: 52px;
    font-weight: 900;
    letter-spacing: 4px;
    color: #000;
    margin: 4px 0;
}
.info-row {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    padding: 1px 0;
}
.info-label {
    color: #555;
    min-width: 70px;
}
.info-value {
    text-align: right;
    font-weight: 600;
}
.items-header {
    font-weight: 700;
    font-size: 11px;
    border-bottom: 1px solid #333;
    padding-bottom: 4px;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.item-row {
    padding: 3px 0;
    border-bottom: 1px dotted #ccc;
}
.item-name {
    font-weight: 600;
    font-size: 11px;
}
.item-qty-price {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    color: #555;
    margin-left: 4px;
}
.item-cust {
    font-size: 9px;
    color: #777;
    margin-left: 12px;
    padding: 1px 0;
}
.item-addon {
    font-size: 9px;
    color: #888;
    margin-left: 12px;
}
.totals {
    margin-top: 8px;
}
.total-row {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    padding: 2px 0;
}
.total-row.final {
    font-size: 16px;
    font-weight: 900;
    border-top: 2px solid #000;
    border-bottom: 2px solid #000;
    padding: 6px 0;
    margin: 4px 0;
}
.footer {
    text-align: center;
    margin-top: 12px;
    font-size: 10px;
    color: #555;
    line-height: 1.6;
}
.footer .thank-you {
    font-size: 13px;
    font-weight: 700;
    color: #000;
    margin-bottom: 4px;
}
@media print {
    body { margin: 0; padding: 6mm 3mm; }
}
</style>
</head>
<body onload="window.print()">

<div class="header">
    <div class="logo">☕</div>
    <div class="shop-name"><?php echo htmlspecialchars($shopName); ?></div>
    <div class="shop-info"><?php echo htmlspecialchars($shopAddress); ?></div>
    <div class="shop-info"><?php echo htmlspecialchars($shopContact); ?></div>
</div>

<hr class="divider">

<div class="queue-section">
    <div class="queue-label">Queue No.</div>
    <div class="queue-number"><?php echo $queueNum; ?></div>
</div>

<hr class="divider">

<div class="info-row">
    <span class="info-label">Receipt No:</span>
    <span class="info-value"><?php echo htmlspecialchars($receiptNum); ?></span>
</div>
<div class="info-row">
    <span class="info-label">Date:</span>
    <span class="info-value"><?php echo htmlspecialchars($dt); ?></span>
</div>
<div class="info-row">
    <span class="info-label">Cashier:</span>
    <span class="info-value"><?php echo htmlspecialchars($cashier); ?></span>
</div>
<div class="info-row">
    <span class="info-label">Customer:</span>
    <span class="info-value"><?php echo htmlspecialchars($customer); ?></span>
</div>

<hr class="divider">

<div class="items-header">Ordered Items</div>

<?php foreach ($items as $item):
    $qty = (int)($item['quantity'] ?? 1);
    $price = (float)($item['price'] ?? 0);
    $itemTotal = $qty * $price;
    $custParts = [];
    if ($item['size']) $custParts[] = $item['size'];
    if ($item['temperature']) $custParts[] = $item['temperature'];
    if ($item['sugar_level'] && $item['sugar_level'] !== 'regular') $custParts[] = 'Sugar: ' . $item['sugar_level'];
    if ($item['ice_level'] && $item['ice_level'] !== 'regular') $custParts[] = 'Ice: ' . $item['ice_level'];
?>
<div class="item-row">
    <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
    <div class="item-qty-price">
        <span>Qty: <?php echo $qty; ?></span>
        <span>₱<?php echo number_format($itemTotal, 2); ?></span>
    </div>
    <?php foreach ($custParts as $cp): ?>
    <div class="item-cust"><?php echo htmlspecialchars($cp); ?></div>
    <?php endforeach; ?>
    <?php if (!empty($item['instructions'])): ?>
    <div class="item-cust">Note: <?php echo htmlspecialchars($item['instructions']); ?></div>
    <?php endif; ?>
    <?php if (!empty($item['addons'])): ?>
        <?php foreach ($item['addons'] as $a): ?>
        <div class="item-addon">+ <?php echo htmlspecialchars($a['addon_name']); ?> (₱<?php echo number_format((float)$a['price'], 2); ?>)</div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="totals">
    <div class="total-row">
        <span>Subtotal</span>
        <span>₱<?php echo $grandTotal; ?></span>
    </div>

    <?php if ($discountAmount > 0): ?>
    <div class="total-row">
        <span>Discount (<?php echo htmlspecialchars($discountLabel); ?>)</span>
        <span>-₱<?php echo number_format($discountAmount, 2); ?></span>
    </div>
    <?php endif; ?>

    <div class="total-row">
        <span>VAT (12% incl.)</span>
        <span>₱<?php echo number_format($vat, 2); ?></span>
    </div>

    <div class="total-row final">
        <span>Grand Total</span>
        <span>₱<?php echo $grandTotal; ?></span>
    </div>

    <hr class="divider">

    <div class="info-row">
        <span class="info-label">Payment:</span>
        <span class="info-value"><?php echo htmlspecialchars($pmtMethod); ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Cash:</span>
        <span class="info-value">₱<?php echo $cashReceived; ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Change:</span>
        <span class="info-value">₱<?php echo $changeDue; ?></span>
    </div>
</div>

<hr class="divider">

<div class="footer">
    <div class="thank-you">Thank you for your order!</div>
    <div>Please wait until your queue number is called.</div>
    <div style="margin-top:6px;font-size:9px;color:#aaa;">#<?php echo htmlspecialchars($receiptNum); ?></div>
</div>

</body>
</html>
