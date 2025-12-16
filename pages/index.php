<?php
// pages/index.php - Homepage

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

// Get featured products
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_featured = 1 
    ORDER BY p.created_at DESC 
    LIMIT 12
");
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get best sellers (products with most orders)
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, COUNT(oi.id) as order_count
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY order_count DESC
    LIMIT 8
");
$best_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ToyStore - Home</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            color: #333;
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

        .auth-links {
            display: flex;
            gap: 15px;
        }

        .btn-auth {
            padding: 10px 20px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-auth:hover {
            background: #ff5252;
        }

        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 30px;
            text-align: center;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
        }

        .btn-primary {
            padding: 15px 40px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #333;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-decoration: none;
            color: #333;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .category-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .category-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .category-count {
            font-size: 14px;
            color: #999;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .product-card:hover {
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
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
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
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
        }

        .discount-badge {
            background: #FF6B6B;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cart {
            background: #FF6B6B;
            color: white;
        }

        .btn-cart:hover {
            background: #ff5252;
        }

        .btn-wishlist {
            background: #f0f0f0;
            color: #FF6B6B;
            border: 2px solid #FF6B6B;
        }

        .btn-wishlist:hover {
            background: #FF6B6B;
            color: white;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #FFD700;
            color: #333;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }

        .product-card {
            position: relative;
        }

        footer {
            background: #2c3e50;
            color: white;
            padding: 40px 30px;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
        }

        .footer-section h4 {
            margin-bottom: 15px;
        }

        .footer-section a {
            display: block;
            color: #bbb;
            text-decoration: none;
            margin-bottom: 8px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
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

            .hero h1 {
                font-size: 32px;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
                <?php if (isLoggedIn()): ?>
                    <a href="cart.php">Cart</a>
                    <a href="wishlist.php">Wishlist</a>
                    <a href="orders.php">Orders</a>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="login.php" class="btn-auth">Login</a>
                        <a href="register.php" class="btn-auth">Register</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <div class="hero">
        <h1>Welcome to ToyStore 🎉</h1>
        <p>Discover amazing toys for all ages!</p>
        <a href="products.php" class="btn-primary">Shop Now</a>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Categories Section -->
        <h2 class="section-title">Browse by Category</h2>
        <div class="categories-grid">
            <?php 
            $category_emojis = [
                'Dolls' => '👧',
                'Action Figures' => '🦸',
                'Building Blocks' => '🧱',
                'Vehicles' => '🚗',
                'Soft Toys' => '🧸',
                'Board Games' => '🎲',
                'Puzzles' => '🧩',
                'Educational Toys' => '🧠'
            ];
            
            foreach ($categories as $category): 
            ?>
                <a href="products.php?category=<?php echo urlencode($category['id']); ?>" class="category-card">
                    <div class="category-icon">
                        <?php echo $category_emojis[$category['name']] ?? '🎁'; ?>
                    </div>
                    <div class="category-name"><?php echo $category['name']; ?></div>
                    <div class="category-count">Explore →</div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Featured Products Section -->
        <h2 class="section-title">⭐ Featured Products</h2>
        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="featured-badge">Featured</div>
                    <div class="product-image">🎁</div>
                    <div class="product-info">
                        <div class="product-category"><?php echo sanitize($product['category_name']); ?></div>
                        <div class="product-name"><?php echo sanitize($product['name']); ?></div>
                        <div class="product-price">
                            <span class="price">₹<?php echo formatPrice($product['price']); ?></span>
                            <?php if ($product['discount_percent'] > 0): ?>
                                <span class="discount-badge">-<?php echo $product['discount_percent']; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-actions">
                            <button class="btn-small btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                            <button class="btn-small btn-wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">❤️</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Best Sellers Section -->
        <?php if (count($best_sellers) > 0): ?>
            <h2 class="section-title">🏆 Best Sellers</h2>
            <div class="products-grid">
                <?php foreach ($best_sellers as $product): ?>
                    <div class="product-card">
                        <div class="product-image">🎁</div>
                        <div class="product-info">
                            <div class="product-category"><?php echo sanitize($product['category_name']); ?></div>
                            <div class="product-name"><?php echo sanitize($product['name']); ?></div>
                            <div class="product-price">
                                <span class="price">₹<?php echo formatPrice($product['price']); ?></span>
                                <?php if ($product['discount_percent'] > 0): ?>
                                    <span class="discount-badge">-<?php echo $product['discount_percent']; ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <button class="btn-small btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">Add to Cart</button>
                                <button class="btn-small btn-wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">❤️</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About Us</h4>
                <a href="#">About ToyStore</a>
                <a href="#">Careers</a>
                <a href="#">Blog</a>
            </div>
            <div class="footer-section">
                <h4>Customer Service</h4>
                <a href="#">Contact Us</a>
                <a href="#">FAQ</a>
                <a href="#">Shipping Info</a>
            </div>
            <div class="footer-section">
                <h4>Policies</h4>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms & Conditions</a>
                <a href="#">Return Policy</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <a href="#">Facebook</a>
                <a href="#">Instagram</a>
                <a href="#">Twitter</a>
            </div>
        </div>
    </footer>

    <script>
        function addToCart(productId) {
            <?php if (!isLoggedIn()): ?>
                alert('Please login to add items to cart');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            fetch('../api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Added to cart!');
                } else {
                    alert(data.message || 'Error adding to cart');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function addToWishlist(productId) {
            <?php if (!isLoggedIn()): ?>
                alert('Please login to add items to wishlist');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            fetch('../api/add-to-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Added to wishlist!');
                } else {
                    alert(data.message || 'Error adding to wishlist');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
