<?php
session_start();
require_once './config/conn.php';

$tracking_result = null;
$tracking_history = null;
$error = '';

if (isset($_GET['tracking_number'])) {
    $maVanDon = $_GET['tracking_number'];

    try {
        // Truy vấn thông tin đơn hàng
        $query = "
            SELECT o.maVanDon, o.ngayTaoDon, o.COD, o.giaTriHang,
                   s.tenNguoiGui, s.sdtNguoiGui, s.diaChiNguoiGui,
                   r.tenNguoiNhan, r.soDienThoai,
                   a.diaChiNguoiNhan, a.tinh_tp, a.quan_huyen, a.phuong_xa,
                   f.tongPhi, f.phiDichVu,f.benTraPhi,
                   os.tenTrangThai as trangThai
            FROM donHang o
            JOIN nguoigui s ON o.id_nguoiGui = s.id_nguoiGui
            JOIN nguoinhan r ON o.id_nguoiNhan = r.id_nguoiNhan
            JOIN diachi a ON r.id_diaChi = a.id_diaChi
            JOIN phi f ON o.id_phi = f.id_phi
            JOIN trangthai os ON o.id_trangThai = os.id_trangThai
            WHERE o.maVanDon = ?";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $maVanDon);
        $stmt->execute();
        $result = $stmt->get_result();
        $tracking_result = $result->fetch_assoc();

        // Nếu tìm thấy đơn hàng, truy vấn lịch sử trạng thái
        if ($tracking_result) {
            $query = "
                SELECT osh.mocThoiGian, os.tenTrangThai, osh.diaDiem, osh.HIMnotes,os.moTa
                FROM lichsu_trangthai osh
                JOIN trangthai os ON osh.id_trangThai = os.id_trangThai
                WHERE osh.maVanDon = ?
                ORDER BY osh.mocThoiGian DESC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $maVanDon);
            $stmt->execute();
            $result = $stmt->get_result();

            $tracking_history = [];
            while ($row = $result->fetch_assoc()) {
                $tracking_history[] = $row;
            }
        } else {
            $error = 'Không tìm thấy vận đơn với mã này';
        }
    } catch (Exception $e) {
        $error = "Lỗi kết nối: " . $e->getMessage();
    }
}
?>
<!-- Tracking Section -->

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mt-5">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Tra cứu vận đơn</h2>

                        <form method="GET" action="index.php" class="mb-4">
                            <input type="hidden" name="url" value="tracking">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-lg" name="tracking_number"
                                    placeholder="Nhập mã vận đơn của bạn" required
                                    value="<?php echo isset($_GET['tracking_number']) ? htmlspecialchars($_GET['tracking_number']) : ''; ?>">
                                <button class="btn btn-primary btn-lg" type="submit">Tra cứu</button>
                            </div>
                        </form>

                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tracking_result): ?>
                            <div class="tracking-result">
                                <h3 class="mb-4">Thông tin vận đơn</h3>
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Mã vận đơn:</strong></p>
                                                <p class="text-primary"><?php echo htmlspecialchars($tracking_result['maVanDon']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1"><strong>Trạng thái:</strong></p>
                                                <p class="text-success"><?php echo htmlspecialchars($tracking_result['trangThai']); ?></p>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-6">
                                                <h5>Thông tin người gửi</h5>
                                                <p><strong>Người gửi:</strong> <?php echo htmlspecialchars($tracking_result['tenNguoiGui']); ?></p>
                                                <p><strong>SĐT:</strong> <?php echo htmlspecialchars($tracking_result['sdtNguoiGui']); ?></p>
                                                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($tracking_result['diaChiNguoiGui']); ?></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h5>Thông tin người nhận</h5>
                                                <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($tracking_result['tenNguoiNhan']); ?></p>
                                                <p><strong>SĐT:</strong> <?php echo htmlspecialchars($tracking_result['soDienThoai']); ?></p>
                                                <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($tracking_result['diaChiNguoiNhan']); ?>,
                                                    <?php echo htmlspecialchars($tracking_result['phuong_xa']); ?>,
                                                    <?php echo htmlspecialchars($tracking_result['quan_huyen']); ?>,
                                                    <?php echo htmlspecialchars($tracking_result['tinh_tp']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-md-3">
                                                <p><strong>COD:</strong> <?php echo number_format($tracking_result['COD'], 0, ',', '.'); ?> VNĐ</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>Phí ship:</strong> <?php echo number_format($tracking_result['phiDichVu'], 0, ',', '.'); ?> VNĐ</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>Tổng phí:</strong> <?php echo number_format($tracking_result['tongPhi'], 0, ',', '.'); ?> VNĐ</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>Thanh toán:</strong> <?php echo ($tracking_result['benTraPhi'] == 'sender-pay') ? 'Người gửi trả phí' : 'Người nhận trả phí'; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tracking Timeline -->
                                <h3 class="mb-4">Lịch sử vận đơn</h3>
                                <div class="tracking-timeline">
                                    <?php if ($tracking_history && count($tracking_history) > 0): ?>
                                        <?php foreach ($tracking_history as $history): ?>
                                            <div class="timeline-item">
                                                <?php
                                                $iconClass = 'fa-box';
                                                $bgClass = 'bg-info';

                                                if (stripos($history['tenTrangThai'], 'Đang giao') !== false) {
                                                    $iconClass = 'fa-truck';
                                                    $bgClass = 'bg-primary';
                                                } elseif (stripos($history['tenTrangThai'], 'Đã giao') !== false || stripos($history['tenTrangThai'], 'hoàn thành') !== false) {
                                                    $iconClass = 'fa-check';
                                                    $bgClass = 'bg-success';
                                                } elseif (stripos($history['tenTrangThai'], 'Giao không thành công') !== false) {
                                                    $iconClass = 'fa-times';
                                                    $bgClass = 'bg-danger';
                                                }
                                                ?>
                                                <div class="timeline-icon <?php echo $bgClass; ?>">
                                                    <i class="fas <?php echo $iconClass; ?> text-white"></i>
                                                </div>
                                                <div class="timeline-content">
                                                    <h5><?php echo htmlspecialchars($history['tenTrangThai']); ?></h5>
                                                    <p class="text-muted"><?php echo date('d/m/Y H:i', strtotime($history['mocThoiGian'])); ?></p>
                                                    <?php if (!empty($history['moTa'])): ?>
                                                        <p><strong>Mô tả: </strong> <?php echo htmlspecialchars($history['moTa']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">Chưa có cập nhật trạng thái nào</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>