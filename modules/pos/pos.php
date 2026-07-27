<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'save_order') {
    header('Content-Type: application/json');
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) throw new Exception('Invalid request data');

        $pdo->beginTransaction();

        $totalAmount = (float)($data['grand_total'] ?? 0);
        $customerName = !empty($data['customer_name']) ? $data['customer_name'] : 'Walk-in Customer';
        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, user_id, total_amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$customerName, $userId, $totalAmount]);
        $orderId = $pdo->lastInsertId();

        $addonNameMap = [
            'addon_extra_shot'    => ['Extra Espresso Shot', 20],
            'addon_vanilla'       => ['Vanilla Syrup', 15],
            'addon_caramel'       => ['Caramel Syrup', 15],
            'addon_whipped_cream' => ['Whipped Cream', 10],
            'addon_soy_milk'      => ['Soy Milk', 25],
            'addon_almond_milk'   => ['Almond Milk', 25]
        ];

        foreach ($data['items'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price, size, temperature, sugar_level, ice_level, instructions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $orderId,
                (int)($item['id'] ?? 0),
                $item['name'] ?? '',
                (int)($item['qty'] ?? 1),
                (float)($item['unit_price'] ?? 0),
                $item['size'] ?? null,
                $item['temp'] ?? null,
                $item['sugar'] ?? null,
                $item['ice'] ?? null,
                $item['instructions'] ?? null
            ]);
            $orderItemId = $pdo->lastInsertId();

            if (!empty($item['addon_ids'])) {
                foreach ($item['addon_ids'] as $addonId) {
                    if (isset($addonNameMap[$addonId])) {
                        $stmt = $pdo->prepare("INSERT INTO order_addons (order_item_id, addon_name, price) VALUES (?, ?, ?)");
                        $stmt->execute([$orderItemId, $addonNameMap[$addonId][0], $addonNameMap[$addonId][1]]);
                    }
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (order_id, amount, payment_method, reference_number, cash_received, change_due, discount_type, discount_amount, receipt_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $orderId,
            $totalAmount,
            $data['payment_method'] ?? 'cash',
            $data['reference_number'] ?? null,
            $data['cash_received'] ?? null,
            $data['change_due'] ?? null,
            $data['discount_type'] ?? null,
            (float)($data['discount_amount'] ?? 0),
            $data['receipt_number'] ?? null
        ]);

        $pdo->commit();
        echo json_encode(['success' => true, 'order_id' => $orderId, 'receipt_number' => $data['receipt_number'] ?? '']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$page_title = 'POS';
$body_class = 'pos-page';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.name");
$products = $stmt->fetchAll();

$cashier_name = $_SESSION['full_name'] ?? 'Cashier';

require_once __DIR__ . '/../../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
    --pos-sidebar-bg: #1a0f0a;
    --pos-sidebar-secondary: #2c1810;
    --pos-menu-bg: #f5f0eb;
    --pos-cart-bg: #fffdfb;
    --pos-gold: #c8a96e;
    --pos-gold-light: #e8d5a8;
    --pos-text-light: #b8a99a;
}

body.pos-page {
    background: var(--pos-sidebar-bg) !important;
    overflow: hidden;
    height: 100vh;
}

.pos-wrapper {
    display: flex;
    height: 100vh;
    overflow: hidden;
}

.pos-sidebar {
    width: 20%;
    min-width: 240px;
    background: var(--pos-sidebar-bg);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    border-right: 1px solid rgba(255,255,255,0.05);
}

.pos-sidebar::-webkit-scrollbar,
.pos-menu::-webkit-scrollbar,
.pos-cart::-webkit-scrollbar {
    width: 4px;
}
.pos-sidebar::-webkit-scrollbar-track,
.pos-menu::-webkit-scrollbar-track,
.pos-cart::-webkit-scrollbar-track {
    background: transparent;
}
.pos-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
}
.pos-menu::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 4px;
}
.pos-cart::-webkit-scrollbar-thumb {
    background: rgba(0,0,0,0.1);
    border-radius: 4px;
}

.pos-brand {
    padding: 20px 18px 16px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

.pos-logo {
    width: 60px;
    height: 60px;
    margin: 0 auto 10px;
    background: linear-gradient(135deg, #5d4037, #c8a96e);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(200, 169, 110, 0.25);
}

.pos-logo svg {
    width: 30px;
    height: 30px;
}

.pos-brand h1 {
    font-family: 'Playfair Display', serif;
    font-size: 15px;
    font-weight: 700;
    color: #f5f0eb;
    margin: 0;
    letter-spacing: 0.3px;
}

.pos-brand p {
    font-size: 10px;
    color: var(--pos-gold);
    text-transform: uppercase;
    letter-spacing: 2px;
    margin: 2px 0 0;
    font-weight: 500;
}

.pos-cashier {
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    display: flex;
    align-items: center;
    gap: 10px;
}

.pos-cashier-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--pos-gold), #d4a574);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    color: #1a0f0a;
    flex-shrink: 0;
}

.pos-cashier-info {
    flex: 1;
    min-width: 0;
}

.pos-cashier-info .label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: rgba(255,255,255,0.35);
    font-weight: 600;
}

.pos-cashier-info .name {
    font-size: 13px;
    font-weight: 600;
    color: #f5f0eb;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pos-cashier-info .time {
    font-size: 11px;
    color: var(--pos-text-light);
    font-weight: 400;
}

.pos-search-wrap {
    padding: 12px 14px;
}

.pos-search-wrap .input-group {
    background: rgba(255,255,255,0.06);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.08);
    transition: border-color 0.2s;
}
.pos-search-wrap .input-group:focus-within {
    border-color: var(--pos-gold);
}
.pos-search-wrap .input-group-text {
    background: transparent;
    border: none;
    color: rgba(255,255,255,0.3);
    padding: 0 0 0 12px;
}
.pos-search-wrap .form-control {
    background: transparent;
    border: none;
    color: #f5f0eb;
    font-size: 13px;
    padding: 9px 8px 9px 4px;
    box-shadow: none;
}
.pos-search-wrap .form-control::placeholder {
    color: rgba(255,255,255,0.3);
}

.pos-categories {
    padding: 8px 10px;
    flex: 1;
    overflow-y: auto;
}

.pos-categories-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255,255,255,0.25);
    font-weight: 600;
    padding: 6px 8px 10px;
}

.cat-btn {
    display: block;
    width: 100%;
    padding: 8px 14px;
    margin-bottom: 3px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: rgba(255,255,255,0.55);
    font-size: 12.5px;
    font-weight: 500;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
    position: relative;
}

.cat-btn:hover {
    background: rgba(200, 169, 110, 0.1);
    color: var(--pos-gold-light);
}

.cat-btn.active {
    background: rgba(200, 169, 110, 0.15);
    color: var(--pos-gold);
    font-weight: 600;
}

.cat-btn.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 4px;
    bottom: 4px;
    width: 3px;
    background: var(--pos-gold);
    border-radius: 0 3px 3px 0;
}

.cat-btn .cat-count {
    float: right;
    background: rgba(255,255,255,0.06);
    padding: 1px 8px;
    border-radius: 10px;
    font-size: 10px;
    color: rgba(255,255,255,0.3);
}

.pos-menu {
    width: 50%;
    background: var(--pos-menu-bg);
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.pos-menu-header {
    padding: 18px 24px 16px;
    background: #faf7f3;
    border-bottom: 1px solid rgba(0,0,0,0.04);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}

.pos-menu-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: #2c1810;
    margin: 0;
}

.pos-menu-header .count {
    font-size: 12px;
    color: #8b7d6b;
    background: rgba(0,0,0,0.04);
    padding: 3px 12px;
    border-radius: 20px;
    font-weight: 500;
}

.pos-products {
    padding: 16px 20px 24px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 14px;
}

.product-card {
    background: #fffdfb;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(44, 24, 16, 0.06);
    overflow: hidden;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid rgba(0,0,0,0.04);
    position: relative;
}

.product-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(44, 24, 16, 0.1);
    border-color: var(--pos-gold);
}

.product-card.unavailable {
    opacity: 0.55;
    cursor: not-allowed;
}

.product-card .img-wrap {
    width: 100%;
    aspect-ratio: 1;
    background: #f5f0eb;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.product-card .img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-card .img-wrap .no-img {
    width: 40px;
    height: 40px;
    color: #c8b8a8;
}

.product-card .card-body {
    padding: 10px 12px 12px;
}

