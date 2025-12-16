<?php
// pages/products.php - Product Listing with Filters

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

// Get filters from URL
$category_id = sanitize($_GET['category'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 999999);

// Build query
$query = "
    SELECT p.*, c.name as category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

// Apply category filter only if category_id is not empty
if (!empty($category_id) && $category_id !== '') {
    $query .= " AND p.category_id = ?";
    $params[] = intval($category_id);
}

// Apply search filter
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Apply price filter
if ($min_price > 0) {
    $query .= " AND p.price >= ?";
    $params[] = floatval($min_price);
}

if ($max_price < 999999) {
    $query .= " AND p.price <= ?";
    $params[] = floatval($max_price);
}

// Apply sorting
switch ($sort) {
    case 'price_asc':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'discount':
        $query .= " ORDER BY p.discount_percent DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for filter dropdown
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - ToyStore</title>
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

        .filters-section {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #555;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            padding: 10px 20px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-filter:hover {
            background: #ff5252;
        }

        .btn-reset {
            padding: 10px 20px;
            background: #e0e0e0;
            color: #333;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-reset:hover {
            background: #d0d0d0;
        }

        .results-info {
            margin-bottom: 20px;
            color: #666;
        }

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
        }

        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 5px;
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
            margin-bottom: 10px;
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

        .btn-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
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

        .no-products {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 12px;
            color: #999;
        }

        .no-products p {
            font-size: 18px;
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

            .filters-grid {
                grid-template-columns: 1fr;
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

    <div class="container">
        <h1 class="page-title">🛍️ All Products</h1>

        <!-- Filters Section -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" value="<?php echo sanitize($_GET['search'] ?? ''); ?>" placeholder="Search products...">
                    </div>

                    <div class="filter-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Sort By</label>
                        <select name="sort">
                            <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest</option>
                            <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="discount" <?php echo ($sort === 'discount') ? 'selected' : ''; ?>>Best Discount</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Min Price (₹)</label>
                        <input type="number" name="min_price" value="<?php echo sanitize($_GET['min_price'] ?? ''); ?>" placeholder="0">
                    </div>

                    <div class="filter-group">
                        <label>Max Price (₹)</label>
                        <input type="number" name="max_price" value="<?php echo sanitize($_GET['max_price'] ?? ''); ?>" placeholder="999999">
                    </div>
                </div>

                <div class="filter-buttons">
                    <button type="submit" class="btn-filter">Apply Filters</button>
                    <a href="products.php" class="btn-reset">Reset Filters</a>
                </div>
            </form>
        </div>

        <!-- Results Info -->
        <div class="results-info">
            Showing <strong><?php echo count($products); ?></strong> product(s)
        </div>

        <!-- Products Grid -->
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            🎁
                            <?php if ($product['discount_percent'] > 0): ?>
                                <div class="discount-badge">-<?php echo $product['discount_percent']; ?>%</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-category"><?php echo sanitize($product['category_name']); ?></div>
                            <div class="product-name"><?php echo sanitize($product['name']); ?></div>
                            <div class="product-description"><?php echo sanitize(substr($product['description'], 0, 60)) . '...'; ?></div>
                            
                            <div class="product-stock">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <span class="stock-status in-stock">In Stock (<?php echo $product['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="stock-status out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="product-price">
                                <span class="price">₹<?php echo formatPrice($product['price']); ?></span>
                                <?php if ($product['discount_percent'] > 0): ?>
                                    <span class="original-price">₹<?php echo formatPrice($product['price'] / (1 - $product['discount_percent'] / 100)); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="product-actions">
                                <button class="btn-small btn-cart" onclick="addToCart(<?php echo $productId; ?>)" <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                    Add to Cart
                                </button>
                                <button class="btn-small btn-wishlist" onclick="addToWishlist(<?php echo $productId; ?>)">❤️</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <p>😢 No products found matching your criteria.</p>
                <p>Try adjusting your filters!</p>
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

            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                // Optionally, refresh cart icon or count
            }).catch(error => console.error('Error:', error));
        }

        function addToWishlist(productId) {
            fetch('api/add-to-wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                // Optionally, update wishlist icon or counter
            });
        }
    </script>
</body>
</html>
