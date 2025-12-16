<?php
// admin/payments_manage.php - Payment Tracking & Management

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn() || !isAdmin($pdo)) {
    redirect('../pages/login.php');
}

$user = getCurrentUser($pdo);

// Get filters
$payment_status = sanitize($_GET['payment_status'] ?? '');
$payment_method = sanitize($_GET['payment_method'] ?? '');
$date_from = sanitize($_GET['date_from'] ?? '');
$date_to = sanitize($_GET['date_to'] ?? '');

// Build main query for payments
$query = "
    SELECT p.*, o.order_number, o.order_status, u.email, u.first_name, u.last_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($payment_status)) {
    $query .= " AND p.payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($payment_method)) {
    $query .= " AND p.payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($date_from)) {
    $query .= " AND p.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $query .= " AND p.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_revenue = 0;
$completed_count = 0;
$pending_count = 0;

foreach ($payments as $payment) {
    if ($payment['payment_status'] === 'completed') {
        $total_revenue += $payment['amount'];
        $completed_count++;
    } elseif ($payment['payment_status'] === 'pending') {
        $pending_count++;
    }
}

// Get payment method breakdown
$stmt = $pdo->query("
    SELECT payment_method, COUNT(*) as count, SUM(amount) as total
    FROM payments
    WHERE payment_status = 'completed'
    GROUP BY payment_method
    ORDER BY total DESC
");
$method_breakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid #FF6B6B;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
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
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }

        .method-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .method-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .method-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .method-count {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .method-total {
            font-size: 14px;
            color: #FF6B6B;
            font-weight: 600;
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
            <li><a href="orders_manage.php">Manage Orders</a></li>
            <li><a href="payments_manage.php" class="active">Payments</a></li>
            <li><a href="status_update.php">Order Status</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>💳 Payment Management</h1>
            <p>Track and manage all payments</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value"><?php echo formatPrice($total_revenue); ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #4ECDC4;">
                <div class="stat-label">Completed Payments</div>
                <div class="stat-value"><?php echo $completed_count; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #FFA500;">
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card" style="border-left-color: #666;">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value"><?php echo count($payments); ?></div>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <?php if (count($method_breakdown) > 0): ?>
            <h3 style="margin-bottom: 15px; color: #333;">Payment Methods</h3>
            <div class="method-breakdown">
                <?php foreach ($method_breakdown as $method): ?>
                    <div class="method-card">
                        <div class="method-label"><?php echo sanitize($method['payment_method']); ?></div>
                        <div class="method-count"><?php echo $method['count']; ?> payments</div>
                        <div class="method-total"><?php echo formatPrice($method['total']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Payment Status</label>
                    <select name="payment_status">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo ($payment_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo ($payment_status === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo ($payment_status === 'failed') ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="">All Methods</option>
                        <option value="UPI" <?php echo ($payment_method === 'UPI') ? 'selected' : ''; ?>>UPI</option>
                        <option value="Credit Card" <?php echo ($payment_method === 'Credit Card') ? 'selected' : ''; ?>>Credit Card</option>
                        <option value="Debit Card" <?php echo ($payment_method === 'Debit Card') ? 'selected' : ''; ?>>Debit Card</option>
                        <option value="Net Banking" <?php echo ($payment_method === 'Net Banking') ? 'selected' : ''; ?>>Net Banking</option>
                        <option value="Wallet" <?php echo ($payment_method === 'Wallet') ? 'selected' : ''; ?>>Wallet</option>
                    </select>
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

        <!-- Payments Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Order Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><strong><?php echo sanitize($payment['order_number']); ?></strong></td>
                            <td>
                                <div><?php echo sanitize($payment['first_name'] . ' ' . $payment['last_name']); ?></div>
                                <small style="color: #999;"><?php echo sanitize($payment['email']); ?></small>
                            </td>
                            <td><?php echo formatPrice($payment['amount']); ?></td>
                            <td><?php echo sanitize($payment['payment_method']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $payment['payment_status']; ?>">
                                    <?php echo ucfirst($payment['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($payment['order_status']); ?></td>
                            <td><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>