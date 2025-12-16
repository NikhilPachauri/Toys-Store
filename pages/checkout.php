<?php
// pages/checkout.php - Checkout & Payment Processing

session_start();
include '../config/database.php';
include '../includes/functions.php';

checkSessionTimeout();

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user = getCurrentUser($pdo);
$errors = [];
$success = false;

// Get cart items
$stmt = $pdo->prepare("
    SELECT c.*, p.name, p.price, p.discount_percent
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($cart_items) === 0) {
    redirect('cart.php');
}

// Calculate totals
$subtotal = 0;
$total_discount = 0;
foreach ($cart_items as $item) {
    $item_price = $item['price'] - ($item['price'] * $item['discount_percent'] / 100);
    $subtotal += $item_price * $item['quantity'];
    $total_discount += ($item['price'] * $item['discount_percent'] / 100) * $item['quantity'];
}

$tax = $subtotal * 0.18;
$final_total = $subtotal + $tax;

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address_id = (int)($_POST['shipping_address_id'] ?? 0);
    $billing_address_id = (int)($_POST['billing_address_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');

    if ($shipping_address_id === 0) {
        $errors[] = 'Please select a shipping address';
    }
    if ($billing_address_id === 0) {
        $errors[] = 'Please select a billing address';
    }
    if (empty($payment_method)) {
        $errors[] = 'Please select a payment method';
    }

    if (count($errors) === 0) {
        try {
            $pdo->beginTransaction();

            // Create order
            $order_number = generateOrderNumber();
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, user_id, total_amount, discount_amount, tax_amount, final_amount, 
                                   payment_method, payment_status, order_status, shipping_address_id, billing_address_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_number,
                $user_id,
                $subtotal,
                $total_discount,
                $tax,
                $final_total,
                $payment_method,
                'pending',
                'pending',
                $shipping_address_id,
                $billing_address_id
            ]);
            $order_id = $pdo->lastInsertId();

            // Add order items from cart
            foreach ($cart_items as $item) {
                $item_price = $item['price'] - ($item['price'] * $item['discount_percent'] / 100);
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item_price]);
            }

            // Create payment record
            $stmt = $pdo->prepare("
                INSERT INTO payments (order_id, amount, payment_method, payment_status)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $final_total, $payment_method, 'completed']);

            // Update payment status and order status to confirmed
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed', order_status = 'confirmed' WHERE id = ?");
            $stmt->execute([$order_id]);

            // Log initial status
            $stmt = $pdo->prepare("
                INSERT INTO order_status_log (order_id, new_status, admin_id, notes)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, 'confirmed', $user_id, 'Order placed via checkout']);

            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            // Redirect to success page
            $_SESSION['order_number'] = $order_number;
            $_SESSION['order_id'] = $order_id;
            redirect('checkout_success.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Error processing order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ToyStore</title>
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
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .form-section {
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 1px solid #eee;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        select, input[type="radio"] {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        select {
            width: 100%;
        }

        select:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .address-option {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .address-option:hover {
            border-color: #FF6B6B;
            background: #fff8f8;
        }

        .address-option input[type="radio"] {
            margin-right: 10px;
            width: auto;
        }

        .address-option.selected {
            border-color: #FF6B6B;
            background: #fff8f8;
        }

        .address-text {
            margin-left: 30px;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .payment-option {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-option:hover {
            border-color: #FF6B6B;
        }

        .payment-option input[type="radio"] {
            width: auto;
            margin-right: 15px;
        }

        .payment-option.selected {
            border-color: #FF6B6B;
            background: #fff8f8;
        }

        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }

        .summary-total {
            font-size: 20px;
            font-weight: 700;
            color: #FF6B6B;
            padding-top: 15px;
            border-top: 2px solid #eee;
            margin-top: 15px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #ff5252;
        }

        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            list-style: none;
        }

        .errors li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .section-title {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">🧸 ToyStore</a>
            <a href="cart.php" style="text-decoration: none; color: #666; font-weight: 600;">← Back to Cart</a>
        </div>
    </header>

    <div class="container">
        <h1>🛍️ Checkout</h1>

        <?php if (count($errors) > 0): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" class="checkout-layout">
            <div class="checkout-form">
                <!-- Shipping Address -->
                <div class="form-section">
                    <h2 class="section-title">📍 Shipping Address</h2>
                    <?php foreach ($addresses as $addr): ?>
                        <label class="address-option">
                            <input type="radio" name="shipping_address_id" value="<?php echo $addr['id']; ?>" required>
                            <div class="address-text">
                                <strong><?php echo sanitize($addr['street_address']); ?></strong><br>
                                <?php echo sanitize($addr['city'] . ', ' . $addr['state'] . ' ' . $addr['postal_code']); ?><br>
                                <?php if ($addr['phone']): ?>
                                    📞 <?php echo sanitize($addr['phone']); ?>
                                <?php endif; ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    <?php if (count($addresses) === 0): ?>
                        <p style="color: #999;">No addresses saved. Please add one in your profile first.</p>
                    <?php endif; ?>
                </div>

                <!-- Billing Address -->
                <div class="form-section">
                    <h2 class="section-title">💳 Billing Address</h2>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="same_address" onchange="updateBillingAddress()">
                            Use same address as shipping
                        </label>
                    </div>
                    <div id="billing_section">
                        <?php foreach ($addresses as $addr): ?>
                            <label class="address-option">
                                <input type="radio" name="billing_address_id" value="<?php echo $addr['id']; ?>" required>
                                <div class="address-text">
                                    <strong><?php echo sanitize($addr['street_address']); ?></strong><br>
                                    <?php echo sanitize($addr['city'] . ', ' . $addr['state'] . ' ' . $addr['postal_code']); ?><br>
                                    <?php if ($addr['phone']): ?>
                                        📞 <?php echo sanitize($addr['phone']); ?>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="form-section">
                    <h2 class="section-title">💰 Payment Method</h2>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="UPI" required>
                        UPI (Google Pay, PhonePe, Paytm)
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Credit Card">
                        Credit Card
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Debit Card">
                        Debit Card
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Net Banking">
                        Net Banking
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Wallet">
                        Digital Wallet
                    </label>
                </div>

                <button type="submit" class="btn-submit">Place Order</button>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-title">Order Summary</div>

                <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                    <?php foreach ($cart_items as $item): 
                        $item_price = $item['price'] - ($item['price'] * $item['discount_percent'] / 100);
                        $item_total = $item_price * $item['quantity'];
                    ?>
                        <div class="summary-item">
                            <div>
                                <div><?php echo sanitize($item['name']); ?></div>
                                <small style="color: #999;">x<?php echo $item['quantity']; ?></small>
                            </div>
                            <div><?php echo formatPrice($item_total); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span><?php echo formatPrice($subtotal); ?></span>
                </div>
                <div class="summary-row">
                    <span>Discount</span>
                    <span>-<?php echo formatPrice($total_discount); ?></span>
                </div>
                <div class="summary-row">
                    <span>Tax (18%)</span>
                    <span><?php echo formatPrice($tax); ?></span>
                </div>

                <div class="summary-total">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Total</span>
                        <span><?php echo formatPrice($final_total); ?></span>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function updateBillingAddress() {
            const checkbox = document.getElementById('same_address');
            const shippingRadios = document.querySelectorAll('input[name="shipping_address_id"]');
            const billingRadios = document.querySelectorAll('input[name="billing_address_id"]');

            if (checkbox.checked) {
                shippingRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        if (this.checked) {
                            billingRadios.forEach(r => r.checked = false);
                            const billingValue = this.value;
                            document.querySelector(`input[name="billing_address_id"][value="${billingValue}"]`).checked = true;
                        }
                    });
                });
            }
        }

        // Add visual feedback for selected options
        document.querySelectorAll('.address-option, .payment-option').forEach(option => {
            option.addEventListener('change', function() {
                document.querySelectorAll('.' + this.parentElement.className.split(' ')[0]).forEach(el => {
                    el.classList.remove('selected');
                });
                this.parentElement.classList.add('selected');
            });
        });
    </script>
</body>
</html>