<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_kitchen_orders') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT o.order_id, o.customer_name, o.total_amount, o.status, o.created_at,
                   u.full_name AS cashier_name,
                   t.receipt_number, t.payment_method
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN transactions t ON o.order_id = t.order_id
            WHERE o.status IN ('pending', 'preparing', 'ready')
            ORDER BY o.created_at ASC
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['order_id']]);
            $items = $stmt->fetchAll();

            foreach ($items as &$item) {
                $stmt = $pdo->prepare("SELECT * FROM order_addons WHERE order_item_id = ?");
                $stmt->execute([$item['order_item_id']]);
                $item['addons'] = $stmt->fetchAll();
            }

            $order['items'] = $items;
        }
        unset($order, $item);

        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_status') {
    header('Content-Type: application/json');
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $orderId = (int)($data['order_id'] ?? 0);
        $status = $data['status'] ?? '';
        $allowed = ['pending', 'preparing', 'ready', 'completed'];
        if (!$orderId || !in_array($status, $allowed)) {
            throw new Exception('Invalid parameters');
        }
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $orderId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_order_history') {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->query("
            SELECT o.order_id, o.customer_name, o.total_amount, o.status, o.created_at,
                   u.full_name AS cashier_name,
                   t.receipt_number, t.payment_method, t.discount_type, t.discount_amount
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.user_id
            LEFT JOIN transactions t ON o.order_id = t.order_id
            WHERE o.status = 'completed'
            ORDER BY o.created_at DESC
            LIMIT 100
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['order_id']]);
            $items = $stmt->fetchAll();

            foreach ($items as &$item) {
                $stmt = $pdo->prepare("SELECT * FROM order_addons WHERE order_item_id = ?");
                $stmt->execute([$item['order_item_id']]);
                $item['addons'] = $stmt->fetchAll();
            }

            $order['items'] = $items;
        }
        unset($order, $item);

        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$page_title = 'Kitchen Queue';
$body_class = 'kitchen-queue-page';
require_once __DIR__ . '/../../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
:root {
    --kq-bg: #1a0f0a;
    --kq-card-bg: #2c1810;
    --kq-border: rgba(255,255,255,0.06);
    --kq-text: #f5f0eb;
    --kq-muted: rgba(255,255,255,0.4);
    --kq-gold: #c8a96e;
    --kq-gold-light: rgba(200,169,110,0.15);
    --kq-pending: #f1c40f;
    --kq-preparing: #3498db;
    --kq-ready: #2ecc71;
}

* { box-sizing: border-box; }

html, body {
    height: 100%;
    margin: 0;
    font-family: 'Inter', sans-serif;
    background: var(--kq-bg);
    color: var(--kq-text);
}

.kq-header {
    background: linear-gradient(135deg, #2c1810, #3e2723);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
}
.kq-header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.kq-header-left .kq-logo {
    width: 40px;
    height: 40px;
    background: var(--kq-gold);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a0f0a;
    font-size: 20px;
}
.kq-header-left h1 {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--kq-text);
    margin: 0;
}
.kq-header-left .kq-sub {
    font-size: 11px;
    color: var(--kq-muted);
    margin: 0;
}
.kq-header-center {
    text-align: center;
}
.kq-header-center h2 {
    font-size: 22px;
    font-weight: 700;
    color: var(--kq-gold);
    margin: 0;
    letter-spacing: 1px;
}
.kq-header-center .kq-count {
    font-size: 12px;
    color: var(--kq-muted);
}
.kq-header-right {
    display: flex;
    align-items: center;
    gap: 10px;
}
.kq-header-right .kq-clock {
    font-size: 14px;
    font-weight: 600;
    color: var(--kq-muted);
    font-variant-numeric: tabular-nums;
}
.kq-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    background: rgba(255,255,255,0.06);
    color: var(--kq-muted);
    text-decoration: none;
    font-size: 12px;
    font-weight: 500;
    transition: all 0.15s;
}
.kq-back-btn:hover {
    background: rgba(255,255,255,0.1);
    color: var(--kq-text);
}

