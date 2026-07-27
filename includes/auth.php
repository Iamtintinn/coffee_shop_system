<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/modules/authentication/login.php');
        exit;
    }
}

function login(string $username, string $password): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
    $stmt->execute(['username' => $username, 'email' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account has been deactivated'];
    }

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    return ['success' => true, 'message' => 'Login successful'];
}

function logout(): void
{
    session_destroy();
    header('Location: ' . BASE_URL . '/modules/authentication/login.php');
    exit;
}

function hasRole(string $role): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}
