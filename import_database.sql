-- Import this into your ezyro database: ezyro_41209625_rnkmdatabase

-- Table 0: Categories
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    category_description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 1: Items
CREATE TABLE IF NOT EXISTS items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,
    item_stocks INT NOT NULL DEFAULT 0,
    item_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table 2: Users (with plain text passwords for testing)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table 3: Receipts
CREATE TABLE IF NOT EXISTS receipts (
    receipt_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_date DATE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    items_json TEXT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('completed', 'saved') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (plain text password: admin123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', 'admin123', 'Administrator', 'admin');

-- Insert default user (plain text password: user123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('user', 'user123', 'Cashier', 'user');

-- Insert sample items
INSERT INTO items (item_name, item_price, item_stocks) VALUES
('Coke', 25.00, 100),
('Pepsi', 25.00, 100),
('Water', 20.00, 50),
('Milk', 55.00, 40),
('Chicken Noodles', 35.00, 80),
('Beef Noodles', 40.00, 75),
('Corned Beef', 65.00, 40),
('Bread', 45.00, 30),
('Chips', 30.00, 50),
('Cookies', 25.00, 40);