.product-card .card-body h6 {
    font-size: 13px;
    font-weight: 600;
    color: #2c1810;
    margin: 0 0 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card .card-body .price {
    font-size: 15px;
    font-weight: 700;
    color: var(--pos-gold);
}

.product-card .badge-avail {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 9px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 20px;
    letter-spacing: 0.3px;
}

.badge-avail.available {
    background: #e8f5e9;
    color: #2e7d32;
}

.badge-avail.unavailable {
    background: #fce4ec;
    color: #c62828;
}

.pos-cart {
    width: 30%;
    min-width: 300px;
    background: var(--pos-cart-bg);
    display: flex;
    flex-direction: column;
    border-left: 1px solid rgba(0,0,0,0.06);
}

.pos-cart-header {
    padding: 18px 20px 14px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    background: #faf7f3;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.pos-cart-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 700;
    color: #2c1810;
    margin: 0;
}

.pos-cart-header .cart-count {
    background: #2c1810;
    color: #f5f0eb;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
}

.pos-cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.03);
    animation: slideIn 0.2s ease-out;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}

.cart-item-info {
    flex: 1;
    min-width: 0;
}

.cart-item-info .item-name {
    font-size: 13px;
    font-weight: 600;
    color: #2c1810;
    margin-bottom: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item-info .item-price {
    font-size: 12px;
    color: var(--pos-gold);
    font-weight: 600;
}

.cart-item-qty {
    display: flex;
    align-items: center;
    gap: 6px;
}

.cart-item-qty .qty-btn {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    border: 1px solid rgba(0,0,0,0.1);
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    color: #5d4037;
    transition: all 0.15s;
    padding: 0;
    line-height: 1;
}

.cart-item-qty .qty-btn:hover {
    background: #2c1810;
    color: #f5f0eb;
    border-color: #2c1810;
}

.cart-item-qty .qty-val {
    font-size: 13px;
    font-weight: 600;
    color: #2c1810;
    min-width: 20px;
    text-align: center;
}

.cart-item-total {
    font-size: 13px;
    font-weight: 700;
    color: #2c1810;
    min-width: 50px;
    text-align: right;
}

.cart-item .item-details {
    display: block;
    font-size: 10px;
    color: #b8a99a;
    font-weight: 400;
    margin-top: 1px;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cart-item .remove-btn {
    background: none;
    border: none;
    color: rgba(0,0,0,0.15);
    cursor: pointer;
    padding: 2px;
    font-size: 16px;
    line-height: 1;
    transition: color 0.15s;
    flex-shrink: 0;
}

.cart-item .remove-btn:hover {
    color: #c62828;
}

.cart-actions {
    display: flex;
    flex-direction: column;
    gap: 3px;
    flex-shrink: 0;
}

.action-btn {
    background: none;
    border: 1px solid #e0d5c9;
    border-radius: 5px;
    width: 28px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    color: #8b7d6b;
    transition: all 0.15s;
    padding: 0;
}

.action-btn:hover {
    background: #2c1810;
    border-color: #2c1810;
    color: #f5f0eb;
}

.cart-empty {
    text-align: center;
    padding: 40px 20px;
    color: #b8a99a;
}

.cart-empty i {
    font-size: 40px;
    margin-bottom: 12px;
    display: block;
    opacity: 0.4;
}

.cart-empty p {
    font-size: 13px;
    margin: 0;
}

.pos-cart-summary {
    padding: 16px 20px;
    border-top: 1px solid rgba(0,0,0,0.06);
    background: #faf7f3;
    flex-shrink: 0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    color: #5d4037;
    margin-bottom: 6px;
}

.summary-row.total {
    font-size: 16px;
    font-weight: 700;
    color: #2c1810;
    padding-top: 10px;
    border-top: 1px solid rgba(0,0,0,0.06);
    margin-top: 6px;
}

.summary-row.total .summary-val {
    color: var(--pos-gold);
}

.seg-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}
.seg-group input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.seg-group label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 7px 16px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 20px;
    border: 1.5px solid #d4c5b5;
    background: #fffdfb;
    color: #5d4037;
    cursor: pointer;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
    user-select: none;
    white-space: nowrap;
}
.seg-group label:hover {
    border-color: #b8a080;
    background: #faf5ef;
}
.seg-group input:checked + label {
    background: #4e342e !important;
    border-color: #4e342e !important;
    color: #f5f0eb !important;
}
.seg-addon {
    display: block;
    font-size: 9px;
    font-weight: 600;
    color: #c8a96e;
    margin-top: 1px;
}
.seg-group input:checked + label .seg-addon {
    color: #e8d5a8;
}

.addon-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1.5px solid #ede5db;
    border-radius: 10px;
    background: #fffdfb;
    cursor: pointer;
    transition: all 0.15s;
}
.addon-card:hover {
    border-color: #d4c5b5;
    background: #faf7f3;
}
.addon-card.has-check {
    border-color: #c8a96e;
    background: rgba(200,169,110,0.06);
}
.addon-card input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.addon-card .check-box {
    width: 18px;
    height: 18px;
    border: 1.5px solid #d4c5b5;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.15s;
    font-size: 11px;
    color: transparent;
}
.addon-card.has-check .check-box {
    background: #4e342e;
    border-color: #4e342e;
    color: #f5f0eb;
}
.addon-card .addon-name {
    flex: 1;
    font-size: 12px;
    font-weight: 500;
    color: #2c1810;
}
.addon-card .addon-price {
    font-size: 11px;
    font-weight: 600;
    color: #c8a96e;
    white-space: nowrap;
}

.modal-product-summary {
    display: flex;
    gap: 16px;
    padding: 0 0 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid #ede5db;
}
.modal-product-summary .summary-img {
    width: 90px;
    height: 90px;
    border-radius: 14px;
    background: #f5f0eb;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-product-summary .summary-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.modal-product-summary .summary-img svg {
    width: 36px;
    height: 36px;
    color: #c8b8a8;
}
.modal-product-summary .summary-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.modal-product-summary .summary-info h3 {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    font-weight: 700;
    color: #2c1810;
    margin: 0 0 2px;
    line-height: 1.2;
}
.modal-product-summary .summary-info .cat-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #c8a96e;
    background: rgba(200,169,110,0.1);
    padding: 2px 10px;
    border-radius: 4px;
    margin-bottom: 4px;
    width: fit-content;
}
.modal-product-summary .summary-info .base-price {
    font-size: 20px;
    font-weight: 700;
    color: #c8a96e;
}

.modal-options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 20px;
    margin-bottom: 14px;
}
.modal-option {
    margin-bottom: 10px;
}
.modal-option.full-width {
    grid-column: 1 / -1;
}
.modal-option .opt-label {
    display: block;
    font-size: 10px;
    font-weight: 600;
    color: #8b7d6b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 7px;
}

.addons-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.qty-summary-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 14px;
    align-items: start;
}

.qty-control {
    display: inline-flex;
    align-items: center;
    background: #f5f0eb;
    border-radius: 10px;
    padding: 3px;
}
.qty-control button {
    width: 38px;
    height: 38px;
    border: none;
    border-radius: 8px;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    font-weight: 600;
    color: #5d4037;
    transition: all 0.15s;
    font-family: 'Inter', sans-serif;
}
.qty-control button:hover {
    background: #4e342e;
    color: #f5f0eb;
}
.qty-control .qty-value {
    min-width: 40px;
    text-align: center;
    font-size: 17px;
    font-weight: 700;
    color: #2c1810;
}

.order-summary-card {
    background: #faf5ef;
    border-radius: 12px;
    padding: 14px 16px;
}
.os-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #5d4037;
    margin-bottom: 5px;
}
.os-row.os-total {
    font-size: 16px;
    font-weight: 700;
    color: #2c1810;
    padding-top: 8px;
    border-top: 1.5px solid #ede5db;
    margin-top: 4px;
    margin-bottom: 0;
}
.os-row.os-total .os-val {
    color: #c8a96e;
    font-size: 18px;
}
.os-row .os-val {
    font-weight: 600;
}

.notes-area {
    font-size: 13px;
    border: 1.5px solid #ede5db;
    border-radius: 10px;
    padding: 10px 12px;
    background: #fffdfb;
    color: #3e3a36;
    resize: none;
    font-family: 'Inter', sans-serif;
    width: 100%;
    transition: border-color 0.15s;
}
.notes-area:focus {
    outline: none;
    border-color: #c8a96e;
    box-shadow: 0 0 0 3px rgba(200,169,110,0.1);
}
.notes-area::placeholder {
    color: #b8a99a;
}

