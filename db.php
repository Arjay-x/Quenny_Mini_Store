<?php
// Database configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'pos_system';

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Helper function to sanitize input
function sanitize($conn, $input) {
    return htmlspecialchars($conn->real_escape_string($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle login
if (isset($_POST['login'])) {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('pos.php');
            }
        } else {
            $login_error = "Invalid username or password";
        }
    } else {
        $login_error = "Invalid username or password";
    }
    
    $stmt->close();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.html');
}
?>
