<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.html');
exit;
?>
