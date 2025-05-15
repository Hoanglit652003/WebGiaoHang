<?php
require 'header.php';
// session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$maVanDon = $_GET['maVanDon'] ?? '';
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Lấy chi tiết đơn hàng
$stmt = $mysqli->prepare("
    SELECT D.maVanDon, S.tenSanPham, N.diaChi, D.trangThaiDonHang, D.ngayTaoDon, 
           NV.tenNhanVien, NV.viTriLat, NV.viTriLng, D.id_nhanVien
    FROM DonHang D
    JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
    JOIN SanPham S ON D.id_sanPham = S.Id_SanPham
    LEFT JOIN NhanVien NV ON D.id_nhanVien = NV.Id_nhanVien
    WHERE D.maVanDon = ? AND (D.id_KhachHang = ? OR D.id_nhanVien = ?)
");
$stmt->bind_param("sii", $maVanDon, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<p>Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.</p>";
    require 'footer.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng</title>
    <link rel="stylesheet" href="./css/order_detail.css">
</head>
<body>
    <div class="content-container">
    <h2>Chi tiết đơn hàng: <?= htmlspecialchars($order['maVanDon']) ?></h2>
    <div class="order-info">
        <ul>
            <li>Địa chỉ: <?= htmlspecialchars($order['diaChi']) ?></li>
            <li>Loại hàng: <?= htmlspecialchars($order['tenSanPham']) ?></li>
            <li>Ngày tạo: <?= $order['ngayTaoDon'] ?></li>
            <li>Trạng thái: <?= htmlspecialchars($order['trangThaiDonHang']) ?></li>
            <?php if ($order['tenNhanVien']): ?>
                <li>Shipper: <?= htmlspecialchars($order['tenNhanVien']) ?></li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if ($userRole === 2 && $order['trangThaiDonHang'] === 'Đã phân công'): ?>
        <form method="post">
            <button type="submit" name="start_delivery" class="btn btn-primary">Bắt đầu giao hàng</button>
        </form>
    <?php endif; ?>

    <?php if ($order['trangThaiDonHang'] === 'Đang giao' && $order['id_nhanVien']): ?>
        <a href="shipper_route.php?maVanDon=<?= urlencode($maVanDon) ?>" class="btn btn-info">
            <i class="fas fa-map-marker-alt"></i> Xem đường đi của shipper
        </a>
    <?php endif; ?>
</div>
<?php require 'footer.php'; ?>
</body>
</html>

<?php
// Xử lý cập nhật trạng thái nếu nhấn "Bắt đầu giao hàng"
if (isset($_POST['start_delivery']) && $userRole === 2) {
    $updateStmt = $mysqli->prepare("UPDATE DonHang SET trangThaiDonHang = 'Đang giao' WHERE maVanDon = ? AND id_nhanVien = ?");
    $updateStmt->bind_param("si", $maVanDon, $userId);
    if ($updateStmt->execute()) {
        echo "<p style='color: green;'>Đã cập nhật trạng thái đơn hàng thành 'Đang giao'.</p>";
        echo "<script>setTimeout(() => window.location.href = 'my_orders.php', 1500);</script>";
    } else {
        echo "<p style='color: red;'>Không thể cập nhật trạng thái đơn hàng. Vui lòng thử lại.</p>";
    }
}
?>

