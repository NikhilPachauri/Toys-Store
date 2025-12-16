<?php
// admin/status_update.php - Update Order Status

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn() || !isAdmin($pdo)) {
    redirect('../pages/login.php');
}

$admin_id = $_SESSION['user_id'];
$order_id = (int)($_GET['id'] ?? 0);
$errors = [];
$success = false;

if ($order_id === 0) {
    redirect('orders_manage.php');
}

// Get order details
$stmt = $pdo->prepare("
    SELECT o.*, u.email, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('orders_manage.php');
}

// Get order items
$stmt = $pdo->prepare("
    SELECT oi.*, p.name, p.category
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get status history
$stmt = $pdo->prepare("
    SELECT * FROM order_status_log
    WHERE order_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$order_id]);
$status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitize($_POST['new_status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($new_status)) {
        $errors[] = 'Please select a new status';
    } elseif ($new_status === $order['order_status']) {
        $errors[] = 'Please select a different status';
    }

    if (count($errors) === 0) {
        try {
            $pdo->beginTransaction();

            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);

            // Log status change
            $stmt = $pdo->prepare("
                INSERT INTO order_status_log (order_id, new_status, admin_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $new_status, $admin_id, $notes]);

            $pdo->commit();
            $success = true;

            // Refresh order data
            $stmt = $pdo->prepare("
                SELECT o.*, u.email, u.first_name, u.last_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                WHERE o.id = ?
            ");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            // Refresh status history
            $stmt = $pdo->prepare("
                SELECT * FROM order_status_log
                WHERE order_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$order_id]);
            $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error updating status: ' . $e->getMessage();
        }
    }
}

$status_options = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order Status - Admin</title>
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

        .layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        .form-container,
        .info-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .info-box {
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
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

        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        select:focus,
        textarea:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .info-box h3 {
            margin-bottom: 15px;
            font-size: 14px;
            color: #333;
        }

        .info-item {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-top: 5px;
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

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .timeline {
            margin-top: 20px;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item:not(:last-child)::before {
            content: '';
            position: absolute;
            left: 17px;
            top: 40px;
            bottom: -20px;
            width: 2px;
            background: #eee;
        }

        .timeline-dot {
            width: 36px;
            height: 36px;
            background: #FF6B6B;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            flex-shrink: 0;
            z-index: 1;
        }

        .timeline-content {
            flex: 1;
            padding-top: 5px;
        }

        .timeline-status {
            font-weight: 600;
            color: #333;
        }

        .timeline-date {
            font-size: 12px;
            color: #999;
            margin-top: 3px;
        }

        .timeline-notes {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .items-list {
            font-size: 12px;
        }

        .item {
            margin-bottom: 8px;
            padding: 8px;
            background: #f5f5f5;
            border-radius: 4px;
        }

        .btn {
            padding: 12px 20px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            transition: background 0.3s;
            width: 100%;
        }

        .btn:hover {
            background: #ff5252;
        }

        .btn-secondary {
            background: #666;
        }

        .btn-secondary:hover {
            background: #555;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            list-style: none;
        }

        .errors li {
            margin-bottom: 5px;
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

            .layout {
                grid-template-columns: 1fr;
            }

            .info-box {
                position: static;
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
            <li><a href="payments_manage.php">Payments</a></li>
            <li><a href="status_update.php" class="active">Order Status</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>📦 Update Order Status</h1>
            <p>Order: <?php echo sanitize($order['order_number']); ?></p>
        </div>

        <?php if ($success): ?>
            <div class="success">✓ Status updated successfully!</div>
        <?php endif; ?>

        <?php if (count($errors) > 0): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="layout">
            <!-- Status Update Form -->
            <div class="form-container">
                <form method="POST">
                    <div class="form-section">
                        <h2 class="section-title">Current Status</h2>
                        <div class="info-value">
                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="section-title">Update to New Status</h2>
                        <div class="form-group">
                            <label>New Status</label>
                            <select name="new_status" required>
                                <option value="">Select Status</option>
                                <?php foreach ($status_options as $status): ?>
                                    <?php if ($status !== $order['order_status']): ?>
                                        <option value="<?php echo $status; ?>"><?php echo ucfirst($status); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Admin Notes (Optional)</label>
                            <textarea name="notes" rows="4" placeholder="Add any notes about this status change..."></textarea>
                        </div>

                        <button type="submit" class="btn">Update Status</button>
                        <a href="orders_manage.php" class="btn btn-secondary" style="text-decoration: none; margin-top: 10px;">Back to Orders</a>
                    </div>
                </form>
            </div>

            <!-- Order Info Sidebar -->
            <div class="info-box">
                <h3>📋 Order Details</h3>
                <div class="info-item">
                    <div class="info-label">Order Number</div>
                    <div class="info-value"><?php echo sanitize($order['order_number']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value"><?php echo sanitize($order['first_name'] . ' ' . $order['last_name']); ?></div>
                    <div style="font-size: 12px; color: #999; margin-top: 3px;"><?php echo sanitize($order['email']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Total Amount</div>
                    <div class="info-value"><?php echo formatPrice($order['final_amount']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?php echo sanitize($order['payment_method']); ?></div>
                </div>

                <h3 style="margin-top: 25px; margin-bottom: 10px;">📦 Items</h3>
                <div class="items-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item">
                            <strong><?php echo sanitize($item['name']); ?></strong><br>
                            <small>Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Status History Timeline -->
        <div style="background: white; padding: 25px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <h2 class="section-title">📜 Status History</h2>
            <div class="timeline">
                <?php foreach ($status_history as $index => $log): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"><?php echo $index + 1; ?></div>
                        <div class="timeline-content">
                            <div class="timeline-status"><?php echo ucfirst($log['new_status']); ?></div>
                            <div class="timeline-date"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></div>
                            <?php if (!empty($log['notes'])): ?>
                                <div class="timeline-notes"><?php echo sanitize($log['notes']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>