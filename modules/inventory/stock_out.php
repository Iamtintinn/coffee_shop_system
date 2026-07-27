<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$ingredients = $pdo->query("SELECT * FROM ingredients ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ingredient_id = $_POST['ingredient_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($ingredient_id)) $error = 'Select an ingredient';
    elseif (empty($quantity) || $quantity <= 0) $error = 'Enter a valid quantity';

    if (!$error) {
        $stmt = $pdo->prepare("SELECT stock_quantity FROM ingredients WHERE ingredient_id = :id");
        $stmt->execute(['id' => $ingredient_id]);
        $current = $stmt->fetch()['stock_quantity'];

        if ($quantity > $current) {
            $error = 'Not enough stock. Available: ' . $current;
        } else {
            $stmt = $pdo->prepare("INSERT INTO stock_movements (ingredient_id, type, quantity, notes, performed_by) VALUES (:iid, 'out', :qty, :notes, :uid)");
            $stmt->execute(['iid' => $ingredient_id, 'qty' => $quantity, 'notes' => $notes, 'uid' => $_SESSION['user_id']]);

            $stmt = $pdo->prepare("UPDATE ingredients SET stock_quantity = stock_quantity - :qty WHERE ingredient_id = :iid");
            $stmt->execute(['qty' => $quantity, 'iid' => $ingredient_id]);

            $success = 'Stock removed successfully!';
        }
    }
}

$page_title = 'Stock Out';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Stock Out</h1>
        <p>Reduce stock from ingredients</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="card" style="max-width:550px;">
    <div class="card-body">
        <?php if ($error): ?><div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div><?php endif; ?>
        <?php if ($success): ?><div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?></span></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Ingredient</label>
                <div class="input-wrapper">
                    <select name="ingredient_id" required>
                        <option value="">Select ingredient</option>
                        <?php foreach ($ingredients as $ing): ?>
                            <option value="<?= $ing['ingredient_id'] ?>"><?= htmlspecialchars($ing['name']) ?> (<?= (float)$ing['stock_quantity'] ?> <?= htmlspecialchars($ing['unit']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Quantity to Remove</label>
                <div class="input-wrapper">
                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="e.g. 500" required>
                </div>
            </div>
            <div class="form-group">
                <label>Reason / Notes (optional)</label>
                <div class="input-wrapper">
                    <textarea name="notes" style="min-height:50px;" placeholder="e.g. Damaged, expired, waste"></textarea>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:4px;background:linear-gradient(135deg,var(--accent-coral),#C62828);">Remove Stock</button>
            <a href="inventory.php" class="btn-outline" style="margin-top:4px;">Back</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
