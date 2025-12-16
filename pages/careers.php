<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Careers - ToyStore</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
        header, footer { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar, .footer-content { max-width: 1400px; margin: 0 auto; padding: 15px 30px; }
        .navbar { display: flex; justify-content: space-between; align-items: center; }
        .footer-content { display: flex; justify-content: space-between; flex-wrap: wrap; padding: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #FF6B6B; }
        nav a { color: #333; text-decoration: none; margin: 0 15px; font-weight: 500; }
        .container { max-width: 1400px; margin: 0 auto; padding: 30px; }
        .page-title { font-size: 32px; margin-bottom: 30px; color: #333; }
        .content { line-height: 1.6; color: #555; }
        .footer-content a { color: #333; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">🧸 ToyStore</div>
            <nav>
                <a href="index.php">Home</a>
                <a href="about-us.php">About Us</a>
                <a href="careers.php">Careers</a>
                <a href="contact-us.php">Contact Us</a>
                <a href="faq.php">FAQ</a>
                <a href="privacy-policy.php">Privacy Policy</a>
                <a href="terms.php">Terms & Conditions</a>
                <a href="return-policy.php">Return Policy</a>
                <?php if (isLoggedIn()): ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 class="page-title">Careers at ToyStore</h1>
        <div class="content">
            <p>ToyStore is always looking for passionate and talented individuals to join our team. Whether you're interested in customer service, logistics, marketing, or technology, there's a place for you at ToyStore.</p>
            <p>We offer a supportive work environment, opportunities for professional growth, and a chance to make a difference in the lives of children and families.</p>
            <p>Check back regularly for new openings, or send your resume to careers@toystore.com.</p>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div>
                <p>&copy; 2025 ToyStore. All rights reserved.</p>
            </div>
            <div>
                <a href="privacy-policy.php">Privacy Policy</a>
                <a href="terms.php">Terms & Conditions</a>
                <a href="return-policy.php">Return Policy</a>
            </div>
        </div>
    </footer>
</body>
</html>
