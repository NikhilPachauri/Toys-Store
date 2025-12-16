# Toys-Store
This is a complete, production‑ready PHP e‑commerce project for a toy store, designed to be easy to deploy and extend.

Overview
ToyStore is a full‑stack, PHP‑based e‑commerce platform tailored for selling toys online. It includes user authentication with OTP login, product browsing and filtering, cart and wishlist management, order processing, and an admin panel for managing products, orders, and payments. The project is built with security and scalability in mind and is suitable as both a learning project and a starting point for real‑world deployments.

Tech Stack
Backend: PHP 7.4+ (PDO, prepared statements)

Database: MySQL 5.7+ / MariaDB (InnoDB, foreign keys)

Frontend: HTML5, CSS3, Vanilla JavaScript

Architecture: Simple MVC‑like structure (pages + includes + API)

Server: Apache (XAMPP / LAMP / WAMP compatible)

Features
User & Auth
User registration with validation

OTP‑based login (numeric code, time‑limited)

Password hashing with bcrypt

Session management with timeout

Profile page with user details

Role‑based access (user / admin)

Shopping
Product listing with:

Category filters

Search

Price sorting

Discount display

Stock information

Product cards with line‑clamped titles/descriptions

Wishlist:

Add/remove items

“Add to Cart” from wishlist

Cart:

Add/update/remove/clear items

Discount calculation

18% tax calculation

Order summary (subtotal, discount, tax, total)

Checkout & Orders
Address management (multiple addresses)

Multiple payment method selection (placeholders for integration)

Order creation with unique order numbers

Order history and status tracking

Payment status and basic analytics in admin area

Admin Panel
Admin dashboard

Product management (CRUD)

Order management (status updates)

Payment overview

Basic activity / status workflow

Security
Prepared statements everywhere (SQL injection protection)

Output escaping to reduce XSS risk

Session hardening and timeout

Role‑based authorization checks

Error messages that avoid leaking sensitive details

Project Structure
bash
ecommerce-toys/
├── config/
│   └── database.php        # PDO connection
├── includes/
│   └── functions.php       # Shared helpers (auth, validation, formatting, etc.)
├── pages/
│   ├── index.php           # Home / landing page
│   ├── products.php        # Product listing + filters
│   ├── cart.php            # Shopping cart
│   ├── wishlist.php        # Wishlist
│   ├── checkout.php        # Checkout flow
│   ├── checkout_success.php# Order confirmation
│   ├── orders.php          # User orders
│   ├── profile.php         # User profile
│   ├── register.php        # Registration
│   ├── login.php           # OTP login
│   └── logout.php
├── admin/
│   ├── dashboard.php
│   ├── products_manage.php
│   ├── orders_manage.php
│   ├── status_update.php
│   ├── payments_manage.php
│   └── logout.php
├── api/
│   ├── add-to-cart.php
│   ├── add-to-wishlist.php
│   └── clear-checkout-session.php
└── uploads/
    └── products/           # Product images
Database Schema (High Level)
Core tables:

users – user accounts (with role, hashed password, etc.)

products – products with price, stock, discount, category

categories – product categories

cart – user cart items (user_id, product_id, quantity)

wishlist – saved items (user_id, product_id)

orders – order header (user, total, status, payment, timestamps)

order_items – line items per order

addresses – user addresses for checkout

otp_verification – OTP codes and expiry for login

All relational tables use foreign keys and InnoDB.

Installation
Clone the repository

bash
git clone https://github.com/your-username/ecommerce-toys.git
cd ecommerce-toys
Create the database

Create a new MySQL / MariaDB database, e.g. ecommerce_toys.

Import the provided SQL schema file (if present) or create tables based on the schema section above.

Configure database connection

Edit config/database.php:

php
$host = '127.0.0.1';
$db   = 'ecommerce_toys';
$user = 'root';
$pass = ''; // or your password
$charset = 'utf8mb4';
Set up your server

If using XAMPP:

Place the project in htdocs/ecommerce-toys.

Start Apache and MySQL from the XAMPP control panel.

Visit: http://localhost/ecommerce-toys/pages/index.php

Create an admin user

Register a normal user via register.php.

In the database, update that user’s role column to admin (or whatever value your schema uses for admin).

Log in with that account to access the admin panel.

Usage
Browse products from the home or products page.

Add items to cart or wishlist using the buttons on product cards.

Manage cart quantities, see price, discount, tax, and final total.

Proceed through checkout, choose address and payment, and place an order.

View order history and details on the orders page.

As admin:

Add / edit / delete products

Update order statuses

Review basic payment/analytics info

Environment & Configuration
Recommended: PHP 7.4+ with PDO and OpenSSL enabled.

Session lifetime and security options can be tuned in php.ini or within the project’s session management code.

For production:

Run over HTTPS.

Use strong database credentials.

Disable display of PHP errors in the browser.

Configure proper file permissions on uploads/.

Customization
Styling: All pages use simple CSS; you can replace or augment with a CSS framework (Tailwind, Bootstrap, etc.).

Payment Integration: Replace the placeholder payment options with real gateways (Razorpay, Stripe, etc.) by adding integration in checkout and order creation.

Email / OTP Delivery: Currently OTP logic is designed to work with the database; you can plug in a mail or SMS provider for real delivery.

SEO / Content: Update static pages like About, FAQ, Privacy, Terms, and Return Policy to match your brand and legal requirements.

Known Limitations
No built‑in multi‑step admin user management (beyond role flag).

Payment integrations are placeholders; actual gateways must be configured.

Email/SMS sending is not wired to a live transactional provider by default.

Contributing
Pull requests and issues are welcome. For major changes:

Open an issue first to discuss what you want to change.

Keep code style consistent with existing files.

Avoid committing secrets or environment‑specific configuration.
