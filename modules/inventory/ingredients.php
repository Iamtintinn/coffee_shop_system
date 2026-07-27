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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'] ?? 0;
    $name = sanitize($_POST['name'] ?? '');
    $unit = sanitize($_POST['unit'] ?? '');
    $min_stock = $_POST['min_stock_level'] ?? 10;
    $expiration = $_POST['expiration_date'] ?? null;

    if ($action === 'add') {
        if (empty($name)) $error = 'Name is required';
        else {
            try {
                $stmt = $pdo->prepare("INSERT INTO ingredients (name, unit, min_stock_level, expiration_date) VALUES (:name, :unit, :min, :exp)");
                $stmt->execute(['name' => $name, 'unit' => $unit, 'min' => $min_stock, 'exp' => $expiration ?: null]);
                $success = 'Ingredient added!';
            } catch (PDOException $e) {
                $error = 'Ingredient name already exists';
            }
        }
    } elseif ($action === 'edit') {
        if (empty($name)) $error = 'Name is required';
        else {
            try {
                $stmt = $pdo->prepare("UPDATE ingredients SET name=:name, unit=:unit, min_stock_level=:min, expiration_date=:exp WHERE ingredient_id=:id");
                $stmt->execute(['name' => $name, 'unit' => $unit, 'min' => $min_stock, 'exp' => $expiration ?: null, 'id' => $id]);
                $success = 'Ingredient updated!';
            } catch (PDOException $e) {
                $error = 'Name already exists';
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM ingredients WHERE ingredient_id = :id")->execute(['id' => $id]);
        $success = 'Ingredient deleted!';
    }
    $ingredients = $pdo->query("SELECT * FROM ingredients ORDER BY name")->fetchAll();
}

$edit_ing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ingredients WHERE ingredient_id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_ing = $stmt->fetch();
}

$page_title = 'Ingredients';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Ingredients</h1>
        <p>Manage ingredient definitions</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 400px 1fr;">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
                <h2><?= $edit_ing ? 'Edit' : 'Add' ?> Ingredient</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div><?php endif; ?>
            <?php if ($success): ?><div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?></span></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_ing ? 'edit' : 'add' ?>">
                <?php if ($edit_ing): ?>
                    <input type="hidden" name="id" value="<?= $edit_ing['ingredient_id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? ($edit_ing['name'] ?? '')) ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Unit</label>
                        <select name="unit" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;background:#fff;">
                            <?php foreach (['pieces', 'grams', 'ml', 'liters', 'kg', 'cups', 'tbsp', 'tsp'] as $u): ?>
                                <option value="<?= $u ?>" <?= ($edit_ing['unit'] ?? 'pieces') === $u ? 'selected' : '' ?>><?= ucfirst($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Min Stock Level</label>
                        <input type="number" name="min_stock_level" step="0.01" value="<?= htmlspecialchars($_POST['min_stock_level'] ?? ($edit_ing['min_stock_level'] ?? '10')) ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Expiration Date (optional)</label>
                    <input type="date" name="expiration_date" value="<?= htmlspecialchars($_POST['expiration_date'] ?? ($edit_ing['expiration_date'] ?? '')) ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                </div>
                <button type="submit" class="btn-primary" style="margin-top:4px;"><?= $edit_ing ? 'Update' : 'Add' ?> Ingredient</button>
                <?php if ($edit_ing): ?>
                    <a href="ingredients.php" class="btn-outline" style="margin-top:4px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Unit</th><th>Stock</th><th>Min Level</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($ingredients as $ing): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($ing['name']) ?></strong></td>
                            <td><?= htmlspecialchars($ing['unit']) ?></td>
                            <td><?= (float)$ing['stock_quantity'] ?></td>
                            <td><?= (float)$ing['min_stock_level'] ?></td>
                            <td>
                                <a href="?edit=<?= $ing['ingredient_id'] ?>" class="btn-outline" style="width:auto;padding:4px 10px;font-size:11px;">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this ingredient?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $ing['ingredient_id'] ?>">
                                    <button type="submit" class="btn-outline" style="width:auto;padding:4px 10px;font-size:11px;border-color:var(--accent-coral);color:var(--accent-coral);">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ingredients)): ?>
                        <tr><td colspan="5" class="empty-state">No ingredients</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
