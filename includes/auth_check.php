<?php
// includes/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ถ้าไม่มี session user_id ให้เด้งไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
