<?php
// admin/products_manage.php - Manage Products (CRUD)

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn() || !isAdmin($pdo)) {
    redirect('../pages/login.php');
}

$user = getCurrentUser($pdo);
$errors = [];
$success = false;
$action = sanitize($_GET['action'] ?? 'list');
$product_id = (int)($_GET['id'] ?? 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock_quantity'] ?? 0);
    $discount = (float)($_POST['discount_percent'] ?? 0);
    $featured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($name)) $errors[] = 'Product name is required';
    if ($price <= 0) $errors[] = 'Price must be greater than 0';
    if ($stock < 0) $errors[] = 'Stock cannot be negative';
    if ($discount < 0 || $discount > 100) $errors[] = 'Discount must be between 0 and 100';

    if (count($errors) === 0) {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO products (name, description, category, price, stock_quantity, discount_percent, is_featured, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $category, $price, $stock, $discount, $featured, $user['id']]);
            $success = true;
            $action = 'list';
        } elseif ($action === 'edit' && $product_id > 0) {
            $stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, description = ?, category = ?, price = ?, stock_quantity = ?, discount_percent = ?, is_featured = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $category, $price, $stock, $discount, $featured, $product_id]);
            $success = true;
            $action = 'list';
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    redirect('products_manage.php');
}

// Get product for edit
$product = null;
if ($action === 'edit' && $product_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
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

        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #FF6B6B;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 12px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 12px;
            border-radius: 5px;
            transition: background 0.3s;
            font-size: 14px;
        }

        .nav-menu a:hover {
            background: #34495e;
        }

        .nav-menu a.active {
            background: #FF6B6B;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
        }

        .btn {
            padding: 10px 20px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #ff5252;
        }

        .btn-secondary {
            background: #4ECDC4;
        }

        .btn-secondary:hover {
            background: #3ab8b0;
        }

        .form-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            max-width: 600px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input {
            width: auto;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f5f5f5;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .link {
            padding: 6px 12px;
            background: #4ECDC4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }

        .link:hover {
            background: #3ab8b0;
        }

        .link-danger {
            background: #dc3545;
        }

        .link-danger:hover {
            background: #c82333;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            list-style: none;
        }

        .errors li {
            margin-bottom: 5px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
        }

        .form-actions button {
            flex: 1;
        }

        .back-link {
            color: #4ECDC4;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            display: inline-block;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-title">🧸 Admin</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="products_manage.php" class="active">Manage Products</a></li>
            <li><a href="orders_manage.php">Manage Orders</a></li>
            <li><a href="payments_manage.php">Payments</a></li>
            <li><a href="status_update.php">Order Status</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>📦 Manage Products</h1>
            <a href="products_manage.php?action=add" class="btn btn-secondary">+ Add New Product</a>
        </div>

        <?php if ($success): ?>
            <div class="success">✓ Operation completed successfully!</div>
        <?php endif; ?>

        <?php if (count($errors) > 0): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Form -->
            <div class="form-container">
                <a href="products_manage.php" class="back-link">← Back to Products</a>
                <h2><?php echo ($action === 'add') ? 'Add New Product' : 'Edit Product'; ?></h2>

                <form method="POST" style="margin-top: 20px;">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="name" value="<?php echo sanitize($product['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo sanitize($product['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">Select Category</option>
                            <option value="action" <?php echo (($product['category'] ?? '') === 'action') ? 'selected' : ''; ?>>Action Figures</option>
                            <option value="educational" <?php echo (($product['category'] ?? '') === 'educational') ? 'selected' : ''; ?>>Educational</option>
                            <option value="building" <?php echo (($product['category'] ?? '') === 'building') ? 'selected' : ''; ?>>Building Sets</option>
                            <option value="dolls" <?php echo (($product['category'] ?? '') === 'dolls') ? 'selected' : ''; ?>>Dolls</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Price (₹)</label>
                        <input type="number" name="price" step="0.01" value="<?php echo sanitize($product['price'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_quantity" value="<?php echo sanitize($product['stock_quantity'] ?? '0'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Discount (%)</label>
                        <input type="number" name="discount_percent" step="0.01" value="<?php echo sanitize($product['discount_percent'] ?? '0'); ?>">
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="is_featured" id="featured" <?php echo (($product['is_featured'] ?? 0) == 1) ? 'checked' : ''; ?>>
                        <label for="featured" style="margin: 0;">Mark as Featured</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Save Product</button>
                        <a href="products_manage.php" class="btn" style="text-align: center; text-decoration: none;">Cancel</a>
                    </div>
                </form>
            </div>

        <?php else: ?>
            <!-- Products Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Discount</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td><?php echo sanitize($prod['name']); ?></td>
                                <td><?php echo ucfirst(sanitize($prod['category'] ?? ''));?></td>
                                <td><?php echo formatPrice($prod['price']); ?></td>
                                <td><?php echo $prod['stock_quantity']; ?></td>
                                <td><?php echo $prod['discount_percent']; ?>%</td>
                                <td><?php echo ($prod['is_featured'] == 1) ? '✓' : '✗'; ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="products_manage.php?action=edit&id=<?php echo $prod['id']; ?>" class="link">Edit</a>
                                        <a href="products_manage.php?delete=<?php echo $prod['id']; ?>" class="link link-danger" onclick="return confirm('Delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>