.kq-container {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px 24px;
}

.kq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}

.kq-card {
    background: var(--kq-card-bg);
    border: 1px solid var(--kq-border);
    border-radius: 14px;
    padding: 20px;
    transition: all 0.15s;
    display: flex;
    flex-direction: column;
}
.kq-card:hover {
    border-color: rgba(200,169,110,0.2);
}
.kq-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.kq-order-num {
    font-size: 48px;
    font-weight: 900;
    color: var(--kq-gold);
    line-height: 1;
    letter-spacing: 1px;
}
.kq-receipt {
    font-size: 11px;
    color: var(--kq-muted);
    background: rgba(255,255,255,0.04);
    padding: 3px 10px;
    border-radius: 6px;
    font-weight: 500;
}
.kq-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 16px;
    margin-bottom: 12px;
    font-size: 12px;
    color: var(--kq-muted);
}
.kq-meta i {
    margin-right: 4px;
    color: rgba(255,255,255,0.2);
}
.kq-meta strong {
    color: rgba(255,255,255,0.6);
    font-weight: 600;
}
.kq-items {
    flex: 1;
    margin-bottom: 12px;
}
.kq-item {
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.kq-item:last-child {
    border-bottom: none;
}
.kq-item-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--kq-text);
}
.kq-item-qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    padding: 0 6px;
    border-radius: 6px;
    background: var(--kq-gold-light);
    color: var(--kq-gold);
    font-size: 13px;
    font-weight: 700;
    margin-right: 8px;
}
.kq-item-cust {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
    margin-left: 34px;
}
.kq-cust-tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 5px;
    background: rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.5);
    font-size: 12px;
    font-weight: 500;
}
.kq-addon-tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 5px;
    background: rgba(200,169,110,0.08);
    color: var(--kq-gold);
    font-size: 12px;
    font-weight: 500;
}
.kq-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.kq-total {
    font-size: 22px;
    font-weight: 800;
    color: var(--kq-gold);
}
.kq-status-group {
    display: flex;
    gap: 6px;
    align-items: center;
}
.kq-status-btn {
    padding: 6px 14px;
    border-radius: 8px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    cursor: pointer;
    transition: all 0.15s;
    background: rgba(255,255,255,0.04);
    color: var(--kq-muted);
}
.kq-status-btn:hover {
    background: rgba(255,255,255,0.08);
}
.kq-status-btn.active {
    cursor: default;
}
.kq-status-btn.active.pending {
    background: rgba(241,196,15,0.15);
    color: #f1c40f;
}
.kq-status-btn.active.preparing {
    background: rgba(52,152,219,0.15);
    color: #3498db;
}
.kq-status-btn.active.ready {
    background: rgba(46,204,113,0.15);
    color: #2ecc71;
}
.kq-status-btn.next {
    background: var(--kq-gold-light);
    color: var(--kq-gold);
}
.kq-status-btn.next:hover {
    background: rgba(200,169,110,0.25);
}

.kq-empty {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    color: var(--kq-muted);
}
.kq-empty i {
    font-size: 56px;
    display: block;
    margin-bottom: 16px;
    color: rgba(255,255,255,0.06);
}
.kq-empty h3 {
    font-size: 20px;
    color: rgba(255,255,255,0.3);
    margin-bottom: 6px;
}
.kq-empty p {
    font-size: 14px;
    color: rgba(255,255,255,0.15);
}

