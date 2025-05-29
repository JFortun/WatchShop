<?php
session_start();
require_once 'database.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle add to cart action
if (isset($_POST['add_to_cart']) && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];

    // If product already in cart, increment quantity
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]++;
    } else {
        $_SESSION['cart'][$productId] = 1;
    }

    // Redirect to prevent form resubmission
    header('Location: index.php');
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
    header('Location: index.php');
    exit;
}

// Get all products from database
$products = getProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WatchShop - Premium Watches</title>
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
                            <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user.php">My Account</a>
                        </li>
                    </ul>
                </section>
            </section>
        </nav>
    </header>

    <main class="container my-4">
        <section class="row mb-4">
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
            <article class="col-md-6">
                <h1>Welcome to WatchShop</h1>
                <p>Discover our premium collection of watches for every occasion.</p>
            </article>
        </section>

        <section class="row">
            <article class="col-md-9">
                <section class="watch-grid">
                    <?php foreach ($products as $product): ?>
                    <article class="watch-card">
                        <img src="<?php echo htmlspecialchars($product['image_location']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="watch-image">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p><?php echo htmlspecialchars($product['description']); ?></p>
                        <p class="price">$<?php echo number_format($product['price']); ?></p>
                        <form method="post" action="index.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </article>
                    <?php endforeach; ?>
                </section>
            </article>

            <aside class="col-md-3">
                <section class="user-section" id="cart-panel">
                    <h2>Shopping Cart</h2>
                    <?php
                    $cartItems = [];
                    $cartTotal = 0;

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

                    if (empty($cartItems)): 
                    ?>
                    <p>Your cart is empty.</p>
                    <?php else: ?>
                    <p>Items in your cart:</p>

                    <?php foreach ($cartItems as $item): ?>
                    <article class="cart-item">
                        <img src="<?php echo htmlspecialchars($item['image_location']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="cart-item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Quantity: <?php echo $item['quantity']; ?></p>
                            <form method="post" action="index.php">
                                <input type="hidden" name="remove_product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_from_cart" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </div>
                        <div class="cart-item-price">$<?php echo number_format($item['price']); ?></div>
                    </article>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <h3>Total: $<?php echo number_format($cartTotal); ?></h3>
                        <a href="user.php" class="btn btn-primary">Checkout</a>
                    </div>
                    <?php endif; ?>
                </section>
            </aside>
        </section>

        <section class="my-5">
            <h2>Upcoming Events</h2>
            <iframe src="https://calendar.google.com/calendar/embed?src=en.usa%23holiday%40group.v.calendar.google.com&ctz=America%2FNew_York" style="border: 0" width="100%" height="400" frameborder="0" scrolling="no"></iframe>
        </section>
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
                        Calle Sol<br>
                        Sevilla, Andalucía, 41003<br>
                        Email: info@watchshop.com<br>
                        Phone: (+34) 567 332 214
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
