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
    f.phiKhaiGia,
    f.benTraPhi,
    os.tenTrangThai, 
    os.id_trangThai
FROM donHang o
JOIN nguoigui s ON o.id_nguoiGui = s.id_nguoiGui
JOIN nguoinhan r ON o.id_nguoiNhan = r.id_nguoiNhan
JOIN diachi a ON r.id_diaChi = a.id_diaChi
JOIN phi f ON o.id_phi = f.id_phi
JOIN trangthai os ON o.id_trangThai = os.id_trangThai
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

// Check if order is editable (status is "Đã tạo" or "Chờ xử lý")
$editableStatuses = ['Đã tạo', 'Chờ xử lý'];
if (!in_array($order['tenTrangThai'], $editableStatuses)) {
    $_SESSION['error'] = "Không thể chỉnh sửa đơn hàng ở trạng thái này.";
    header('Location: detail_listOrder.php?id=' . $orderId);
    exit;
}

// Get products for this order
$products = [];
$stmt = $mysqli->prepare("SELECT id_sanPham, tenSanPham, soLuong, khoiLuong, maSP FROM sanpham WHERE maVanDon = ?");
$stmt->bind_param("s", $orderId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $mysqli->begin_transaction();

        // Update sender information
        $updateSender = "UPDATE nguoigui SET 
            tenNguoiGui = ?,
            sdtNguoiGui = ?,
            diaChiNguoiGui = ?
            WHERE id_nguoiGui = (SELECT id_nguoiGui FROM donHang WHERE maVanDon = ?)";
        
        $stmt = $mysqli->prepare($updateSender);
        $stmt->bind_param("ssss", 
            $_POST['tenNguoiGui'],
            $_POST['sdtNguoiGui'],
            $_POST['diaChiNguoiGui'],
            $orderId
        );
        $stmt->execute();

        // Update recipient information
        $updateRecipient = "UPDATE nguoinhan SET 
            tenNguoiNhan = ?,
            soDienThoai = ?
            WHERE id_nguoiNhan = (SELECT id_nguoiNhan FROM donHang WHERE maVanDon = ?)";
        
        $stmt = $mysqli->prepare($updateRecipient);
        $stmt->bind_param("sss", 
            $_POST['tenNguoiNhan'],
            $_POST['soDienThoai'],
            $orderId
        );
        $stmt->execute();

        // Update address
        $updateAddress = "UPDATE diachi SET 
            diaChiNguoiNhan = ?,
            tinh_tp = ?,
            quan_huyen = ?,
            phuong_xa = ?
            WHERE id_diaChi = (SELECT id_diaChi FROM nguoinhan WHERE id_nguoiNhan = (SELECT id_nguoiNhan FROM donHang WHERE maVanDon = ?))";
        
        $stmt = $mysqli->prepare($updateAddress);
        $stmt->bind_param("sssss", 
            $_POST['diaChiNguoiNhan'],
            $_POST['tinh_tp'],
            $_POST['quan_huyen'],
            $_POST['phuong_xa'],
            $orderId
        );
        $stmt->execute();

        // Update order information
        $updateOrder = "UPDATE donHang SET 
            ghiChu = ?,
            hinhThucGui = ?
            WHERE maVanDon = ?";
        
        $stmt = $mysqli->prepare($updateOrder);
        $stmt->bind_param("sss", 
            $_POST['ghiChu'],
            $_POST['hinhThucGui'],
            $orderId
        );
        $stmt->execute();

        // Update fee information
        $updateFee = "UPDATE phi SET 
            phiDichVu = ?,
            phiKhaiGia = ?,
            tongPhi = ?,
            benTraPhi = ?
            WHERE id_phi = (SELECT id_phi FROM donHang WHERE maVanDon = ?)";
        
        $stmt = $mysqli->prepare($updateFee);
        $stmt->bind_param("dddis", 
            $_POST['phiDichVu'],
            $_POST['phiKhaiGia'],
            $_POST['tongPhi'],
            $_POST['benTraPhi'],
            $orderId
        );
        $stmt->execute();

        // Delete products that are no longer in the list
        if (isset($_POST['product_id'])) {
            $current_ids = array_map('intval', $_POST['product_id']);
            $ids_str = implode(',', $current_ids);
            $mysqli->query("DELETE FROM sanpham WHERE maVanDon = '$orderId' AND id_sanPham NOT IN ($ids_str)");
        }

        // Update or add new products
        if (isset($_POST['product_name'])) {
            for ($i = 0; $i < count($_POST['product_name']); $i++) {
                $id = $_POST['product_id'][$i] ?? null;
                $name = $_POST['product_name'][$i];
                $qty = $_POST['product_quantity'][$i];
                $weight = $_POST['product_weight'][$i];
                $code = $_POST['product_code'][$i];

                if ($id) {
                    // Update existing product
                    $stmt = $mysqli->prepare("UPDATE sanpham SET tenSanPham=?, soLuong=?, khoiLuong=?, maSP=? WHERE id_sanPham=?");
                    $stmt->bind_param("sidsi", $name, $qty, $weight, $code, $id);
                    $stmt->execute();
                } else {
                    // Insert new product
                    $stmt = $mysqli->prepare("INSERT INTO sanpham (maVanDon, tenSanPham, soLuong, khoiLuong, maSP) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssids", $orderId, $name, $qty, $weight, $code);
                    $stmt->execute();
                }
            }
        }

        // Commit transaction
        $mysqli->commit();
        $_SESSION['success'] = "Cập nhật đơn hàng thành công.";
        header('Location: detail_listOrder.php?id=' . $orderId);
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa đơn hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/orders.css">
    <style>
        /* Include all styles from detail_listOrder.php */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .content-wrapper {
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
            align-items: center;
        }
        .info-row label {
            color: #666;
            min-width: 150px;
        }
        .info-row input, .info-row select {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-left: 10px;
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
        .submit-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 20px;
        }
        .submit-button:hover {
            background: #218838;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8d7da;
            border-radius: 4px;
        }
        .success-message {
            color: #155724;
            margin-bottom: 20px;
            padding: 10px;
            background: #d4edda;
            border-radius: 4px;
        }
        .product-list {
            margin-top: 20px;
        }
        .product-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .remove-product {
            color: #dc3545;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 20px;
        }
        .add-product {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .add-product:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <a href="detail_listOrder.php?id=<?= htmlspecialchars($orderId) ?>" class="back-button">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>

        <h2 style="text-align: center; color: #0dcaf0; margin-bottom: 20px;">Chỉnh sửa đơn hàng</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <section class="order-info">
                <div class="order-block">
                    <h2>THÔNG TIN ĐƠN HÀNG</h2>
                    <div class="info-row">
                        <label>Mã đơn hàng:</label>
                        <input type="text" value="<?= htmlspecialchars($order['maVanDon']) ?>" readonly>
                    </div>
                    <div class="info-row">
                        <label>Ngày tạo:</label>
                        <input type="text" value="<?= date('d/m/Y H:i', strtotime($order['ngayTaoDon'])) ?>" readonly>
                    </div>
                    <div class="info-row">
                        <label>Hình thức gửi:</label>
                        <select name="hinhThucGui" required>
                            <option value="Lấy hàng tận nơi" <?= $order['hinhThucGui'] === 'Lấy hàng tận nơi' ? 'selected' : '' ?>>Lấy hàng tận nơi</option>
                            <option value="Gửi tại bưu cục" <?= $order['hinhThucGui'] === 'Gửi tại bưu cục' ? 'selected' : '' ?>>Gửi tại bưu cục</option>
                        </select>
                    </div>
                </div>

                <div class="order-block">
                    <h2>THÔNG TIN SẢN PHẨM</h2>
                    <div id="product-list" class="product-list">
                        <?php foreach ($products as $index => $product): ?>
                            <div class="product-item">
                                <input type="hidden" name="product_id[]" value="<?= $product['id_sanPham'] ?>">
                                <div class="info-row">
                                    <label>Tên sản phẩm:</label>
                                    <input type="text" name="product_name[]" value="<?= htmlspecialchars($product['tenSanPham']) ?>" required>
                                </div>
                                <div class="info-row">
                                    <label>Mã sản phẩm:</label>
                                    <input type="text" name="product_code[]" value="<?= htmlspecialchars($product['maSP']) ?>">
                                </div>
                                <div class="info-row">
                                    <label>Số lượng:</label>
                                    <input type="number" name="product_quantity[]" value="<?= htmlspecialchars($product['soLuong']) ?>" min="1" required>
                                </div>
                                <div class="info-row">
                                    <label>Cân nặng (gram):</label>
                                    <input type="number" name="product_weight[]" value="<?= htmlspecialchars($product['khoiLuong']) ?>" min="0" required>
                                </div>
                                <button type="button" class="remove-product" onclick="removeProduct(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-product" onclick="addProduct()">
                        <i class="bi bi-plus-circle"></i> Thêm sản phẩm
                    </button>
                </div>
            </section>

            <section class="order-info">
                <div class="order-block">
                    <h2>NGƯỜI GỬI</h2>
                    <div class="info-row">
                        <label>Họ và tên:</label>
                        <input type="text" name="tenNguoiGui" value="<?= htmlspecialchars($order['tenNguoiGui']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Điện thoại:</label>
                        <input type="text" name="sdtNguoiGui" value="<?= htmlspecialchars($order['sdtNguoiGui']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Địa chỉ:</label>
                        <input type="text" name="diaChiNguoiGui" value="<?= htmlspecialchars($order['diaChiNguoiGui']) ?>" required>
                    </div>
                </div>

                <div class="order-block">
                    <h2>NGƯỜI NHẬN</h2>
                    <div class="info-row">
                        <label>Họ và tên:</label>
                        <input type="text" name="tenNguoiNhan" value="<?= htmlspecialchars($order['tenNguoiNhan']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Điện thoại:</label>
                        <input type="text" name="soDienThoai" value="<?= htmlspecialchars($order['soDienThoai']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Địa chỉ:</label>
                        <input type="text" name="diaChiNguoiNhan" value="<?= htmlspecialchars($order['diaChiNguoiNhan']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Phường/Xã:</label>
                        <input type="text" name="phuong_xa" value="<?= htmlspecialchars($order['phuong_xa']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Quận/Huyện:</label>
                        <input type="text" name="quan_huyen" value="<?= htmlspecialchars($order['quan_huyen']) ?>" required>
                    </div>
                    <div class="info-row">
                        <label>Tỉnh/Thành phố:</label>
                        <input type="text" name="tinh_tp" value="<?= htmlspecialchars($order['tinh_tp']) ?>" required>
                    </div>
                </div>
            </section>

            <section class="order-info">
                <div class="order-block">
                    <h2>CHI PHÍ</h2>
                    <div class="info-row">
                        <label>Phí dịch vụ:</label>
                        <input type="number" name="phiDichVu" value="<?= htmlspecialchars($order['phiDichVu']) ?>" min="0" required>
                    </div>
                    <div class="info-row">
                        <label>Phí khai giá:</label>
                        <input type="number" name="phiKhaiGia" value="<?= htmlspecialchars($order['phiKhaiGia']) ?>" min="0" required>
                    </div>
                    <div class="info-row">
                        <label>Tổng phí:</label>
                        <input type="number" name="tongPhi" value="<?= htmlspecialchars($order['tongPhi']) ?>" min="0" required>
                    </div>
                    <div class="info-row">
                        <label>Người trả phí:</label>
                        <select name="benTraPhi" required>
                            <option value="1" <?= $order['benTraPhi'] == 1 ? 'selected' : '' ?>>Người nhận trả phí</option>
                            <option value="0" <?= $order['benTraPhi'] == 0 ? 'selected' : '' ?>>Người gửi trả phí</option>
                        </select>
                    </div>
                </div>
            </section>

            <div style="text-align: center;">
                <button type="submit" class="submit-button">
                    <i class="bi bi-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>

    <script>
        function addProduct() {
            const productList = document.getElementById('product-list');
            const productItem = document.createElement('div');
            productItem.className = 'product-item';
            productItem.innerHTML = `
                <input type="hidden" name="product_id[]" value="">
                <div class="info-row">
                    <label>Tên sản phẩm:</label>
                    <input type="text" name="product_name[]" required>
                </div>
                <div class="info-row">
                    <label>Mã sản phẩm:</label>
                    <input type="text" name="product_code[]">
                </div>
                <div class="info-row">
                    <label>Số lượng:</label>
                    <input type="number" name="product_quantity[]" min="1" required>
                </div>
                <div class="info-row">
                    <label>Cân nặng (gram):</label>
                    <input type="number" name="product_weight[]" min="0" required>
                </div>
                <button type="button" class="remove-product" onclick="removeProduct(this)">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            productList.appendChild(productItem);
        }

        function removeProduct(button) {
            const productItem = button.closest('.product-item');
            productItem.remove();
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#ddd';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Vui lòng điền đầy đủ thông tin bắt buộc');
            }
        });
    </script>
</body>
</html>