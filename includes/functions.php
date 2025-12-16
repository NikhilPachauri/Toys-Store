<?php
// includes/functions.php - Reusable Functions

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate OTP
function generateOTP() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Generate Order Number
function generateOrderNumber() {
    return 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
}

// Generate CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Format price
function formatPrice($price) {
    return '₹' . number_format($price, 2);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if user is admin
function isAdmin($pdo) {
    $user = getCurrentUser($pdo);
    return $user && $user['is_admin'] == 1;
}

// Get cart count
function getCartCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Get wishlist count
function getWishlistCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Send OTP (email simulation - use PHPMailer in production)
function sendOTP($email, $otp) {
    // In production, use PHPMailer or SendGrid
    // For testing, log to file or display
    error_log("OTP for $email: $otp");
    return true;
}

// Get discounted price
function getDiscountedPrice($price, $discount) {
    return $price - ($price * $discount / 100);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// JSON response
function jsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Session timeout check (30 minutes)
function checkSessionTimeout() {
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_destroy();
            redirect('login.php');
        }
    }
    $_SESSION['last_activity'] = time();
}

// Get product with discount
function getProductWithDiscount($pdo, $product_id) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $product['discounted_price'] = getDiscountedPrice($product['price'], $product['discount_percent']);
    }
    
    return $product;
}
?>