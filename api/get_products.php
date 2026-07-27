<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'available'";
$params = [];

if ($category !== 'all') {
    $sql .= " AND p.category_id = :category";
    $params[':category'] = (int)$category;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :search OR p.description LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

$sql .= " ORDER BY p.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

if (empty($products)): ?>
    <div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">
        <i class="bi bi-cup" style="font-size:48px;display:block;margin-bottom:12px;opacity:0.3;"></i>
        No products found
    </div>
<?php else:
    foreach ($products as $p):
        $img = $p['image'] ? BASE_URL . '/uploads/products/' . htmlspecialchars($p['image']) : null;
?>
    <div class="prod-card" onclick="openProductModal(<?= $p['product_id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['price'] ?>, '<?= htmlspecialchars(addslashes($p['image'] ?? '')) ?>')">
        <div class="prod-img">
            <?php if ($img): ?>
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <?php else: ?>
                ☕
            <?php endif; ?>
        </div>
        <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="prod-cat"><?= htmlspecialchars($p['category_name'] ?? '') ?></div>
        <div class="prod-price">₱<?= number_format($p['price'], 2) ?></div>
    </div>
<?php endforeach;
endif;
