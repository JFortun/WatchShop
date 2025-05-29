<?php
session_start();
require_once 'database.php';

// Initialize variables
$loginError = '';
$user = null;
$purchaseHistory = [];
$cartItems = [];
$cartTotal = 0;

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user = ['id' => $_SESSION['user_id'], 'name' => $_SESSION['user_name']];
    $purchaseHistory = getPurchaseHistory($_SESSION['user_id']);

    // Get cart items details
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = getProductById($productId);
            if ($product) {
                $product['quantity'] = $quantity;
                $cartItems[] = $product;
                $cartTotal += ($product['price'] * $quantity);
            }
        }
    }
}

// Handle login
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = authenticateUser($email, $password);

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        // Get purchase history
        $purchaseHistory = getPurchaseHistory($user['id']);

        // Redirect to prevent form resubmission
        header('Location: user.php');
        exit;
    } else {
        $loginError = 'Invalid email or password';
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    // Clear user session data but keep cart
    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);

    // Redirect to prevent resubmission
    header('Location: user.php');
    exit;
}

// Handle remove from cart action
if (isset($_POST['remove_from_cart']) && isset($_POST['remove_product_id'])) {
    $productId = $_POST['remove_product_id'];

    // Remove the product from the cart
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }

    // Redirect to prevent form resubmission
    header('Location: user.php');
    exit;
}

