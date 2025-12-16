<?php
// pages/orders.php - Order History and Tracking

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

// Get all user orders
$stmt = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ToyStore</title>
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
            padding: 20px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #FF6B6B;
            text-decoration: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        h1 {
            margin-bottom: 30px;
            color: #333;
        }

        .orders-container {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .order-header {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 20px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .order-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
        }

        .order-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .status-badges {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-processing {
            background: #e2e3e5;
            color: #383d41;
        }

        .badge-shipped {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .order-items {
            margin: 15px 0;
        }

        .items-list {
            display: grid;
            gap: 10px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 1fr 100px 80px;
            gap: 20px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 6px;
            font-size: 14px;
        }

        .item-name {
            color: #333;
        }

        .item-qty {
            color: #666;
        }

        .item-price {
            text-align: right;
            color: #FF6B6B;
            font-weight: 600;
        }

        .order-footer {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .total-amount {
            text-align: right;
        }

        .total-label {
            font-size: 12px;
            color: #999;
        }

        .total-value {
            font-size: 20px;
            font-weight: 700;
            color: #FF6B6B;
        }

        .action-links {
            display: flex;
            gap: 10px;
        }

        .link-btn {
            padding: 8px 16px;
            background: #4ECDC4;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        .link-btn:hover {
            background: #3ab8b0;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-icon {
            font-size: 100px;
            margin-bottom: 20px;
        }

        .empty-text {
            color: #999;
            margin-bottom: 30px;
        }

        .btn-shop {
            display: inline-block;
            padding: 12px 30px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
        }

        .timeline {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #4ECDC4;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
        }

        .timeline-item:last-child {
            margin-bottom: 0;
        }

        .timeline-status {
            font-weight: 600;
            color: #333;
            min-width: 100px;
        }

        .timeline-date {
            color: #999;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
            }

            .item-row {
                grid-template-columns: 1fr;
            }

            .order-footer {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">🧸 ToyStore</a>
            <a href="profile.php" style="text-decoration: none; color: #666;">← Back to Profile</a>
        </div>
    </header>

    <div class="container">
        <h1>📦 My Orders</h1>

        <?php if (count($orders) === 0): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p class="empty-text">You haven't placed any orders yet</p>
                <a href="products.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="orders-container">
                <?php foreach ($orders as $order): 
                    // Get order items
                    $stmt = $pdo->prepare("
                        SELECT oi.*, p.name FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Get order status history
                    $stmt = $pdo->prepare("
                        SELECT * FROM order_status_log WHERE order_id = ? ORDER BY created_at DESC
                    ");
                    $stmt->execute([$order['id']]);
                    $status_log = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <span class="order-label">Order ID</span>
                                <span class="order-value"><?php echo sanitize($order['order_number']); ?></span>
                            </div>
                            <div class="order-info">
                                <span class="order-label">Order Date</span>
                                <span class="order-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="order-info">
                                <span class="order-label">Total Amount</span>
                                <span class="order-value"><?php echo formatPrice($order['final_amount']); ?></span>
                            </div>
                            <div class="status-badges">
                                <span class="badge badge-<?php echo $order['payment_status']; ?>">
                                    💳 <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                                <span class="badge badge-<?php echo $order['order_status']; ?>">
                                    📍 <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items">
                            <div style="font-weight: 600; color: #333; margin-bottom: 10px;">Items Ordered</div>
                            <div class="items-list">
                                <?php foreach ($items as $item): ?>
                                    <div class="item-row">
                                        <div class="item-name"><?php echo sanitize($item['name']); ?></div>
                                        <div class="item-qty">Qty: <?php echo $item['quantity']; ?></div>
                                        <div class="item-price"><?php echo formatPrice($item['price']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <?php if (count($status_log) > 0): ?>
                        <div class="timeline">
                            <div style="font-weight: 600; color: #333; margin-bottom: 10px;">Order Timeline</div>
                            <?php foreach (array_slice($status_log, 0, 3) as $log): ?>
                                <div class="timeline-item">
                                    <div class="timeline-status">
                                        ✓ <?php echo ucfirst($log['new_status']); ?>
                                    </div>
                                    <div class="timeline-date">
                                        <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Order Footer -->
                        <div class="order-footer">
                            <div>
                                <strong>Payment Method:</strong> <?php echo $order['payment_method']; ?>
                            </div>
                            <div></div>
                            <div class="action-links">
                                <a href="profile.php" class="link-btn">View Details</a>
                                <?php if ($order['order_status'] !== 'cancelled'): ?>
                                    <button class="link-btn" onclick="alert('Contact support to cancel this order')">Cancel</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>