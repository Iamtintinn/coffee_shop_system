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
    $reference = sanitize($_POST['reference'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($ingredient_id)) $error = 'Select an ingredient';
    elseif (empty($quantity) || $quantity <= 0) $error = 'Enter a valid quantity';

    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO stock_movements (ingredient_id, type, quantity, reference, notes, performed_by) VALUES (:iid, 'in', :qty, :ref, :notes, :uid)");
        $stmt->execute(['iid' => $ingredient_id, 'qty' => $quantity, 'ref' => $reference, 'notes' => $notes, 'uid' => $_SESSION['user_id']]);

        $stmt = $pdo->prepare("UPDATE ingredients SET stock_quantity = stock_quantity + :qty, last_restocked_at = NOW() WHERE ingredient_id = :iid");
        $stmt->execute(['qty' => $quantity, 'iid' => $ingredient_id]);

        $success = 'Stock added successfully!';
    }
}

$page_title = 'Stock In';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Stock In</h1>
        <p>Add stock to ingredients</p>
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
                <label>Quantity to Add</label>
                <div class="input-wrapper">
                    <input type="number" name="quantity" step="0.01" min="0.01" placeholder="e.g. 1000" required>
                </div>
            </div>
            <div class="form-group">
                <label>Reference (optional)</label>
                <div class="input-wrapper">
                    <input type="text" name="reference" placeholder="e.g. Invoice #123, Supplier X">
                </div>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <div class="input-wrapper">
                    <textarea name="notes" style="min-height:50px;"></textarea>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:4px;">Add Stock</button>
            <a href="inventory.php" class="btn-outline" style="margin-top:4px;">Back</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
