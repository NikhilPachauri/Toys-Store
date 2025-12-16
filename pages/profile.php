<?php
// pages/profile.php - User Profile and Address Management

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'update_profile') {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');

        if (empty($first_name)) {
            $errors[] = 'First name is required';
        }

        if (count($errors) === 0) {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $phone, $user_id]);
            $success = true;
            $user = getCurrentUser($pdo); // Refresh user data
        }
    }
    
    elseif ($_POST['action'] === 'add_address') {
        $street = sanitize($_POST['street_address'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $state = sanitize($_POST['state'] ?? '');
        $postal = sanitize($_POST['postal_code'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $type = sanitize($_POST['address_type'] ?? 'both');

        if (empty($street) || empty($city) || empty($state) || empty($postal)) {
            $errors[] = 'All address fields are required';
        }

        if (count($errors) === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO addresses (user_id, street_address, city, state, postal_code, phone, address_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $street, $city, $state, $postal, $phone, $type]);
            $success = true;
        }
    }
}

// Handle delete address
if (isset($_GET['delete_address'])) {
    $address_id = (int)$_GET['delete_address'];
    $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$address_id, $user_id]);
    header('Location: profile.php');
    exit;
}

// Get user addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user orders count
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE user_id = $user_id");
$order_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ToyStore</title>
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

        .nav-tabs {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #999;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            margin-bottom: -2px;
        }

        .tab-btn.active {
            color: #FF6B6B;
            border-bottom-color: #FF6B6B;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .card h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #FF6B6B;
        }

        .btn {
            padding: 12px 30px;
            background: #FF6B6B;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
            width: 100%;
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

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            list-style: none;
        }

        .errors li {
            margin-bottom: 5px;
        }

        .address-list {
            display: grid;
            gap: 15px;
        }

        .address-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4ECDC4;
        }

        .address-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .address-type {
            font-size: 12px;
            background: #4ECDC4;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .address-default {
            font-size: 12px;
            background: #FFE066;
            color: #333;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .address-text {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .address-actions {
            display: flex;
            gap: 10px;
        }

        .link-btn {
            padding: 6px 12px;
            background: #FF6B6B;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }

        .link-btn:hover {
            background: #ff5252;
        }

        .link-btn-danger {
            background: #dc3545;
        }

        .link-btn-danger:hover {
            background: #c82333;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e0e0e0;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #FF6B6B;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }

            .nav-tabs {
                flex-wrap: wrap;
            }

            .tab-btn {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">🧸 ToyStore</a>
            <a href="orders.php" style="text-decoration: none; color: #666; font-weight: 600;">My Orders</a>
        </div>
    </header>

    <div class="container">
        <h1>👤 My Profile</h1>

        <?php if ($success): ?>
            <div class="success">✓ Updated successfully!</div>
        <?php endif; ?>

        <?php if (count($errors) > 0): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="switchTab('profile')">Profile Info</button>
            <button class="tab-btn" onclick="switchTab('addresses')">Addresses</button>
            <button class="tab-btn" onclick="switchTab('stats')">Account Stats</button>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <div class="grid-2">
                <div class="card">
                    <h2>Personal Information</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo sanitize($user['first_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo sanitize($user['last_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo sanitize($user['email']); ?>" disabled style="background: #f5f5f5; cursor: not-allowed;">
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo sanitize($user['phone'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn">Update Profile</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Account Info</h2>
                    <div style="padding: 20px 0;">
                        <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        <p style="margin-top: 15px;"><strong>Total Orders:</strong> <?php echo $order_count; ?></p>
                        <p style="margin-top: 15px;"><strong>Status:</strong> <span style="color: #4ECDC4; font-weight: 600;">Active</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Addresses Tab -->
        <div id="addresses" class="tab-content">
            <div class="grid-2">
                <div class="card">
                    <h2>Add New Address</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_address">

                        <div class="form-group">
                            <label>Street Address</label>
                            <input type="text" name="street_address" required>
                        </div>

                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" required>
                        </div>

                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="state" required>
                        </div>

                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" name="postal_code" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone">
                        </div>

                        <div class="form-group">
                            <label>Address Type</label>
                            <select name="address_type">
                                <option value="both">Shipping & Billing</option>
                                <option value="shipping">Shipping Only</option>
                                <option value="billing">Billing Only</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary">Add Address</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Saved Addresses</h2>
                    <?php if (count($addresses) === 0): ?>
                        <p style="color: #999; text-align: center; padding: 30px 0;">No addresses saved yet</p>
                    <?php else: ?>
                        <div class="address-list">
                            <?php foreach ($addresses as $addr): ?>
                                <div class="address-card">
                                    <div class="address-header">
                                        <div style="display: flex; gap: 10px;">
                                            <span class="address-type"><?php echo ucfirst($addr['address_type']); ?></span>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="address-default">Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="address-text">
                                        <strong><?php echo sanitize($addr['street_address']); ?></strong><br>
                                        <?php echo sanitize($addr['city'] . ', ' . $addr['state'] . ' ' . $addr['postal_code']); ?><br>
                                        <?php if ($addr['phone']): ?>
                                            <small>📞 <?php echo sanitize($addr['phone']); ?></small><br>
                                        <?php endif; ?>
                                    </div>
                                    <div class="address-actions">
                                        <a href="profile.php?delete_address=<?php echo $addr['id']; ?>" class="link-btn link-btn-danger" onclick="return confirm('Delete this address?')">Delete</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Stats Tab -->
        <div id="stats" class="tab-content">
            <div class="card">
                <h2>Account Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $order_count; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($addresses); ?></div>
                        <div class="stat-label">Saved Addresses</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">Active</div>
                        <div class="stat-label">Account Status</div>
                    </div>
                </div>

                <h3 style="margin-top: 30px; margin-bottom: 15px;">Account Actions</h3>
                <div style="display: grid; gap: 10px;">
                    <a href="logout.php" style="padding: 12px; background: #FF6B6B; color: white; text-decoration: none; border-radius: 6px; text-align: center; font-weight: 600;">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>