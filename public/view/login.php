<?php
require_once './config/conn.php';
session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        try {
            // Kiểm tra email
            $stmt = $conn->prepare("SELECT id_khachHang, password, tenKhachHang FROM khachhang WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            
            // Kiểm tra người dùng            
            if ($user) {
                // So sánh mật khẩu đã mã hóa
                if (password_verify($password, $user['password'])) {
                    // Lưu thông tin người dùng vào session
                    $_SESSION['id_khach'] = $user['id_khachHang'];
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $user['tenKhachHang'];

                    header("Location: index.php?url=trangchu");
                    exit();
                } else {
                    $error = 'Mật khẩu không chính xác';
                }
            } else {
                $error = 'Email không tồn tại';
            }
        } catch (PDOException $e) {
            $error = "Lỗi kết nối: " . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    }
}
?>

<div class="login container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <a href="index.php">
                            <img src="public/img/logo flybee.png" alt="Logo" height="40" class="mb-4">
                        </a>
                        <h2 class="fw-bold">Chào mừng bạn</h2>
                        <p class="text-muted">Hãy nhập tài khoản bên dưới nhé</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="POST" action="index.php?url=login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Mật khẩu</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đăng nhập</button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Bạn chưa có tài khoản? <a href="index.php?url=register" class="text-primary">Đăng ký</a></p>
                        <a href="index.php?url=forgot_password" class="text-muted">Quên mật khẩu</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>