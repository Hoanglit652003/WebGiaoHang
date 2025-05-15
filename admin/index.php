<?php
session_start();

// Kiểm tra nếu đã đăng nhập
if(isset($_SESSION['user_id'])) {
    // Nếu đã đăng nhập, chuyển hướng đến dashboard
    $role = $_SESSION['role'];
    if($role === 1)
            header('Location: dashboard.php');
        else
            header('Location: my_orders.php');
    exit();
} else {
    // Nếu chưa đăng nhập, chuyển hướng đến trang login
    header("Location: login.php");
    exit();
}
?> 