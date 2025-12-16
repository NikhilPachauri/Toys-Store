<?php
// admin/dashboard.php - Admin Dashboard

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

// Check if user is admin
if (!isLoggedIn() || !isAdmin($pdo)) {
    redirect('../pages/login.php');
}

$user = getCurrentUser($pdo);

// Get statistics
$stats = [];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
$stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue
$stmt = $pdo->query("SELECT SUM(final_amount) as total FROM orders WHERE payment_status = 'completed'");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['total_revenue'] = $result['total'] ?? 0;

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
$stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_admin = 0");
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent orders
$stmt = $pdo->query("
    SELECT o.*, u.email, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ToyStore</title>
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
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 30px;
            color: #FF6B6B;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 15px;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 5px;
            transition: background 0.3s;
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

        .header h1 {
            color: #333;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #FF6B6B;
        }

        .stat-label {
            color: #999;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn {
            padding: 12px 20px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
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

        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .table-title {
            padding: 20px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #333;
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

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .action-links a {
            padding: 6px 12px;
            background: #4ECDC4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                max-width: 100%;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
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
        <div class="sidebar-title">🧸 ToyStore Admin</div>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li><a href="products_manage.php">Manage Products</a></li>
            <li><a href="orders_manage.php">Manage Orders</a></li>
            <li><a href="payments_manage.php">Payment Tracking</a></li>
            <li><a href="status_update.php">Update Order Status</a></li>
            <li><a href="reports.php">Sales Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>👋 Welcome, <?php echo sanitize($user['first_name']); ?></h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #4ECDC4;">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?php echo formatPrice($stats['total_revenue']); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #95E1D3;">
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?php echo $stats['total_products']; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #F38181;">
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="products_manage.php" class="btn btn-secondary">+ Add New Product</a>
            <a href="orders_manage.php" class="btn">View All Orders</a>
        </div>

        <!-- Recent Orders -->
        <div class="table-container">
            <div class="table-title">📋 Recent Orders</div>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td><?php echo sanitize($order['order_number']); ?></td>
                            <td><?php echo sanitize($order['first_name'] . ' ' . $order['last_name']); ?></td>
                            <td><?php echo formatPrice($order['final_amount']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div class="action-links">
                                    <a href="status_update.php?order_id=<?php echo $order['id']; ?>">Update</a>
                                    <a href="orders_manage.php?view=<?php echo $order['id']; ?>">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>