<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$ingredients = $pdo->query("SELECT * FROM ingredients ORDER BY name")->fetchAll();

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
    elseif (empty($price) || !is_numeric($price) || $price <= 0) $error = 'Enter a valid price';
    elseif (empty($category_id)) $error = 'Select a category';

    if (!$error) {
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['image']['type'], $allowed)) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_name = 'prod_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_PATH . '/products/' . $image_name);
            } else {
                $error = 'Image must be JPG, PNG, GIF, or WebP';
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, image, status) VALUES (:name, '', :price, :category_id, :image, :status)");
            $stmt->execute([
                'name' => $name, 'price' => $price,
                'category_id' => $category_id, 'image' => $image_name, 'status' => $status,
            ]);
            $product_id = $pdo->lastInsertId();

            foreach ($selected_ingredients as $ing_id) {
                $qty = $quantities[$ing_id] ?? 1;
                if ($qty > 0) {
                    $stmt = $pdo->prepare("INSERT INTO product_ingredients (product_id, ingredient_id, quantity) VALUES (:pid, :iid, :qty)");
                    $stmt->execute(['pid' => $product_id, 'iid' => $ing_id, 'qty' => $qty]);
                }
            }

            $success = 'Product added successfully!';
        }
    }
}

$page_title = 'Add Product';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Add Product</h1>
        <p>Add a new item to your menu</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 1fr 360px;">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                </span>
                <h2>Product Details</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="flash-message success">
                    <span class="msg-icon">✓</span>
                    <span><?= $success ?> <a href="products.php" style="color:var(--coffee-gold);font-weight:600;">View all products</a></span>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Product Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="name" placeholder="e.g. Caramel Macchiato" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
                        <?php foreach ($categories as $cat): ?>
                            <label style="cursor:pointer;display:flex;align-items:center;gap:8px;padding:10px 14px;border:2px solid <?= ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'var(--coffee-gold)' : 'var(--border-med)' ?>;border-radius:8px;background:<?= ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'rgba(198,142,78,0.08)' : 'var(--bg-warm)' ?>;transition:all 0.2s;font-size:13px;font-weight:500;" onmouseover="this.style.borderColor='var(--coffee-gold)'" onmouseout="this.style.borderColor=this.querySelector('input').checked?'var(--coffee-gold)':'var(--border-med)'">
                                <input type="radio" name="category_id" value="<?= $cat['category_id'] ?>" style="accent-color:var(--coffee-gold);" <?= ($_POST['category_id'] ?? '') == $cat['category_id'] ? 'checked' : '' ?> required onchange="this.closest('label').querySelectorAll('label').forEach(l=>l.style.borderColor='var(--border-med)');this.closest('label').style.borderColor='var(--coffee-gold)'">
                                <span><?= htmlspecialchars($cat['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Price (₱)</label>
                        <div class="input-wrapper">
                            <input type="number" name="price" step="0.01" min="0.01" placeholder="0.00" required value="<?= htmlspecialchars($_POST['price'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <div class="input-wrapper">
                            <select name="status">
                                <option value="available" <?= ($_POST['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="unavailable" <?= ($_POST['status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Image</label>
                    <div style="border:1.5px dashed var(--border-med);border-radius:8px;padding:20px;text-align:center;background:var(--bg-warm);cursor:pointer;transition:all 0.2s;" onclick="document.getElementById('imageInput').click()" onmouseover="this.style.borderColor='var(--coffee-gold)'" onmouseout="this.style.borderColor='var(--border-med)'">
                        <input type="file" id="imageInput" name="image" accept="image/*" style="display:none;" onchange="this.closest('div').querySelector('span').textContent=this.files[0]?.name||'Click to upload image'">
                        <span style="color:var(--text-muted);font-size:13px;">Click to upload image</span>
                        <p style="font-size:11px;color:var(--text-light);margin-top:4px;">JPG, PNG, GIF, WebP</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top:8px;">Add Product</button>
                <a href="products.php" class="btn-outline" style="margin-top:8px;">Cancel</a>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </span>
                <h2>Ingredients</h2>
            </div>
        </div>
        <div class="card-body" style="max-height:420px;overflow-y:auto;">
            <?php if (empty($ingredients)): ?>
                <p style="font-size:13px;color:var(--text-muted);text-align:center;padding:20px 0;">
                    No ingredients yet.<br>
                    <a href="<?= BASE_URL ?>/modules/inventory/ingredients.php" style="color:var(--coffee-gold);font-weight:500;">Add ingredients</a>
                </p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:2px;">
                    <?php foreach ($ingredients as $ing): ?>
                        <label style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;cursor:pointer;font-size:13px;transition:background 0.15s;" onmouseover="this.style.background='var(--bg-warm)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="ingredients[]" value="<?= $ing['ingredient_id'] ?>" style="accent-color:var(--coffee-gold);" <?= in_array($ing['ingredient_id'], $_POST['ingredients'] ?? []) ? 'checked' : '' ?>>
                            <span style="flex:1;"><?= htmlspecialchars($ing['name']) ?></span>
                            <input type="number" name="quantities[<?= $ing['ingredient_id'] ?>]" value="<?= htmlspecialchars($_POST['quantities'][$ing['ingredient_id']] ?? '1') ?>" min="0.01" step="0.01" style="width:58px;padding:3px 6px;border:1px solid var(--border-med);border-radius:4px;font-size:11px;text-align:center;">
                            <span style="font-size:10px;color:var(--text-muted);width:40px;text-align:right;"><?= htmlspecialchars($ing['unit']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p style="font-size:11px;color:var(--text-muted);margin-top:8px;text-align:center;">Check ingredients and set quantities used per serving</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
