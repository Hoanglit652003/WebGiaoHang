<?php
require_once './config/conn.php';
require 'vendor/autoload.php'; // Thêm dòng này để load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
error_reporting(1);
session_start();


$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO lienhe (ten, email, chuDe, tinNhan, ngayGui) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            $result = $stmt->execute();

            if ($result) {
                $success_message = 'Cảm ơn bạn đã gửi tin nhắn. Chúng tôi sẽ liên hệ lại với bạn sớm!';

                // Gửi email thông báo cho quản trị viên sử dụng PHPMailer
                $mail = new PHPMailer(true);
                try {
                    // Cấu hình server
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Thay đổi SMTP server của bạn
                    $mail->SMTPAuth = true;
                    $mail->Username = 'hoanglit652003@gmail.com'; // Email của bạn
                    $mail->Password = 'nhefbuicsvrrtnlz'; // Mật khẩu ứng dụng
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    // Người gửi và người nhận
                    $mail->setFrom($email, $name);
                    $mail->addAddress('hoanglit652003@gmail.com', 'Admin');
                    $mail->addReplyTo($email, $name);

                    // Nội dung email
                    $mail->isHTML(true);
                    $mail->Subject = "Tin nhắn liên hệ mới: " . $subject;
                    $mail->Body = "<div style='font-family: Arial, sans-serif; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;'>
                        <div style='background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%); padding: 25px; text-align: center; border-radius: 8px 8px 0 0;'>
                            <h2 style='color: white; margin: 0; font-size: 24px; text-shadow: 1px 1px 2px rgba(0,0,0,0.1);'>Thông Báo Liên Hệ Mới</h2>
                        </div>
                        
                        <div style='padding: 25px; border: 1px solid #FFE4D6; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);'>
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>Kính gửi Admin,</p>
                            
                            <p style='font-size: 16px; color: #333; margin-bottom: 20px;'>Bạn có một tin nhắn liên hệ mới từ khách hàng:</p>
                            
                            <div style='background-color: #FFF5F0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #FF6B35;'>
                                <p style='margin: 8px 0;'><strong style='color: #FF6B35;'>Họ và tên:</strong> $name</p>
                                <p style='margin: 8px 0;'><strong style='color: #FF6B35;'>Email:</strong> $email</p>
                                <p style='margin: 8px 0;'><strong style='color: #FF6B35;'>Tiêu đề:</strong> $subject</p>
                            </div>
                            
                            <p style='font-size: 16px; color: #333; margin: 20px 0 10px;'><strong>Nội dung tin nhắn:</strong></p>
                            <div style='background-color: #FFF5F0; padding: 20px; border-radius: 8px; border-left: 4px solid #FF6B35; margin: 10px 0 20px;'>
                                <p style='margin: 0; color: #444; line-height: 1.8;'>$message</p>
                            </div>
                            
                            <div style='margin-top: 30px; padding-top: 20px; border-top: 2px solid #FFE4D6;'>
                                <p style='margin: 5px 0; color: #666;'>Trân trọng,</p>
                                <p style='margin: 5px 0; color: #FF6B35; font-weight: bold; font-size: 18px;'>🚚 CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC</p>
                            </div>
                        </div>
                    </div>";

                    $mail->send();
                } catch (Exception $e) {
                    // Log lỗi gửi email nhưng không hiển thị cho người dùng
                    error_log("Lỗi gửi email: " . $mail->ErrorInfo);
                }
            } else {
                $error_message = 'Xin lỗi, đã xảy ra lỗi khi gửi tin nhắn. Vui lòng thử lại sau.';
            }
        } catch (Exception $e) {
            $error_message = 'Xin lỗi, đã xảy ra lỗi khi gửi tin nhắn. Vui lòng thử lại sau.';
        }
    }
}
?>

<div class="contact container py-5">
    <div class="row">
        <!-- Form Liên hệ -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h2 class="card-title mb-4">Liên hệ với chúng tôi</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?url=contact">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name"
                                        required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Địa chỉ email</label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email"
                                        required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Tiêu đề</label>
                            <input type="text" class="form-control form-control-lg" id="subject" name="subject"
                                required value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">Nội dung</label>
                            <textarea class="form-control form-control-lg" id="message" name="message" rows="5"
                                required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Gửi tin nhắn</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Thông tin liên hệ -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h4 class="card-title mb-4">Thông tin liên hệ</h4>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                            12 Lê Thánh Tôn<br>
                            phường Bến Nghé, Quận 1, TP.HCM
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-phone text-primary me-2"></i>
                            +84 123123123
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            info@flybeeshipping.com
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-clock text-primary me-2"></i>
                            Thứ 2 - Thứ 6: 8:00 - 17:30
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bản đồ -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.5177580045246!2d106.69891731533417!3d10.771597192324364!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f3c586421ef%3A0xb606461945d70bc9!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBLaG9hIGjhu41jIFThu7Egbmhpw6puIFRQLiBIQ00!5e0!3m2!1svi!2s!4v1682225583059!5m2!1svi!2s"
                        width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>