.modal-footer-bar {
    display: flex;
    gap: 10px;
    padding: 14px 24px;
    border-top: 1px solid #ede5db;
    background: #faf7f3;
}
.modal-btn {
    flex: 1;
    padding: 11px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
}
.modal-btn-cancel {
    background: #fffdfb;
    border: 1.5px solid #ede5db;
    color: #5d4037;
}
.modal-btn-cancel:hover {
    background: #f5f0eb;
}
.modal-btn-primary {
    background: linear-gradient(135deg, #4e342e, #3e2723);
    color: #f5f0eb;
}
.modal-btn-primary:hover {
    box-shadow: 0 4px 16px rgba(78,52,46,0.3);
}

@media (max-width: 576px) {
    .modal-options-grid {
        grid-template-columns: 1fr;
    }
    .addons-grid {
        grid-template-columns: 1fr;
    }
    .qty-summary-row {
        grid-template-columns: 1fr;
    }
    .modal-product-summary .summary-img {
        width: 70px;
        height: 70px;
    }
    .modal-product-summary .summary-info h3 {
        font-size: 15px;
    }
}

.review-card {
    border: 1.5px solid #ede5db;
    border-radius: 14px;
    background: #fffdfb;
    overflow: hidden;
    margin-bottom: 12px;
}
.review-card-body {
    display: flex;
    gap: 14px;
    padding: 14px;
}
.review-card-img {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    background: #f5f0eb;
    flex-shrink: 0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}
.review-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.review-card-img svg {
    width: 28px;
    height: 28px;
    color: #c8b8a8;
}
.review-card-info {
    flex: 1;
    min-width: 0;
}
.review-card-name {
    font-size: 15px;
    font-weight: 700;
    color: #2c1810;
    font-family: 'Playfair Display', serif;
    margin-bottom: 6px;
}
.review-card-grid {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 2px 10px;
    font-size: 12px;
}
.review-card-grid .rc-label {
    color: #8b7d6b;
    font-weight: 500;
    white-space: nowrap;
}
.review-card-grid .rc-value {
    color: #2c1810;
    font-weight: 500;
}
.review-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    background: #faf5ef;
    border-top: 1px solid #ede5db;
    font-size: 13px;
}
.review-card-footer .rc-qty {
    color: #5d4037;
    font-weight: 600;
}
.review-card-footer .rc-price {
    color: #8b7d6b;
}
.review-card-footer .rc-subtotal {
    color: #c8a96e;
    font-weight: 700;
    font-size: 15px;
}
.rc-actions {
    display: flex;
    gap: 4px;
    margin-left: 8px;
}
.rc-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    border: 1.5px solid #ede5db;
    background: #fffdfb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    font-size: 13px;
    color: #8b7d6b;
    padding: 0;
}
.rc-btn:hover {
    background: #f5f0eb;
    border-color: #d4c8b8;
}
.rc-edit:hover {
    color: #4e342e;
    border-color: #c8a96e;
}
.rc-remove:hover {
    color: #c0392b;
    border-color: #e8b4a8;
    background: #fdf2f0;
}

.review-summary-card {
    border: 1.5px solid #ede5db;
    border-radius: 14px;
    background: #fffdfb;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.review-summary-card .card-body {
    padding: 16px 18px;
}
.rsc-header {
    font-family: 'Playfair Display', serif;
    font-size: 15px;
    font-weight: 700;
    color: #2c1810;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1.5px solid #ede5db;
}
.rsc-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    color: #5d4037;
    padding: 4px 0;
}
.rsc-row span:last-child {
    font-weight: 600;
}
.rsc-grand-total {
    font-size: 17px;
    font-weight: 800;
    color: #2c1810;
    padding-top: 10px;
    margin-top: 8px;
    border-top: 2px solid #ede5db;
}
.rsc-grand-total span:last-child {
    font-size: 20px;
    font-weight: 800;
    color: #c8a96e;
}

.review-section-card {
    border: 1.5px solid #ede5db;
    border-radius: 14px;
    background: #fffdfb;
    margin-top: 10px;
}
.review-section-card .card-body {
    padding: 14px 18px;
}
.review-select {
    width: 100%;
    padding: 10px 12px;
    border: 1.5px solid #ede5db;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: #2c1810;
    background: #fffdfb;
    outline: none;
    cursor: pointer;
    transition: border-color 0.15s;
}
.review-select:focus {
    border-color: #c8a96e;
    box-shadow: 0 0 0 3px rgba(200,169,110,0.1);
}
.promo-row {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    align-items: stretch;
}
.review-input {
    flex: 1;
    padding: 10px 12px;
    border: 1.5px solid #ede5db;
    border-radius: 10px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    color: #2c1810;
    background: #fffdfb;
    outline: none;
    transition: border-color 0.15s;
}
.review-input:focus {
    border-color: #c8a96e;
    box-shadow: 0 0 0 3px rgba(200,169,110,0.1);
}
.review-input::placeholder {
    color: #b8a99a;
}
.review-apply-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #4e342e, #3e2723);
    color: #f5f0eb;
    font-size: 13px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.review-apply-btn:hover {
    box-shadow: 0 4px 16px rgba(78,52,46,0.3);
}
.cust-type-row {
    display: flex;
    gap: 18px;
    margin-bottom: 2px;
}
.cust-radio {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #2c1810;
    cursor: pointer;
}
.cust-radio input[type="radio"] {
    accent-color: #4e342e;
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.pos-checkout-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #2c1810, #5d4037);
    border: none;
    border-radius: 10px;
    color: #f5f0eb;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 12px;
    font-family: 'Inter', sans-serif;
    letter-spacing: 0.3px;
}

.pos-checkout-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(44, 24, 16, 0.3);
}

.pos-checkout-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    transform: none !important;
}

@media (max-width: 1200px) {
    .pos-sidebar { width: 22%; min-width: 200px; }
    .pos-menu { width: 48%; }
    .pos-cart { width: 30%; min-width: 260px; }
    .pos-products { grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; }
}