@media (max-width: 768px) {
    .kq-header {
        flex-direction: column;
        gap: 8px;
        padding: 12px 16px;
    }
    .kq-header-left h1 { font-size: 16px; }
    .kq-header-center h2 { font-size: 18px; }
    .kq-grid {
        grid-template-columns: 1fr;
    }
    .kq-order-num {
        font-size: 28px;
    }
    .kq-card {
        padding: 14px;
    }
}
.kq-modal-content {
    background: #2c1810;
    color: #f5f0eb;
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 14px;
}
.kq-modal-content .modal-header {
    border-bottom: 1px solid rgba(255,255,255,0.06);
    padding: 16px 20px;
}
.kq-modal-content .modal-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--kq-gold);
}
.kq-modal-content .modal-body {
    padding: 16px 20px;
}
.kq-modal-content .btn-close-white {
    opacity: 0.5;
}
.hist-item {
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.hist-item:last-child {
    border-bottom: none;
}
.hist-item-name {
    font-size: 13px;
    font-weight: 600;
}
.hist-item-qty {
    color: var(--kq-gold);
    font-weight: 700;
}
.hist-item-cust {
    font-size: 11px;
    color: rgba(255,255,255,0.45);
    margin-left: 8px;
    margin-top: 2px;
}
.hist-addon-tag {
    display: inline-block;
    padding: 1px 6px;
    border-radius: 4px;
    background: rgba(200,169,110,0.1);
    color: var(--kq-gold);
    font-size: 10px;
    font-weight: 500;
    margin-right: 4px;
}
</style>

<div class="kq-header">
    <div class="kq-header-left">
        <div class="kq-logo"><i class="bi bi-cup-hot-fill"></i></div>
        <div>
            <h1>Brew & Bean</h1>
            <p class="kq-sub">Brewing Excellence</p>
        </div>
    </div>
    <div class="kq-header-center">
        <h2><i class="bi bi-clipboard-check me-2"></i>Kitchen Queue</h2>
        <div class="kq-count"><span id="kqCount">0</span> order(s) waiting</div>
    </div>
    <div class="kq-header-right">
        <span class="kq-clock" id="kqClock"></span>
        <button class="kq-back-btn" onclick="openOrderHistory()"><i class="bi bi-clock-history me-1"></i>Order History</button>
        <a href="pos.php" class="kq-back-btn"><i class="bi bi-arrow-left"></i>Back to POS</a>
    </div>
</div>

<div class="kq-container">
    <div class="kq-grid" id="kqGrid"></div>
</div>

<div class="modal fade" id="orderHistoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content kq-modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Order History</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderHistoryBody"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const statusLabels = { pending: 'Pending', preparing: 'Preparing', ready: 'Ready for Pickup', completed: 'Completed' };
const nextStatus = { pending: 'preparing', preparing: 'ready', ready: 'completed' };

function fetchKitchenOrders() {
    fetch(window.location.pathname + '?action=get_kitchen_orders')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderKitchenOrders(data.orders);
        })
        .catch(() => {});
}

