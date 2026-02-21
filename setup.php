<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';

$message = "";
$error = "";

// Check if database connection works
if (!$conn) {
    $error = "Database connection failed! Please check db.php credentials.";
} else {
    $message .= "✓ Database connected successfully<br>";
    
    // Create categories table
    $sql1 = "CREATE TABLE IF NOT EXISTS categories (
        category_id INT AUTO_INCREMENT PRIMARY KEY,
        category_name VARCHAR(100) NOT NULL UNIQUE,
        category_description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql1)) {
        $message .= "✓ Categories table created<br>";
    } else {
        $error .= "Error creating categories: " . $conn->error . "<br>";
    }

    // Create users table
    $sql2 = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql2)) {
        $message .= "✓ Users table created<br>";
    } else {
        $error .= "Error creating users: " . $conn->error . "<br>";
    }

    // Create items table
    $sql3 = "CREATE TABLE IF NOT EXISTS items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        item_price DECIMAL(10,2) NOT NULL,
        item_stocks INT NOT NULL DEFAULT 0,
        item_image VARCHAR(255) DEFAULT NULL,
        category_id INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql3)) {
        $message .= "✓ Items table created<br>";
    } else {
        $error .= "Error creating items: " . $conn->error . "<br>";
    }

    // Create receipts table
    $sql4 = "CREATE TABLE IF NOT EXISTS receipts (
        receipt_id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_date DATE NOT NULL,
        customer_name VARCHAR(100) NOT NULL,
        remarks VARCHAR(255) DEFAULT NULL,
        items_json TEXT NOT NULL,
        total_price DECIMAL(10,2) NOT NULL,
        status ENUM('completed', 'saved') DEFAULT 'completed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    if ($conn->query($sql4)) {
        $message .= "✓ Receipts table created<br>";
    } else {
        $error .= "Error creating receipts: " . $conn->error . "<br>";
    }

    // Insert default users
    $check_user = $conn->query("SELECT COUNT(*) as cnt FROM users");
    $user_exists = 0;
    if ($check_user && $check_user->num_rows > 0) {
        $user_exists = $check_user->fetch_assoc()['cnt'];
    }

    if ($user_exists == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $user_password = password_hash('user123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", 'admin', $admin_password, $Administrator);
        $stmt->execute();
        $stmt->close();
        
        $stmt2 = $conn->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'user')");
        $stmt2->bind_param("sss", 'user', $user_password, $Cashier);
        $stmt2->execute();
        $stmt2->close();
        
        $message .= "✓ Default users created (admin/admin123, user/user123)<br>";
    } else {
        $message .= "✓ Users already exist<br>";
    }

    // Insert default categories
    $check_cat = $conn->query("SELECT COUNT(*) as cnt FROM categories");
    $cat_exists = 0;
    if ($check_cat && $check_cat->num_rows > 0) {
        $cat_exists = $check_cat->fetch_assoc()['cnt'];
    }

    if ($cat_exists == 0) {
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('All Products', 'Show all products')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Noodles', 'All types of noodles')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Can Foods', 'Canned food products')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Beverages', 'Drinks and beverages')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Bread', 'Bread and bakery items')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Snacks', 'Snack items')");
        $conn->query("INSERT INTO categories (category_name, category_description) VALUES ('Others', 'Other products')");
        $message .= "✓ Default categories created<br>";
    }

    // Insert sample items
    $check_items = $conn->query("SELECT COUNT(*) as cnt FROM items");
    $items_exists = 0;
    if ($check_items && $check_items->num_rows > 0) {
        $items_exists = $check_items->fetch_assoc()['cnt'];
    }

    if ($items_exists == 0) {
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
        $message .= "✓ Sample items created<br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Setup - Quenny Store POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); max-width: 500px; width: 100%; }
        h1 { color: #667eea; margin-bottom: 30px; text-align: center; font-size: 28px; }
        .success { color: #155724; background: #d4edda; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .info { color: #0c5460; background: #d1ecf1; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #bee5eb; }
        a { color: #667eea; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 12px 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 10px; margin-top: 15px; font-weight: 600; transition: transform 0.3s; }
        .btn:hover { transform: translateY(-2px); }
        .steps { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .steps h3 { color: #333; margin-bottom: 15px; }
        .steps ol { margin-left: 20px; color: #666; }
        .steps li { margin-bottom: 8px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔧 Database Setup</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!$error && $conn): ?>
            <div class="info">
                <strong>✅ Setup Complete!</strong><br>
                Database tables have been created and sample data inserted.
            </div>
            <div style="text-align: center;">
                <p><a href="login.php">➡ Go to Login Page</a></p>
                <a href="setup.php" class="btn">Run Setup Again</a>
            </div>
        <?php endif; ?>
        
        <div class="steps">
            <h3>📋 Next Steps:</h3>
            <ol>
                <li>Go to <strong>Login Page</strong></li>
                <li>Login with <strong>admin / admin123</strong> for Admin Dashboard</li>
                <li>Login with <strong>user / user123</strong> for POS</li>
            </ol>
        </div>
    </div>
</body>
</html>
