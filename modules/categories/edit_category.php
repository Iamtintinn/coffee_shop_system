<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = :id");
$stmt->execute(['id' => $id]);
$category = $stmt->fetch();
if (!$category) redirect(BASE_URL . '/modules/categories/categories.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    if (empty($name)) {
        $error = 'Category name is required';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name=:name, description=:desc WHERE category_id=:id");
            $stmt->execute(['name' => $name, 'desc' => $description, 'id' => $id]);
            $success = 'Category updated!';
            $category['name'] = $name;
            $category['description'] = $description;
        } catch (PDOException $e) {
            $error = 'Category name already exists';
        }
    }
}

$page_title = 'Edit Category';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Category</h1>
        <p><?= htmlspecialchars($category['name']) ?></p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <?php if ($error): ?><div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div><?php endif; ?>
        <?php if ($success): ?><div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?> <a href="categories.php" style="color:var(--coffee-gold);font-weight:600;">Back</a></span></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Category Name</label>
                <div class="input-wrapper">
                    <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? $category['name']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <div class="input-wrapper">
                    <textarea name="description" style="min-height:60px;"><?= htmlspecialchars($_POST['description'] ?? $category['description']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:4px;">Update Category</button>
            <a href="categories.php" class="btn-outline" style="margin-top:4px;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