function renderKitchenOrders(orders) {
    const grid = document.getElementById('kqGrid');
    document.getElementById('kqCount').textContent = orders.length;

    if (!orders.length) {
        grid.innerHTML = '<div class="kq-empty"><i class="bi bi-emoji-smile"></i><h3>All caught up!</h3><p>No orders waiting in the queue.</p></div>';
        return;
    }

    let html = '';
    orders.forEach(o => {
        const items = o.items || [];
        const total = parseFloat(o.total_amount || 0).toFixed(2);
        const status = o.status || 'pending';
        const dt = o.created_at || '';
        const rec = o.receipt_number || '—';

        let itemsHtml = '';
        items.forEach(item => {
            const qty = item.quantity || 1;
            const name = item.product_name || 'Product';
            const custParts = [];
            if (item.size) custParts.push(item.size);
            if (item.temperature) custParts.push(item.temperature);
            if (item.sugar_level && item.sugar_level !== 'regular') custParts.push('Sugar: ' + item.sugar_level);
            if (item.ice_level && item.ice_level !== 'regular') custParts.push('Ice: ' + item.ice_level);
            if (item.instructions) custParts.push(item.instructions);

            itemsHtml += '<div class="kq-item">'
                + '<div class="kq-item-name"><span class="kq-item-qty">' + qty + '</span>' + name + '</div>';
            if (custParts.length) {
                itemsHtml += '<div class="kq-item-cust">';
                custParts.forEach(c => { itemsHtml += '<span class="kq-cust-tag">' + c + '</span>'; });
                itemsHtml += '</div>';
            }
            const addons = item.addons || [];
            if (addons.length) {
                itemsHtml += '<div class="kq-item-cust">';
                addons.forEach(a => { itemsHtml += '<span class="kq-addon-tag">+' + a.addon_name + '</span>'; });
                itemsHtml += '</div>';
            }
            itemsHtml += '</div>';
        });

        const next = nextStatus[status];
        let statusHtml = '';
        if (status === 'ready') {
            statusHtml = '<button class="kq-status-btn active ready">Ready <i class="bi bi-check2-circle ms-1"></i></button>'
                + '<button class="kq-status-btn next" onclick="updateKitchenStatus(' + o.order_id + ', \'completed\')"><i class="bi bi-check2-all"></i></button>';
        } else if (status === 'preparing') {
            statusHtml = '<button class="kq-status-btn active preparing"><i class="bi bi-hourglass-split me-1"></i>Preparing</button>'
                + '<button class="kq-status-btn next" onclick="updateKitchenStatus(' + o.order_id + ', \'ready\')">Ready?</button>';
        } else {
            statusHtml = '<button class="kq-status-btn active pending">Pending</button>'
                + '<button class="kq-status-btn next" onclick="updateKitchenStatus(' + o.order_id + ', \'preparing\')">Start</button>';
        }

        html += '<div class="kq-card" id="kqCard-' + o.order_id + '">'
            + '<div class="kq-card-header">'
            + '<span class="kq-order-num">Q' + String(o.order_id).padStart(3, '0') + '</span>'
            + '<span class="kq-receipt">' + rec + '</span>'
            + '</div>'
            + '<div class="kq-meta">'
            + '<span><i class="bi bi-person"></i><strong>' + (o.customer_name || 'Walk-in') + '</strong></span>'
            + '<span><i class="bi bi-person-badge"></i>' + (o.cashier_name || 'Unknown') + '</span>'
            + '<span><i class="bi bi-clock"></i>' + dt + '</span>'
            + '</div>'
            + '<div class="kq-items">' + itemsHtml + '</div>'
            + '<div class="kq-card-footer">'
            + '<span class="kq-total">₱' + total + '</span>'
            + '<div class="kq-status-group">' + statusHtml + '</div>'
            + '</div>'
            + '</div>';
    });
    grid.innerHTML = html;
}

function updateKitchenStatus(orderId, status) {
    const card = document.getElementById('kqCard-' + orderId);
    if (card) card.style.opacity = '0.4';

    fetch(window.location.pathname + '?action=update_status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ order_id: orderId, status: status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (status === 'completed') {
                setTimeout(fetchKitchenOrders, 400);
            } else {
                if (card) card.style.opacity = '1';
                fetchKitchenOrders();
            }
        }
    })
    .catch(() => {});
}

function updateClock() {
    const now = new Date();
    const time = now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    const date = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    document.getElementById('kqClock').textContent = date + ' ' + time;
}

/* ── Order History ── */
let historyModalInstance = null;

function openOrderHistory() {
    if (!historyModalInstance) {
        historyModalInstance = new bootstrap.Modal(document.getElementById('orderHistoryModal'));
    }
    document.getElementById('orderHistoryBody').innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.3);"><div class="spinner-border" role="status"></div><p class="mt-2">Loading history...</p></div>';
    historyModalInstance.show();
    fetchOrderHistory();
}

function fetchOrderHistory() {
    fetch(window.location.pathname + '?action=get_order_history')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            renderOrderHistory(data.orders);
        })
        .catch(() => {});
}

