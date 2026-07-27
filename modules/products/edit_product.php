<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = :id");
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    redirect(BASE_URL . '/modules/products/products.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$ingredients = $pdo->query("SELECT * FROM ingredients ORDER BY name")->fetchAll();
$product_ings = $pdo->prepare("SELECT * FROM product_ingredients WHERE product_id = :id");
$product_ings->execute(['id' => $id]);
$selected_ings = [];
foreach ($product_ings->fetchAll() as $pi) {
    $selected_ings[$pi['ingredient_id']] = $pi['quantity'];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $price = $_POST['price'] ?? '';
    $status = $_POST['status'] ?? 'available';
    $selected_ingredients = $_POST['ingredients'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    if (empty($name)) $error = 'Product name is required';
    elseif (empty($price) || !is_numeric($price)) $error = 'Valid price is required';

    if (!$error) {
        $image_name = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['image']['type'], $allowed)) {
                if ($product['image'] && file_exists(UPLOAD_PATH . '/products/' . $product['image'])) {
                    unlink(UPLOAD_PATH . '/products/' . $product['image']);
                }
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = 'prod_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . '/products/' . $image_name);
            } else {
                $error = 'Image must be JPG, PNG, GIF, or WebP';
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("UPDATE products SET name=:name, price=:price, category_id=:cat, image=:image, status=:status WHERE product_id=:id");
            $stmt->execute([
                'name' => $name, 'price' => $price, 'cat' => $category_id,
                'image' => $image_name, 'status' => $status, 'id' => $id
            ]);

            $pdo->prepare("DELETE FROM product_ingredients WHERE product_id = :id")->execute(['id' => $id]);
            foreach ($selected_ingredients as $ing_id) {
                $qty = $quantities[$ing_id] ?? 1;
                if ($qty > 0) {
                    $stmt = $pdo->prepare("INSERT INTO product_ingredients (product_id, ingredient_id, quantity) VALUES (:pid, :iid, :qty)");
                    $stmt->execute(['pid' => $id, 'iid' => $ing_id, 'qty' => $qty]);
                }
            }

            $success = 'Product updated!';
            $product['name'] = $name;
            $product['price'] = $price;
            $product['category_id'] = $category_id;
            $product['status'] = $status;
            $product['image'] = $image_name;
        }
    }
}

$page_title = 'Edit Product';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Edit Product</h1>
        <p><?= htmlspecialchars($product['name']) ?></p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <?php if ($error): ?>
            <div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?> <a href="products.php" style="color:var(--coffee-gold);font-weight:600;">Back to products</a></span></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name</label>
                <div class="input-wrapper">
                    <input type="text" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <div class="input-wrapper">
                        <select name="category_id">
                            <option value="">Select</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($_POST['category_id'] ?? $product['category_id']) == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Price (₱)</label>
                    <div class="input-wrapper">
                        <input type="number" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Image</label>
                    <div class="input-wrapper">
                        <input type="file" name="image" accept="image/*">
                        <?php if ($product['image']): ?>
                            <p style="font-size:11px;color:var(--text-muted);margin-top:4px;">Current: <?= htmlspecialchars($product['image']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <div class="input-wrapper">
                        <select name="status">
                            <option value="available" <?= ($_POST['status'] ?? $product['status']) === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="unavailable" <?= ($_POST['status'] ?? $product['status']) === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Ingredients</label>
                <div style="border:1.5px solid var(--border-med);border-radius:6px;padding:14px;background:var(--bg-warm);">
                    <?php foreach ($ingredients as $ing): ?>
                        <label style="display:flex;align-items:center;gap:10px;padding:6px 0;cursor:pointer;font-size:13px;">
                            <input type="checkbox" name="ingredients[]" value="<?= $ing['ingredient_id'] ?>" style="accent-color:var(--coffee-gold);" <?= isset($selected_ings[$ing['ingredient_id']]) ? 'checked' : '' ?>>
                            <span style="flex:1;"><?= htmlspecialchars($ing['name']) ?></span>
                            <input type="number" name="quantities[<?= $ing['ingredient_id'] ?>]" value="<?= htmlspecialchars($_POST['quantities'][$ing['ingredient_id']] ?? $selected_ings[$ing['ingredient_id']] ?? '1') ?>" min="0.01" step="0.01" style="width:70px;padding:4px 8px;border:1px solid var(--border-med);border-radius:4px;font-size:12px;text-align:center;">
                            <span style="font-size:11px;color:var(--text-muted);width:50px;text-align:right;"><?= htmlspecialchars($ing['unit']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:8px;">Update Product</button>
            <a href="products.php" class="btn-outline" style="margin-top:8px;">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
