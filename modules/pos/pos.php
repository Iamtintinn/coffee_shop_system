<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$add_ons = $pdo->query("SELECT * FROM add_ons WHERE status = 'active' ORDER BY name")->fetchAll();

$page_title = 'POS';
$body_class = 'pos-page';
require_once __DIR__ . '/../../includes/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --pos-header: 60px;
    --cat-width: 180px;
    --cart-width: 400px;
}
*{box-sizing:border-box;margin:0;padding:0}
body.pos-page{background:#F5F0EA;overflow:hidden;height:100vh}
.pos-layout{display:flex;height:100vh;padding-top:var(--pos-header)}
.pos-categories{width:var(--cat-width);background:var(--coffee-dark);display:flex;flex-direction:column;padding:12px 10px;gap:4px;overflow-y:auto;flex-shrink:0}
.pos-categories .cat-btn{display:flex;align-items:center;gap:8px;padding:10px 12px;border:none;border-radius:8px;background:transparent;color:rgba(255,255,255,0.6);font-size:12px;font-weight:500;cursor:pointer;transition:all 0.2s;text-align:left;font-family:'Inter',sans-serif;width:100%}
.pos-categories .cat-btn:hover{background:rgba(255,255,255,0.08);color:#fff}
.pos-categories .cat-btn.active{background:rgba(198,142,78,0.2);color:var(--coffee-gold);font-weight:600}
.pos-categories .cat-btn i{font-size:18px;width:24px;text-align:center}
.pos-products{flex:1;display:flex;flex-direction:column;padding:12px 16px;overflow:hidden;min-width:0}
.pos-search{position:relative;margin-bottom:12px}
.pos-search input{width:100%;padding:10px 14px 10px 40px;border:1.5px solid var(--border-med);border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;outline:none;background:#fff;transition:border-color 0.2s}
.pos-search input:focus{border-color:var(--coffee-gold)}
.pos-search i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:16px}
.prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;overflow-y:auto;padding:4px 2px;flex:1}
.prod-card{background:#fff;border-radius:10px;padding:12px;cursor:pointer;transition:all 0.2s;border:1.5px solid transparent;box-shadow:0 1px 4px rgba(0,0,0,0.04);position:relative;user-select:none}
.prod-card:hover{border-color:var(--coffee-gold);box-shadow:0 4px 16px rgba(198,142,78,0.12);transform:translateY(-2px)}
.prod-card .prod-img{width:100%;height:90px;border-radius:6px;background:linear-gradient(135deg,var(--coffee-cream),var(--coffee-latte));display:flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:8px;overflow:hidden}
.prod-card .prod-img img{width:100%;height:100%;object-fit:cover}
.prod-card .prod-name{font-size:13px;font-weight:600;color:var(--coffee-dark);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.prod-card .prod-cat{font-size:10px;color:var(--text-muted);margin-bottom:4px}
.prod-card .prod-price{font-size:15px;font-weight:700;color:var(--coffee-gold)}
.prod-card .badge-oos{position:absolute;top:8px;right:8px;background:#C62828;color:#fff;font-size:9px;font-weight:600;padding:3px 8px;border-radius:4px}
.prod-card .badge-new{position:absolute;top:8px;left:8px;background:var(--coffee-gold);color:#fff;font-size:9px;font-weight:600;padding:3px 8px;border-radius:4px}
.pos-cart{width:var(--cart-width);background:#fff;border-left:1px solid var(--border-light);display:flex;flex-direction:column;flex-shrink:0}
.cart-header{padding:14px 16px;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between}
.cart-header h3{font-size:15px;font-weight:600;color:var(--coffee-dark)}
.cart-header span{font-size:12px;color:var(--text-muted)}
.cart-items{flex:1;overflow-y:auto;padding:8px 12px}
.cart-item{background:var(--bg-warm);border-radius:8px;padding:10px 12px;margin-bottom:8px;position:relative}
.cart-item .ci-header{display:flex;justify-content:space-between;align-items:start;margin-bottom:4px}
.cart-item .ci-name{font-size:13px;font-weight:600;color:var(--coffee-dark);flex:1}
.cart-item .ci-price{font-size:13px;font-weight:700;color:var(--coffee-gold)}
.cart-item .ci-opts{font-size:11px;color:var(--text-muted);margin-bottom:6px;line-height:1.5}
.cart-item .ci-opts span{display:inline-block;background:#fff;padding:1px 6px;border-radius:3px;margin:1px 2px;font-size:10px}
.cart-item .ci-actions{display:flex;align-items:center;gap:6px}
.cart-item .ci-actions button{width:26px;height:26px;border:1px solid var(--border-med);border-radius:5px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;color:var(--text-body);transition:all 0.15s}
.cart-item .ci-actions button:hover{background:var(--coffee-gold);color:#fff;border-color:var(--coffee-gold)}
.cart-item .ci-qty{font-size:13px;font-weight:600;min-width:24px;text-align:center}
.cart-item .ci-remove{position:absolute;top:6px;right:8px;background:none;border:none;color:var(--text-light);cursor:pointer;font-size:16px;padding:2px}
.cart-item .ci-remove:hover{color:var(--accent-coral)}
.cart-totals{padding:14px 16px;border-top:1px solid var(--border-light);font-size:13px}
.cart-totals .tl-row{display:flex;justify-content:space-between;padding:3px 0}
.cart-totals .tl-total{font-size:18px;font-weight:700;color:var(--coffee-dark);padding-top:6px;border-top:2px solid var(--coffee-dark);margin-top:6px}
.cart-actions{padding:12px 16px;border-top:1px solid var(--border-light);display:grid;grid-template-columns:1fr 1fr;gap:6px}
.cart-actions button{padding:10px;border:none;border-radius:8px;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:all 0.2s}
.cart-actions .btn-hold{background:var(--bg-warm);color:var(--coffee-medium)}
.cart-actions .btn-hold:hover{background:var(--border-light)}
.cart-actions .btn-clear{background:var(--bg-warm);color:var(--accent-coral)}
.cart-actions .btn-clear:hover{background:#FCE4EC}
.cart-actions .btn-pay{background:linear-gradient(135deg,var(--coffee-medium),var(--coffee-dark));color:#fff;grid-column:1/-1}
.cart-actions .btn-pay:hover{box-shadow:0 4px 16px rgba(78,52,46,0.3)}
.cart-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--text-muted);font-size:13px;gap:8px}
.cart-empty i{font-size:40px;opacity:0.3}

@media(max-width:1200px){.pos-cart{width:340px}}
@media(max-width:992px){.pos-layout{flex-direction:column}.pos-categories{width:100%;flex-direction:row;padding:8px;overflow-x:auto;height:auto}.pos-categories .cat-btn{white-space:nowrap;flex-shrink:0}.pos-cart{width:100%;height:50vh;border-left:none;border-top:1px solid var(--border-light)}}
@media(max-width:576px){.prod-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<?php
$cart = $_SESSION['pos_cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'qty'));
$cart_subtotal = 0;
foreach ($cart as $item) {
    $addon_total = array_sum(array_column($item['addons'] ?? [], 'price'));
    $cart_subtotal += ($item['price'] + $addon_total) * $item['qty'];
}
?>

<div class="pos-layout">
    <div class="pos-categories">
        <button class="cat-btn active" data-cat="all" onclick="filterProducts('all',this)">
            <i class="bi bi-grid"></i> All
        </button>
        <?php foreach ($categories as $cat): ?>
            <button class="cat-btn" data-cat="<?= $cat['category_id'] ?>" onclick="filterProducts(<?= $cat['category_id'] ?>,this)">
                <i class="bi <?= [
                    'Coffee' => 'bi-cup-hot-fill',
                    'Non-Coffee' => 'bi-cup-straw',
                    'Milk Tea' => 'bi-cup', 'Refreshers' => 'bi-snow2',
                    'Food' => 'bi-basket2-fill', 'Pastries' => 'bi-egg-fried',
                    'Desserts' => 'bi-cake2'
                ][$cat['name']] ?? 'bi-box' ?>"></i>
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="pos-products">
        <div class="pos-search">
            <i class="bi bi-search"></i>
            <input type="text" id="posSearch" placeholder="Search products..." oninput="searchProducts(this.value)">
        </div>
        <div class="prod-grid" id="prodGrid"></div>
    </div>

    <div class="pos-cart">
        <div class="cart-header">
            <h3><i class="bi bi-cart3"></i> Order</h3>
            <span id="cartCount"><?= $cart_count ?> item<?= $cart_count !== 1 ? 's' : '' ?></span>
        </div>
        <div class="cart-items" id="cartItems">
            <?php if (empty($cart)): ?>
                <div class="cart-empty"><i class="bi bi-bag-x"></i> Cart is empty</div>
            <?php else: ?>
                <?php foreach ($cart as $key => $item): ?>
                    <?php $addon_total = array_sum(array_column($item['addons'] ?? [], 'price')); $line_total = ($item['price'] + $addon_total) * $item['qty']; ?>
                    <div class="cart-item" data-key="<?= $key ?>">
                        <button class="ci-remove" onclick="cartAction('remove','<?= $key ?>')">&times;</button>
                        <div class="ci-header">
                            <span class="ci-name"><?= htmlspecialchars($item['name']) ?></span>
                            <span class="ci-price">₱<?= number_format($line_total, 2) ?></span>
                        </div>
                        <div class="ci-opts">
                            <?php if ($item['size'] ?? ''): ?><span><?= $item['size'] ?></span><?php endif; ?>
                            <?php if ($item['temperature'] ?? ''): ?><span><?= $item['temperature'] ?></span><?php endif; ?>
                            <?php if ($item['sugar_level'] ?? ''): ?><span>Sugar: <?= $item['sugar_level'] ?></span><?php endif; ?>
                            <?php if ($item['ice_level'] ?? ''): ?><span>Ice: <?= $item['ice_level'] ?></span><?php endif; ?>
                            <?php foreach ($item['addons'] ?? [] as $a): ?><span>+<?= htmlspecialchars($a['name']) ?></span><?php endforeach; ?>
                            <?php if ($item['instructions'] ?? ''): ?><br><em style="font-size:10px;"><?= htmlspecialchars($item['instructions']) ?></em><?php endif; ?>
                        </div>
                        <div class="ci-actions">
                            <button onclick="cartAction('qty','<?= $key ?>',-1)">−</button>
                            <span class="ci-qty"><?= $item['qty'] ?></span>
                            <button onclick="cartAction('qty','<?= $key ?>',1)">+</button>
                            <button onclick="cartAction('edit','<?= $key ?>')" style="margin-left:auto;width:auto;padding:0 8px;font-size:11px;">Edit</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="cart-totals" id="cartTotals">
            <div class="tl-row"><span>Subtotal</span><span id="subtotalDisplay">₱<?= number_format($cart_subtotal, 2) ?></span></div>
            <div class="tl-row" id="discountRow" style="display:none;"><span>Discount</span><span id="discountDisplay" style="color:var(--accent-coral);">-₱0.00</span></div>
            <div class="tl-row tl-total"><span>Total</span><span id="totalDisplay">₱<?= number_format($cart_subtotal, 2) ?></span></div>
        </div>
        <div class="cart-actions">
            <button class="btn-hold" onclick="holdOrder()"><i class="bi bi-pause-circle"></i> Hold</button>
            <button class="btn-clear" onclick="clearCart()"><i class="bi bi-trash3"></i> Clear</button>
            <button class="btn-pay" id="payBtn" onclick="openPayment()" <?= empty($cart) ? 'disabled' : '' ?>><i class="bi bi-credit-card"></i> Complete Payment</button>
        </div>
    </div>
</div>

<div class="modal fade" id="productModal" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-lg modal-dialog-centered">
<div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.2);">
<div class="modal-body p-4">
    <button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute;top:16px;right:16px;z-index:2"></button>
    <div class="row g-4">
        <div class="col-md-5">
            <div id="modalProductImage" style="width:100%;height:200px;border-radius:10px;background:linear-gradient(135deg,var(--coffee-cream),var(--coffee-latte));display:flex;align-items:center;justify-content:center;font-size:56px;"></div>
            <h4 id="modalProductName" class="mt-3 mb-1" style="font-weight:700;color:var(--coffee-dark);"></h4>
            <p id="modalProductPrice" class="h4" style="color:var(--coffee-gold);font-weight:700;"></p>
        </div>
        <div class="col-md-7">
            <input type="hidden" id="modalProductId">
            <div class="row g-2 mb-3">
                <div class="col-4"><label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Size</label>
                    <select id="optSize" class="form-select form-select-sm" style="border-radius:6px;border-color:var(--border-med);font-size:13px;" onchange="updateModalPrice()">
                        <option value="Small">Small</option><option value="Medium" selected>Medium</option><option value="Large">Large</option>
                    </select></div>
                <div class="col-4"><label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Temperature</label>
                    <select id="optTemp" class="form-select form-select-sm" style="border-radius:6px;border-color:var(--border-med);font-size:13px;">
                        <option value="Hot">Hot</option><option value="Iced">Iced</option>
                    </select></div>
                <div class="col-4"><label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Sugar Level</label>
                    <select id="optSugar" class="form-select form-select-sm" style="border-radius:6px;border-color:var(--border-med);font-size:13px;">
                        <option value="0%">0%</option><option value="25%">25%</option><option value="50%" selected>50%</option><option value="75%">75%</option><option value="100%">100%</option>
                    </select></div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6"><label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Ice Level</label>
                    <select id="optIce" class="form-select form-select-sm" style="border-radius:6px;border-color:var(--border-med);font-size:13px;">
                        <option value="No Ice">No Ice</option><option value="Less Ice">Less Ice</option><option value="Regular Ice" selected>Regular Ice</option><option value="Extra Ice">Extra Ice</option>
                    </select></div>
                <div class="col-6"><label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Quantity</label>
                    <div class="input-group input-group-sm">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)" style="border-color:var(--border-med);">−</button>
                        <input type="number" id="optQty" value="1" min="1" max="99" class="form-control text-center" style="border-color:var(--border-med);font-weight:600;" onchange="updateModalPrice()">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)" style="border-color:var(--border-med);">+</button>
                    </div></div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Add-ons</label>
                <div class="row g-1" id="addonList">
                    <?php foreach ($add_ons as $a): ?>
                    <div class="col-6">
                        <label style="display:flex;align-items:center;gap:6px;padding:4px 8px;border-radius:5px;cursor:pointer;font-size:12px;background:var(--bg-warm);" class="addon-label">
                            <input type="checkbox" class="addon-check" value="<?= $a['addon_id'] ?>" data-name="<?= htmlspecialchars($a['name']) ?>" data-price="<?= $a['price'] ?>" style="accent-color:var(--coffee-gold);" onchange="updateModalPrice()">
                            <span style="flex:1"><?= htmlspecialchars($a['name']) ?></span>
                            <span style="font-weight:600;color:var(--coffee-gold);">+₱<?= number_format($a['price'], 2) ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:var(--coffee-medium);">Special Instructions</label>
                <textarea id="optInstructions" class="form-control form-control-sm" rows="2" style="border-radius:6px;border-color:var(--border-med);font-size:12px;" placeholder="Any special requests..."></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <span class="h5 mb-0" style="font-weight:700;">Total: <span id="modalTotalPrice" style="color:var(--coffee-gold);">₱0.00</span></span>
                <button class="btn btn-lg px-4" style="background:linear-gradient(135deg,var(--coffee-medium),var(--coffee-dark));color:#fff;border:none;border-radius:8px;font-weight:600;" onclick="addToCart()">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </div>
        </div>
    </div>
</div></div></div></div>

<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
<div class="modal-dialog modal-dialog-centered" style="max-width:500px;">
<div class="modal-content" style="border-radius:14px;border:none;box-shadow:0 16px 48px rgba(0,0,0,0.2);">
<div class="modal-body p-4 position-relative">
<button type="button" class="btn-close" data-bs-dismiss="modal" style="position:absolute;top:16px;right:16px;z-index:2"></button>
<h4 class="mb-3" style="font-weight:700;color:var(--coffee-dark);">Complete Payment</h4>
<div id="payError" class="alert alert-danger py-2" style="display:none;font-size:13px;"></div>

<div class="mb-3">
    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--coffee-medium);">Discount</label>
    <select id="payDiscount" class="form-select" style="border-radius:8px;border-color:var(--border-med);" onchange="togglePromoCode()">
        <option value="None">None</option>
        <option value="Senior Citizen">Senior Citizen (20%)</option>
        <option value="PWD">PWD (20%)</option>
        <option value="Student">Student (10%)</option>
        <option value="Employee">Employee (15%)</option>
        <option value="Promo Code">Promo Code</option>
    </select>
</div>
<div id="promoRow" style="display:none;" class="mb-3">
    <div class="input-group">
        <input type="text" id="payPromo" class="form-control" placeholder="Enter promo code" style="border-radius:8px 0 0 8px;border-color:var(--border-med);font-size:13px;">
        <button class="btn" style="background:var(--coffee-gold);color:#fff;border-radius:0 8px 8px 0;" onclick="applyPromo()">Apply</button>
    </div>
</div>

<div class="mb-3">
    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--coffee-medium);">Payment Method</label>
    <div class="row g-2">
        <?php foreach (['Cash' => 'bi-cash', 'GCash' => 'bi-phone', 'Maya' => 'bi-phone', 'Credit Card' => 'bi-credit-card-2-front', 'Debit Card' => 'bi-credit-card'] as $pm => $icon): ?>
        <div class="col">
            <label style="display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px;border:2px solid var(--border-med);border-radius:10px;cursor:pointer;font-size:11px;font-weight:500;transition:all 0.2s;text-align:center;" class="pay-method" onclick="selectPayment(this,'<?= $pm ?>')">
                <input type="radio" name="payMethod" value="<?= $pm ?>" style="display:none;">
                <i class="<?= $icon ?>" style="font-size:22px;"></i>
                <span><?= $pm ?></span>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="cashRow" style="display:none;" class="mb-3">
    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--coffee-medium);">Cash Received</label>
    <input type="number" id="payCash" class="form-control form-control-lg" placeholder="0.00" style="border-radius:8px;border-color:var(--border-med);font-size:18px;font-weight:600;" oninput="calcChange()">
    <div class="d-flex justify-content-between mt-2">
        <span style="font-size:14px;">Change:</span>
        <span id="changeDisplay" style="font-size:18px;font-weight:700;color:var(--coffee-gold);">₱0.00</span>
    </div>
</div>
<div id="digitalRow" style="display:none;" class="mb-3">
    <label class="form-label" style="font-size:12px;font-weight:600;color:var(--coffee-medium);">Reference Number</label>
    <input type="text" id="payRef" class="form-control" placeholder="Enter reference number" style="border-radius:8px;border-color:var(--border-med);font-size:13px;">
</div>

<div class="d-flex justify-content-between align-items-center pt-2 border-top mb-3">
    <span style="font-size:14px;font-weight:600;">Total to Pay:</span>
    <span id="payTotal" style="font-size:22px;font-weight:700;color:var(--coffee-dark);">₱0.00</span>
</div>

<button class="btn btn-lg w-100" style="background:linear-gradient(135deg,var(--coffee-medium),var(--coffee-dark));color:#fff;border:none;border-radius:10px;font-weight:600;padding:12px;" onclick="processPayment()">
    <i class="bi bi-check2-circle"></i> Confirm Payment
</button>
</div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
var posCart = <?= json_encode($cart) ?>;
var modalBs, paymentBs;

document.addEventListener('DOMContentLoaded', function() {
    modalBs = new bootstrap.Modal(document.getElementById('productModal'));
    paymentBs = new bootstrap.Modal(document.getElementById('paymentModal'));
    filterProducts('all');
});

function filterProducts(catId, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else document.querySelector('.cat-btn[data-cat="all"]')?.classList.add('active');
    loadProducts(catId, document.getElementById('posSearch')?.value || '');
}

function searchProducts(val) {
    var active = document.querySelector('.cat-btn.active');
    var cat = active ? active.dataset.cat : 'all';
    loadProducts(cat, val);
}

function loadProducts(catId, search) {
    var url = '<?= BASE_URL ?>/api/get_products.php?category=' + catId + '&search=' + encodeURIComponent(search);
    fetch(url).then(r => r.text()).then(html => {
        document.getElementById('prodGrid').innerHTML = html;
    });
}

function openProductModal(id, name, price, image) {
    document.getElementById('modalProductId').value = id;
    document.getElementById('modalProductName').textContent = name;
    document.getElementById('modalProductPrice').textContent = '₱' + parseFloat(price).toFixed(2);
    var imgDiv = document.getElementById('modalProductImage');
    if (image) imgDiv.innerHTML = '<img src="<?= BASE_URL ?>/uploads/products/' + image + '" style="width:100%;height:100%;object-fit:cover;border-radius:10px;">';
    else imgDiv.innerHTML = '☕';
    document.getElementById('optQty').value = 1;
    document.getElementById('optInstructions').value = '';
    document.querySelectorAll('.addon-check').forEach(c => c.checked = false);
    document.getElementById('optSize').value = 'Medium';
    updateModalPrice();
    modalBs.show();
}

function updateModalPrice() {
    var base = parseFloat(document.getElementById('modalProductPrice').textContent.replace('₱','').replace(',',''));
    var qty = parseInt(document.getElementById('optQty').value) || 1;
    var addonTotal = 0;
    document.querySelectorAll('.addon-check:checked').forEach(c => addonTotal += parseFloat(c.dataset.price));
    var total = (base + addonTotal) * qty;
    document.getElementById('modalTotalPrice').textContent = '₱' + total.toFixed(2);
}

function changeQty(delta) {
    var inp = document.getElementById('optQty');
    var val = parseInt(inp.value) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    inp.value = val;
    updateModalPrice();
}

function addToCart() {
    var data = new FormData();
    data.append('action', 'add');
    data.append('product_id', document.getElementById('modalProductId').value);
    data.append('size', document.getElementById('optSize').value);
    data.append('temperature', document.getElementById('optTemp').value);
    data.append('sugar_level', document.getElementById('optSugar').value);
    data.append('ice_level', document.getElementById('optIce').value);
    data.append('qty', document.getElementById('optQty').value);
    data.append('instructions', document.getElementById('optInstructions').value);
    document.querySelectorAll('.addon-check:checked').forEach(c => data.append('addons[]', c.value));

    fetch('<?= BASE_URL ?>/api/add_to_cart.php', {method:'POST', body:data})
    .then(r => r.json()).then(res => {
        if (res.success) {
            modalBs.hide();
            refreshCart();
        } else alert(res.message || 'Error adding to cart');
    });
}

function cartAction(action, key, val) {
    if (action === 'remove') {
        if (!confirm('Remove this item?')) return;
    }
    var data = new FormData();
    data.append('action', action);
    data.append('key', key);
    if (val !== undefined) data.append('value', val);

    fetch('<?= BASE_URL ?>/api/add_to_cart.php', {method:'POST', body:data})
    .then(r => r.json()).then(res => {
        if (res.success) refreshCart();
    });
}

function clearCart() {
    if (!confirm('Clear all items?')) return;
    var data = new FormData();
    data.append('action', 'clear');
    fetch('<?= BASE_URL ?>/api/add_to_cart.php', {method:'POST', body:data})
    .then(r => r.json()).then(res => {
        if (res.success) refreshCart();
    });
}

function refreshCart() {
    fetch('<?= BASE_URL ?>/api/add_to_cart.php?action=refresh')
    .then(r => r.json()).then(res => {
        if (res.success) {
            document.getElementById('cartItems').innerHTML = res.cart_html;
            document.getElementById('cartCount').textContent = res.count + ' item' + (res.count !== 1 ? 's' : '');
            document.getElementById('subtotalDisplay').textContent = '₱' + res.subtotal.toFixed(2);
            document.getElementById('totalDisplay').textContent = '₱' + res.total.toFixed(2);
            document.getElementById('payBtn').disabled = res.count === 0;
        }
    });
}

function selectPayment(el, method) {
    document.querySelectorAll('.pay-method').forEach(l => l.style.borderColor = 'var(--border-med)');
    el.style.borderColor = 'var(--coffee-gold)';
    el.querySelector('input').checked = true;
    document.getElementById('cashRow').style.display = method === 'Cash' ? 'block' : 'none';
    document.getElementById('digitalRow').style.display = method !== 'Cash' ? 'block' : 'none';
    if (method === 'Cash') calcChange();
}

function togglePromoCode() {
    document.getElementById('promoRow').style.display = document.getElementById('payDiscount').value === 'Promo Code' ? 'block' : 'none';
}

function applyPromo() { alert('Promo code feature coming soon'); }

function calcChange() {
    var total = parseFloat(document.getElementById('payTotal').textContent.replace('₱','').replace(',','')) || 0;
    var cash = parseFloat(document.getElementById('payCash').value) || 0;
    document.getElementById('changeDisplay').textContent = '₱' + Math.max(0, cash - total).toFixed(2);
}

function openPayment() {
    fetch('<?= BASE_URL ?>/api/add_to_cart.php?action=refresh')
    .then(r => r.json()).then(res => {
        if (res.count === 0) { alert('Cart is empty'); return; }
        document.getElementById('payTotal').textContent = '₱' + res.total.toFixed(2);
    document.getElementById('payError').style.display = 'none';
    document.getElementById('payCash').value = '';
    document.getElementById('payRef').value = '';
    document.getElementById('changeDisplay').textContent = '₱0.00';
    document.querySelectorAll('.pay-method input').forEach(i => i.checked = false);
    document.querySelectorAll('.pay-method').forEach(l => l.style.borderColor = 'var(--border-med)');
    document.getElementById('cashRow').style.display = 'none';
    document.getElementById('digitalRow').style.display = 'none';
        paymentBs.show();
    });
}

function processPayment() {
    var method = document.querySelector('.pay-method input:checked');
    if (!method) { showPayError('Select a payment method'); return; }
    var data = new FormData();
    data.append('action', 'checkout');
    data.append('payment_method', method.value);
    data.append('discount_type', document.getElementById('payDiscount').value);
    if (method.value === 'Cash') {
        data.append('cash_received', document.getElementById('payCash').value);
    } else {
        data.append('reference_number', document.getElementById('payRef').value);
    }

    var btn = document.querySelector('#paymentModal .btn-lg');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

    fetch('<?= BASE_URL ?>/api/checkout.php', {method:'POST', body:data})
    .then(r => r.json()).then(res => {
        if (res.success) {
            paymentBs.hide();
            window.open('<?= BASE_URL ?>/modules/pos/receipt.php?id=' + res.order_id, '_blank');
            refreshCart();
        } else {
            showPayError(res.message || 'Payment failed');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirm Payment';
        }
    }).catch(() => {
        showPayError('Connection error');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2-circle"></i> Confirm Payment';
    });
}

function showPayError(msg) {
    var el = document.getElementById('payError');
    el.textContent = msg;
    el.style.display = 'block';
}

function holdOrder() {
    fetch('<?= BASE_URL ?>/api/add_to_cart.php?action=hold')
    .then(r => r.json()).then(res => {
        if (res.success) { alert('Order held. You can resume it later.'); refreshCart(); }
    });
}
</script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
