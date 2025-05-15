<?php
require 'header1.php';
require '../config/conn.php';
require 'vendor/autoload.php';    // Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra đăng nhập
$idNhanVien = $_SESSION['user_id'] ?? null;
if (!$idNhanVien) {
    header('Location: login.php');
    exit;
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maVanDon'])) {
    $maVanDon = $_POST['maVanDon'];
    $trangThaiMoi = $_POST['trangThai'] ?? '';
    $idNhanVien = $_SESSION['user_id'];

    // Lấy id_trangThai từ tên trạng thái
    $stmtTrangThai = $mysqli->prepare("SELECT id_trangThai FROM TrangThai WHERE tenTrangThai = ?");
    $stmtTrangThai->bind_param("s", $trangThaiMoi);
    $stmtTrangThai->execute();
    $result = $stmtTrangThai->get_result();
    $trangThaiRow = $result->fetch_assoc();
    $idTrangThai = $trangThaiRow['id_trangThai'];

    // 1) Cập nhật trạng thái đơn hàng
    $stmt = $mysqli->prepare(
        "UPDATE DonHang 
         SET id_trangThai = ? 
         WHERE maVanDon = ? 
           AND id_nhanVien = ?"
    );
    $stmt->bind_param("isi", $idTrangThai, $maVanDon, $idNhanVien);

    if ($stmt->execute()) {
        // Nếu trạng thái là "Đã giao", cập nhật doanh thu
        if ($trangThaiMoi === 'Đã giao') {
            // Lấy thông tin phí của đơn hàng
            $stmtPhi = $mysqli->prepare("
                SELECT tongPhi 
                FROM phi 
                WHERE maVanDon = ?
            ");
            $stmtPhi->bind_param("s", $maVanDon);
            $stmtPhi->execute();
            $resultPhi = $stmtPhi->get_result();
            $phiRow = $resultPhi->fetch_assoc();
            
            if ($phiRow) {
                // Thêm vào bảng doanhthu
                $stmtDoanhThu = $mysqli->prepare("
                    INSERT INTO doanhthu (maVanDon, tongTien, ngayTinh) 
                    VALUES (?, ?, CURDATE())
                ");
                $stmtDoanhThu->bind_param("sd", $maVanDon, $phiRow['tongPhi']);
                $stmtDoanhThu->execute();
            }
        }
    
        // 2) Thêm vào lịch sử trạng thái
        $stmtLichSu = $mysqli->prepare(
            "INSERT INTO LichSu_TrangThai (maVanDon, id_trangThai, mocThoiGian, diaDiem, HIMnotes) 
             VALUES (?, ?, NOW(), 'Thành phố Hồ Chí Minh', ?)"
        );
        $ghiChu = $trangThaiMoi;
        $stmtLichSu->bind_param("sis", $maVanDon, $idTrangThai, $ghiChu);
        $stmtLichSu->execute();

        // 3) Thêm thông báo cho nhân viên
        $noiDungTB = "Đơn hàng $maVanDon đã được cập nhật trạng thái: $trangThaiMoi";
        $stmtTB = $mysqli->prepare(
            "INSERT INTO ThongBao (id_NhanVien, noiDung, ngayTao, trangThai) 
             VALUES (?, ?, NOW(), 'Chưa đọc')"
        );
        $stmtTB->bind_param("is", $idNhanVien, $noiDungTB);
        $stmtTB->execute();

        // 4) Lấy email và tên khách hàng
        $stmtEmail = $mysqli->prepare(
            "SELECT k.email, k.tenKhachHang
             FROM donhang d
             JOIN khachhang k ON d.id_khachHang = k.id_khachHang
             WHERE d.maVanDon = ?"
        );
        $stmtEmail->bind_param("s", $maVanDon);
        $stmtEmail->execute();
        $stmtEmail->bind_result($emailKH, $tenKH);
        $stmtEmail->fetch();
        $stmtEmail->close();

        // 5) Gửi email thông báo cho khách hàng bằng PHPMailer
        if (filter_var($emailKH, FILTER_VALIDATE_EMAIL)) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';            // thay SMTP của bạn nếu cần
                $mail->SMTPAuth   = true;
                $mail->Username   = 'hoanglit652003@gmail.com';      // email SMTP
                $mail->Password   = 'nhefbuicsvrrtnlz';         // app password hoặc mật khẩu SMTP
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('hoanglit652003@gmail.com', 'CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC');
                $mail->addAddress($emailKH, $tenKH);

                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';       // mã hoá Base64 sẽ giữ nguyên dấu
                $mail->setLanguage('vi');  
                // Content
                $mail->isHTML(true);
                $mail->Subject = "Cập nhật trạng thái đơn hàng $maVanDon";
                $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;'>
                    <div style='text-align: center; margin-bottom: 20px;'>
                        <h2 style='color: #2c3e50; margin: 0;'>FLYBEEMOVE</h2>
                        <p style='color: #7f8c8d; margin: 5px 0;'>Chuyển phát nhanh toàn quốc</p>
                    </div>
                    
                    <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;'>
                        <p style='margin: 0 0 15px 0;'>Kính gửi <strong>$tenKH</strong>,</p>
                        
                        <p style='margin: 0 0 15px 0;'>Chúng tôi xin thông báo đơn hàng của quý khách với mã vận đơn:</p>
                        
                        <div style='background-color: #fff; padding: 15px; border-radius: 5px; margin: 15px 0; text-align: center;'>
                            <strong style='color: #e74c3c; font-size: 18px;'>$maVanDon</strong>
                        </div>
                        
                        <p style='margin: 0 0 15px 0;'>đã được cập nhật trạng thái:</p>
                        
                        <div style='background-color: #2ecc71; color: white; padding: 10px; border-radius: 5px; text-align: center; margin: 15px 0;'>
                            <strong>$trangThaiMoi</strong>
                        </div>
                    </div>
                    
                    <div style='text-align: center; margin-top: 30px;'>
                        <p style='margin: 0 0 10px 0;'>Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!</p>
                        <p style='margin: 0; color: #7f8c8d;'>Trân trọng,<br>🚚 CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC</p>
                    </div>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; font-size: 12px; color: #95a5a6;'>
                        <p style='margin: 0;'>Đây là email tự động, vui lòng không trả lời email này.</p>
                    </div>
                </div>";

                // Set plain text version for email clients that don't support HTML
                $mail->AltBody = "Kính gửi $tenKH,\n\n"
                               . "Đơn hàng của quý khách với mã vận đơn $maVanDon "
                               . "hiện đã được cập nhật trạng thái: $trangThaiMoi.\n\n"
                               . "Cảm ơn quý khách đã sử dụng dịch vụ của chúng tôi!\n"
                               . "Trân trọng,\n"
                               . "🚚 CÔNG TY FLYBEEMOVE CHUYỂN PHÁT NHANH TOÀN QUỐC";

                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error ({$emailKH}): " . $mail->ErrorInfo);
                echo "<script>
                        alert('Không gửi được email: " . addslashes($mail->ErrorInfo) . "');
                      </script>";
            }
        }

        // 6) Thông báo trên trình duyệt và reload trang
        echo "<script>
                alert('Cập nhật trạng thái thành công và đã gửi email cho khách hàng!');
                window.location.href = window.location.pathname;
              </script>";
        exit;
    } else {
        echo "<script>
                alert('Lỗi cập nhật trạng thái: " . addslashes($stmt->error) . "');
              </script>";
    }
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug information
echo "<!-- Debug: idNhanVien = " . $idNhanVien . " -->";

// First check if there are any orders assigned to this staff member
$checkStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM DonHang WHERE id_nhanVien = ?");
$checkStmt->bind_param("i", $idNhanVien);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$totalOrders = $checkResult->fetch_assoc()['total'];
echo "<!-- Debug: Total orders for staff = " . $totalOrders . " -->";

// Debug: Check SanPham table
$checkSanPham = $mysqli->prepare("SELECT * FROM sanpham WHERE maVanDon IN (SELECT maVanDon FROM donhang WHERE id_nhanVien = ?)");
$checkSanPham->bind_param("i", $idNhanVien);
$checkSanPham->execute();
$sanPhamResult = $checkSanPham->get_result();
echo "<!-- Debug: Found " . $sanPhamResult->num_rows . " products for staff's orders -->";
if ($row = $sanPhamResult->fetch_assoc()) {
    echo "<!-- Debug: Sample product data: " . print_r($row, true) . " -->";
}

// Lấy danh sách đơn đang giao kèm thông tin chi tiết
$stmt = $mysqli->prepare(
    "SELECT D.maVanDon, D.ngayTaoDon, T.tenTrangThai,
            S.tenSanPham, N.tenNguoiNhan, N.soDienThoai, DC.diaChiNguoiNhan
     FROM donhang D
     LEFT JOIN nguoinhan N ON D.id_nguoiNhan = N.id_nguoiNhan
     LEFT JOIN diachi DC ON N.id_diaChi = DC.id_diaChi
     LEFT JOIN sanpham S ON D.maVanDon = S.maVanDon
     LEFT JOIN trangthai T ON D.id_trangThai = T.id_trangThai
     WHERE D.id_nhanVien = ? AND T.tenTrangThai = 'Đang giao'
     ORDER BY D.ngayTaoDon DESC"
);

$stmt->bind_param("i", $idNhanVien);
$stmt->execute();
$orders = $stmt->get_result();

// Debug: Check if we got any results
if ($orders->num_rows === 0) {
    echo "<!-- Debug: No orders found for this staff member -->";
    
    // Debug: Let's check each table separately
    $tables = [
        'DonHang' => "SELECT * FROM DonHang WHERE id_nhanVien = " . $idNhanVien,
        'NguoiNhan' => "SELECT * FROM NguoiNhan",
        'DiaChi' => "SELECT * FROM DiaChi",
        'SanPham' => "SELECT * FROM SanPham",
        'TrangThai' => "SELECT * FROM TrangThai"
    ];
    
    foreach ($tables as $table => $sql) {
        $result = $mysqli->query($sql);
        echo "<!-- Debug: Table $table has " . $result->num_rows . " rows -->";
        if ($row = $result->fetch_assoc()) {
            echo "<!-- Debug: Sample $table data: " . print_r($row, true) . " -->";
        }
    }
}

// Debug: Print the first row if exists
if ($row = $orders->fetch_assoc()) {
    echo "<!-- Debug: First row data: " . print_r($row, true) . " -->";
    // Reset the result pointer
    $orders->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng đang giao</title>
    <link rel="stylesheet" href="./css/donhang_danggiao.css">
    <link rel="stylesheet" href="./css/footer.css">
</head>
<body>
    <div class="content-wrapper">
    <div class="card-orders">
        <div class="card-header">
            <i class="bi bi-truck"></i>
            Danh sách đơn hàng đang giao
            
        </div>
        <table class="table-orders">
            <tr>
                <th>Mã vận đơn</th>
                <th>Sản phẩm</th>
                <th>Người nhận</th>
                <th>Địa chỉ</th>
                <th>Ngày tạo</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            <?php 
            if ($orders->num_rows === 0): 
            ?>
            <tr>
                <td colspan="7" style="text-align: center;">Không có đơn hàng nào đang giao</td>
            </tr>
            <?php 
            else:
                while ($don = $orders->fetch_assoc()): 
            ?>
            <tr>
                <td class="bold"><?= htmlspecialchars($don['maVanDon'] ?? '') ?></td>
                <td><?= htmlspecialchars($don['tenSanPham'] ?? 'N/A') ?></td>
                <td>
                    <?= htmlspecialchars($don['tenNguoiNhan'] ?? 'N/A') ?><br>
                    <span style="color:#222;font-size:15px"><i class="bi bi-telephone"></i> <?= htmlspecialchars($don['soDienThoai'] ?? 'N/A') ?></span>
                </td>
                <td><?= htmlspecialchars($don['diaChiNguoiNhan'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($don['ngayTaoDon'] ?? 'N/A') ?></td>
                <td><span class="status-label"><?= htmlspecialchars($don['tenTrangThai'] ?? 'N/A') ?></span></td>
                <td>
                    <button
                        class="btn btn-primary btn-update"
                        data-ma="<?= htmlspecialchars($don['maVanDon'] ?? '') ?>"
                        data-product="<?= htmlspecialchars($don['tenSanPham'] ?? 'N/A') ?>"
                        data-recipient="<?= htmlspecialchars($don['tenNguoiNhan'] ?? 'N/A') ?>"
                        data-phone="<?= htmlspecialchars($don['soDienThoai'] ?? 'N/A') ?>"
                        data-address="<?= htmlspecialchars($don['diaChiNguoiNhan'] ?? 'N/A') ?>"
                        data-date="<?= htmlspecialchars($don['ngayTaoDon'] ?? 'N/A') ?>"
                        data-status="<?= htmlspecialchars($don['tenTrangThai'] ?? 'N/A') ?>"
                    ><i class="bi bi-pencil-square"></i> Cập nhật</button>
                </td>
            </tr>
            <?php 
                endwhile;
            endif;
            ?>
        </table>
    </div>
</div>

<!-- Modal cập nhật và xem chi tiết -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <button class="btn-close" id="closeModal">&times;</button>
        <div class="modal-header"><h3>Chi tiết đơn hàng</h3></div>
        <div class="modal-body" id="modalBody">
            <!-- Nội dung sẽ được JS chèn vào -->
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('updateModal');
const modalBody = document.getElementById('modalBody');
const closeModal = document.getElementById('closeModal');

// Đóng modal
closeModal.addEventListener('click', () => modal.style.display = 'none');
window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

// Xử lý click Chi tiết & Cập nhật
document.querySelectorAll('.btn-update').forEach(btn => {
    btn.addEventListener('click', () => {
        const ma = btn.dataset.ma;
        const product = btn.dataset.product;
        const recipient = btn.dataset.recipient;
        const phone = btn.dataset.phone;
        const address = btn.dataset.address;
        const date = btn.dataset.date;
        const status = btn.dataset.status;
        
        // Tạo HTML chi tiết + form
        let html = `
            <ul>
                <li><strong>Mã vận đơn:</strong> ${ma}</li>
                <li><strong>Sản phẩm:</strong> ${product}</li>
                <li><strong>Người nhận:</strong> ${recipient}</li>
                <li><strong>Số điện thoại:</strong> <i class='bi bi-telephone'></i> ${phone}</li>
                <li><strong>Địa chỉ:</strong> ${address}</li>
                <li><strong>Ngày tạo:</strong> ${date}</li>
                <li><strong>Trạng thái hiện tại:</strong> ${status}</li>
            </ul>
            <hr>
            <form method="post">
                <input type="hidden" name="maVanDon" value="${ma}">
                <label for="trangThai">Chọn trạng thái mới:</label><br>
                <select name="trangThai" id="trangThai" required>
                    <option value="">-- Chọn --</option>
                    <option value="Đang giao">Đang giao</option>
                    <option value="Đã giao">Đã giao</option>
                    <option value="Giao không thành công">Giao không thành công</option>
                </select><br><br>
                <button type="submit" class="btn btn-success">Xác nhận</button>
            </form>
        `;
        modalBody.innerHTML = html;
        modal.style.display = 'block';
    });
});
</script>
<script>
        // Khởi tạo kết nối WebSocket đến server Node.js
        const socket = new WebSocket('ws://localhost:8081');

        socket.addEventListener('open', () => {
        console.log('WebSocket connected');
        });

        socket.addEventListener('error', (err) => {
        console.error('WebSocket error:', err);
        });

        // Gửi vị trí liên tục khi có thay đổi
        if (navigator.geolocation) {
        navigator.geolocation.watchPosition(
            pos => {
            const { latitude: lat, longitude: lng } = pos.coords;
            const payload = {
                staffId: <?= $idNhanVien ?>,
                timestamp: new Date().toISOString(),
                lat,
                lng
            };
            if (socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify(payload));
            }
            },
            err => {
            console.error('Error getting location:', err.code, err.message);
            },
            {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
            }
        );
        } else {
        console.error('Geolocation không được hỗ trợ');
        }
        socket.addEventListener('close', () => {
        console.log('Socket closed, retry in 3s');
        setTimeout(initWebSocket, 3000);
        });
</script>

<?php require 'footer.php'; ?>
</body>
</html>