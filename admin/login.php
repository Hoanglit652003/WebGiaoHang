<?php
session_start();
require '../config/conn.php';
$mysqli = require '../config/conn.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $mysqli->prepare(
        "SELECT id_nhanVien, password, phanQuyen 
         FROM nhanvien WHERE email = ?"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($id, $hash, $role);
    if ($stmt->fetch() && $password === $hash) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role']    = $role;
        if($role === 1)
            header('Location: dashboard.php');
        else
            header('Location: my_orders.php');
        exit;
    }
    $error = 'Email hoặc mật khẩu không đúng';
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - FlybeeMove</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="company-logo">
            <img src="../admin/img/admin1.png" alt="FlybeeMove Logo">
            <div class="company-name">CÔNG TY FLYBEEMOVE</div>
            <div class="company-subtitle">ADMIN - NHÂN VIÊN</div>
        </div>

        <h2 class="login-title">Đăng nhập</h2>

        <?php if (!empty($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="input-group">
                <label for="email">Email</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" required placeholder="Nhập email của bạn">
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu của bạn">
            </div>
            <button type="submit">Đăng nhập</button>
        </form>
    </div>
</body>
</html>