@media (max-width: 992px) {
    .pos-wrapper { flex-direction: column; overflow-y: auto; }
    .pos-sidebar { width: 100%; min-width: unset; flex-direction: row; flex-wrap: wrap; max-height: 180px; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .pos-brand { padding: 12px 14px; flex: 0 0 auto; display: flex; align-items: center; gap: 10px; border-bottom: none; }
    .pos-logo { width: 36px; height: 36px; margin: 0; }
    .pos-logo svg { width: 18px; height: 18px; }
    .pos-brand h1 { font-size: 13px; }
    .pos-brand p { display: none; }
    .pos-cashier { padding: 10px 12px; border-bottom: none; flex: 0 0 auto; }
    .pos-search-wrap { padding: 8px 10px; flex: 1; min-width: 150px; }
    .pos-categories { flex: 1 1 100%; padding: 4px 8px; display: flex; gap: 4px; overflow-x: auto; }
    .pos-categories-label { display: none; }
    .cat-btn { white-space: nowrap; width: auto; padding: 5px 12px; font-size: 11px; }
    .cat-btn .cat-count { display: none; }
    .cat-btn.active::before { display: none; }
    .pos-menu { width: 100%; max-height: 50vh; }
    .pos-cart { width: 100%; min-width: unset; border-left: none; border-top: 1px solid rgba(0,0,0,0.06); max-height: 40vh; }
}
</style>

<div class="pos-wrapper">

    <aside class="pos-sidebar">
        <div class="pos-brand">
            <div class="pos-logo">
                <svg viewBox="0 0 24 24" fill="none" stroke="#f5f0eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                    <line x1="6" y1="1" x2="6" y2="4"/>
                    <line x1="10" y1="1" x2="10" y2="4"/>
                    <line x1="14" y1="1" x2="14" y2="4"/>
                </svg>
            </div>
            <h1>Brew & Bean</h1>
            <p>Brewing Excellence</p>
        </div>

        <div class="pos-cashier">
            <div class="pos-cashier-avatar"><?php echo strtoupper(substr($cashier_name, 0, 1)); ?></div>
            <div class="pos-cashier-info">
                <div class="label">Cashier</div>
                <div class="name"><?php echo htmlspecialchars($cashier_name); ?></div>
                <div class="time" id="posClock"></div>
            </div>
        </div>

        <div class="pos-search-wrap">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="posSearch" placeholder="Search products..." autocomplete="off">
            </div>
        </div>

        <div class="pos-categories">
            <div class="pos-categories-label">Categories</div>
            <button class="cat-btn active" data-category="all" onclick="filterCategory('all', this)">
                <i class="bi bi-grid-3x3-gap-fill me-2"></i>All <span class="cat-count"><?php echo count($products); ?></span>
            </button>
            <?php foreach ($categories as $cat): ?>
            <button class="cat-btn" data-category="<?php echo $cat['category_id']; ?>" onclick="filterCategory(<?php echo $cat['category_id']; ?>, this)">
                <i class="bi bi-cup-hot me-2"></i><?php echo htmlspecialchars($cat['name']); ?>
                <span class="cat-count"><?php echo count(array_filter($products, fn($p) => $p['category_id'] == $cat['category_id'])); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </aside>

    <main class="pos-menu">
        <div class="pos-menu-header">
            <h2><i class="bi bi-menu-app me-2"></i>Menu</h2>
            <span class="count" id="productCount"><?php echo count($products); ?> items</span>
        </div>
        <div class="pos-products" id="productGrid">
            <?php foreach ($products as $product):
                $imgPath = !empty($product['image']) && file_exists(__DIR__ . '/../../uploads/products/' . $product['image'])
                    ? BASE_URL . '/uploads/products/' . htmlspecialchars($product['image'])
                    : null;
                $available = $product['status'] === 'available';
            ?>
            <div class="product-card<?php echo $available ? '' : ' unavailable'; ?>" data-id="<?php echo $product['product_id']; ?>" data-category="<?php echo $product['category_id']; ?>" data-category-name="<?php echo htmlspecialchars($product['category_name'] ?? ''); ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" onclick="<?php echo $available ? "openCustomizationModal({$product['product_id']}, '" . htmlspecialchars(addslashes($product['name'])) . "', {$product['price']})" : ''; ?>">
                <div class="img-wrap">
                    <?php if ($imgPath): ?>
                    <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                    <?php else: ?>
                    <svg class="no-img" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                        <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                        <line x1="6" y1="1" x2="6" y2="4"/>
                        <line x1="10" y1="1" x2="10" y2="4"/>
                        <line x1="14" y1="1" x2="14" y2="4"/>
                    </svg>
                    <?php endif; ?>
                    <span class="badge-avail <?php echo $available ? 'available' : 'unavailable'; ?>">
                        <?php echo $available ? 'In Stock' : 'Out of Stock'; ?>
                    </span>
                </div>
                <div class="card-body">
                    <h6><?php echo htmlspecialchars($product['name']); ?></h6>
                    <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <aside class="pos-cart">
        <div class="pos-cart-header">
            <h3><i class="bi bi-cart3 me-2"></i>Order</h3>
            <span class="cart-count" id="cartCount">0</span>
        </div>
        <div class="pos-cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <i class="bi bi-cart-x"></i>
                <p>Select products to start an order</p>
            </div>
        </div>
        <div class="pos-cart-summary" id="cartSummary" style="display:none;">
            <div class="summary-row">
                <span>Subtotal</span>
                <span class="summary-val" id="subtotal">₱0.00</span>
            </div>
            <div class="summary-row">
                <span>Tax (12%)</span>
                <span class="summary-val" id="tax">₱0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span class="summary-val" id="total">₱0.00</span>
            </div>
            <button class="pos-checkout-btn" id="checkoutBtn" disabled>
                <i class="bi bi-credit-card me-2"></i>Checkout
            </button>
        </div>
    </aside>

</div>

<div class="modal fade" id="customizeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-height:90vh;">
        <div class="modal-content" style="border-radius:18px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.25);overflow:hidden;">
            <div class="modal-body" style="padding:20px 24px 8px;max-height:calc(90vh - 68px);overflow-y:auto;">

                <div class="modal-product-summary" id="modalProductSummary">
                    <div class="summary-img" id="modalProductImg">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                    </div>
                    <div class="summary-info">
                        <span class="cat-badge" id="modalCategoryBadge">Coffee</span>
                        <h3 id="modalProductName">Product Name</h3>
                        <span class="base-price" id="modalProductPrice">₱0.00</span>
                    </div>
                </div>

                <div class="modal-options-grid">
                    <div class="modal-option">
                        <span class="opt-label">Size</span>
                        <div class="seg-group">
                            <input type="radio" class="size-opt" name="size" id="size-small" value="small" data-price="0" checked>
                            <label for="size-small">Small</label>
                            <input type="radio" class="size-opt" name="size" id="size-medium" value="medium" data-price="15">
                            <label for="size-medium">Medium<span class="seg-addon">+₱15</span></label>
                            <input type="radio" class="size-opt" name="size" id="size-large" value="large" data-price="25">
                            <label for="size-large">Large<span class="seg-addon">+₱25</span></label>
                        </div>
                    </div>
                    <div class="modal-option" id="tempSection">
                        <span class="opt-label">Temperature</span>
                        <div class="seg-group">
                            <input type="radio" class="temp-opt" name="temp" id="temp-hot" value="hot" checked>
                            <label for="temp-hot">Hot</label>
                            <input type="radio" class="temp-opt" name="temp" id="temp-iced" value="iced">
                            <label for="temp-iced">Iced</label>
                        </div>
                    </div>
                    <div class="modal-option">
                        <span class="opt-label">Sugar</span>
                        <div class="seg-group">
                            <input type="radio" class="sugar-opt" name="sugar" id="sugar-0" value="0%">
                            <label for="sugar-0">0%</label>
                            <input type="radio" class="sugar-opt" name="sugar" id="sugar-25" value="25%">
                            <label for="sugar-25">25%</label>
                            <input type="radio" class="sugar-opt" name="sugar" id="sugar-50" value="50%" checked>
                            <label for="sugar-50">50%</label>
                            <input type="radio" class="sugar-opt" name="sugar" id="sugar-75" value="75%">
                            <label for="sugar-75">75%</label>
                            <input type="radio" class="sugar-opt" name="sugar" id="sugar-100" value="100%">
                            <label for="sugar-100">100%</label>
                        </div>
                    </div>
                    <div class="modal-option">
                        <span class="opt-label">Ice</span>
                        <div class="seg-group">
                            <input type="radio" class="ice-opt" name="ice" id="ice-none" value="none">
                            <label for="ice-none">No Ice</label>
                            <input type="radio" class="ice-opt" name="ice" id="ice-less" value="less">
                            <label for="ice-less">Less Ice</label>
                            <input type="radio" class="ice-opt" name="ice" id="ice-regular" value="regular" checked>
                            <label for="ice-regular">Regular Ice</label>
                            <input type="radio" class="ice-opt" name="ice" id="ice-extra" value="extra">
                            <label for="ice-extra">Extra Ice</label>
                        </div>
                    </div>
                </div>

                <div class="modal-option full-width">
                    <span class="opt-label">Add-ons</span>
                    <div class="addons-grid" id="modalAddons">
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_extra_shot" data-price="25">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Extra Shot</span>
                            <span class="addon-price">+₱25</span>
                        </div>
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_vanilla" data-price="15">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Vanilla Syrup</span>
                            <span class="addon-price">+₱15</span>
                        </div>
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_caramel" data-price="15">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Caramel Sauce</span>
                            <span class="addon-price">+₱15</span>
                        </div>
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_whipped_cream" data-price="20">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Whipped Cream</span>
                            <span class="addon-price">+₱20</span>
                        </div>
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_soy_milk" data-price="20">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Soy Milk</span>
                            <span class="addon-price">+₱20</span>
                        </div>
                        <div class="addon-card" onclick="toggleAddon(this)">
                            <input type="checkbox" id="addon_almond_milk" data-price="25">
                            <span class="check-box"><i class="bi bi-check"></i></span>
                            <span class="addon-name">Almond Milk</span>
                            <span class="addon-price">+₱25</span>
                        </div>
                    </div>
                </div>

                <div class="qty-summary-row">
                    <div class="modal-option" style="margin-bottom:0;">
                        <span class="opt-label">Quantity</span>
                        <div class="qty-control">
                            <button onclick="modalQtyChange(-1)">−</button>
                            <span class="qty-value" id="modalQty">1</span>
                            <button onclick="modalQtyChange(1)">+</button>
                        </div>
                    </div>
                    <div>
                        <span class="opt-label" style="display:block;font-size:10px;font-weight:600;color:#8b7d6b;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:7px;">Order Summary</span>
                        <div class="order-summary-card">
                            <div class="os-row"><span>Subtotal</span><span class="os-val" id="osSubtotal">₱0.00</span></div>
                            <div class="os-row"><span>Add-ons</span><span class="os-val" id="osAddons">₱0.00</span></div>
                            <div class="os-row"><span>Qty</span><span class="os-val" id="osQty">1</span></div>
                            <div class="os-row os-total"><span>TOTAL</span><span class="os-val" id="modalTotal">₱0.00</span></div>
                        </div>
                    </div>
                </div>

                <div class="modal-option full-width" style="margin-bottom:4px;">
                    <span class="opt-label">Notes</span>
                    <textarea id="modalInstructions" class="notes-area" rows="2" placeholder="• Less sweet&#10;• No whipped cream&#10;• Extra hot"></textarea>
                </div>

            </div>
            <div class="modal-footer-bar">
                <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="modalAddBtn" class="modal-btn modal-btn-primary"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.2);overflow:hidden;">
            <div class="modal-header" style="border:none;background:#faf7f3;padding:20px 24px 12px;">
                <h5 class="modal-title" style="font-family:'Playfair Display',serif;font-weight:700;color:#2c1810;font-size:18px;">
                    <i class="bi bi-receipt me-2"></i>Item Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 24px 20px;">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid #f0ebe5;">
                    <div id="viewProductImg" style="width:60px;height:60px;border-radius:12px;background:#f5f0eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                    </div>
                    <div>
                        <h6 id="viewProductName" style="font-size:16px;font-weight:700;color:#2c1810;margin:0 0 2px;"></h6>
                        <span id="viewProductPrice" style="font-size:15px;font-weight:700;color:#c8a96e;"></span>
                    </div>
                </div>

                <div id="viewDetailsBody">
                    <div class="view-row"><span class="view-label">Size</span><span class="view-value" id="viewSize">—</span></div>
                    <div class="view-row"><span class="view-label">Temperature</span><span class="view-value" id="viewTemp">—</span></div>
                    <div class="view-row"><span class="view-label">Sugar Level</span><span class="view-value" id="viewSugar">—</span></div>
                    <div class="view-row"><span class="view-label">Ice Level</span><span class="view-value" id="viewIce">—</span></div>
                    <div class="view-row"><span class="view-label">Add-ons</span><span class="view-value" id="viewAddons">None</span></div>
                    <div class="view-row"><span class="view-label">Quantity</span><span class="view-value" id="viewQty">—</span></div>
                    <div class="view-row"><span class="view-label">Instructions</span><span class="view-value" id="viewInstructions">None</span></div>
                    <div class="view-row view-total"><span class="view-label">Item Subtotal</span><span class="view-value" id="viewSubtotal" style="font-weight:700;color:#c8a96e;font-size:16px;">—</span></div>
                </div>
            </div>
            <div class="modal-footer" style="border:none;padding:0 24px 20px;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="width:100%;padding:10px;border-radius:10px;border:1.5px solid #e0d5c9;background:transparent;color:#5d4037;font-weight:600;font-size:13px;font-family:'Inter',sans-serif;">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius:18px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.25);overflow:hidden;">
            <div class="modal-header" style="border:none;background:#faf7f3;padding:20px 24px 12px;">
                <h5 class="modal-title" style="font-family:'Playfair Display',serif;font-weight:700;color:#2c1810;font-size:18px;">
                    <i class="bi bi-receipt me-2"></i>Review Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:8px 24px 4px;" id="reviewModalBody">
            </div>
            <div class="modal-footer review-footer" style="border-top:1px solid #ede5db;padding:14px 24px;background:#faf7f3;gap:10px;">
                <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</button>
                <button type="button" class="modal-btn modal-btn-primary" id="reviewProceedBtn">
                    <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius:18px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.25);overflow:hidden;">
            <div class="modal-header" style="border:none;background:#faf7f3;padding:20px 24px 12px;">
                <h5 class="modal-title" style="font-family:'Playfair Display',serif;font-weight:700;color:#2c1810;font-size:18px;">
                    <i class="bi bi-credit-card me-2"></i>Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 24px 8px;">
                <div class="pmt-grand-total">
                    <span class="pmt-gt-label">Grand Total</span>
                    <span class="pmt-gt-value" id="pmtGrandTotal">₱0.00</span>
                </div>

                <div class="card review-summary-card" style="margin-top:14px;">
                    <div class="card-body">
                        <div class="rsc-header">Order Summary</div>
                        <div class="rsc-row"><span>Subtotal</span><span id="pmtSubtotal">₱0.00</span></div>
                        <div class="rsc-row"><span>Discount</span><span id="pmtDiscount">₱0.00</span></div>
                        <div class="rsc-row"><span>VAT</span><span id="pmtTax">₱0.00</span></div>
                        <div class="rsc-row rsc-grand-total" style="margin-top:8px;"><span>Grand Total</span><span id="pmtGrandTotalDisplay">₱0.00</span></div>
                    </div>
                </div>

                <div class="card review-summary-card" style="margin-top:10px;">
                    <div class="card-body">
                        <div class="rsc-header">Payment Method</div>
                        <div class="pmt-options">
                            <label class="pmt-option" onclick="document.getElementById('pmtCash').checked=true; onPmtMethodChange()">
                                <input type="radio" name="pmtMethod" id="pmtCash" value="cash" checked onchange="onPmtMethodChange()">
                                <span class="pmt-icon"><i class="bi bi-cash"></i></span>
                                <span class="pmt-label">Cash</span>
                            </label>
                            <label class="pmt-option" onclick="document.getElementById('pmtGCash').checked=true; onPmtMethodChange()">
                                <input type="radio" name="pmtMethod" id="pmtGCash" value="gcash" onchange="onPmtMethodChange()">
                                <span class="pmt-icon"><i class="bi bi-phone"></i></span>
                                <span class="pmt-label">GCash</span>
                            </label>
                            <label class="pmt-option" onclick="document.getElementById('pmtMaya').checked=true; onPmtMethodChange()">
                                <input type="radio" name="pmtMethod" id="pmtMaya" value="maya" onchange="onPmtMethodChange()">
                                <span class="pmt-icon"><i class="bi bi-wallet2"></i></span>
                                <span class="pmt-label">Maya</span>
                            </label>
                            <label class="pmt-option" onclick="document.getElementById('pmtCreditCard').checked=true; onPmtMethodChange()">
                                <input type="radio" name="pmtMethod" id="pmtCreditCard" value="credit_card" onchange="onPmtMethodChange()">
                                <span class="pmt-icon"><i class="bi bi-credit-card-2-front"></i></span>
                                <span class="pmt-label">Credit Card</span>
                            </label>
                            <label class="pmt-option" onclick="document.getElementById('pmtDebitCard').checked=true; onPmtMethodChange()">
                                <input type="radio" name="pmtMethod" id="pmtDebitCard" value="debit_card" onchange="onPmtMethodChange()">
                                <span class="pmt-icon"><i class="bi bi-credit-card-2-back"></i></span>
                                <span class="pmt-label">Debit Card</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="pmt-cash-section" id="pmtCashSection">
                    <div class="pmt-cash-row">
                        <div class="pmt-cash-field">
                            <span class="pmt-cash-label">Cash Received</span>
                            <div class="pmt-input-wrap">
                                <span class="pmt-currency">₱</span>
                                <input type="number" class="pmt-cash-input" id="cashReceived" placeholder="0.00" step="0.01" min="0" oninput="onCashReceivedChange()">
                            </div>
                        </div>
                        <div class="pmt-change-display">
                            <span class="pmt-cash-label">Change</span>
                            <span class="pmt-change-value" id="changeDisplay">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer review-footer" style="border-top:1px solid #ede5db;padding:14px 24px;background:#faf7f3;gap:10px;">
                <button type="button" class="modal-btn modal-btn-cancel" data-bs-dismiss="modal"><i class="bi bi-x-lg me-2"></i>Cancel</button>
                <button type="button" class="modal-btn modal-btn-primary" id="processPaymentBtn">
                    <i class="bi bi-check2-circle me-2"></i>Process Payment
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm">
        <div class="modal-content" style="border-radius:18px;border:none;box-shadow:0 20px 60px rgba(0,0,0,0.25);overflow:hidden;">
            <div class="modal-body" style="padding:32px 28px;text-align:center;">
                <div class="success-icon">&#10004;</div>
                <div class="success-title">Payment Successful</div>
                <div class="success-divider"></div>
                <div class="success-details">
                    <div class="s-detail"><span class="s-label">Receipt Number</span><span class="s-value" id="sReceipt">—</span></div>
                    <div class="s-detail"><span class="s-label">Transaction Date</span><span class="s-value" id="sDate">—</span></div>
                    <div class="s-detail s-detail-total"><span class="s-label">Grand Total</span><span class="s-value s-value-total" id="sTotal">₱0.00</span></div>
                </div>
                <div class="success-actions">
                    <button type="button" class="modal-btn modal-btn-cancel" id="printReceiptBtn"><i class="bi bi-printer me-2"></i>Print Receipt</button>
                    <button type="button" class="modal-btn modal-btn-primary" id="newOrderBtn"><i class="bi bi-cart-plus me-2"></i>New Order</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pmt-grand-total {
    text-align: center;
    padding: 18px 20px;
    background: linear-gradient(135deg, #2c1810, #4e342e);
    border-radius: 14px;
}
.pmt-gt-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #c8a96e;
    margin-bottom: 4px;
}
.pmt-gt-value {
    display: block;
    font-size: 34px;
    font-weight: 800;
    color: #fff;
    font-family: 'Playfair Display', serif;
}
.pmt-options {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}
.pmt-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 18px 12px 14px;
    border: 2px solid #ede5db;
    border-radius: 14px;
    cursor: pointer;
    transition: all 0.15s;
    background: #fffdfb;
    position: relative;
}
.pmt-option:hover {
    border-color: #c8a96e;
    background: #faf5ef;
}
.pmt-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.pmt-option .pmt-icon {
    font-size: 28px;
    color: #c8a96e;
    line-height: 1;
}
.pmt-option .pmt-label {
    font-size: 12px;
    font-weight: 600;
    color: #2c1810;
    text-align: center;
    line-height: 1.2;
}
.pmt-option:has(input:checked) {
    border-color: #c8a96e;
    background: #faf5ef;
    box-shadow: 0 0 0 2px rgba(200,169,110,0.15);
}
.pmt-option:has(input:checked) .pmt-label {
    color: #4e342e;
}

