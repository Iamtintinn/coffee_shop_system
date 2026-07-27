<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$ingredients = $pdo->query("SELECT i.*, (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE ingredient_id = i.ingredient_id AND type = 'in' AND DATE(created_at) = CURDATE()) AS stock_in_today FROM ingredients i ORDER BY i.stock_quantity <= i.min_stock_level DESC, i.name ASC")->fetchAll();

$page_title = 'Inventory';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Inventory</h1>
        <p>Monitor and manage ingredient stock levels</p>
        <div class="page-header-accent"></div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="stock_in.php" class="btn-primary" style="width:auto;padding:10px 18px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Stock In
        </a>
        <a href="stock_out.php" class="btn-outline" style="width:auto;padding:10px 18px;border-color:var(--accent-coral);color:var(--accent-coral);">
            Stock Out
        </a>
        <a href="ingredients.php" class="btn-outline" style="width:auto;padding:10px 18px;">Manage Ingredients</a>
        <a href="suppliers.php" class="btn-outline" style="width:auto;padding:10px 18px;">Suppliers</a>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead>
                <tr>
                    <th>Ingredient</th>
                    <th>Stock</th>
                    <th>Min Level</th>
                    <th>Status</th>
                    <th>Stock In Today</th>
                    <th>Expiration</th>
                    <th>Last Restocked</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ingredients as $ing): ?>
                    <?php $low = $ing['stock_quantity'] <= $ing['min_stock_level']; ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ing['name']) ?></strong></td>
                        <td><strong style="color:<?= $low ? '#C62828' : 'var(--text-body)' ?>;"><?= (float)$ing['stock_quantity'] ?></strong> <?= htmlspecialchars($ing['unit']) ?></td>
                        <td><?= (float)$ing['min_stock_level'] ?> <?= htmlspecialchars($ing['unit']) ?></td>
                        <td>
                            <?php if ($low): ?>
                                <span class="badge" style="background:linear-gradient(135deg,#FCE4EC,#F8BBD0);color:#C62828;">Low Stock</span>
                            <?php else: ?>
                                <span class="badge" style="background:linear-gradient(135deg,#E8F5E9,#C8E6C9);color:#2E7D32;">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$ing['stock_in_today'] ?> <?= htmlspecialchars($ing['unit']) ?></td>
                        <td style="font-size:12px;color:var(--text-muted);">
                            <?= $ing['expiration_date'] ? date('M d, Y', strtotime($ing['expiration_date'])) : '—' ?>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted);">
                            <?= $ing['last_restocked_at'] ? date('M d, h:i A', strtotime($ing['last_restocked_at'])) : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ingredients)): ?>
                    <tr><td colspan="7" class="empty-state">No ingredients found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
