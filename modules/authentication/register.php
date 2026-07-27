<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/modules/dashboard/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email      = sanitize($_POST['email'] ?? '');
    $role       = sanitize($_POST['role'] ?? '');
    $address    = sanitize($_POST['address'] ?? '');
    $phone      = sanitize($_POST['phone'] ?? '');
    $age        = sanitize($_POST['age'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    $allowed_roles = ['admin', 'manager', 'cashier'];
    if (!in_array($role, $allowed_roles)) {
        $errors[] = 'Please select a valid role';
    }

    if (empty($address)) {
        $errors[] = 'Please enter your address';
    }

    if (empty($phone)) {
        $errors[] = 'Please enter your phone number';
    }

    if ($age === '' || !ctype_digit($age) || (int)$age < 1 || (int)$age > 120) {
        $errors[] = 'Please enter a valid age';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match';
    }

    $id_path = '';
    if (isset($_FILES['id_upload']) && $_FILES['id_upload']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($_FILES['id_upload']['type'], $allowed)) {
            $errors[] = 'ID upload must be JPG, PNG, GIF, or PDF';
        } elseif ($_FILES['id_upload']['size'] > $max_size) {
            $errors[] = 'ID upload must be under 5MB';
        } else {
            $ext = pathinfo($_FILES['id_upload']['name'], PATHINFO_EXTENSION);
            $id_path = 'users/id_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['id_upload']['tmp_name'], UPLOAD_PATH . '/' . $id_path);
        }
    } else {
        $errors[] = 'Please upload a valid ID';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists';
        }
    }

    if (empty($errors)) {
        $username  = explode('@', $email)[0];
        $base      = $username;
        $i         = 1;
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :username LIMIT 1');
        while (true) {
            $stmt->execute(['username' => $username]);
            if (!$stmt->fetch()) break;
            $username = $base . $i;
            $i++;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password, role, address, phone, age, id_upload)
            VALUES (:username, :email, :password, :role, :address, :phone, :age, :id_upload)
        ');
        $stmt->execute([
            'username'  => $username,
            'email'     => $email,
            'password'  => $hash,
            'role'      => $role,
            'address'   => $address,
            'phone'     => $phone,
            'age'       => (int)$age,
            'id_upload' => $id_path,
        ]);

        $success = 'Account created successfully! You can now log in.';
    } else {
        $error = implode('<br>', $errors);
    }
}

$page_title = 'Create Account';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="login-page">
    <div class="register-card">
        <div class="login-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="#F5E6D3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                    <line x1="6" y1="1" x2="6" y2="4"/>
                    <line x1="10" y1="1" x2="10" y2="4"/>
                    <line x1="14" y1="1" x2="14" y2="4"/>
                </svg>
            </div>
            <h1>Create Account</h1>
            <p>Join <?= APP_NAME ?></p>
        </div>

        <?php if ($error): ?>
            <div class="flash-message error">
                <span class="msg-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </span>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="flash-message success">
                <span class="msg-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </span>
                <span><?= $success ?></span>
            </div>
            <div class="divider" style="border-top: none; margin-top: 0;">
                <a href="<?= BASE_URL ?>/modules/authentication/login.php" class="btn-outline">Go to Login</a>
            </div>
        <?php else: ?>
            <form method="POST" action="" id="registerForm" enctype="multipart/form-data" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <div class="input-wrapper">
                            <select id="role" name="role" required>
                                <option value="">Select role</option>
                                <option value="cashier" <?= ($_POST['role'] ?? '') === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= ($_POST['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                            </select>
                            <span class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <div class="input-wrapper">
                            <input type="number" id="age" name="age" placeholder="Age" required min="1" max="120" value="<?= htmlspecialchars($_POST['age'] ?? '') ?>">
                            <span class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <div class="input-wrapper">
                        <textarea id="address" name="address" placeholder="Enter your address" required><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_upload">Upload ID</label>
                    <div class="input-wrapper">
                        <input type="file" id="id_upload" name="id_upload" accept=".jpg,.jpeg,.png,.gif,.pdf" required>
                        <span class="input-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="12" y1="18" x2="12" y2="12"/>
                                <line x1="9" y1="15" x2="15" y2="15"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Min. 6 characters" required minlength="6">
                            <span class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required minlength="6">
                            <span class="input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary" id="registerBtn">
                    <span class="btn-text">Create Account</span>
                    <div class="spinner"></div>
                </button>
            </form>

            <div class="divider">
                <span>Already have an account?</span>
                <a href="<?= BASE_URL ?>/modules/authentication/login.php" class="btn-outline">Sign In</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('registerForm')?.addEventListener('submit', function () {
    var btn = document.getElementById('registerBtn');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