.pmt-cash-section {
    margin-top: 14px;
    padding: 16px 18px;
    background: #faf5ef;
    border-radius: 14px;
    border: 1.5px solid #ede5db;
}
.pmt-cash-row {
    display: flex;
    gap: 16px;
    align-items: flex-start;
}
.pmt-cash-field {
    flex: 1;
}
.pmt-cash-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #8b7d6b;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 6px;
}
.pmt-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.pmt-currency {
    position: absolute;
    left: 14px;
    font-size: 20px;
    font-weight: 700;
    color: #2c1810;
    pointer-events: none;
}
.pmt-cash-input {
    width: 100%;
    padding: 12px 14px 12px 34px;
    border: 2px solid #ede5db;
    border-radius: 12px;
    font-size: 22px;
    font-weight: 700;
    font-family: 'Inter', sans-serif;
    color: #2c1810;
    background: #fffdfb;
    outline: none;
    transition: border-color 0.15s;
}
.pmt-cash-input:focus {
    border-color: #c8a96e;
    box-shadow: 0 0 0 3px rgba(200,169,110,0.1);
}
.pmt-cash-input::placeholder {
    color: #d4c8b8;
    font-weight: 400;
}
.pmt-change-display {
    min-width: 160px;
    text-align: center;
    padding: 12px 16px;
    background: #fffdfb;
    border-radius: 12px;
    border: 2px solid #ede5db;
}
.pmt-change-value {
    display: block;
    font-size: 28px;
    font-weight: 800;
    color: #27ae60;
    margin-top: 2px;
    font-family: 'Playfair Display', serif;
}

