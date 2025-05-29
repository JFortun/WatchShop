<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'watchshop';
$username = 'root';

// Initialize database connection
function connectDB() {
    global $host, $dbname, $username;
    try {
        $conn = new PDO("mysql:host=$host", $username);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Check if database exists, if not create it
        $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
        $conn->exec("USE $dbname");

        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database tables and data
function initializeDatabase(): void
{
    $conn = connectDB();

    // Create tables if they don't exist
    $clientTable = "CREATE TABLE IF NOT EXISTS client (
        id INT(128) NOT NULL PRIMARY KEY,
        name VARCHAR(64) NOT NULL,
        password VARCHAR(64) NOT NULL
    ) COMMENT 'Table that holds information about the clients'";

    $productTable = "CREATE TABLE IF NOT EXISTS product (
        id INT(128) NOT NULL PRIMARY KEY,
        name VARCHAR(128) NOT NULL,
        price INT(255) NOT NULL,
        image_location VARCHAR(128) NOT NULL,
        description VARCHAR(512) NOT NULL
    ) COMMENT 'Table that holds the information about products'";

    $clientProductTable = "CREATE TABLE IF NOT EXISTS client_product (
        id_client INT(128) NOT NULL,
        id_product INT(128) NOT NULL,
        amount INT(128) NOT NULL,
        PRIMARY KEY (id_client, id_product),
        CONSTRAINT client_id_fk FOREIGN KEY (id_client) REFERENCES client (id) ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT product_id_fk FOREIGN KEY (id_product) REFERENCES product (id) ON UPDATE CASCADE ON DELETE CASCADE
    ) COMMENT 'Table with relation between clients and products'";

    $conn->exec($clientTable);
    $conn->exec($productTable);
    $conn->exec($clientProductTable);

    // Check if pepe user exists, if not create it
    $stmt = $conn->prepare("SELECT COUNT(*) FROM client WHERE name = :name");
    $stmt->bindParam(':name', $name);
    $name = "pepe@mail.com";
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $stmt = $conn->prepare("INSERT INTO client (id, name, password) VALUES (:id, :name, :password)");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':password', $password);

        $id = 1;
        $name = "pepe@mail.com";
        $password = "pepe";
        $stmt->execute();
    }

    // Check if products exist, if not create them
    $stmt = $conn->prepare("SELECT COUNT(*) FROM product");
    $stmt->execute();

    if ($stmt->fetchColumn() == 0) {
        $products = [
            [1, "Elegant Classic", 29, "resources/watch1.png", "A timeless design with premium materials."],
            [2, "Sport Pro", 249, "resources/watch2.png", "Perfect for active lifestyles and sports enthusiasts."],
            [3, "Minimalist", 199, "resources/watch3.png", "Clean design with essential features."],
            [4, "Luxury Gold", 49, "resources/watch4.png", "Premium gold-plated watch for special occasions."],
            [5, "Smart Watch", 149, "resources/watch5.png", "Connect to your digital life with style."],
            [6, "Diver Pro", 39, "resources/watch6.png", "Water-resistant up to 200m for underwater adventures."],
            [7, "Vintage Collection", 220, "resources/watch7.png", "Classic design inspired by the 1960s."]
        ];

        $stmt = $conn->prepare("INSERT INTO product (id, name, price, image_location, description) VALUES (:id, :name, :price, :image_location, :description)");

        foreach ($products as $product) {
            $stmt->bindParam(':id', $product[0]);
            $stmt->bindParam(':name', $product[1]);
            $stmt->bindParam(':price', $product[2]);
            $stmt->bindParam(':image_location', $product[3]);
            $stmt->bindParam(':description', $product[4]);
            $stmt->execute();
        }
    }
}

// Get all products
function getProducts(): array
{
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM product");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get a specific product by ID
function getProductById($id) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM product WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Authenticate user
function authenticateUser($email, $password) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT * FROM client WHERE name = :email AND password = :password");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add purchase to database
function addPurchase($clientId, $cart): void
{
    $conn = connectDB();
    $stmt = $conn->prepare("INSERT INTO client_product (id_client, id_product, amount) VALUES (:clientId, :productId, :amount) ON DUPLICATE KEY UPDATE amount = amount + :amount");

    foreach ($cart as $productId => $amount) {
        $stmt->bindParam(':clientId', $clientId);
        $stmt->bindParam(':productId', $productId);
        $stmt->bindParam(':amount', $amount);
        $stmt->execute();
    }
}

// Get purchase history for a client
function getPurchaseHistory($clientId): array
{
    $conn = connectDB();
    $stmt = $conn->prepare("
        SELECT cp.id_product, cp.amount, p.name, p.price, p.image_location, p.description 
        FROM client_product cp 
        JOIN product p ON cp.id_product = p.id 
        WHERE cp.id_client = :clientId
    ");
    $stmt->bindParam(':clientId', $clientId);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Return a product (subtract from client_product or delete if amount is 1)
function returnProduct($clientId, $productId): bool
{
    $conn = connectDB();

    // First, check the current amount
    $stmt = $conn->prepare("SELECT amount FROM client_product WHERE id_client = :clientId AND id_product = :productId");
    $stmt->bindParam(':clientId', $clientId);
    $stmt->bindParam(':productId', $productId);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return false; // Product not found in client's purchase history
    }

    $currentAmount = $result['amount'];

    if ($currentAmount > 1) {
        // If more than one, decrement the amount
        $stmt = $conn->prepare("UPDATE client_product SET amount = amount - 1 WHERE id_client = :clientId AND id_product = :productId");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->bindParam(':productId', $productId);
        return $stmt->execute();
    } else {
        // If only one, delete the row
        $stmt = $conn->prepare("DELETE FROM client_product WHERE id_client = :clientId AND id_product = :productId");
        $stmt->bindParam(':clientId', $clientId);
        $stmt->bindParam(':productId', $productId);
        return $stmt->execute();
    }
}

// Initialize the database on script load
initializeDatabase();
