<?php
// START SESSION FIRST - before any other code
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === EZYRO HOSTING DATABASE CONFIGURATION ===
$db_host = 'sql308.ezyro.com';
$db_username = 'ezyro_41209625';
$db_password = '65ymcpx9';
$db_name = 'ezyro_41209625_rnkmdatabase';

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    // Don't die - just set a flag for debugging
    $db_connection_error = "Connection failed: " . $conn->connect_error;
} else {
    $db_connection_error = null;
}

// Set charset to UTF-8
if (!$db_connection_error) {
    $conn->set_charset("utf8mb4");
}

// AUTO-CREATE TABLES IF THEY DON'T EXIST (for first-time setup)
if (!$db_connection_error) {

    // Check and create categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        category_description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check and create users table
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Check and create items table
    $conn->query("CREATE TABLE IF NOT EXISTS items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        item_price DECIMAL(10,2) NOT NULL,
        item_stocks INT NOT NULL DEFAULT 0,
        item_image VARCHAR(255) DEFAULT NULL,
        category_id INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Check and create receipts table
    $conn->query("CREATE TABLE IF NOT EXISTS receipts (
        receipt_id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_date DATE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        remarks VARCHAR(255) DEFAULT NULL,
        items_json TEXT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        status ENUM('completed', 'saved') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert default admin user if not exists
    $check_admin = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE username = 'admin'");
    if ($check_admin && $check_admin->fetch_assoc()['cnt'] == 0) {
        $conn->query("INSERT INTO users (username, password, full_name, role) VALUES ('admin', 'admin123', 'Administrator', 'admin')");
    }

    // Insert default user if not exists
    $check_user = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE username = 'user'");
    if ($check_user && $check_user->fetch_assoc()['cnt'] == 0) {
        $conn->query("INSERT INTO users (username, password, full_name, role) VALUES ('user', 'user123', 'Cashier', 'user')");
    }

    // Insert default categories if not exists
    $check_cat = $conn->query("SELECT COUNT(*) as cnt FROM categories");
    if ($check_cat && $check_cat->fetch_assoc()['cnt'] == 0) {
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('All Products', 'Show all products')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Noodles', 'All types of noodles')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Can Foods', 'Canned food products')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Beverages', 'Drinks and beverages')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Bread', 'Bread and bakery items')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Snacks', 'Snack items')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Others', 'Other products')");
    }

    // Insert sample items if not exists
    $check_items = $conn->query("SELECT COUNT(*) as cnt FROM items");
    if ($check_items && $check_items->fetch_assoc()['cnt'] == 0) {
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Coke', 25.00, 100, 4)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Pepsi', 25.00, 100, 4)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Water', 20.00, 50, 4)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Milk', 55.00, 40, 4)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Chicken Noodles', 35.00, 80, 2)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Beef Noodles', 40.00, 75, 2)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Pancit Canton', 30.00, 60, 2)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Lucky Me Noodles', 15.00, 100, 2)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Corned Beef', 65.00, 40, 3)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Spam', 85.00, 35, 3)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Sardines', 35.00, 50, 3)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Tuna', 55.00, 45, 3)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Bread', 45.00, 30, 5)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Pandesal', 2.00, 100, 5)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Loaf Bread', 60.00, 25, 5)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Chips', 30.00, 50, 6)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Cookies', 25.00, 40, 6)");
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('Chocolate Bar', 20.00, 60, 6)");
    }
}

// Helper function to sanitize input
function sanitize($conn, $input) {
    if (is_object($conn) && method_exists($conn, 'real_escape_string')) {
        return htmlspecialchars($conn->real_escape_string($input), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}
?>
