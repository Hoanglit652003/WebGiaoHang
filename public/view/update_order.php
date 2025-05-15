<?php
require_once './config/conn.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['id_khach'])) {
    header('Location: index.php?url=login');
    exit();
}

$error = '';
$success = '';
$order = null;
$products = [];

if (isset($_GET['maVanDon'])) {
    $maVanDon = $_GET['maVanDon'];
    
    try {
        // Get order information
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
                LEFT JOIN sanpham sp ON o.id_sanPham = sp.id_sanPham
                WHERE o.maVanDon = ? AND o.id_khachHang = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $maVanDon, $_SESSION['id_khach']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();
            
            // Check if order can be edited
            if ($order['trangThai'] != 'Đã tạo' && $order['trangThai'] != 'Đang xử lý') {
                $error = "Không thể chỉnh sửa đơn hàng ở trạng thái này";
            }
        } else {
            $error = "Không tìm thấy đơn hàng";
        }
    } catch (Exception $e) {
        $error = "Lỗi kết nối: " . $e->getMessage();
    }
} else {
    $error = "Mã vận đơn không hợp lệ";
}

if (isset($maVanDon)) {
    $stmt = $conn->prepare("SELECT id_sanPham, tenSanPham, soLuong, khoiLuong, maSP FROM sanpham WHERE maVanDon = ?");
    $stmt->bind_param("s", $maVanDon);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        // Start transaction
        $conn->begin_transaction();

        // Update sender information
        $update_sender = "UPDATE nguoigui SET 
                         tenNguoiGui = ?, 
                         sdtNguoiGui = ?, 
                         diaChiNguoiGui = ? 
                         WHERE id_nguoiGui = (SELECT id_nguoiGui FROM donHang WHERE maVanDon = ?)";
        $stmt = $conn->prepare($update_sender);
        $stmt->bind_param("ssss", 
            $_POST['tenNguoiGui'],
            $_POST['sdtNguoiGui'],
            $_POST['diaChiNguoiGui'],
            $maVanDon
        );
        $stmt->execute();

        // Update receiver information
        $update_receiver = "UPDATE nguoinhan SET 
                          tenNguoiNhan = ?, 
                          soDienThoai = ? 
                          WHERE id_nguoiNhan = (SELECT id_nguoiNhan FROM donHang WHERE maVanDon = ?)";
        $stmt = $conn->prepare($update_receiver);
        $stmt->bind_param("sss", 
            $_POST['tenNguoiNhan'],
            $_POST['soDienThoai'],
            $maVanDon
        );
        $stmt->execute();

        // Update receiver address
        $update_address = "UPDATE diachi SET 
                          diaChiNguoiNhan = ?, 
                          tinh_tp = ?, 
                          quan_huyen = ?, 
                          phuong_xa = ? 
                          WHERE id_diaChi = (SELECT id_diaChi FROM nguoinhan WHERE id_nguoiNhan = (SELECT id_nguoiNhan FROM donHang WHERE maVanDon = ?))";
        $stmt = $conn->prepare($update_address);
        $stmt->bind_param("sssss", 
            $_POST['diaChiNguoiNhan'],
            $_POST['tinh_tp'],
            $_POST['quan_huyen'],
            $_POST['phuong_xa'],
            $maVanDon
        );
        $stmt->execute();

        // Update order information
        $update_order = "UPDATE donHang SET 
                        ghiChu = ? 
                        WHERE maVanDon = ?";
        $stmt = $conn->prepare($update_order);
        $stmt->bind_param("ss", 
            $_POST['ghiChu'],
            $maVanDon
        );
        $stmt->execute();

        // Update fees
        $update_fees = "UPDATE phi SET 
                       phiDichVu = ?, 
                       phiKhaiGia = ?, 
                       tongPhi = ? 
                       WHERE id_phi = (SELECT id_phi FROM donHang WHERE maVanDon = ?)";
        $stmt = $conn->prepare($update_fees);
        $stmt->bind_param("ddds", 
            $_POST['phiDichVu'],
            $_POST['phiKhaiGia'],
            $_POST['tongPhi'],
            $maVanDon
        );
        $stmt->execute();

        // Xóa sản phẩm cũ không còn trong danh sách
        if (isset($_POST['product_id'])) {
            $current_ids = array_map('intval', $_POST['product_id']);
            $ids_str = implode(',', $current_ids);
            $conn->query("DELETE FROM sanpham WHERE maVanDon = '$maVanDon' AND id_sanPham NOT IN ($ids_str)");
        }

        // Cập nhật hoặc thêm mới sản phẩm
        if (isset($_POST['product_name'])) {
            for ($i = 0; $i < count($_POST['product_name']); $i++) {
                $id = $_POST['product_id'][$i] ?? null;
                $name = $_POST['product_name'][$i];
                $qty = $_POST['product_quantity'][$i];
                $weight = $_POST['product_weight'][$i];
                $code = $_POST['product_code'][$i];
                if ($id) {
                    // Update
                    $stmt = $conn->prepare("UPDATE sanpham SET tenSanPham=?, soLuong=?, khoiLuong=?, maSP=? WHERE id_sanPham=?");
                    $stmt->bind_param("sidsi", $name, $qty, $weight, $code, $id);
                    $stmt->execute();
                } else {
                    // Insert mới
                    $stmt = $conn->prepare("INSERT INTO sanpham (maVanDon, tenSanPham, soLuong, khoiLuong, maSP) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssids", $maVanDon, $name, $qty, $weight, $code);
                    $stmt->execute();
                }
            }
        }

        // Commit transaction
        $conn->commit();
        $success = "Cập nhật đơn hàng thành công";
        header("Location: index.php?url=detail-orders&maVanDon=$maVanDon");
        
        // Refresh order data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $maVanDon, $_SESSION['id_khach']);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Lỗi cập nhật: " . $e->getMessage();
    }
}
?>

