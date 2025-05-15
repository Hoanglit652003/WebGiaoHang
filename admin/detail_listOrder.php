<?php
require 'header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/conn.php';

// Get order ID from URL
$orderId = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($orderId)) {
    header('Location: list_orders.php');
    exit;
}

// Get order details
$sql = "SELECT 
    o.maVanDon, 
    o.ngayTaoDon, 
    o.COD, 
    o.giaTriHang, 
    o.KL_DH,
    o.ghiChu, 
    o.hinhThucGui,
    s.tenNguoiGui, 
    s.sdtNguoiGui, 
    s.diaChiNguoiGui,
    r.tenNguoiNhan, 
    r.soDienThoai,
    a.diaChiNguoiNhan, 
    a.tinh_tp, 
    a.quan_huyen, 
    a.phuong_xa,
    f.tongPhi, 
    f.phiDichVu, 
    f.benTraPhi,
    os.tenTrangThai, 
    os.id_trangThai,
    sp.tenSanPham, 
    sp.soLuong
FROM donHang o
JOIN nguoigui s ON o.id_nguoiGui = s.id_nguoiGui
JOIN nguoinhan r ON o.id_nguoiNhan = r.id_nguoiNhan
JOIN diachi a ON r.id_diaChi = a.id_diaChi
JOIN phi f ON o.id_phi = f.id_phi
JOIN trangthai os ON o.id_trangThai = os.id_trangThai
LEFT JOIN sanpham sp ON o.maVanDon = sp.maVanDon
WHERE o.maVanDon = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header('Location: list_orders.php');
    exit;
}

// Get order history
$historySql = "SELECT 
    osh.mocThoiGian, 
    os.tenTrangThai, 
    osh.diaDiem, 
    osh.HIMnotes
FROM lichsu_trangthai osh
JOIN trangthai os ON osh.id_trangThai = os.id_trangThai
WHERE osh.maVanDon = ?
ORDER BY osh.mocThoiGian DESC";

