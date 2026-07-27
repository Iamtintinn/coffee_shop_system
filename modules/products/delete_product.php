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

if ($product) {
    if ($product['image'] && file_exists(UPLOAD_PATH . '/products/' . $product['image'])) {
        unlink(UPLOAD_PATH . '/products/' . $product['image']);
    }
    $pdo->prepare("DELETE FROM products WHERE product_id = :id")->execute(['id' => $id]);
}

redirect(BASE_URL . '/modules/products/products.php');
