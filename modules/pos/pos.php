<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

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

.cart-item .remove-btn {
    background: none;
    border: none;
    color: rgba(0,0,0,0.15);
    cursor: pointer;
    padding: 2px;
    font-size: 16px;
    line-height: 1;
    transition: color 0.15s;
}

.cart-item .remove-btn:hover {
    color: #c62828;
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

.size-opt:checked + .size-label,
.temp-opt:checked + .temp-label,
.sugar-opt:checked + .sugar-label,
.ice-opt:checked + .ice-label {
    background: rgba(200,169,110,0.1) !important;
    border-color: #c8a96e !important;
    color: #2c1810 !important;
    box-shadow: none !important;
}
.size-label:focus,
.temp-label:focus,
.sugar-label:focus,
.ice-label:focus {
    box-shadow: none !important;
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
            <div class="product-card<?php echo $available ? '' : ' unavailable'; ?>" data-id="<?php echo $product['product_id']; ?>" data-category="<?php echo $product['category_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>" onclick="<?php echo $available ? "openCustomizationModal({$product['product_id']}, '" . htmlspecialchars(addslashes($product['name'])) . "', {$product['price']})" : ''; ?>">
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.2);overflow:hidden;">
            <div class="modal-header" style="border:none;background:#faf7f3;padding:20px 24px 12px;">
                <h5 class="modal-title" style="font-family:'Playfair Display',serif;font-weight:700;color:#2c1810;font-size:18px;">
                    <i class="bi bi-pencil-square me-2"></i>Customize Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 24px 8px;max-height:70vh;">

                <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid #f0ebe5;">
                    <div id="modalProductImg" style="width:64px;height:64px;border-radius:12px;background:#f5f0eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                    </div>
                    <div>
                        <h6 id="modalProductName" style="font-size:15px;font-weight:700;color:#2c1810;margin:0 0 2px;"></h6>
                        <span id="modalProductPrice" style="font-size:17px;font-weight:700;color:#c8a96e;"></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Size</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check size-opt" name="size" id="size-small" value="small" checked>
                        <label class="btn btn-outline-secondary size-label" for="size-small" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:8px 0 0 8px;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Small</label>
                        <input type="radio" class="btn-check size-opt" name="size" id="size-medium" value="medium">
                        <label class="btn btn-outline-secondary size-label" for="size-medium" style="padding:7px 12px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Medium</label>
                        <input type="radio" class="btn-check size-opt" name="size" id="size-large" value="large">
                        <label class="btn btn-outline-secondary size-label" for="size-large" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:0 8px 8px 0;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Large</label>
                    </div>
                </div>

                <div class="mb-3" id="tempSection">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Temperature</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check temp-opt" name="temp" id="temp-hot" value="hot" checked>
                        <label class="btn btn-outline-secondary temp-label" for="temp-hot" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:8px 0 0 8px;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Hot</label>
                        <input type="radio" class="btn-check temp-opt" name="temp" id="temp-iced" value="iced">
                        <label class="btn btn-outline-secondary temp-label" for="temp-iced" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:0 8px 8px 0;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Iced</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Sugar Level</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check sugar-opt" name="sugar" id="sugar-0" value="0%">
                        <label class="btn btn-outline-secondary sugar-label" for="sugar-0" style="padding:7px 6px;font-size:12px;font-weight:600;border-radius:8px 0 0 8px;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">0%</label>
                        <input type="radio" class="btn-check sugar-opt" name="sugar" id="sugar-25" value="25%">
                        <label class="btn btn-outline-secondary sugar-label" for="sugar-25" style="padding:7px 6px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">25%</label>
                        <input type="radio" class="btn-check sugar-opt" name="sugar" id="sugar-50" value="50%" checked>
                        <label class="btn btn-outline-secondary sugar-label" for="sugar-50" style="padding:7px 6px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">50%</label>
                        <input type="radio" class="btn-check sugar-opt" name="sugar" id="sugar-75" value="75%">
                        <label class="btn btn-outline-secondary sugar-label" for="sugar-75" style="padding:7px 6px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">75%</label>
                        <input type="radio" class="btn-check sugar-opt" name="sugar" id="sugar-100" value="100%">
                        <label class="btn btn-outline-secondary sugar-label" for="sugar-100" style="padding:7px 6px;font-size:12px;font-weight:600;border-radius:0 8px 8px 0;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">100%</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Ice Level</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check ice-opt" name="ice" id="ice-none" value="none">
                        <label class="btn btn-outline-secondary ice-label" for="ice-none" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:8px 0 0 8px;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">No Ice</label>
                        <input type="radio" class="btn-check ice-opt" name="ice" id="ice-less" value="less">
                        <label class="btn btn-outline-secondary ice-label" for="ice-less" style="padding:7px 12px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Less Ice</label>
                        <input type="radio" class="btn-check ice-opt" name="ice" id="ice-regular" value="regular" checked>
                        <label class="btn btn-outline-secondary ice-label" for="ice-regular" style="padding:7px 12px;font-size:12px;font-weight:600;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Regular Ice</label>
                        <input type="radio" class="btn-check ice-opt" name="ice" id="ice-extra" value="extra">
                        <label class="btn btn-outline-secondary ice-label" for="ice-extra" style="padding:7px 12px;font-size:12px;font-weight:600;border-radius:0 8px 8px 0;border-color:#e0d5c9;color:#5d4037;transition:all 0.15s;">Extra Ice</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Add-ons</label>
                    <div class="row g-2" id="modalAddons">
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_extra_shot" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_extra_shot" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Extra Shot <span style="color:#c8a96e;font-weight:600;">+₱25</span></label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_vanilla" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_vanilla" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Vanilla Syrup <span style="color:#c8a96e;font-weight:600;">+₱15</span></label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_caramel" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_caramel" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Caramel Sauce <span style="color:#c8a96e;font-weight:600;">+₱15</span></label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_whipped_cream" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_whipped_cream" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Whipped Cream <span style="color:#c8a96e;font-weight:600;">+₱20</span></label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_soy_milk" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_soy_milk" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Soy Milk <span style="color:#c8a96e;font-weight:600;">+₱20</span></label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check" style="padding:8px 12px;border:1.5px solid #f0ebe5;border-radius:8px;margin:0;background:#fffdfb;">
                                <input class="form-check-input" type="checkbox" id="addon_almond_milk" style="accent-color:#c8a96e;cursor:pointer;">
                                <label class="form-check-label" for="addon_almond_milk" style="font-size:12px;color:#5d4037;cursor:pointer;width:100%;">Almond Milk <span style="color:#c8a96e;font-weight:600;">+₱25</span></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Quantity</label>
                    <div class="d-inline-flex align-items-center">
                        <button type="button" class="btn btn-outline-secondary" onclick="modalQtyChange(-1)" style="width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:8px 0 0 8px;border-color:#e0d5c9;color:#5d4037;font-size:18px;font-weight:600;">−</button>
                        <span id="modalQty" style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:38px;border-top:1.5px solid #e0d5c9;border-bottom:1.5px solid #e0d5c9;font-size:16px;font-weight:700;color:#2c1810;background:#fffdfb;">1</span>
                        <button type="button" class="btn btn-outline-secondary" onclick="modalQtyChange(1)" style="width:38px;height:38px;padding:0;display:flex;align-items:center;justify-content:center;border-radius:0 8px 8px 0;border-color:#e0d5c9;color:#5d4037;font-size:18px;font-weight:600;">+</button>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="modalInstructions" style="font-size:11px;font-weight:600;color:#5d4037;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;display:block;">Special Instructions</label>
                    <textarea id="modalInstructions" class="form-control" rows="2" placeholder="Any special requests..." style="font-size:13px;border:1.5px solid #e0d5c9;border-radius:8px;padding:8px 12px;background:#fffdfb;color:#3e3a36;resize:none;font-family:'Inter',sans-serif;"></textarea>
                </div>

                <div id="modalTotalDisplay" style="background:#faf7f3;border-radius:10px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
                    <span style="font-size:13px;font-weight:500;color:#5d4037;">Total</span>
                    <span id="modalTotal" style="font-size:18px;font-weight:700;color:#c8a96e;">₱0.00</span>
                </div>
            </div>
            <div class="modal-footer" style="border:none;padding:0 24px 20px;gap:8px;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="flex:1;padding:10px;border-radius:10px;border:1.5px solid #e0d5c9;background:transparent;color:#5d4037;font-weight:600;font-size:13px;font-family:'Inter',sans-serif;">Cancel</button>
                <button type="button" id="modalAddBtn" class="btn" style="flex:1;padding:10px;border-radius:10px;border:none;background:linear-gradient(135deg,#2c1810,#5d4037);color:#f5f0eb;font-weight:700;font-size:13px;font-family:'Inter',sans-serif;letter-spacing:0.3px;transition:all 0.2s;" onmouseover="this.style.boxShadow='0 4px 16px rgba(44,24,16,0.3)'" onmouseout="this.style.boxShadow='none'">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                </button>
            </div>
        </div>
    </div>
</div>

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

function openCustomizationModal(id, name, price) {
    modalProductId = id;
    modalProductName = name;
    modalProductPrice = price;
    modalQtyVal = 1;

    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductPrice').textContent = '₱' + price.toFixed(2);
    document.getElementById('modalQty').textContent = '1';

    const card = document.querySelector(`.product-card[data-id="${id}"]`);
    if (card) {
        const img = card.querySelector('.img-wrap img');
        const imgWrap = document.getElementById('modalProductImg');
        if (img) {
            imgWrap.innerHTML = '<img src="' + img.src + '" alt="' + name + '" style="width:100%;height:100%;object-fit:cover;">';
        } else {
            imgWrap.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#c8b8a8" stroke-width="1.5"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>';
        }
    }

    updateModalTotal();
    const modal = new bootstrap.Modal(document.getElementById('customizeModal'));
    modal.show();
}

function modalQtyChange(delta) {
    modalQtyVal = Math.max(1, modalQtyVal + delta);
    document.getElementById('modalQty').textContent = modalQtyVal;
    updateModalTotal();
}

function updateModalTotal() {
    const total = modalProductPrice * modalQtyVal;
    document.getElementById('modalTotal').textContent = '₱' + total.toFixed(2);
}

document.getElementById('modalAddBtn').addEventListener('click', function() {
    if (!modalProductId) return;

    const existing = cart.find(item => item.id === modalProductId);
    if (existing) {
        existing.qty += modalQtyVal;
    } else {
        cart.push({ id: modalProductId, name: modalProductName, price: modalProductPrice, qty: modalQtyVal });
    }
    renderCart();

    const card = document.querySelector(`.product-card[data-id="${modalProductId}"]`);
    if (card) {
        card.style.transform = 'scale(0.95)';
        setTimeout(() => card.style.transform = '', 150);
    }

    const modal = bootstrap.Modal.getInstance(document.getElementById('customizeModal'));
    if (modal) modal.hide();
});

function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1 });
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
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="item-name">${item.name}</div>
                    <div class="item-price">₱${item.price.toFixed(2)}</div>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="updateQty(${i}, -1)">−</button>
                    <span class="qty-val">${item.qty}</span>
                    <button class="qty-btn" onclick="updateQty(${i}, 1)">+</button>
                </div>
                <div class="cart-item-total">₱${lineTotal.toFixed(2)}</div>
                <button class="remove-btn" onclick="removeFromCart(${i})"><i class="bi bi-x"></i></button>
            </div>
        `;
    });
    container.innerHTML = html;

    updateSummary();
}

function updateSummary() {
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const tax = subtotal * 0.12;
    const total = subtotal + tax;

    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
}

document.getElementById('checkoutBtn').addEventListener('click', function() {
    if (cart.length === 0) return;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    this.disabled = true;
    setTimeout(() => {
        alert('Order placed successfully!');
        cart = [];
        renderCart();
        this.innerHTML = '<i class="bi bi-credit-card me-2"></i>Checkout';
        this.disabled = true;
    }, 1500);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
