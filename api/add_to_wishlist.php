<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please login to add items to wishlist']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$product_id = intval($input['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product']);
    exit;
}

// Check if already in wishlist
$stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id]);
$exists = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exists) {
    echo json_encode(['status' => 'error', 'message' => 'Product already in wishlist']);
    exit;
}

// Insert into wishlist
$stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
$stmt->execute([$_SESSION['user_id'], $product_id]);

echo json_encode(['status' => 'success', 'message' => 'Product added to wishlist']);
?>
