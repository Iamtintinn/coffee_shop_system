<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.category_id) AS product_count FROM categories c ORDER BY c.name")->fetchAll();

$page_title = 'Categories';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Categories</h1>
        <p>Organize your menu by category</p>
        <div class="page-header-accent"></div>
    </div>
    <a href="add_category.php" class="btn-primary" style="width:auto;padding:10px 20px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Category
    </a>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <?php if (empty($categories)): ?>
            <p class="empty-state">No categories yet</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Description</th><th>Products</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                            <td style="color:var(--text-muted);"><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                            <td><span class="badge" style="background:var(--border-light);color:var(--text-body);"><?= $cat['product_count'] ?></span></td>
                            <td>
                                <a href="edit_category.php?id=<?= $cat['category_id'] ?>" class="btn-outline" style="width:auto;padding:5px 12px;font-size:11px;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
