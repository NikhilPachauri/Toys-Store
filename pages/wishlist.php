<?php
// pages/wishlist.php - User Wishlist

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = false;

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product'])) {
    $product_id = intval($_POST['product_id']);
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $success = true;
}

// Handle add to cart from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    
    // Check if product is in stock
    $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['stock_quantity'] > 0) {
        // Add to cart
        $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
        $stmt->execute([$user_id, $product_id]);
        $success = true;
    } else {
        $errors[] = 'Product is out of stock';
    }
}

// Get wishlist items with product details - FIXED SQL QUERY
$stmt = $pdo->prepare("SELECT w.id, w.user_id, w.product_id, w.created_at, p.name, p.description, p.price, p.discount_percent, p.stock_quantity FROM wishlist w JOIN products p ON w.product_id = p.id WHERE w.user_id = ? ORDER BY w.created_at DESC");
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - ToyStore</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #FF6B6B;
        }

        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        nav a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        nav a:hover {
            color: #FF6B6B;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #ff5252;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-title {
            font-size: 32px;
            margin-bottom: 30px;
            color: #333;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .wishlist-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            position: relative;
        }

        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #FF6B6B;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-info {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            min-height: 40px;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Fallback for older browsers */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-clamp: 2; /* Standard property for modern browsers */
        }

        .product-description {
            font-size: 13px;
            color: #999;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Fallback for older browsers */
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-clamp: 2; /* Standard property for modern browsers */
        }

        .product-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #FF6B6B;
        }

        .original-price {
            font-size: 13px;
            color: #999;
            text-decoration: line-through;
        }

        .product-stock {
            font-size: 12px;
            color: #666;
            margin-bottom: 15px;
        }

        .stock-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: 600;
        }

        .in-stock {
            background: #d4edda;
            color: #155724;
        }

        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
        }

        .btn-small {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 13px;
        }

        .btn-cart {
            background: #FF6B6B;
            color: white;
        }

        .btn-cart:hover {
            background: #ff5252;
        }

        .btn-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .btn-remove {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .btn-remove:hover {
            background: #f5c6cb;
        }

        .empty-wishlist {
            text-align: center;
            padding: 80px 30px;
            background: white;
            border-radius: 12px;
        }

        .empty-wishlist p {
            font-size: 18px;
            color: #999;
            margin-bottom: 20px;
        }

        .empty-wishlist a {
            padding: 12px 30px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background 0.3s;
        }

        .empty-wishlist a:hover {
            background: #ff5252;
        }

        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .wishlist-count {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            color: #666;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
            }

            nav {
                flex-direction: column;
                gap: 10px;
            }

            .wishlist-grid {
                grid-template-columns: 1fr;
            }

            .wishlist-header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <header>
        <div class="navbar">
            <div class="logo">🧸 ToyStore</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="cart.php">Cart</a>
                <a href="wishlist.php" style="color: #FF6B6B; font-weight: bold;">Wishlist</a>
                <a href="orders.php">Orders</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (count($wishlist_items) > 0): ?>
            <div class="wishlist-header">
                <h1 class="page-title">❤️ My Wishlist</h1>
                <div class="wishlist-count">
                    Items: <strong><?php echo count($wishlist_items); ?></strong>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="success-message">✅ Wishlist updated successfully!</div>
            <?php endif; ?>

            <?php if (count($errors) > 0): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        ❌ <?php echo $error; ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <?php 
                        $item_price = $item['price'];
                        $item_discount = ($item_price * $item['discount_percent']) / 100;
                        $price_after_discount = $item_price - $item_discount;
                    ?>
                    <div class="wishlist-card">
                        <div class="product-image">
                            ❤️
                            <?php if ($item['discount_percent'] > 0): ?>
                                <div class="discount-badge">-<?php echo $item['discount_percent']; ?>%</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo sanitize($item['name']); ?></div>
                            <div class="product-description"><?php echo sanitize(substr($item['description'], 0, 60)) . '...'; ?></div>
                            
                            <div class="product-stock">
                                <?php if ($item['stock_quantity'] > 0): ?>
                                    <span class="stock-status in-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="stock-status out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="product-price">
                                <span class="price">₹<?php echo formatPrice($price_after_discount); ?></span>
                                <?php if ($item['discount_percent'] > 0): ?>
                                    <span class="original-price">₹<?php echo formatPrice($item_price); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="product-actions">
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" name="add_to_cart" class="btn-small btn-cart" <?php echo ($item['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                        🛒 Add to Cart
                                    </button>
                                </form>

                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <button type="submit" name="remove_product" class="btn-small btn-remove" onclick="return confirm('Remove from wishlist?');">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <div class="empty-wishlist">
                <p>❤️ Your wishlist is empty</p>
                <p style="color: #bbb; font-size: 14px; margin-bottom: 30px;">Add your favorite toys to save them for later!</p>
                <a href="products.php">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