.success-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: #fff;
    font-size: 32px;
    line-height: 64px;
    margin: 0 auto 16px;
    box-shadow: 0 8px 24px rgba(39,174,96,0.25);
}
.success-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: #2c1810;
    margin-bottom: 14px;
}
.success-divider {
    width: 40px;
    height: 3px;
    background: #c8a96e;
    border-radius: 2px;
    margin: 0 auto 16px;
}
.success-details {
    text-align: left;
}
.s-detail {
    display: flex;
    justify-content: space-between;
    padding: 7px 0;
    border-bottom: 1px solid #f0ebe5;
    font-size: 13px;
}
.s-detail:last-of-type {
    border-bottom: none;
}
.s-label {
    color: #8b7d6b;
    font-weight: 500;
}
.s-value {
    font-weight: 600;
    color: #2c1810;
}
.s-detail-total {
    padding-top: 10px;
    margin-top: 4px;
    border-top: 1.5px solid #ede5db;
}
.s-value-total {
    font-size: 18px;
    font-weight: 800;
    color: #c8a96e;
}
.success-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}
.success-actions .modal-btn {
    flex: 1;
}

.view-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f5f0eb;
}
.view-row:last-of-type {
    border-bottom: none;
}
.view-row.view-total {
    margin-top: 8px;
    padding-top: 12px;
    border-top: 2px solid #f0ebe5;
}
.view-label {
    font-size: 13px;
    color: #8b7d6b;
    font-weight: 500;
}
.view-value {
    font-size: 13px;
    color: #2c1810;
    font-weight: 600;
    text-align: right;
    max-width: 60%;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart = [];
let currentCategory = 'all';

function updateClock() {
    const now = new Date();
    const opts = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true };
    document.getElementById('posClock').textContent = now.toLocaleDateString('en-US', opts);
}
updateClock();
setInterval(updateClock, 10000);

