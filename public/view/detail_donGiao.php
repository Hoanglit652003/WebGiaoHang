<?php 
    require_once './config/conn.php';

    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['id_khach'])) {
        header('Location: index.php?url=login');
        exit();
    }

    $error = '';
    $order = null;
    $order_history = null;

    if (isset($_GET['maVanDon'])) {
        $maVanDon = $_GET['maVanDon'];
        
        try {
            // Truy vấn thông tin đơn hàng
            $sql = "SELECT o.maVanDon, o.ngayTaoDon, o.COD, o.giaTriHang, o.ghiChu,
                           s.tenNguoiGui, s.sdtNguoiGui, s.diaChiNguoiGui,
                           r.tenNguoiNhan, r.soDienThoai,
                           a.diaChiNguoiNhan, a.tinh_tp, a.quan_huyen, a.phuong_xa,
                           f.tongPhi, f.phiDichVu, f.phiKhaiGia, f.benTraPhi,
                           os.tenTrangThai as trangThai,
                           sp.tenSanPham, sp.soLuong, sp.khoiLuong
                    FROM donHang o
                    JOIN nguoigui s ON o.id_nguoiGui = s.id_nguoiGui
                    JOIN nguoinhan r ON o.id_nguoiNhan = r.id_nguoiNhan
                    JOIN diachi a ON r.id_diaChi = a.id_diaChi
                    JOIN phi f ON o.id_phi = f.id_phi
                    JOIN trangthai os ON o.id_trangThai = os.id_trangThai
                    LEFT JOIN sanpham sp ON o.maVanDon = sp.maVanDon
                    WHERE o.maVanDon = ? AND o.id_khachHang = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $maVanDon, $_SESSION['id_khach']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $order = $result->fetch_assoc();
                
                // Lấy lịch sử đơn hàng
                $history_sql = "SELECT lst.mocThoiGian as ngayCapNhat, t.tenTrangThai, lst.HIMnotes as ghiChu, t.moTa 
                               FROM lichsu_trangthai lst
                               JOIN trangthai t ON lst.id_TrangThai = t.id_trangThai
                               WHERE lst.maVanDon = ? 
                               ORDER BY lst.mocThoiGian DESC";
                $history_stmt = $conn->prepare($history_sql);
                $history_stmt->bind_param("s", $maVanDon);
                $history_stmt->execute();
                $order_history = $history_stmt->get_result();
            } else {
                $error = "Không tìm thấy đơn hàng";
            }
        } catch (Exception $e) {
            $error = "Lỗi kết nối: " . $e->getMessage();
        }
    } else {
        $error = "Mã vận đơn không hợp lệ";
    }
?>
<div class="detail_orders">
    <div class="container py-5">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($order): ?>
            <div class="card shadow-sm mb-4 mt-4">
                <div class="card-header text-white" style="background-color: orangered;">
                    <h5 class="mb-0">Chi tiết đơn hàng: <?php echo htmlspecialchars($order['maVanDon']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Thông tin đơn hàng -->
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin đơn hàng</h4>
                            <table class="table">
                                <tr>
                                    <th>Mã vận đơn:</th>
                                    <td><?php echo htmlspecialchars($order['maVanDon']); ?></td>
                                </tr>
                                <tr>
                                    <th>Ngày tạo:</th>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['ngayTaoDon'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($order['trangThai']) {
                                                'Đã tạo' => 'warning',
                                                'Đang xử lý' => 'info',
                                                'Đang giao' => 'primary',
                                                'Đã giao' => 'success',
                                                'Giao không thành công' => 'danger',
                                                'Đã hủy' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($order['trangThai']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Sản phẩm:</th>
                                    <td><?php echo htmlspecialchars($order['tenSanPham'] ?? 'Không có'); ?> (<?php echo $order['soLuong'] ?? 0; ?> cái)</td>
                                </tr>
                                <tr>
                                    <th>Khối lượng:</th>
                                    <td><?php echo $order['khoiLuong'] ?? 0; ?> gam</td>
                                </tr>
                                <tr>
                                    <th>Giá trị hàng:</th>
                                    <td><?php echo number_format($order['giaTriHang'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Ghi chú:</th>
                                    <td><?php echo htmlspecialchars($order['ghiChu'] ?? 'Không có'); ?></td>
                                </tr>
                            </table>
                        </div>
    
                        <!-- Thông tin người gửi/nhận -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4 class="mb-3">Người gửi</h4>
                                    <table class="table">
                                        <tr>
                                            <th>Họ tên:</th>
                                            <td><?php echo htmlspecialchars($order['tenNguoiGui']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số điện thoại:</th>
                                            <td><?php echo htmlspecialchars($order['sdtNguoiGui']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Địa chỉ:</th>
                                            <td><?php echo htmlspecialchars($order['diaChiNguoiGui']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="mb-3">Người nhận</h4>
                                    <table class="table">
                                        <tr>
                                            <th>Họ tên:</th>
                                            <td><?php echo htmlspecialchars($order['tenNguoiNhan']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Số điện thoại:</th>
                                            <td><?php echo htmlspecialchars($order['soDienThoai']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Địa chỉ:</th>
                                            <td>
                                                <?php 
                                                echo htmlspecialchars($order['diaChiNguoiNhan'] . ', ' . 
                                                    $order['phuong_xa'] . ', ' . 
                                                    $order['quan_huyen'] . ', ' . 
                                                    $order['tinh_tp']);
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- Thông tin phí -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin phí</h4>
                            <table class="table">
                                <tr>
                                    <th>Phí dịch vụ:</th>
                                    <td><?php echo number_format($order['phiDichVu'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Phí khai giá:</th>
                                    <td><?php echo number_format($order['phiKhaiGia'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Thu hộ (COD):</th>
                                    <td><?php echo number_format($order['COD'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Tổng phí:</th>
                                    <td class="fw-bold"><?php echo number_format($order['tongPhi'], 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                                <tr>
                                    <th>Bên trả phí:</th>
                                    <td><?php echo htmlspecialchars($order['benTraPhi']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
    
                    <!-- Lịch sử đơn hàng -->
                    <?php if ($order_history && $order_history->num_rows > 0): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4 class="mb-3">Lịch sử đơn hàng</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Thời gian</th>
                                            <th>Trạng thái</th>
                                            <th>Mô tả</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($history = $order_history->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($history['ngayCapNhat'])); ?></td>
                                            <td><?php echo htmlspecialchars($history['tenTrangThai']); ?></td>
                                            <td><?php echo htmlspecialchars($history['moTa'] ?? 'Không có'); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
