<?php
require_once './config/conn.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?url=login');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM khachhang WHERE id_khachHang = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission for profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Start transaction
        $conn->begin_transaction();

        // Update basic information
        $update_stmt = $conn->prepare("UPDATE khachhang SET tenKhachHang = ?, soDienThoai = ?, diaChi = ? WHERE id_khachHang = ?");
        $update_stmt->bind_param("sssi", $name, $phone, $address, $user_id);
        $update_stmt->execute();

        // If password change is requested
        if (!empty($current_password) && !empty($new_password)) {
            if ($new_password === $confirm_password) {
                // Verify current password
                if (password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_stmt = $conn->prepare("UPDATE khachhang SET password = ? WHERE id_khachHang = ?");
                    $password_stmt->bind_param("si", $hashed_password, $user_id);
                    $password_stmt->execute();
                } else {
                    throw new Exception("Mật khẩu hiện tại không chính xác");
                }
            } else {
                throw new Exception("Mật khẩu mới không khớp");
            }
        }

        $conn->commit();
        $success_message = "Cập nhật thông tin thành công!";
        
        // Refresh user data
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title text-center mb-4">Thông tin tài khoản</h2>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['tenKhachHang']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['soDienThoai']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Địa chỉ</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user['diaChi']); ?></textarea>
                        </div>

                        <hr class="my-4">
                        <h4 class="mb-3">Đổi mật khẩu</h4>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Cập nhật thông tin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 