<?php
// Simple setup script to create database and tables

$host = "localhost";
$username = "root";
$password = "";
$db_name = "pos_system";

$success_msg = "";
$error_msg = "";

// Connect without database first
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    $error_msg = "Connection failed: " . $conn->connect_error;
} else {
    $success_msg .= "Connected to MySQL successfully!\n";
    
    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if ($conn->query($sql)) {
        $success_msg .= "Database '$db_name' created successfully!\n";
    } else {
        $error_msg .= "Error creating database: " . $conn->error . "\n";
    }
    
    // Select database
    $conn->select_db($db_name);
    
    // Drop existing tables to start fresh
    $conn->query("DROP TABLE IF EXISTS receipts");
    $conn->query("DROP TABLE IF EXISTS items");
    $conn->query("DROP TABLE IF EXISTS users");
    $conn->query("DROP TABLE IF EXISTS categories");
    
    // Create categories table
    $sql = "CREATE TABLE categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        category_description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    $success_msg .= "Categories table created!\n";
    
    // Create items table
    $sql = "CREATE TABLE items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        item_price DECIMAL(10,2) NOT NULL,
        item_stocks INT NOT NULL DEFAULT 0,
        item_image VARCHAR(255) DEFAULT NULL,
        category_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    $success_msg .= "Items table created!\n";
    
    // Create users table
    $sql = "CREATE TABLE users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    $success_msg .= "Users table created!\n";
    
    // Create receipts table
    $sql = "CREATE TABLE receipts (
        receipt_id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_date DATE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        remarks VARCHAR(255) DEFAULT NULL,
        items_json TEXT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        status ENUM('completed', 'saved') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    $success_msg .= "Receipts table created!\n";
    
    // Insert categories
    $categories = [
        ['All Products', 'Show all products'],
        ['Noodles', 'All types of noodles'],
        ['Can Foods', 'Canned food products'],
        ['Beverages', 'Drinks and beverages'],
        ['Bread', 'Bread and bakery items'],
        ['Snacks', 'Snack items'],
        ['Others', 'Other products']
    ];
    
    foreach ($categories as $cat) {
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('$cat[0]', '$cat[1]')");
    }
    $success_msg .= "Categories inserted!\n";
    
    // Insert sample items
    $items = [
        ['Coke', 25.00, 100, 4],
        ['Pepsi', 25.00, 100, 4],
        ['Water', 20.00, 50, 4],
        ['Milk', 55.00, 40, 4],
        ['Chicken Noodles', 35.00, 80, 2],
        ['Beef Noodles', 40.00, 75, 2],
        ['Pancit Canton', 30.00, 60, 2],
        ['Lucky Me Noodles', 15.00, 100, 2],
        ['Corned Beef', 65.00, 40, 3],
        ['Spam', 85.00, 35, 3],
        ['Sardines', 35.00, 50, 3],
        ['Tuna', 55.00, 45, 3],
        ['Bread', 45.00, 30, 5],
        ['Pandesal', 2.00, 100, 5],
        ['Loaf Bread', 60.00, 25, 5],
        ['Chips', 30.00, 50, 6],
        ['Cookies', 25.00, 40, 6],
        ['Chocolate Bar', 20.00, 60, 6]
    ];
    
    foreach ($items as $item) {
        $conn->query("INSERT INTO items (item_name, item_price, item_stocks, category_id) VALUES ('$item[0]', $item[1], $item[2], $item[3])");
    }
    $success_msg .= "Sample items inserted!\n";
    
    // Insert default users with proper password hashing
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $user_password = password_hash("user123", PASSWORD_DEFAULT);
    
    // Insert admin directly
    $conn->query("INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$admin_password', 'Administrator', 'admin')");
    
    // Insert user directly
    $conn->query("INSERT INTO users (username, password, full_name, role) VALUES ('user', '$user_password', 'Cashier', 'user')");
    $success_msg .= "Default users created!\n";
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quenny Store POS - Database Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f0f2f5; }
        h2 { color: #1a1a2e; }
        .success { color: #065f46; background: #d1fae5; padding: 15px; border-radius: 8px; margin: 10px 0; white-space: pre-line; }
        .error { color: #991b1b; background: #fee2e2; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .credentials { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; }
        a { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin: 5px; }
        a:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
    </style>
</head>
<body>
<h2>Quenny Store POS - Database Setup</h2>

<?php if ($error_msg): ?>
<div class="error"><?php echo $error_msg; ?></div>
<?php endif; ?>

<?php if ($success_msg): ?>
<div class="success"><?php echo $success_msg; ?></div>
<?php endif; ?>

<?php if (!$error_msg): ?>
<div class="credentials">
    <p><strong>You can now login with these credentials:</strong></p>
    <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
    <p>User: <strong>user</strong> / <strong>user123</strong></p>
</div>

<a href="login.php">Go to Login Page</a>
<?php endif; ?>

</body>
</html>
