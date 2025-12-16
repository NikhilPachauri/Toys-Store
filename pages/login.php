<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$otp_sent = false;
$otp_verified = false;

// Handle OTP generation and sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    }

    if (count($errors) === 0) {
        // Check if user exists and password matches
        $stmt = $pdo->prepare("SELECT id, password, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = 'Invalid email or password';
        } elseif (!verifyPassword($password, $user['password'])) {
            $errors[] = 'Invalid email or password';
        } else {
            // Delete previous OTP records for this user
            $stmt = $pdo->prepare("DELETE FROM otp_verification WHERE user_id = ?");
            $stmt->execute([$user['id']]);

            // Generate new 4-digit OTP
            $otp = mt_rand(1000, 9999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Insert OTP into database
            $stmt = $pdo->prepare("INSERT INTO otp_verification (user_id, otp_code, expires_at, is_verified) VALUES (?, ?, ?, 0)");
            $stmt->execute([$user['id'], $otp, $expires_at]);

            // Store user ID and email in session temporarily
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['temp_email'] = $email;
            $_SESSION['otp_id'] = $pdo->lastInsertId();
            $otp_sent = true;
        }
    }
}

// Handle OTP verification from database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $entered_otp = sanitize($_POST['otp'] ?? '');
    $otp_id = $_SESSION['otp_id'] ?? 0;
    $user_id = $_SESSION['temp_user_id'] ?? 0;

    if (empty($entered_otp)) {
        $errors[] = 'OTP is required';
    } else {
        $stmt = $pdo->prepare("SELECT otp_code, expires_at, is_verified FROM otp_verification WHERE id = ? AND user_id = ?");
        $stmt->execute([$otp_id, $user_id]);
        $otp_row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_row) {
            $errors[] = 'OTP not found. Please try again.';
        } elseif ($otp_row['is_verified'] == 1) {
            $errors[] = 'OTP already used';
        } elseif ($entered_otp !== $otp_row['otp_code']) {
            $errors[] = 'Invalid OTP entered';
        } elseif (date('Y-m-d H:i:s') > $otp_row['expires_at']) {
            $errors[] = 'OTP has expired. Please request a new one.';
        } else {
            // Mark OTP as verified in database
            $stmt = $pdo->prepare("UPDATE otp_verification SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$otp_id]);
            
            // Set user session variables for login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Clear temporary session variables
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['otp_id']);
            
            // Redirect to index.php
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ToyStore</title>
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
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .errors {
            background: #fee;
            color: #c33;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            list-style: none;
        }

        .errors li {
            margin-bottom: 8px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .otp-info {
            background: #e7f3ff;
            color: #0066cc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #0066cc;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 10px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 8px;
            font-weight: 600;
            color: #999;
        }

        .step.active {
            background: #667eea;
            color: white;
        }

        .step.completed {
            background: #4CAF50;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧸 Login to ToyStore</h1>

        <?php if (count($errors) > 0): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li>❌ <?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?php echo ($otp_sent ? 'completed' : 'active'); ?>">
                Step 1<br><small>Email & Password</small>
            </div>
            <div class="step <?php echo ($otp_sent ? 'active' : ''); ?>">
                Step 2<br><small>Verify OTP</small>
            </div>
        </div>

        <!-- Step 1: Email and Password -->
        <?php if (!$otp_sent): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo sanitize($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" name="send_otp" class="btn-submit">Send OTP</button>
            </form>

        <!-- Step 2: OTP Verification -->
        <?php else: ?>
            <div class="otp-info">
                📧 OTP sent to: <strong><?php echo sanitize($_SESSION['temp_email']); ?></strong>
                <br>OTP is valid for 10 minutes
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Enter 4-Digit OTP</label>
                    <input type="text" name="otp" placeholder="Enter OTP" maxlength="4" inputmode="numeric" required autofocus>
                </div>

                <button type="submit" name="verify_otp" class="btn-submit">Verify OTP & Login</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <form method="POST" style="display: inline;">
                    <button type="submit" name="send_otp" style="background: none; border: none; color: #667eea; cursor: pointer; text-decoration: underline; font-weight: 600;">
                        Resend OTP
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Register Link -->
        <div class="login-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>
