<?php
require_once './config/conn.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['id_khach'])) {
    echo "<script>alert('Bạn cần đăng nhập để truy cập trang này.'); window.location.href='index.php?url=login';</script>";
    header('Location: index.php?url=login');
    exit();
}

if (isset($_POST['btnTaoDon'])) {
    $maVanDon = "DGH" . date("YmdHis");
    $created_at = date('Y-m-d H:i:s');
    $id_khachhang = $_SESSION['id_khach'];
    // Người gửi
    $sender_name = $_POST['sender_name'];
    $sender_phone = $_POST['sender_phone'];
    $sender_address = $_POST['sender_address'];

    // Người nhận
    $receiver_name = $_POST['receiver_name'];
    $receiver_phone = $_POST['receiver_phone'];
    $receiver_address = $_POST['receiver_address'];
    $province = $_POST['provinceSelect'];
    $district = $_POST['districtSelect'];
    $ward = $_POST['wardSelect'];

    // Thông tin đơn hàng
    $shipping_option = $_POST['shipping-option'];
    $total_weight = $_POST['total-weight'];
    $product_length = $_POST['product-length'];
    $product_width = $_POST['product-width'];
    $product_height = $_POST['product-height'];
    $product_value = $_POST['product-value'];
    $COD = $_POST['COD'];
    $note = $_POST['note'];
    $thanh_toan = $_POST['thanh-toan'];

    // Phí
    $cost_ship = $_POST['cost_ship'];
    $cod_ship = $_POST['cod_ship'];
    $phiKhaiGia = $_POST['phiKhaiGia'];
    $total_costShip = $_POST['total_costShip'];

    // Sản phẩm (mảng)
    $product_names = $_POST['product-name'];
    $product_weights = $_POST['product-weight'];
    $product_quantities = $_POST['product-quantity'];
    $product_codes = $_POST['product-code'];

    // 
    $conn->begin_transaction();

    try {
        //người gửi
        $conn->query("INSERT INTO nguoigui(tenNguoiGui, diaChiNguoiGui, sdtNguoiGui) 
                      VALUES ('$sender_name', '$sender_address', '$sender_phone')");
        $sender_id = $conn->insert_id;

        // 2. Insert địa chỉ người nhận
        $conn->query("INSERT INTO diachi (diaChiNguoiNhan, tinh_tp, quan_huyen, phuong_xa)
                      VALUES ('$receiver_address', '$province', '$district', '$ward')");
        $address_id = $conn->insert_id;

        // 3. Insert người nhận
        $conn->query("INSERT INTO nguoinhan(tenNguoiNhan, soDienThoai, id_diaChi)
                      VALUES ('$receiver_name', '$receiver_phone', '$address_id')");
        $receiver_id = $conn->insert_id;

        // 4. Insert trạng thái đơn hàng mặc định
        $result = $conn->query("SELECT id_trangThai FROM trangthai WHERE tenTrangThai = 'Đã tạo'");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $status_id = $row['id_trangThai'];
        } else {
            throw new Exception("Trạng thái 'Đã tạo' không tồn tại trong cơ sở dữ liệu.");
        }

        // 5. Insert đơn hàng
        $conn->query("INSERT INTO donhang (
                        maVanDon,id_khachHang, hinhThucGui, KL_DH, dai, rong, cao, giaTriHang, COD, ghiChu, ngayTaoDon, 
                        id_nguoiGui, id_nguoiNhan, id_phi, id_trangThai
                      ) VALUES (
                        '$maVanDon','$id_khachhang','$shipping_option', '$total_weight', '$product_length', '$product_width', '$product_height',
                        '$product_value', '$COD', '$note', '$created_at',
                        '$sender_id', '$receiver_id', NULL, '$status_id'
                      )");

        // 6. Insert phí giao hàng
        $conn->query("INSERT INTO phi (maVanDon, phiDichVu, phiKhaiGia, phiCOD, tongPhi, benTraPhi) 
                      VALUES ('$maVanDon', '$cost_ship', '$phiKhaiGia', '$cod_ship', '$total_costShip', '$thanh_toan')");
        $fee_id = $conn->insert_id;

        // 7. Cập nhật lại fee_id vào bảng orders
        $conn->query("UPDATE donhang SET id_phi = '$fee_id' WHERE maVanDon = '$maVanDon'");

        // 8. Insert sản phẩm (danh sách)
        for ($i = 0; $i < count($product_names); $i++) {
            $tenSP = $product_names[$i];
            $klSP = $product_weights[$i];
            $slSP = $product_quantities[$i];
            $maSP = $product_codes[$i];

            $conn->query("INSERT INTO sanpham (maVanDon, maSP, tenSanPham, khoiLuong, soLuong)
                          VALUES ('$maVanDon', '$maSP', '$tenSP', '$klSP', '$slSP')");
        }

        // 9. Insert lịch sử trạng thái đơn hàng
        $conn->query("INSERT INTO lichsu_trangthai (maVanDon, id_trangThai, mocThoiGian, diaDiem, HIMnotes)
                      VALUES ('$maVanDon', '$status_id', '$created_at', '$province', 'Tạo đơn thành công')");

        // Commit transaction
        $conn->commit();

        header("Location: index.php?url=success-create-shipment&maVanDon=$maVanDon");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Lỗi tạo đơn: " . $e->getMessage() . "');</script>";
    }
}


?>


<div class="taodongiao">
    <div class="container py-4">
        <form action="" method="post">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4 shadow-sm" id="sender-section">
                        <h1>Tạo đơn giao</h1>
                        <!-- Sender Information Section -->
                        <div class="card-body">
                            <h5 class="mb-3">Bên gửi</h5>
                            <div class="row mb-3">
                                <!-- Dòng 1: Họ tên và Số điện thoại nằm ngang -->
                                <div class="col-md-12">
                                    <div class="row">
                                        <!-- Họ tên -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" placeholder="Tên người gửi" id="sender_name" name="sender_name" required>
                                        </div>
                                        <!-- Số điện thoại -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" placeholder="Số điện thoại người gửi" id="sender_phone" name="sender_phone" required> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <!-- Dòng 2: Địa chỉ và Hình thức gửi nằm ngang -->
                                <div class="col-md-12">
                                    <div class="row">
                                        <!-- Địa chỉ -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" placeholder="Địa chỉ người gửi" id="sender_address" name="sender_address" required>
                                        </div>
                                        <!-- Hình thức gửi -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Hình thức gửi</label>
                                            <div class="d-flex">
                                                <div class="form-check me-4">
                                                    <input class="form-check-input" type="radio" name="shipping-option" id="pickup" value="Lấy hàng tận nơi" checked>
                                                    <label class="form-check-label" for="pickup">Lấy hàng tận nơi</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="shipping-option" id="dropoff" value="Gửi tại bưu cục">
                                                    <label class="form-check-label" for="dropoff">Gửi hàng tại bưu cục</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Receiver Section -->
                        <div class="card-body border-top" id="receiver-section">
                            <h5 class="mb-3">Bên nhận</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" placeholder="Tên người nhận" id="receiver_name" name="receiver_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" placeholder="Số điện thoại người nhận" id="receiver_phone" name="receiver_phone" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="receiver_address" name="receiver_address" placeholder="Địa chỉ người nhận" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tỉnh/Thành phố <span class="text-danger">*</span></label>
                                        <select class="form-select" id="provinceSelect" name="provinceSelect" onchange="loadDistricts(this.value)" required>
                                            <option value="">-- Chọn Tỉnh/Thành phố --</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Quận - Huyện <span class="text-danger">*</span></label>
                                        <select class="form-select" id="districtSelect" name="districtSelect" onchange="loadWards(this.value)" required>
                                            <option value="">-- Chọn Quận/Huyện --</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Phường - Xã <span class="text-danger">*</span></label>
                                        <select class="form-select" id="wardSelect" name="wardSelect" required>
                                            <option value="">-- Chọn Phường/Xã --</option>
                                        </select>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Product Information Section -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <div class="product-info">
                                    <div class="product-info-header">
                                        <h5 class="product-info-title">Thông tin sản phẩm</h5>
                                        <button type="button" class="add-product-btn" id="addProductBtn">
                                            <i class="fas fa-plus"></i>
                                            <span>Thêm sản phẩm</span>
                                        </button>
                                    </div>

                                    <div class="product-form-group" id="productFormGroup">
                                        <!-- Dòng sản phẩm đầu tiên -->
                                        <div class="product-form-row" data-index="1">
                                            <div class="form-field">
                                                <label class="required">SP 1</label>
                                                <input type="text" placeholder="Nhập tên sản phẩm" class="form-control" name="product-name[]" required>
                                            </div>
                                            <div class="form-field">
                                                <label class="required">KL (gram)</label>
                                                <input type="number" value="200" class="form-control" name="product-weight[]" required>
                                            </div>
                                            <div class="form-field">
                                                <label class="required">Số lượng</label>
                                                <div class="quantity-control">
                                                    <input type="number" value="1" min="1" class="form-control" name="product-quantity[]" required>
                                                </div>
                                            </div>
                                            <div class="form-field">
                                                <label>Mã sản phẩm</label>
                                                <input type="text" placeholder="Nhập mã sản phẩm" class="form-control" name="product-code[]" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Information Section -->
                                <div class="product-info">
                                    <div class="product-info-header">
                                        <h5 class="product-info-title">Thông tin đơn hàng</h5>
                                    </div>

                                    <div class="product-form-group">
                                        <div class="product-form-row">
                                            <div class="form-field">
                                                <label class="required">KL (gram)</label>
                                                <input type="number" class="form-control" id="total-weight" name="total-weight" required>
                                            </div>
                                            <div class="form-field">
                                                <label class="required">Dài (cm)</label>
                                                <input type="number" class="form-control" id="product-length" name="product-length" required>
                                            </div>
                                            <div class="form-field">
                                                <label class="required">Rộng (cm)</label>
                                                <input type="number" class="form-control" id="product-width" name="product-width" required>
                                            </div>
                                            <div class="form-field">
                                                <label class="required">Cao (cm)</label>
                                                <input type="number" class="form-control" id="product-height" name="product-height" required>
                                            </div>
                                        </div>
                                        <div class="costProduct">
                                            <div class="product-value">
                                                <label class="required">Giá trị hàng hóa</label>
                                                <input type="text" class="form-control" id="product-value" name="product-value">
                                            </div>
                                            <div class="COD">
                                                <label class="required">Tổng tiền thu hộ(COD)</label>
                                                <input type="text" class="form-control" id="COD" name="COD">
                                            </div>
                                            <div class="product-value">
                                            </div>
                                        </div>
                                        <div class="note">
                                            <label class="required">Ghi chú</label>
                                            <input type="text" class="form-control" id="note" name="note" placeholder="Nhập ghi chú" >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Shipping Summary Section -->
                <div class="totalShip col-md-4" id="shipping-summary">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí dịch vụ</span>
                                <span class="fw-bold" id="cost-ship-view"></span>
                                <input type="hidden" name="cost_ship" id="cost-ship" value="">
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>COD</span>
                                <span class="fw-bold" id="cod-ship-view"></span>
                                <input type="hidden" name="cod_ship" id="cod-ship" value="">
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Phí khai giá</span>
                                <span class="fw-bold" id="phiKhaiGia-view"></span>
                                <input type="hidden" name="phiKhaiGia" id="phiKhaiGia" value="">
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Tổng phí:</span>
                                <span class="fw-bold" id="total-costShip-view"></span>
                                <input type="hidden" name="total_costShip" id="total-costShip" value="">
                            </div>

                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Nhập mã khuyến mãi">
                                <button class="btn btn-outline-secondary" type="button">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="dropdown mb-3" style="padding-top: 40px;">
                                <span>Vui lòng chọn bên trả phí:</span>
                                <select class="form-select" id="thanh-toan" name="thanh-toan" style="max-width: 350px;">
                                    <option value="Người gửi trả phí">Người gửi trả phí</option>
                                    <option value="Người nhận trả phí">Người nhận trả phí</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary flex-grow-1" id="btnLuuNhap" name="btnLuuNhap">LƯU NHÁP</button>
                                <button class="btn btn-primary flex-grow-1" style="background-color: #ff5722; border-color: #ff5722;" id="btnTaoDon" name="btnTaoDon">TẠO ĐƠN</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>