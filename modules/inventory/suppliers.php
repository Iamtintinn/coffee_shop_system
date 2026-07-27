<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = $_POST['id'] ?? 0;
    $name = sanitize($_POST['name'] ?? '');
    $contact = sanitize($_POST['contact_person'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'active';

    if ($action === 'add' || $action === 'edit') {
        if (empty($name)) $error = 'Supplier name is required';
        else {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, status) VALUES (:name, :contact, :phone, :email, :address, :status)");
                $stmt->execute(['name' => $name, 'contact' => $contact, 'phone' => $phone, 'email' => $email, 'address' => $address, 'status' => $status]);
                $success = 'Supplier added!';
            } else {
                $stmt = $pdo->prepare("UPDATE suppliers SET name=:name, contact_person=:contact, phone=:phone, email=:email, address=:address, status=:status WHERE supplier_id=:id");
                $stmt->execute(['name' => $name, 'contact' => $contact, 'phone' => $phone, 'email' => $email, 'address' => $address, 'status' => $status, 'id' => $id]);
                $success = 'Supplier updated!';
            }
        }
    } elseif ($action === 'delete') {
        $pdo->prepare("DELETE FROM suppliers WHERE supplier_id = :id")->execute(['id' => $id]);
        $success = 'Supplier deleted!';
    }
    $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();
}

$edit_sup = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = :id");
    $stmt->execute(['id' => $_GET['edit']]);
    $edit_sup = $stmt->fetch();
}

$page_title = 'Suppliers';
require_once __DIR__ . '/../../includes/dashboard_header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Suppliers</h1>
        <p>Manage your ingredient suppliers</p>
        <div class="page-header-accent"></div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 420px 1fr;">
    <div class="card">
        <div class="card-header">
            <div class="card-header-left">
                <span class="card-header-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </span>
                <h2><?= $edit_sup ? 'Edit' : 'Add' ?> Supplier</h2>
            </div>
        </div>
        <div class="card-body">
            <?php if ($error): ?><div class="flash-message error"><span class="msg-icon">✕</span><span><?= $error ?></span></div><?php endif; ?>
            <?php if ($success): ?><div class="flash-message success"><span class="msg-icon">✓</span><span><?= $success ?></span></div><?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="<?= $edit_sup ? 'edit' : 'add' ?>">
                <?php if ($edit_sup): ?>
                    <input type="hidden" name="id" value="<?= $edit_sup['supplier_id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($edit_sup['name'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                </div>
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" value="<?= htmlspecialchars($edit_sup['contact_person'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($edit_sup['phone'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($edit_sup['email'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;min-height:50px;"><?= htmlspecialchars($edit_sup['address'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" style="width:100%;padding:8px 12px;border:1.5px solid var(--border-med);border-radius:6px;font-size:13px;font-family:'Inter',sans-serif;outline:none;background:#fff;">
                        <option value="active" <?= ($edit_sup['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($edit_sup['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="margin-top:4px;"><?= $edit_sup ? 'Update' : 'Add' ?> Supplier</button>
                <?php if ($edit_sup): ?>
                    <a href="suppliers.php" class="btn-outline" style="margin-top:4px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:0;">
            <table class="table">
                <thead>
                    <tr><th>Company</th><th>Contact</th><th>Phone</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($s['contact_person'] ?? '—') ?></td>
                            <td style="font-size:12px;"><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td>
                                <span class="badge" style="background:<?= $s['status'] === 'active' ? 'linear-gradient(135deg,#E8F5E9,#C8E6C9);color:#2E7D32' : 'linear-gradient(135deg,#FCE4EC,#F8BBD0);color:#C62828' ?>;">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="?edit=<?= $s['supplier_id'] ?>" class="btn-outline" style="width:auto;padding:4px 10px;font-size:11px;">Edit</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this supplier?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $s['supplier_id'] ?>">
                                    <button type="submit" class="btn-outline" style="width:auto;padding:4px 10px;font-size:11px;border-color:var(--accent-coral);color:var(--accent-coral);">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="5" class="empty-state">No suppliers</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/dashboard_footer.php'; ?>
