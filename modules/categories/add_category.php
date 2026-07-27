<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :desc)");
            $stmt->execute(['name' => $name, 'desc' => $description]);
            $success = 'Category added!';
        } catch (PDOException $e) {
            $error = 'Category name already exists';
        }
    }
}

$page_title = 'Add Category';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Add Category</h1>
        <p>Create a new product category</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <?php if ($error): ?><div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?> <a href="categories.php" style="color:var(--coffee-gold);font-weight:600;">Back</a></span></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label>Category Name</label>
                <div class="input-wrapper">
                    <input type="text" name="name" placeholder="e.g. Coffee, Frappe, Pastries" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <div class="input-wrapper">
                    <textarea name="description" placeholder="Brief description..." style="min-height:60px;"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:4px;">Add Category</button>
            <a href="categories.php" class="btn-outline" style="margin-top:4px;">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