function filterCategory(categoryId, btn) {
    currentCategory = categoryId;
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.product-card');
    let visible = 0;
    cards.forEach(card => {
        const match = categoryId === 'all' || parseInt(card.dataset.category) === parseInt(categoryId);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('productCount').textContent = visible + ' item' + (visible !== 1 ? 's' : '');
}

document.getElementById('posSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    let visible = 0;
    cards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const catMatch = currentCategory === 'all' || parseInt(card.dataset.category) === parseInt(currentCategory);
        const searchMatch = !q || name.includes(q);
        const show = catMatch && searchMatch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('productCount').textContent = visible + ' item' + (visible !== 1 ? 's' : '');
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.opt-btn');
    if (!btn) return;
    const opt = btn.dataset.opt;
    document.querySelectorAll(`.opt-btn[data-opt="${opt}"]`).forEach(b => {
        b.style.borderColor = '#e0d5c9';
        b.style.background = '#fffdfb';
        b.style.color = '#5d4037';
    });
    btn.style.borderColor = '#c8a96e';
    btn.style.background = 'rgba(200,169,110,0.1)';
    btn.style.color = '#2c1810';
});

let modalProductId = null;
let modalProductName = '';
let modalProductPrice = 0;
let modalQtyVal = 1;
let modalEditIndex = null;

function openCustomizationModal(id, name, price, editIndex) {
    modalProductId = id;
    modalProductName = name;
    modalProductPrice = price;
    modalQtyVal = 1;
    modalEditIndex = editIndex != null ? editIndex : null;

    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductPrice').textContent = '₱' + price.toFixed(2);
    document.getElementById('modalQty').textContent = '1';
    document.getElementById('modalInstructions').value = '';

    document.querySelectorAll('.size-opt, .temp-opt, .sugar-opt, .ice-opt').forEach(r => r.checked = false);
    document.getElementById('size-small').checked = true;
    document.getElementById('temp-hot').checked = true;
    document.getElementById('sugar-50').checked = true;
    document.getElementById('ice-regular').checked = true;
    document.querySelectorAll('#modalAddons input[type="checkbox"]').forEach(c => {
        c.checked = false;
        c.closest('.addon-card') && c.closest('.addon-card').classList.remove('has-check');
    });

    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    let catName = '';
    if (card) {
        catName = card.dataset.categoryName || '';
        const img = card.querySelector('.img-wrap img');
        const imgWrap = document.getElementById('modalProductImg');
        if (img) {
            imgWrap.innerHTML = '<img src="' + img.src + '" alt="' + name + '" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            imgWrap.innerHTML = '<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>';
        }
    }
    document.getElementById('modalCategoryBadge').textContent = catName || 'Product';

    if (editIndex != null && cart[editIndex]) {
        const item = cart[editIndex];
        populateModalSelections(item);
    }

    const btn = document.getElementById('modalAddBtn');
    if (editIndex != null) {
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Update Cart';
    } else {
        btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i>Add to Cart';
    }

    updateModalTotal();
    const modal = new bootstrap.Modal(document.getElementById('customizeModal'));
    modal.show();
}

function populateModalSelections(item) {
    const sizeRadio = document.querySelector(`.size-opt[value="${item.size || 'small'}"]`);
    if (sizeRadio) sizeRadio.checked = true;

    const tempRadio = document.querySelector(`.temp-opt[value="${item.temp || 'hot'}"]`);
    if (tempRadio) tempRadio.checked = true;

    const sugarRadio = document.querySelector(`.sugar-opt[value="${item.sugar || '50%'}"]`);
    if (sugarRadio) sugarRadio.checked = true;

    const iceRadio = document.querySelector(`.ice-opt[value="${item.ice || 'regular'}"]`);
    if (iceRadio) iceRadio.checked = true;

    document.querySelectorAll('#modalAddons input[type="checkbox"]').forEach(c => {
        const checked = item.addons && item.addons.includes(c.id);
        c.checked = checked;
        const card = c.closest('.addon-card');
        if (card) card.classList.toggle('has-check', checked);
    });

    document.getElementById('modalQty').textContent = item.qty || 1;
    modalQtyVal = item.qty || 1;
    document.getElementById('modalInstructions').value = item.instructions || '';
}

function toggleAddon(el) {
    const cb = el.querySelector('input[type="checkbox"]');
    if (!cb) return;
    cb.checked = !cb.checked;
    el.classList.toggle('has-check', cb.checked);
    updateModalTotal();
}

function collectModalSelections() {
    const size = document.querySelector('.size-opt:checked');
    const temp = document.querySelector('.temp-opt:checked');
    const sugar = document.querySelector('.sugar-opt:checked');
    const ice = document.querySelector('.ice-opt:checked');
    const addons = [];
    let addonTotal = 0;
    document.querySelectorAll('#modalAddons input[type="checkbox"]:checked').forEach(c => {
        addons.push(c.id);
        addonTotal += parseFloat(c.dataset.price) || 0;
    });
    const sizePrice = parseFloat(size ? size.dataset.price : 0) || 0;
    return {
        size: size ? size.value : 'small',
        sizePrice: sizePrice,
        temp: temp ? temp.value : 'hot',
        sugar: sugar ? sugar.value : '50%',
        ice: ice ? ice.value : 'regular',
        addons: addons,
        addonTotal: addonTotal,
        instructions: document.getElementById('modalInstructions').value
    };
}

function modalQtyChange(delta) {
    modalQtyVal = Math.max(1, modalQtyVal + delta);
    document.getElementById('modalQty').textContent = modalQtyVal;
    updateModalTotal();
}

function updateModalTotal() {
    const sizeEl = document.querySelector('.size-opt:checked');
    const sizePrice = parseFloat(sizeEl ? sizeEl.dataset.price : 0) || 0;
    const baseTotal = modalProductPrice * modalQtyVal;
    const sizeTotal = sizePrice * modalQtyVal;
    let addonTotal = 0;
    document.querySelectorAll('#modalAddons input[type="checkbox"]:checked').forEach(c => {
        addonTotal += parseFloat(c.dataset.price) || 0;
    });
    addonTotal *= modalQtyVal;
    const grandTotal = baseTotal + sizeTotal + addonTotal;
    document.getElementById('modalTotal').textContent = '₱' + grandTotal.toFixed(2);
    document.getElementById('osSubtotal').textContent = '₱' + (modalProductPrice * modalQtyVal).toFixed(2);
    document.getElementById('osAddons').textContent = '₱' + addonTotal.toFixed(2);
    document.getElementById('osQty').textContent = modalQtyVal;
}

document.getElementById('modalAddBtn').addEventListener('click', function() {
    if (!modalProductId) return;

    const sel = collectModalSelections();
    const unitPrice = modalProductPrice + sel.sizePrice + sel.addonTotal;

    function makeCartItem(qty) {
        return {
            id: modalProductId,
            name: modalProductName,
            basePrice: modalProductPrice,
            sizePrice: sel.sizePrice,
            addonTotal: sel.addonTotal,
            price: unitPrice,
            qty: qty,
            size: sel.size,
            temp: sel.temp,
            sugar: sel.sugar,
            ice: sel.ice,
            addons: sel.addons,
            instructions: sel.instructions
        };
    }

    if (modalEditIndex != null && cart[modalEditIndex]) {
        cart[modalEditIndex] = makeCartItem(modalQtyVal);
    } else {
        const existingIdx = cart.findIndex(item => item.id === modalProductId
            && item.size === sel.size
            && item.temp === sel.temp
            && item.sugar === sel.sugar
            && item.ice === sel.ice
            && JSON.stringify(item.addons) === JSON.stringify(sel.addons));
        if (existingIdx > -1) {
            cart[existingIdx].qty += modalQtyVal;
        } else {
            cart.push(makeCartItem(modalQtyVal));
        }
    }

    renderCart();

    const card = document.querySelector(`.product-card[data-id="${modalProductId}"]`);
    if (card) {
        card.style.transform = 'scale(0.95)';
        setTimeout(() => card.style.transform = '', 150);
    }

    modalEditIndex = null;
    const modal = bootstrap.Modal.getInstance(document.getElementById('customizeModal'));
    if (modal) modal.hide();
});

function openViewModal(index) {
    const item = cart[index];
    if (!item) return;

    const card = document.querySelector(`.product-card[data-id="${item.id}"]`);
    const imgWrap = document.getElementById('viewProductImg');
    if (card) {
        const img = card.querySelector('.img-wrap img');
        if (img) {
            imgWrap.innerHTML = '<img src="' + img.src + '" alt="' + item.name + '" style="width:100%;height:100%;object-fit:cover;">';
        }
    }

    document.getElementById('viewProductName').textContent = item.name;
    document.getElementById('viewProductPrice').textContent = '₱' + item.price.toFixed(2);

    const sizeLabel = { small:'Small', medium:'Medium', large:'Large' };
    document.getElementById('viewSize').textContent = sizeLabel[item.size] || '—';

    const tempLabel = { hot:'Hot', iced:'Iced' };
    document.getElementById('viewTemp').textContent = tempLabel[item.temp] || '—';

    document.getElementById('viewSugar').textContent = item.sugar || '—';

    const iceLabel = { none:'No Ice', less:'Less Ice', regular:'Regular Ice', extra:'Extra Ice' };
    document.getElementById('viewIce').textContent = iceLabel[item.ice] || '—';

    const addonNames = { addon_extra_shot:'Extra Shot', addon_vanilla:'Vanilla Syrup', addon_caramel:'Caramel Sauce', addon_whipped_cream:'Whipped Cream', addon_soy_milk:'Soy Milk', addon_almond_milk:'Almond Milk' };
    const addons = (item.addons || []).map(a => addonNames[a] || a);
    document.getElementById('viewAddons').textContent = addons.length ? addons.join(', ') : 'None';

    document.getElementById('viewQty').textContent = item.qty;
    document.getElementById('viewInstructions').textContent = item.instructions || 'None';
    document.getElementById('viewSubtotal').textContent = '₱' + (item.price * item.qty).toFixed(2);

    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}

function editCartItem(index) {
    const item = cart[index];
    if (!item) return;
    openCustomizationModal(item.id, item.name, item.basePrice || item.price, index);
}

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, basePrice: price, sizePrice: 0, addonTotal: 0, price: price, qty: 1 });
    }
    renderCart();

    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    if (card) {
        card.style.transform = 'scale(0.95)';
        setTimeout(() => card.style.transform = '', 150);
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, delta) {
    const item = cart[index];
    item.qty += delta;
    if (item.qty <= 0) {
        cart.splice(index, 1);
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty = document.getElementById('cartEmpty');
    const summary = document.getElementById('cartSummary');
    const count = document.getElementById('cartCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
    count.textContent = totalItems;

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty" id="cartEmpty"><i class="bi bi-cart-x"></i><p>Select products to start an order</p></div>';
        summary.style.display = 'none';
        checkoutBtn.disabled = true;
        return;
    }

    summary.style.display = 'block';
    checkoutBtn.disabled = false;

    let html = '';
    cart.forEach((item, i) => {
        const lineTotal = item.price * item.qty;
        const details = [];
        if (item.size) details.push(item.size.charAt(0).toUpperCase() + item.size.slice(1));
        if (item.temp) details.push(item.temp.charAt(0).toUpperCase() + item.temp.slice(1));
        if (item.sugar) details.push('Sugar: ' + item.sugar);
        if (item.ice) details.push('Ice: ' + item.ice.replace('none','No').replace('less','Less').replace('regular','Regular').replace('extra','Extra'));
        if (item.addons && item.addons.length) details.push('+' + item.addons.length + ' add-on' + (item.addons.length > 1 ? 's' : ''));
        html += `
            <div class="cart-item">
                <button class="remove-btn" onclick="removeFromCart(${i})" title="Remove"><i class="bi bi-x"></i></button>
                <div class="cart-item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">₱${item.price.toFixed(2)} <span class="item-details">${details.join(' | ')}</span></div>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="updateQty(${i}, -1)">−</button>
                    <span class="qty-val">${item.qty}</span>
                    <button class="qty-btn" onclick="updateQty(${i}, 1)">+</button>
                </div>
                <div class="cart-item-total">₱${lineTotal.toFixed(2)}</div>
                <div class="cart-actions">
                    <button class="action-btn" onclick="openViewModal(${i})" title="View Details"><i class="bi bi-eye"></i></button>
                    <button class="action-btn" onclick="editCartItem(${i})" title="Edit"><i class="bi bi-pencil-square"></i></button>
                </div>
            </div>
        `;
    });
    container.innerHTML = html;

    updateSummary();
}

function updateSummary() {
    const total = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const vatExclusive = total / 1.12;
    const tax = total - vatExclusive;

    document.getElementById('subtotal').textContent = '₱' + total.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
}

function buildReviewCardHtml(item, i, getImgFn, sizeLbl, tempLbl, iceLbl) {
    const addonNames = { addon_extra_shot:'Extra Shot', addon_vanilla:'Vanilla Syrup', addon_caramel:'Caramel Sauce', addon_whipped_cream:'Whipped Cream', addon_soy_milk:'Soy Milk', addon_almond_milk:'Almond Milk' };
    const addons = (item.addons || []).map(a => addonNames[a] || a);
    return `
        <div class="review-card">
            <div class="review-card-body">
                <div class="review-card-img">${getImgFn(item.id)}</div>
                <div class="review-card-info">
                    <div class="review-card-name">${item.name}</div>
                    <div class="review-card-grid">
                        <span class="rc-label">Size</span><span class="rc-value">${sizeLbl[item.size] || item.size}</span>
                        <span class="rc-label">Temperature</span><span class="rc-value">${tempLbl[item.temp] || item.temp}</span>
                        <span class="rc-label">Sugar Level</span><span class="rc-value">${item.sugar}</span>
                        <span class="rc-label">Ice Level</span><span class="rc-value">${iceLbl[item.ice] || item.ice}</span>
                        <span class="rc-label">Add-ons</span><span class="rc-value">${addons.length ? addons.join(', ') : 'None'}</span>
                    </div>
                </div>
            </div>
            <div class="review-card-footer">
                <span class="rc-qty">Qty: ${item.qty}</span>
                <span class="rc-price">₱${item.price.toFixed(2)} each</span>
                <span class="rc-subtotal">₱${(item.price * item.qty).toFixed(2)}</span>
                <span class="rc-actions">
                    <button class="rc-btn rc-edit" onclick="editCartItem(${i}); reopenReviewModal = true;" title="Edit"><i class="bi bi-pencil-square"></i></button>
                    <button class="rc-btn rc-remove" onclick="removeFromCart(${i}); refreshReviewBody();" title="Remove"><i class="bi bi-trash3"></i></button>
                </span>
            </div>
        </div>`;
}

function getItemImgHtml(id) {
    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    if (card) {
        const img = card.querySelector('.img-wrap img');
        if (img) return '<img src="' + img.src + '" alt="">';
    }
    return '<svg viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>';
}

function renderReviewBody() {
    const sizeLabel = { small:'Small', medium:'Medium', large:'Large' };
    const tempLabel = { hot:'Hot', iced:'Iced' };
    const iceLabel = { none:'No Ice', less:'Less Ice', regular:'Regular Ice', extra:'Extra Ice' };
    let itemsHtml = '';
    cart.forEach((item, i) => {
        itemsHtml += buildReviewCardHtml(item, i, getItemImgHtml, sizeLabel, tempLabel, iceLabel);
    });
    const total = cart.reduce((s, item) => s + item.price * item.qty, 0);
    const vatExclusive = total / 1.12;
    const tax = total - vatExclusive;
    return `
        <div style="margin-bottom:8px;">${itemsHtml}</div>
        <div class="card review-summary-card">
            <div class="card-body">
                <div class="rsc-header">Order Summary</div>
                <div class="rsc-row"><span>Subtotal</span><span id="reviewSubtotal">₱${total.toFixed(2)}</span></div>
                <div class="rsc-row"><span>Discount</span><span id="reviewDiscountDisplay">₱0.00</span></div>
                <div class="rsc-row"><span>VAT</span><span id="reviewVat">₱${tax.toFixed(2)}</span></div>
                <div class="rsc-row rsc-grand-total"><span>Grand Total</span><span id="reviewGrandTotal">₱${total.toFixed(2)}</span></div>
            </div>
        </div>

        <div class="card review-section-card">
            <div class="card-body">
                <div class="rsc-header">Discount</div>
                <select class="review-select" id="discountType" onchange="onDiscountTypeChange()">
                    <option value="none">None</option>
                    <option value="senior">Senior Citizen</option>
                    <option value="pwd">PWD</option>
                    <option value="student">Student</option>
                    <option value="employee">Employee</option>
                    <option value="promo">Promo Code</option>
                </select>
                <div class="promo-row" id="promoCodeRow" style="display:none;">
                    <input type="text" class="review-input" id="promoCodeInput" placeholder="Enter promo code">
                    <button class="review-apply-btn" onclick="applyPromoCode()">Apply</button>
                </div>
            </div>
        </div>

        <div class="card review-section-card">
            <div class="card-body">
                <div class="rsc-header">Customer Information</div>
                <div class="cust-type-row">
                    <label class="cust-radio"><input type="radio" name="custType" value="walkin" checked onchange="onCustTypeChange()"> Walk-in Customer</label>
                    <label class="cust-radio"><input type="radio" name="custType" value="registered" onchange="onCustTypeChange()"> Registered Customer</label>
                </div>
                <div class="promo-row" id="customerSearchRow" style="display:none;">
                    <input type="text" class="review-input" id="customerSearchInput" placeholder="Search by name, email, or phone">
                </div>
            </div>
        </div>`;
}

function openReviewModal() {
    document.getElementById('reviewModalBody').innerHTML = renderReviewBody();
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

function refreshReviewBody() {
    const modalEl = document.getElementById('reviewModal');
    if (!modalEl.classList.contains('show')) return;
    document.getElementById('reviewModalBody').innerHTML = renderReviewBody();
}

function onDiscountTypeChange() {
    const val = document.getElementById('discountType').value;
    document.getElementById('promoCodeRow').style.display = val === 'promo' ? 'flex' : 'none';
}

function applyPromoCode() {
    const code = document.getElementById('promoCodeInput').value.trim();
    if (!code) return;
}

function onCustTypeChange() {
    const val = document.querySelector('input[name="custType"]:checked').value;
    document.getElementById('customerSearchRow').style.display = val === 'registered' ? 'flex' : 'none';
}

document.getElementById('reviewProceedBtn').addEventListener('click', function() {
    function readVal(id) {
        const el = document.getElementById(id);
        return el ? parseFloat(el.textContent.replace(/[₱,]/g, '')) || 0 : 0;
    }
    const subtotal = readVal('reviewSubtotal');
    const discount = readVal('reviewDiscountDisplay');
    const tax = readVal('reviewVat');
    const grandTotal = readVal('reviewGrandTotal');

    document.getElementById('pmtSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('pmtDiscount').textContent = '₱' + discount.toFixed(2);
    document.getElementById('pmtTax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('pmtGrandTotal').textContent = '₱' + grandTotal.toFixed(2);
    document.getElementById('pmtGrandTotalDisplay').textContent = '₱' + grandTotal.toFixed(2);
    document.getElementById('cashReceived').value = '';
    document.getElementById('changeDisplay').textContent = '₱0.00';

    const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
    if (reviewModal) reviewModal.hide();
    const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
});

function onPmtMethodChange() {
    const cash = document.getElementById('pmtCash').checked;
    document.getElementById('pmtCashSection').style.display = cash ? 'block' : 'none';
    if (!cash) {
        document.getElementById('cashReceived').value = '';
        document.getElementById('changeDisplay').textContent = '₱0.00';
    }
}

function onCashReceivedChange() {
    const gtEl = document.getElementById('pmtGrandTotal');
    const grandTotal = parseFloat(gtEl.textContent.replace(/[₱,]/g, '')) || 0;
    const received = parseFloat(document.getElementById('cashReceived').value) || 0;
    const change = Math.max(0, received - grandTotal);
    document.getElementById('changeDisplay').textContent = '₱' + change.toFixed(2);
}

document.getElementById('processPaymentBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

    const gtEl = document.getElementById('pmtGrandTotal');
    const grandTotal = parseFloat(gtEl.textContent.replace(/[₱,]/g, '')) || 0;
    const pmtMethod = document.querySelector('input[name="pmtMethod"]:checked');
    const discountType = document.getElementById('discountType') ? document.getElementById('discountType').value : 'none';
    const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
    const changeDue = parseFloat(document.getElementById('changeDisplay').textContent.replace(/[₱,]/g, '')) || 0;
    const custType = document.querySelector('input[name="custType"]:checked');
    const customerName = (custType && custType.value === 'registered' && document.getElementById('customerSearchInput').value.trim())
        ? document.getElementById('customerSearchInput').value.trim() : 'Walk-in Customer';

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' }) + ' ' + now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });
    const receiptNum = 'POS-' + now.getFullYear() + String(now.getMonth()+1).padStart(2,'0') + String(now.getDate()).padStart(2,'0') + '-' + String(Math.floor(Math.random() * 9999) + 1).padStart(4,'0');

    const items = cart.map(item => ({
        id: item.id,
        name: item.name,
        qty: item.qty,
        unit_price: item.price,
        size: item.size,
        temp: item.temp,
        sugar: item.sugar,
        ice: item.ice,
        instructions: item.instructions,
        addon_ids: item.addons || []
    }));

    function readVal(id) {
        const el = document.getElementById(id);
        return el ? parseFloat(el.textContent.replace(/[₱,]/g, '')) || 0 : 0;
    }

    fetch('pos.php?action=save_order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            items: items,
            customer_name: customerName,
            payment_method: pmtMethod ? pmtMethod.value : 'cash',
            cash_received: cashReceived || null,
            change_due: changeDue || null,
            discount_type: discountType !== 'none' && discountType !== 'promo' ? discountType : null,
            discount_amount: discountType !== 'none' ? readVal('reviewDiscountDisplay') : 0,
            reference_number: receiptNum,
            receipt_number: receiptNum,
            grand_total: grandTotal
        })
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            document.getElementById('sReceipt').textContent = receiptNum;
            document.getElementById('sDate').textContent = dateStr;
            document.getElementById('sTotal').textContent = '₱' + grandTotal.toFixed(2);

            const pmtModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            if (pmtModal) pmtModal.hide();
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        } else {
            alert('Error saving order: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Error saving order: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-2"></i>Process Payment';
    });
});

document.getElementById('newOrderBtn').addEventListener('click', function() {
    cart = [];
    renderCart();
    const successModal = bootstrap.Modal.getInstance(document.getElementById('successModal'));
    if (successModal) successModal.hide();
    const pmtModal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
    if (pmtModal) pmtModal.hide();
});

document.getElementById('printReceiptBtn').addEventListener('click', function() {
});

let reopenReviewModal = false;
document.getElementById('customizeModal').addEventListener('hidden.bs.modal', function() {
    if (reopenReviewModal) {
        reopenReviewModal = false;
        setTimeout(() => openReviewModal(), 150);
    }
});

document.getElementById('checkoutBtn').addEventListener('click', function() {
    if (cart.length === 0) return;
    openReviewModal();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