function formatCustomizations(item) {
    const parts = [];
    if (item.size) parts.push(item.size);
    if (item.temperature) parts.push(item.temperature);
    if (item.sugar_level && item.sugar_level !== 'regular') parts.push('Sugar: ' + item.sugar_level);
    if (item.ice_level && item.ice_level !== 'regular') parts.push('Ice: ' + item.ice_level);
    if (item.instructions) parts.push('Note: ' + item.instructions);
    return parts.length ? parts.join(' &middot; ') : '';
}

function renderOrderHistory(orders) {
    const container = document.getElementById('orderHistoryBody');
    if (!orders.length) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.2);"><i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;"></i><p>No completed orders yet.</p></div>';
        return;
    }
    let html = '';
    orders.forEach(o => {
        const items = o.items || [];
        const grandTotal = parseFloat(o.total_amount || 0).toFixed(2);
        const dt = o.created_at || '';
        const pmt = o.payment_method ? o.payment_method.charAt(0).toUpperCase() + o.payment_method.slice(1) : '—';
        const rec = o.receipt_number || '—';
        const cashier = o.cashier_name || 'Unknown';
        const customer = o.customer_name || 'Walk-in';
        const qnum = String(o.order_id).padStart(3, '0');

        let itemsHtml = '';
        items.forEach(item => {
            const qty = item.quantity || 1;
            const price = parseFloat(item.price || 0).toFixed(2);
            const cust = formatCustomizations(item);
            const addons = item.addons || [];
            let addonHtml = '';
            addons.forEach(a => {
                addonHtml += '<span class="hist-addon-tag">+' + a.addon_name + ' (₱' + parseFloat(a.price || 0).toFixed(2) + ')</span>';
            });

            itemsHtml += '<div class="hist-item">'
                + '<div><span class="hist-item-name">' + (item.product_name || 'Product') + ' <span class="hist-item-qty">x' + qty + '</span></span> <span style="float:right;font-weight:600;">₱' + (qty * price).toFixed(2) + '</span></div>';
            if (cust) {
                itemsHtml += '<div class="hist-item-cust">' + cust + '</div>';
            }
            if (addonHtml) {
                itemsHtml += '<div class="hist-item-cust">' + addonHtml + '</div>';
            }
            itemsHtml += '</div>';
        });

        html += '<div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:14px;margin-bottom:10px;">'
            + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">'
            + '<div><span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:22px;padding:0 7px;border-radius:5px;background:var(--kq-gold);color:#1a0f0a;font-size:12px;font-weight:800;margin-right:8px;">Q' + qnum + '</span><span style="font-size:11px;color:rgba(255,255,255,0.35);">' + rec + '</span></div>'
            + '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;padding:2px 8px;border-radius:4px;background:rgba(46,204,113,0.15);color:#2ecc71;">Completed</span>'
            + '</div>'
            + '<div style="display:flex;gap:12px;flex-wrap:wrap;font-size:11px;color:rgba(255,255,255,0.45);margin-bottom:8px;">'
            + '<span><i class="bi bi-person me-1"></i>' + customer + '</span>'
            + '<span><i class="bi bi-person-badge me-1"></i>' + cashier + '</span>'
            + '<span><i class="bi bi-credit-card me-1"></i>' + pmt + '</span>'
            + '<span><i class="bi bi-clock me-1"></i>' + dt + '</span>'
            + '</div>'
            + '<div style="border-top:1px solid rgba(255,255,255,0.06);padding-top:8px;">' + itemsHtml + '</div>'
            + '<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid rgba(255,255,255,0.06);">'
            + '<span style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#2ecc71;padding:2px 8px;border-radius:4px;background:rgba(46,204,113,0.1);">Paid</span>'
            + '<span style="font-size:18px;font-weight:800;color:var(--kq-gold);">₱' + grandTotal + '</span>'
            + '</div>'
            + '</div>';
    });
    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    fetchKitchenOrders();
    setInterval(fetchKitchenOrders, 10000);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
