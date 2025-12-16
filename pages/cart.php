<?php
// pages/cart.php - Shopping Cart

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

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product'])) {
    $product_id = intval($_POST['product_id']);
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $success = true;
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity <= 0) {
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    }
    $success = true;
}

// Handle clear cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_cart'])) {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $success = true;
}

// Get cart items with product details - FIXED SQL SYNTAX
$stmt = $pdo->prepare("SELECT c.user_id, c.product_id, c.quantity, c.created_at, c.updated_at, p.name, p.description, p.price, p.discount_percent, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
$discount_total = 0;
$tax_percent = 18;

foreach ($cart_items as $item) {
    $item_price = $item['price'];
    $item_discount = ($item_price * $item['discount_percent']) / 100;
    $item_total = ($item_price - $item_discount) * $item['quantity'];
    
    $subtotal += $item['price'] * $item['quantity'];
    $discount_total += $item_discount * $item['quantity'];
}

$total_after_discount = $subtotal - $discount_total;
$tax_amount = ($total_after_discount * $tax_percent) / 100;
$final_total = $total_after_discount + $tax_amount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ToyStore</title>
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

        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .cart-items-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 100px;
            height: 100px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .item-price {
            color: #FF6B6B;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .discount-info {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 3px;
        }

        .quantity-control button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 8px;
            color: #FF6B6B;
            font-weight: bold;
        }

        .quantity-control input {
            width: 40px;
            border: none;
            text-align: center;
            font-weight: 600;
        }

        .remove-btn {
            padding: 8px 12px;
            background: #f8d7da;
            color: #721c24;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .remove-btn:hover {
            background: #f5c6cb;
        }

        .cart-summary {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 2px solid #FF6B6B;
            border-bottom: 2px solid #FF6B6B;
            padding-top: 12px;
            padding-bottom: 12px;
            font-weight: 600;
            font-size: 18px;
            color: #FF6B6B;
        }

        .tax-info {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .checkout-btn {
            width: 100%;
            padding: 12px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.3s;
            margin-bottom: 10px;
        }

        .checkout-btn:hover {
            background: #ff5252;
        }

        .checkout-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .continue-shopping-btn {
            width: 100%;
            padding: 12px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background 0.3s;
        }

        .continue-shopping-btn:hover {
            background: #d0d0d0;
        }

        .clear-cart-btn {
            width: 100%;
            padding: 10px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            transition: background 0.3s;
        }

        .clear-cart-btn:hover {
            background: #ffe69c;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 12px;
        }

        .empty-cart p {
            font-size: 18px;
            color: #999;
            margin-bottom: 20px;
        }

        .empty-cart a {
            padding: 12px 30px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            transition: background 0.3s;
        }

        .empty-cart a:hover {
            background: #ff5252;
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

            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-actions {
                width: 100%;
                flex-direction: column;
            }

            .quantity-control {
                width: 100%;
            }

            .remove-btn {
                width: 100%;
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
                <a href="cart.php" style="color: #FF6B6B; font-weight: bold;">Cart</a>
                <a href="wishlist.php">Wishlist</a>
                <a href="orders.php">Orders</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php" class="logout-btn">Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">🛒 Shopping Cart</h1>

        <?php if ($success): ?>
            <div class="success-message">✅ Cart updated successfully!</div>
        <?php endif; ?>

        <?php if (count($cart_items) > 0): ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items-section">
                    <h2 style="margin-bottom: 20px; color: #333;">Items in Cart (<?php echo count($cart_items); ?>)</h2>

                    <?php foreach ($cart_items as $item): ?>
                        <?php 
                            $item_price = $item['price'];
                            $item_discount = ($item_price * $item['discount_percent']) / 100;
                            $price_after_discount = $item_price - $item_discount;
                            $item_total = $price_after_discount * $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div class="item-image">🎁</div>
                            <div class="item-details">
                                <div class="item-name"><?php echo sanitize($item['name']); ?></div>
                                <div class="item-price">₹<?php echo formatPrice($price_after_discount); ?></div>
                                
                                <?php if ($item['discount_percent'] > 0): ?>
                                    <div class="discount-info">
                                        Original: ₹<?php echo formatPrice($item_price); ?> | Discount: -<?php echo $item['discount_percent']; ?>%
                                    </div>
                                <?php endif; ?>

                                <div class="item-actions">
                                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        
                                        <div class="quantity-control">
                                            <button type="button" onclick="decreaseQty(this)">−</button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>">
                                            <button type="button" onclick="increaseQty(this)">+</button>
                                        </div>

                                        <button type="submit" name="update_quantity" style="padding: 8px 12px; background: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">Update</button>
                                    </form>

                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" name="remove_product" class="remove-btn" onclick="return confirm('Remove from cart?');">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 600; color: #333; min-width: 80px;">₹<?php echo formatPrice($item_total); ?></div>
                                <div style="font-size: 12px; color: #999;">Qty: <?php echo $item['quantity']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <div class="summary-title">Order Summary</div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo formatPrice($subtotal); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Discount</span>
                        <span style="color: #4CAF50;">-₹<?php echo formatPrice($discount_total); ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Tax (<?php echo $tax_percent; ?>%)</span>
                        <span>₹<?php echo formatPrice($tax_amount); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>₹<?php echo formatPrice($final_total); ?></span>
                    </div>

                    <div class="tax-info">💡 Including all applicable taxes and charges</div>

                    <form method="POST">
                        <button type="submit" formaction="checkout.php" class="checkout-btn" <?php echo (count($cart_items) === 0) ? 'disabled' : ''; ?>>
                            Proceed to Checkout
                        </button>
                    </form>

                    <a href="products.php" class="continue-shopping-btn">Continue Shopping</a>

                    <form method="POST" style="margin-top: 10px;">
                        <button type="submit" name="clear_cart" class="clear-cart-btn" onclick="return confirm('Clear entire cart?');">
                            Clear Cart
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <p>🛒 Your cart is empty</p>
                <a href="products.php">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function increaseQty(btn) {
            const input = btn.previousElementSibling;
            input.value = parseInt(input.value) + 1;
        }

        function decreaseQty(btn) {
            const input = btn.nextElementSibling;
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
    </script>
</body>
</html>
