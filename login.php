<?php
// Process login FIRST - before any HTML output
require_once 'db.php';

$login_error = '';

// Handle login form submission
if (isset($_POST['login'])) {
    $username = sanitize($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Check if database connection exists
    if (!isset($conn) || (isset($db_connection_error) && $db_connection_error)) {
        $login_error = "Database connection error. Please try again later.";
    } else {
        // Prepare and execute query
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ?");
        
        if (!$stmt) {
            $login_error = "Database error. Please try again.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password - accept both hashed and plain text
                $password_match = false;
                if (password_verify($password, $user['password'])) {
                    $password_match = true;
                } elseif ($user['password'] === $password) {
                    $password_match = true;
                }
                
                if ($password_match) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        redirect('dashboard.php');
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
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Quenny Store POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { min-height: 100vh; display: flex; justify-content: center; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; position: relative; overflow: hidden; }
        body::before { content: ''; position: absolute; width: 500px; height: 500px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; top: -100px; right: -100px; animation: float 6s ease-in-out infinite; }
        body::after { content: ''; position: absolute; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.1); border-radius: 50%; bottom: -50px; left: -50px; animation: float 8s ease-in-out infinite reverse; }
        @keyframes float { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-20px) rotate(5deg); } }
        .login-container { background: white; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); width: 100%; max-width: 420px; padding: 40px; position: relative; z-index: 1; animation: slideUp 0.6s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .login-header { text-align: center; margin-bottom: 35px; }
        .logo { width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4); }
        .logo svg { width: 36px; height: 36px; fill: white; }
        .login-header h1 { font-size: 24px; font-weight: 700; color: #1a1a2e; margin-bottom: 8px; }
        .login-header p { color: #6b7280; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #374151; font-size: 14px; font-weight: 500; }
        .input-wrapper { position: relative; }
        .input-wrapper svg { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; fill: #9ca3af; transition: fill 0.3s ease; }
        .form-group input { width: 100%; padding: 14px 14px 14px 45px; border: 2px solid #e5e7eb; border-radius: 12px; font-size: 14px; font-weight: 400; color: #1a1a2e; background: #f9fafb; transition: all 0.3s ease; }
        .form-group input:focus { outline: none; border-color: #667eea; background: white; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .form-group input:focus + svg, .input-wrapper:focus-within svg { fill: #667eea; }
        .form-group input::placeholder { color: #9ca3af; }
        .remember-forgot { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 13px; }
        .remember-me { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .remember-me input[type="checkbox"] { width: 18px; height: 18px; accent-color: #667eea; cursor: pointer; }
        .remember-me span { color: #6b7280; }
        .login-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 12px; color: white; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5); }
        .login-btn:active { transform: translateY(0); }
        .divider { display: flex; align-items: center; margin: 25px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
        .divider span { padding: 0 15px; color: #9ca3af; font-size: 13px; }
        .demo-credentials { background: #f8fafc; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
        .demo-credentials p { font-size: 12px; color: #6b7280; margin-bottom: 8px; font-weight: 500; }
        .demo-credentials .credential { display: flex; justify-content: space-between; font-size: 11px; color: #374151; padding: 4px 0; }
        .demo-credentials .credential span:last-child { font-family: monospace; background: #e5e7eb; padding: 2px 8px; border-radius: 4px; }
        .footer { text-align: center; margin-top: 25px; font-size: 13px; color: #9ca3af; }
        .error-message { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 12px 15px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; display: none; animation: shake 0.5s ease; }
        .error-message.show { display: block; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
        @media (max-width: 480px) { .login-container { padding: 30px 25px; } .login-header h1 { font-size: 22px; } .logo { width: 60px; height: 60px; } }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17 2H7C5.9 2 5 2.9 5 4V20C5 21.1 5.9 22 7 22H17C18.1 22 19 21.1 19 20V4C19 2.9 18.1 2 17 2ZM7 20V4H17V20H7ZM9 6H15V8H9V6ZM9 10H15V12H9V10ZM9 14H15V16H9V14ZM9 18H15V20H9V18Z"/></svg>
            </div>
            <h1>Quenny Store POS</h1>
            <p>Sign in to access your account</p>
        </div>

        <?php if ($login_error): ?>
            <div class="error-message show" id="errorMessage">
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" name="login" class="login-btn">Sign In</button>
        </form>

        <div class="divider">
            <span>Demo Credentials</span>
        </div>

        <div class="demo-credentials">
            <p>Use these credentials to test:</p>
            <div class="credential">
                <span>Admin:</span>
                <span>admin / admin123</span>
            </div>
            <div class="credential">
                <span>User:</span>
                <span>user / user123</span>
            </div>
        </div>

        <div class="footer">
            <p>&copy; 2024 Quenny Store POS. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorMessage = document.getElementById('errorMessage');

            if (!username || !password) {
                e.preventDefault();
                return;
            }

            if (password.length < 6) {
                e.preventDefault();
                return;
            }
        });
    </script>
    
    <!-- Google Adsense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2862802439514164"
     crossorigin="anonymous"></script>
</body>
</html>
