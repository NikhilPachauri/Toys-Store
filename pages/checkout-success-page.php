<?php
// pages/checkout_success.php - Order Confirmation

session_start();
include '../config/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['order_number'])) {
    redirect('index.php');
}

$order_number = sanitize($_SESSION['order_number']);
$order_id = $_SESSION['order_id'] ?? 0;

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ?");
$stmt->execute([$order_number]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - ToyStore</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .success-container {
            background: white;
            border-radius: 15px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }

        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 0.6s ease-in-out;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .order-details {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 30px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #999;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .detail-value {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        .actions {
            display: grid;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #FF6B6B;
            color: white;
        }

        .btn-primary:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid #FF6B6B;
            color: #FF6B6B;
        }

        .btn-secondary:hover {
            background: #fff8f8;
        }

        .timer {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>Order Confirmed!</h1>
        <p class="subtitle">Thank you for your purchase. Your order has been placed successfully.</p>

        <div class="order-details">
            <div class="detail-row">
                <div class="detail-label">Order Number</div>
                <div class="detail-value"><?php echo sanitize($order['order_number']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Order Date</div>
                <div class="detail-value"><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Total Amount</div>
                <div class="detail-value"><?php echo formatPrice($order['final_amount']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Payment Method</div>
                <div class="detail-value"><?php echo sanitize($order['payment_method']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Order Status</div>
                <div class="detail-value" style="color: #4ECDC4;">Confirmed</div>
            </div>
        </div>

        <p style="color: #666; font-size: 14px; margin: 20px 0;">
            📧 A confirmation email has been sent to your email address.<br>
            📍 You can track your order status anytime from your dashboard.
        </p>

        <div class="actions">
            <a href="orders.php" class="btn btn-primary">View Order Details</a>
            <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
        </div>

        <div class="timer">
            Redirecting to home in <span id="countdown">5</span> seconds...
        </div>
    </div>

    <script>
        let countdown = 5;
        const countdownElement = document.getElementById('countdown');

        setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            if (countdown === 0) {
                window.location.href = 'index.php';
            }
        }, 1000);

        // Clear session variables
        fetch('../api/clear-checkout-session.php');
    </script>
</body>
</html>