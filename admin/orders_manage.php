<?php
// admin/orders_manage.php - View and Manage All Orders

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn() || !isAdmin($pdo)) {
    redirect('../pages/login.php');
}

$user = getCurrentUser($pdo);

// Get filters
$status_filter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');

// Build query
$query = "
    SELECT o.*, u.email, u.first_name, u.last_name, COUNT(oi.id) as item_count
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND o.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $query .= " AND o.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-weight: 600;
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .btn-filter {
            padding: 10px 20px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            align-self: flex-end;
        }

        .btn-filter:hover {
            background: #ff5252;
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

        .status-confirmed {
            background: #cfe2ff;
            color: #084298;
        }

        .status-processing {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-shipped {
            background: #cfe2ff;
            color: #084298;
        }

        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .filter-grid {
                grid-template-columns: 1fr;
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
            <li><a href="products_manage.php">Manage Products</a></li>
            <li><a href="orders_manage.php" class="active">Manage Orders</a></li>
            <li><a href="payments_manage.php">Payments</a></li>
            <li><a href="status_update.php">Order Status</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>📦 Manage Orders</h1>
            <p>Total Orders: <?php echo count($orders); ?></p>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo ($status_filter === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="processing" <?php echo ($status_filter === 'processing') ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo ($status_filter === 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo ($status_filter === 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Order ID, Email, Name..." value="<?php echo sanitize($search); ?>">
                </div>

                <div class="filter-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" value="<?php echo sanitize($date_from); ?>">
                </div>

                <div class="filter-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" value="<?php echo sanitize($date_to); ?>">
                </div>

                <button type="submit" class="btn-filter">Apply Filters</button>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Items</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo sanitize($order['order_number']); ?></strong></td>
                            <td>
                                <div><?php echo sanitize($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                <small style="color: #999;"><?php echo sanitize($order['email']); ?></small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td><?php echo formatPrice($order['final_amount']); ?></td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td>
                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-links">
                                    <a href="status_update.php?id=<?php echo $order['id']; ?>" class="link">Update</a>
                                    <a href="#" class="link" onclick="viewDetails(<?php echo $order['id']; ?>)">View</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function viewDetails(orderId) {
            alert('View details for order: ' + orderId + '\nDetailed view coming soon!');
        }
    </script>
</body>
</html>