$historyStmt = $mysqli->prepare($historySql);
$historyStmt->bind_param("s", $orderId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

// Get status class
$statusClass = match ($order['tenTrangThai']) {
    'Đã tạo' => 'status-waitconfirm',
    'Chờ xử lý' => 'status-waiting',
    'Đang giao' => 'status-delivering',
    'Đã giao' => 'status-completed',
    'Giao không thành công' => 'status-canceled',
    'Đã hủy' => 'status-lost-damaged',
    default => ''
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/orders.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo img {
            height: 40px;
        }
        .logo span {
            font-size: 14px;
        }
        .search-bar {
            display: flex;
            gap: 10px;
        }
        .search-bar input {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            width: 200px;
        }
        .search-bar button {
            background: white;
            color: #0dcaf0;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        main {
            max-width: 1200px;
            margin: 0 auto;
        }
        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .order-block {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .order-block h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #0dcaf0;
            padding-bottom: 10px;
        }
        .info-row {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .info-row span:first-child {
            color: #666;
        }
        .info-row b {
            color: #333;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status.processing {
            background: #cce5ff;
            color: #004085;
        }
        .status.delivering {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .qty {
            color: #666;
            font-size: 14px;
        }
        .order-history {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .order-history h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #0dcaf0;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        a {
            color: #0dcaf0;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #0dcaf0;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-button:hover {
            background: #0dcaf0;
            text-decoration: none;
        }
        .status-waitconfirm {
            background: #fff3cd;
            color: #856404;
        }
        .status-waiting {
            background: #cce5ff;
            color: #004085;
        }
        .status-delivering {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-returning {
            background: #f8d7da;
            color: #721c24;
        }
        .status-reconfirm {
            background: #e2e3e5;
            color: #383d41;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-canceled {
            background: #f8d7da;
            color: #721c24;
        }
        .status-lost-damaged {
            background: #f8d7da;
            color: #721c24;
        }
        .address-details {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .price-info {
            font-weight: bold;
            color: #ff6b00;
        }
        th{
            background: #0dcaf0;
            color: black;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <a href="list_orders.php" class="back-button">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
        <h2 style="text-align: center; color: #0dcaf0; margin-bottom: 20px;">Chi tiết đơn hàng</h3>
        <main>
            <section class="order-info">
                <div class="order-block">
                    <h2>THÔNG TIN ĐƠN HÀNG</h2>
                    <div class="info-row">
                        <span>Mã đơn hàng:</span>
                        <b><?= htmlspecialchars($order['maVanDon']) ?></b>
                    </div>
                    <div class="info-row">
                        <span>Ngày tạo:</span>
                        <b><?= date('d/m/Y H:i', strtotime($order['ngayTaoDon'])) ?></b>
                    </div>
                    <div class="info-row">
                        <span>Hình thức gửi:</span>
                        <b><?= htmlspecialchars($order['hinhThucGui']) ?></b>
                    </div>
                    <div class="info-row">
                        <span>Trạng thái hiện tại:</span>
                        <span class="status <?= $statusClass ?>">
                            <?= htmlspecialchars($order['tenTrangThai']) ?>
                        </span>
                    </div>
                </div>

                <div class="order-block">
                    <h2>THÔNG TIN CHI TIẾT</h2>
                    <div class="info-row">
                        <span>Sản phẩm:</span>
                        <span>
                            <?= htmlspecialchars($order['tenSanPham'] ?? 'N/A') ?>
                            <?php if (isset($order['soLuong'])): ?>
                                <span class="qty">x<?= $order['soLuong'] ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span>Cân nặng:</span>
                        <span><?= number_format($order['KL_DH'], 0) ?> gram</span>
                    </div>
                    <div class="info-row">
                        <span>Giá trị hàng:</span>
                        <span class="price-info"><?= number_format($order['giaTriHang'], 0) ?> VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span>COD:</span>
                        <span class="price-info"><?= number_format($order['COD'], 0) ?> VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span>Lưu ý giao hàng:</span>
                        <span><?= htmlspecialchars($order['ghiChu'] ?? 'Không có') ?></span>
                    </div>
                </div>
            </section>

            <section class="order-info">
                <div class="order-block">
                    <h2>NGƯỜI GỬI</h2>
                    <div class="info-row">
                        <span>Họ và tên:</span>
                        <b><?= htmlspecialchars($order['tenNguoiGui']) ?></b>
                    </div>
                    <div class="info-row">
                        <span>Điện thoại:</span>
                        <span><?= htmlspecialchars($order['sdtNguoiGui']) ?></span>
                    </div>
                    <div class="info-row">
                        <span>Địa chỉ:</span>
                        <span><?= htmlspecialchars($order['diaChiNguoiGui']) ?></span>
                    </div>
                </div>

                <div class="order-block">
                    <h2>NGƯỜI NHẬN</h2>
                    <div class="info-row">
                        <span>Họ và tên:</span>
                        <b><?= htmlspecialchars($order['tenNguoiNhan']) ?></b>
                    </div>
                    <div class="info-row">
                        <span>Điện thoại:</span>
                        <span><?= htmlspecialchars($order['soDienThoai']) ?></span>
                    </div>
                    <div class="info-row">
                        <span>Địa chỉ:</span>
                        <span>
                            <?= htmlspecialchars($order['diaChiNguoiNhan']) ?>
                            <div class="address-details">
                                <?= htmlspecialchars($order['phuong_xa']) ?>, 
                                <?= htmlspecialchars($order['quan_huyen']) ?>, 
                                <?= htmlspecialchars($order['tinh_tp']) ?>
                            </div>
                        </span>
                    </div>
                </div>
            </section>

            <section class="order-info">
                <div class="order-block">
                    <h2>CHI PHÍ</h2>
                    <div class="info-row">
                        <span>Phí dịch vụ:</span>
                        <span class="price-info"><?= number_format($order['phiDichVu'], 0) ?> VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span>Tổng phí:</span>
                        <span class="price-info"><?= number_format($order['tongPhi'], 0) ?> VNĐ</span>
                    </div>
                    <div class="info-row">
                        <span>Người trả phí:</span>
                        <span><?= $order['benTraPhi'] == 1 ? 'Người nhận trả phí' : 'Người gửi trả phí' ?></span>
                    </div>
                </div>
            </section>

            <section class="order-history">
                <h2>Lịch sử đơn hàng</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Trạng thái</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($historyResult->num_rows > 0): ?>
                            <?php while ($history = $historyResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($history['mocThoiGian'])) ?></td>
                                    <td><?= htmlspecialchars($history['tenTrangThai']) ?></td>
                                    <td><?= htmlspecialchars($history['HIMnotes']) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Chưa có lịch sử cập nhật</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>