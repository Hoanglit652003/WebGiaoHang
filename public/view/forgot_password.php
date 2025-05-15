<?php
require_once './config/conn.php';
require_once './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';

    if (!empty($email)) {
        try {
            // Kiểm tra email có tồn tại không
            $stmt = $conn->prepare("SELECT id_khachHang, tenKhachHang FROM khachhang WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                // Tạo mã OTP ngẫu nhiên 6 số
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                
                // Lưu OTP vào session
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_email'] = $email;
                $_SESSION['otp_time'] = time(); // Lưu thời gian tạo OTP

                // Tạo instance của PHPMailer
                $mail = new PHPMailer(true);

                try {
                    // Cấu hình server
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'hoanglit652003@gmail.com';
                    $mail->Password = 'nhefbuicsvrrtnlz';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    // Người gửi và người nhận
                    $mail->setFrom('hoanglit652003@gmail.com', 'FlybeeMove');
                    $mail->addAddress($email, $user['tenKhachHang']);

                    // Nội dung email
                    $mail->isHTML(true);
                    $mail->Subject = 'Mã xác thực OTP - FlybeeMove';
                    $mail->Body = "
                        <h2>Xin chào {$user['tenKhachHang']},</h2>
                        <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu của bạn.</p>
                        <p>Mã OTP của bạn là: <strong style='font-size: 20px; color: #ff5722;'>{$otp}</strong></p>
                        <p>Mã OTP này sẽ hết hạn sau 5 phút.</p>
                        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                        <p>Trân trọng,<br>FlybeeMove</p>
                    ";

                    $mail->send();
                    header("Location: index.php?url=verify_otp");
                    exit();
                } catch (Exception $e) {
                    $error = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
                }
            } else {
                $error = 'Email không tồn tại trong hệ thống';
            }
        } catch (Exception $e) {
            $error = "Lỗi kết nối: " . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập email của bạn';
    }
}
?>

<div class="forgot-password container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <a href="index.php">
                            <img src="public/img/logo flybee.png" alt="Logo" height="40" class="mb-4">
                        </a>
                        <h2 class="fw-bold">Quên mật khẩu</h2>
                        <p class="text-muted">Nhập email của bạn để nhận mã OTP</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php elseif ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?url=forgot_password">
                        <div class="mb-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Gửi mã OTP</button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <a href="index.php?url=login" class="text-primary">Quay lại đăng nhập</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 