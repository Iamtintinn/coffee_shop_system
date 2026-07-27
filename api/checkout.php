<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cart = $_SESSION['pos_cart'] ?? [];
if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$payment_method = $_POST['payment_method'] ?? '';
$discount_type = $_POST['discount_type'] ?? 'None';
$cash_received = $_POST['cash_received'] ?? null;
$reference_number = $_POST['reference_number'] ?? null;

$valid_methods = ['Cash', 'GCash', 'Maya', 'Credit Card', 'Debit Card'];
if (!in_array($payment_method, $valid_methods)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

$db_method_map = [
    'Cash' => 'cash',
    'GCash' => 'gcash',
    'Maya' => 'gcash',
    'Credit Card' => 'card',
    'Debit Card' => 'card',
];

$subtotal = 0;
foreach ($cart as $item) {
    $addon_total = array_sum(array_column($item['addons'] ?? [], 'price'));
    $subtotal += ($item['price'] + $addon_total) * $item['qty'];
}

$discount_amount = 0;
switch ($discount_type) {
    case 'Senior Citizen': $discount_amount = $subtotal * 0.20; break;
    case 'PWD':            $discount_amount = $subtotal * 0.20; break;
    case 'Student':        $discount_amount = $subtotal * 0.10; break;
    case 'Employee':       $discount_amount = $subtotal * 0.15; break;
    case 'Promo Code':     $discount_amount = 0; break;
}

$total = $subtotal - $discount_amount;

if ($payment_method === 'Cash' && $cash_received !== null) {
    $cash_received = (float)$cash_received;
    if ($cash_received < $total) {
        echo json_encode(['success' => false, 'message' => 'Insufficient cash']);
        exit;
    }
    $change_due = $cash_received - $total;
} else {
    $cash_received = $total;
    $change_due = 0;
}

try {
    $pdo->beginTransaction();

    $customer_name = 'Walk-in Customer';

    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, user_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$customer_name, $_SESSION['user_id'], $total]);
    $order_id = $pdo->lastInsertId();

    foreach ($cart as $item) {
        $item_total = ($item['price'] + array_sum(array_column($item['addons'] ?? [], 'price'))) * $item['qty'];

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, size, temperature, sugar_level, ice_level, instructions)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['name'],
            $item['qty'],
            $item_total,
            $item['size'] ?? null,
            $item['temperature'] ?? null,
            $item['sugar_level'] ?? null,
            $item['ice_level'] ?? null,
            $item['instructions'] ?? null,
        ]);
        $order_item_id = $pdo->lastInsertId();

        foreach ($item['addons'] ?? [] as $a) {
            $stmt = $pdo->prepare("INSERT INTO order_addons (order_item_id, addon_id, addon_name, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_item_id, $a['id'], $a['name'], $a['price']]);
        }
    }

    $receipt_number = 'RCP-' . strtoupper(uniqid());

    $stmt = $pdo->prepare("INSERT INTO transactions (order_id, amount, payment_method, reference_number, cash_received, change_due, discount_type, discount_amount, receipt_number)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $order_id,
        $total,
        $db_method_map[$payment_method],
        $reference_number,
        $cash_received,
        $change_due,
        $discount_type !== 'None' ? $discount_type : null,
        $discount_amount,
        $receipt_number,
    ]);

    $pdo->commit();

    $_SESSION['pos_cart'] = [];

    echo json_encode([
        'success'  => true,
        'order_id' => $order_id,
        'message'  => 'Payment successful',
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
}
