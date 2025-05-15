<?php
require_once './config/conn.php';
session_start();

$error = '';
$success = '';

// Kiểm tra xem người dùng đã yêu cầu OTP chưa
if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_email'])) {
    header("Location: index.php?url=forgot_password");
    exit();
}

// Kiểm tra OTP hết hạn (5 phút)
if (time() - $_SESSION['otp_time'] > 300) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['reset_email']);
    unset($_SESSION['otp_time']);
    header("Location: index.php?url=forgot_password");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = $_POST['otp'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($otp) && !empty($new_password) && !empty($confirm_password)) {
        if ($otp === $_SESSION['reset_otp']) {
            if ($new_password === $confirm_password) {
                // Mã hóa mật khẩu mới
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Cập nhật mật khẩu mới
                $stmt = $conn->prepare("UPDATE khachhang SET password = ? WHERE email = ?");
                $stmt->bind_param("ss", $hashed_password, $_SESSION['reset_email']);
                
                if ($stmt->execute()) {
                    // Xóa session OTP
                    unset($_SESSION['reset_otp']);
                    unset($_SESSION['reset_email']);
                    unset($_SESSION['otp_time']);
                    
                    $success = 'Mật khẩu đã được đặt lại thành công. Vui lòng đăng nhập lại.';
                    header("refresh:2;url=index.php?url=login");
                } else {
                    $error = 'Không thể cập nhật mật khẩu. Vui lòng thử lại.';
                }
            } else {
                $error = 'Mật khẩu mới không khớp';
            }
        } else {
            $error = 'Mã OTP không chính xác';
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    }
}
?>

<div class="verify-otp container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <a href="index.php">
                            <img src="public/img/logo flybee.png" alt="Logo" height="40" class="mb-4">
                        </a>
                        <h2 class="fw-bold">Xác thực OTP</h2>
                        <p class="text-muted">Nhập mã OTP đã được gửi đến email của bạn</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?url=verify_otp">
                        <div class="mb-3">
                            <label for="otp" class="form-label">Mã OTP</label>
                            <input type="text" class="form-control" id="otp" name="otp" required 
                                   pattern="[0-9]{6}" maxlength="6" placeholder="Nhập mã OTP 6 số">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đặt lại mật khẩu</button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <a href="index.php?url=forgot_password" class="text-primary">Gửi lại mã OTP</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>