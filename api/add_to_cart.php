<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to add items to cart']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product or quantity']);
    exit;
}

// Check stock availability
$stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product || $product['stock_quantity'] < $quantity) {
    echo json_encode(['status' => 'error', 'message' => 'Product out of stock or insufficient quantity']);
    exit;
}

// Insert or update cart
$stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
$stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);

echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
?>
