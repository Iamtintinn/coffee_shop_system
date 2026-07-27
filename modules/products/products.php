<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE 1=1";
$params = [];

if ($category_filter) {
    $sql .= " AND p.category_id = :category_id";
    $params['category_id'] = $category_filter;
}
if ($search) {
    $sql .= " AND p.name LIKE :search";
    $params['search'] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$page_title = 'Products';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Products</h1>
        <p>Manage your coffee shop menu</p>
        <div class="page-header-accent"></div>
    </div>
    <a href="add_product.php" class="btn-primary" style="width:auto;padding:10px 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Product
    </a>
</div>

<div class="card" style="margin-bottom:22px;">
    <div class="card-body">
        <form method="GET" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;font-size:11px;font-weight:600;color:var(--coffee-medium);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.8px;">Search</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--coffee-gold)'" onblur="this.style.borderColor=''" />
            </div>
            <div style="min-width:160px;">
                <label style="display:block;font-size:11px;font-weight:600;color:var(--coffee-medium);margin-bottom:4px;text-transform:uppercase;letter-spacing:0.8px;">Category</label>
                <select name="category" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;background:#fff;cursor:pointer;" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" <?= $category_filter == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary" style="width:auto;padding:8px 18px;font-size:13px;">Filter</button>
            <?php if ($category_filter || $search): ?>
                <a href="products.php" class="btn-outline" style="width:auto;padding:8px 18px;font-size:13px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($products)): ?>
            <p class="empty-state">No products found</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <span style="display:flex;align-items:center;gap:10px;">
                                    <?php if ($p['image']): ?>
                                        <img src="<?= BASE_URL ?>/uploads/products/<?= htmlspecialchars($p['image']) ?>" alt="" style="width:36px;height:36px;border-radius:6px;object-fit:cover;background:var(--border-light);">
                                    <?php else: ?>
                                        <span style="width:36px;height:36px;border-radius:6px;background:var(--border-light);display:flex;align-items:center;justify-content:center;font-size:16px;">☕</span>
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
                            <td><strong>₱<?= number_format($p['price'], 2) ?></strong></td>
                            <td>
                                <span class="badge" style="background:<?= $p['status'] === 'available' ? 'linear-gradient(135deg,#E8F5E9,#C8E6C9)' : 'linear-gradient(135deg,#FCE4EC,#F8BBD0)' ?>;color:<?= $p['status'] === 'available' ? '#2E7D32' : '#C62828' ?>;">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="display:flex;gap:6px;">
                                    <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn-outline" style="width:auto;padding:5px 12px;font-size:11px;">Edit</a>
                                    <a href="delete_product.php?id=<?= $p['product_id'] ?>" class="btn-outline" style="width:auto;padding:5px 12px;font-size:11px;border-color:var(--accent-coral);color:var(--accent-coral);" onclick="return confirm('Delete this product?')">Delete</a>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