<div class="container py-5 mt-4">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($order): ?>
        <div class="card shadow-sm mb-4 mt-4">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Chỉnh sửa đơn hàng: <?php echo htmlspecialchars($order['maVanDon']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <!-- Thông tin người gửi -->
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin người gửi</h4>
                            <div class="mb-3">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" name="tenNguoiGui" value="<?php echo htmlspecialchars($order['tenNguoiGui']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" name="sdtNguoiGui" value="<?php echo htmlspecialchars($order['sdtNguoiGui']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control" name="diaChiNguoiGui" value="<?php echo htmlspecialchars($order['diaChiNguoiGui']); ?>" required>
                            </div>
                        </div>

                        <!-- Thông tin người nhận -->
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin người nhận</h4>
                            <div class="mb-3">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" name="tenNguoiNhan" value="<?php echo htmlspecialchars($order['tenNguoiNhan']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại</label>
                                <input type="tel" class="form-control" name="soDienThoai" value="<?php echo htmlspecialchars($order['soDienThoai']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa chỉ</label>
                                <input type="text" class="form-control" name="diaChiNguoiNhan" value="<?php echo htmlspecialchars($order['diaChiNguoiNhan']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tỉnh/Thành phố</label>
                                        <input type="text" class="form-control" name="tinh_tp" value="<?php echo htmlspecialchars($order['tinh_tp']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Quận/Huyện</label>
                                        <input type="text" class="form-control" name="quan_huyen" value="<?php echo htmlspecialchars($order['quan_huyen']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Phường/Xã</label>
                                        <input type="text" class="form-control" name="phuong_xa" value="<?php echo htmlspecialchars($order['phuong_xa']); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Thông tin phí -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h4 class="mb-3">Thông tin phí</h4>
                            <div class="mb-3">
                                <label class="form-label">Phí dịch vụ (VNĐ)</label>
                                <input type="number" class="form-control" name="phiDichVu" value="<?php echo $order['phiDichVu']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phí khai giá (VNĐ)</label>
                                <input type="number" class="form-control" name="phiKhaiGia" value="<?php echo $order['phiKhaiGia']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tổng phí (VNĐ)</label>
                                <input type="number" class="form-control" name="tongPhi" value="<?php echo $order['tongPhi']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h4 class="mb-3">Ghi chú</h4>
                            <div class="mb-3">
                                <textarea class="form-control" name="ghiChu" rows="4"><?php echo htmlspecialchars($order['ghiChu'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách sản phẩm -->
                    <h4 class="mb-3">Danh sách sản phẩm</h4>
                    <div id="product-list">
                        <?php foreach ($products as $idx => $sp): ?>
                        <div class="row align-items-end mb-2 product-row">
                            <input type="hidden" name="product_id[]" value="<?php echo $sp['id_sanPham']; ?>">
                            <div class="col-md-3">
                                <label>Tên sản phẩm</label>
                                <input type="text" class="form-control" name="product_name[]" value="<?php echo htmlspecialchars($sp['tenSanPham']); ?>" required>
                            </div>
                            <div class="col-md-2">
                                <label>Số lượng</label>
                                <input type="number" class="form-control" name="product_quantity[]" value="<?php echo $sp['soLuong']; ?>" min="1" required>
                            </div>
                            <div class="col-md-2">
                                <label>Khối lượng (gram)</label>
                                <input type="number" class="form-control" name="product_weight[]" value="<?php echo $sp['khoiLuong']; ?>" min="0" required>
                            </div>
                            <div class="col-md-3">
                                <label>Mã sản phẩm</label>
                                <input type="text" class="form-control" name="product_code[]" value="<?php echo htmlspecialchars($sp['maSP']); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-remove-product">Xóa</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-success mb-3" id="add-product-btn">Thêm sản phẩm</button>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-warning">Cập nhật đơn hàng</button>
                        <a href="index.php?url=manager-shipment" class="btn btn-secondary">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('add-product-btn').onclick = function() {
        var row = document.createElement('div');
        row.className = 'row align-items-end mb-2 product-row';
        row.innerHTML = `
            <input type="hidden" name="product_id[]" value="">
            <div class="col-md-3">
                <label>Tên sản phẩm</label>
                <input type="text" class="form-control" name="product_name[]" required>
            </div>
            <div class="col-md-2">
                <label>Số lượng</label>
                <input type="number" class="form-control" name="product_quantity[]" min="1" required>
            </div>
            <div class="col-md-2">
                <label>Khối lượng (gram)</label>
                <input type="number" class="form-control" name="product_weight[]" min="0" required>
            </div>
            <div class="col-md-3">
                <label>Mã sản phẩm</label>
                <input type="text" class="form-control" name="product_code[]">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-remove-product">Xóa</button>
            </div>
        `;
        document.getElementById('product-list').appendChild(row);
    };
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-product')) {
            e.target.closest('.product-row').remove();
        }
    });
});
</script> 