// Handle checkout
if (isset($_POST['checkout']) && isset($_SESSION['user_id']) && !empty($_SESSION['cart'])) {
    // Add purchase to database
    addPurchase($_SESSION['user_id'], $_SESSION['cart']);

    // Clear cart
    $_SESSION['cart'] = [];

    // Refresh purchase history
    $purchaseHistory = getPurchaseHistory($_SESSION['user_id']);

    // Redirect to prevent form resubmission
    header('Location: user.php?checkout_success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - WatchShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <section class="container">
                <a class="navbar-brand" href="index.php">WatchShop</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <section class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="user.php">My Account</a>
                        </li>
                    </ul>
                </section>
            </section>
        </nav>
    </header>

    <main class="container my-4">
        <section class="row mb-4">
            <article class="col-md-6">
                <h1>My Account</h1>
                <p>Manage your shopping cart and view your purchase history.</p>
            </article>
            <article class="col-md-6 text-end">
                <canvas id="logo-canvas" width="200" height="100" style="max-width: 100%; height: auto;"></canvas>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('logo-canvas');
                        const ctx = canvas.getContext('2d');

                        // Background rectangle
                        ctx.fillStyle = '#1a1a1a';
                        ctx.fillRect(0, 0, 200, 100);

                        // Watch face
                        ctx.beginPath();
                        ctx.arc(100, 50, 40, 0, Math.PI * 2);
                        ctx.fillStyle = '#f0f0f0';
                        ctx.fill();
                        ctx.strokeStyle = 'gold';
                        ctx.lineWidth = 3;
                        ctx.stroke();

                        // Hour hand
                        ctx.beginPath();
                        ctx.moveTo(100, 50);
                        ctx.lineTo(100, 30);
                        ctx.strokeStyle = '#333';
                        ctx.lineWidth = 4;
                        ctx.stroke();

                        // Minute hand
                        ctx.beginPath();
                        ctx.moveTo(100, 50);
                        ctx.lineTo(120, 50);
                        ctx.strokeStyle = '#333';
                        ctx.lineWidth = 2;
                        ctx.stroke();

                        // Text
                        ctx.font = 'bold 20px Arial';
                        ctx.fillStyle = 'red';
                        ctx.fillText('WatchShop', 50, 90);
                    });
                </script>
            </article>
        </section>

        <?php if (!$user): ?>
        <section class="user-section" id="login-section">
            <h2>Login</h2>
            <p>Please login to access your account. For demo purposes, use the credentials below:</p>
            <p><strong>Email:</strong> pepe@mail.com | <strong>Password:</strong> pepe</p>

            <?php if ($loginError): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>

            <form class="login-form" action="user.php" method="post">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="pepe@mail.com" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" value="pepe" required>
                </div>
                <button type="submit" name="login" class="btn btn-login">Login</button>
            </form>
        </section>
        <?php else: ?>
        <section class="user-dashboard" id="logged-in">
            <div class="user-welcome">
                <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p>You are now logged in. Manage your cart and view your purchase history below.</p>
                <a href="user.php?logout=1" class="btn btn-outline-danger">Logout</a>
                <a href="index.php" class="btn btn-outline-primary ms-2">Continue Shopping</a>
            </div>

            <section class="user-section" id="cart-section">
                <h2>Shopping Cart</h2>
                <?php if (isset($_GET['checkout_success'])): ?>
                <div class="alert alert-success">Your order has been successfully processed!</div>
                <?php endif; ?>

                <?php if (empty($cartItems)): ?>
                <p>Your cart is empty. <a href="index.php">Continue shopping</a> to add items to your cart.</p>
                <?php else: ?>
                <p>Items in your cart:</p>

                <?php foreach ($cartItems as $item): ?>
                <article class="cart-item">
                    <img src="<?php echo htmlspecialchars($item['image_location']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    <div class="cart-item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                        <form method="post" action="user.php">
                            <input type="hidden" name="remove_product_id" value="<?php echo $item['id']; ?>">
                            <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                        </form>
                    </div>
                    <div class="cart-item-price">$<?php echo number_format($item['price'] ); ?></div>
                </article>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between align-items-center mt-4">
                    <h3>Total: $<?php echo number_format($cartTotal); ?></h3>
                    <form method="post" action="user.php">
                        <button type="submit" name="checkout" class="btn btn-primary">Checkout</button>
                    </form>
                </div>
                <?php endif; ?>
            </section>

            <section class="user-section" id="invoices-section">
                <h2>Purchase History</h2>
                <?php if (empty($purchaseHistory)): ?>
                <p>You haven't made any purchases yet.</p>
                <?php else: ?>
                <p>Your previous orders:</p>

                <?php foreach ($purchaseHistory as $index => $purchase): ?>
                <article class="invoice-item">
                    <img src="<?php echo htmlspecialchars($purchase['image_location']); ?>" alt="<?php echo htmlspecialchars($purchase['name']); ?>">
                    <div class="invoice-item-details">
                        <h3>Invoice #<?php echo $index + 1000; ?></h3>
                        <p>Item: <?php echo htmlspecialchars($purchase['name']); ?></p>
                        <p>Quantity: <?php echo $purchase['amount']; ?></p>
                    </div>
                    <div class="invoice-item-price">$<?php echo number_format($purchase['price']); ?></div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </section>
        <?php endif; ?>
    </main>

    <footer class="bg-dark text-white py-4">
        <section class="container">
            <section class="row">
                <article class="col-md-4">
                    <h3>About WatchShop</h3>
                    <p>Premium watch retailer offering the finest timepieces for all occasions.</p>
                </article>
                <article class="col-md-4">
                    <h3>Useful Links</h3>
                    <ul class="list-unstyled">
                        <li><a href="https://www.hodinkee.com" target="_blank" class="text-white">Hodinkee - Watch News</a></li>
                        <li><a href="https://www.ablogtowatch.com" target="_blank" class="text-white">A Blog To Watch</a></li>
                        <li><a href="https://www.watchtime.com" target="_blank" class="text-white">WatchTime Magazine</a></li>
                    </ul>
                </article>
                <article class="col-md-4">
                    <h3>Contact Us</h3>
                    <address>
                        123 Watch Street<br>
                        Timeville, TX 12345<br>
                        Email: info@watchshop.com<br>
                        Phone: (123) 456-7890
                    </address>
                </article>
            </section>
            <section class="row mt-3">
                <article class="col-12 text-center">
                    <p>&copy; 2023 WatchShop. All rights reserved.</p>
                </article>
            </section>
        </section>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
