<?php
require 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$maVanDon = $_GET['maVanDon'] ?? '';
$userId = $_SESSION['user_id'];

// Lấy chi tiết đơn hàng với thông tin đầy đủ
$stmt = $mysqli->prepare("
    SELECT 
        D.maVanDon,
        D.ngayTaoDon,
        D.trangThaiDonHang,
        D.ghiChu,
        S.tenSanPham,
        S.moTa as moTaSanPham,
        N.tenNguoiNhan,
        N.diaChi,
        N.soDienThoai,
        N.email,
        NG.tenNguoiGui,
        NG.diaChi as diaChiNguoiGui,
        NG.soDienThoai as sdtNguoiGui,
        NG.email as emailNguoiGui,
        NV.tenNhanVien
    FROM DonHang D
    JOIN NguoiNhan N ON D.id_nguoiNhan = N.Id_NguoiNhan
    JOIN SanPham S ON D.id_sanPham = S.Id_SanPham
    JOIN NguoiGui NG ON D.id_nguoiGui = NG.Id_NguoiGui
    LEFT JOIN NhanVien NV ON D.id_nhanVien = NV.Id_NhanVien
    WHERE D.maVanDon = ? AND (D.id_nguoiGui = ? OR D.id_nguoiNhan = ?)
");
$stmt->bind_param("sii", $maVanDon, $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    echo "<div class='alert alert-danger'>Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn này.</div>";
    require 'footer.php';
    exit;
}
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">Chi tiết đơn hàng: <?= htmlspecialchars($order['maVanDon']) ?></h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Thông tin người nhận</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item"><strong>Tên:</strong> <?= htmlspecialchars($order['tenNguoiNhan']) ?></li>
                        <li class="list-group-item"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['diaChi']) ?></li>
                        <li class="list-group-item"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['soDienThoai']) ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></li>
                    </ul>

                    <h4>Thông tin người gửi</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item"><strong>Tên:</strong> <?= htmlspecialchars($order['tenNguoiGui']) ?></li>
                        <li class="list-group-item"><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['diaChiNguoiGui']) ?></li>
                        <li class="list-group-item"><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['sdtNguoiGui']) ?></li>
                        <li class="list-group-item"><strong>Email:</strong> <?= htmlspecialchars($order['emailNguoiGui']) ?></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h4>Thông tin đơn hàng</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item"><strong>Mã vận đơn:</strong> <?= htmlspecialchars($order['maVanDon']) ?></li>
                        <li class="list-group-item"><strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($order['ngayTaoDon'])) ?></li>
                        <li class="list-group-item"><strong>Trạng thái:</strong> 
                            <span class="badge bg-<?= getStatusColor($order['trangThaiDonHang']) ?>">
                                <?= htmlspecialchars($order['trangThaiDonHang']) ?>
                            </span>
                        </li>
                        <li class="list-group-item"><strong>Loại hàng:</strong> <?= htmlspecialchars($order['tenSanPham']) ?></li>
                        <li class="list-group-item"><strong>Mô tả sản phẩm:</strong> <?= htmlspecialchars($order['moTaSanPham']) ?></li>
                        <?php if ($order['tenNhanVien']): ?>
                            <li class="list-group-item"><strong>Nhân viên giao hàng:</strong> <?= htmlspecialchars($order['tenNhanVien']) ?></li>
                        <?php endif; ?>
                        <?php if ($order['ghiChu']): ?>
                            <li class="list-group-item"><strong>Ghi chú:</strong> <?= htmlspecialchars($order['ghiChu']) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <?php if ($order['trangThaiDonHang'] === 'Đang giao'): ?>
                <div class="mt-4">
                    <a href="tracking.php?maVanDon=<?= urlencode($order['maVanDon']) ?>" class="btn btn-info">
                        <i class="fas fa-map-marker-alt"></i> Theo dõi vị trí giao hàng
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'Đã phân công':
            return 'info';
        case 'Đang giao':
            return 'warning';
        case 'Đã giao':
            return 'success';
        case 'Giao hàng thất bại':
            return 'danger';
        default:
            return 'secondary';
    }
}

require 'footer.php';
?> 