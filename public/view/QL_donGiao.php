<?php
require_once './config/conn.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['id_khach'])) {
    echo "<script>alert('Bạn cần đăng nhập để truy cập trang này.'); window.location.href='index.php?url=login';</script>";
    header('Location: index.php?url=login');
    exit();
}

// Lấy id của khách hàng đang đăng nhập
$user_id = $_SESSION['id_khach'];

// Lấy dữ liệu từ form lọc/tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status_name']) ? $_GET['status_name'] : 'all';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Xây dựng câu truy vấn
$sql = "SELECT o.maVanDon, o.ngayTaoDon, s.tenNguoiGui, r.tenNguoiNhan, o.COD, f.tongPhi, f.benTraPhi, os.tenTrangThai
            FROM donhang o
            JOIN nguoigui s ON o.id_nguoiGui = s.id_nguoiGui
            JOIN nguoinhan r ON o.id_nguoiNhan = r.id_nguoiNhan
            JOIN phi f ON o.id_phi = f.id_phi
            JOIN trangthai os ON o.id_trangThai = os.id_trangThai
            WHERE o.id_khachHang = $user_id"; 

// Điều kiện tìm kiếm
if ($search) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (o.maVanDon LIKE '%$search%' OR s.tenNguoiGui LIKE '%$search%' OR r.tenNguoiNhan LIKE '%$search%')";
}

// Điều kiện lọc trạng thái
if ($status !== 'all') {
    $status = $conn->real_escape_string($status);
    $sql .= " AND os.tenTrangThai = '$status'";
}

// Điều kiện lọc ngày
if ($date) {
    $date = $conn->real_escape_string($date);
    $sql .= " AND DATE(o.ngayTaoDon) = '$date'";
}

$sql .= " ORDER BY o.ngayTaoDon DESC";
$result = $conn->query($sql);
?>

<div class="management-container">
    <h1 class="mb-4" style="text-align:center;padding:20px">Quản lý đơn giao hàng</h1>

    <!-- Bộ lọc và tìm kiếm -->
    <form method="GET" action="">
        <div class="row mb-3">
            <!-- Tìm kiếm -->
            <div class="col-md-4 mb-2">
                <label for="searchInput" class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control search-input" id="searchInput" name="search"
                    placeholder="Tìm theo mã, người gửi, người nhận" value="<?php echo htmlspecialchars($search); ?>" oninput="filterOrders()">
            </div>
            <!-- Lọc trạng thái -->
            <div class="col-md-4 mb-2">
                <label for="statusFilter" class="form-label">Lọc theo trạng thái</label>
                <select class="form-select status-filter" id="statusFilter" name="status" onchange="filterOrders()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tất cả</option>
                    <option value="Đã tạo" <?php echo $status === 'Đã tạo' ? 'selected' : ''; ?>>Đã tạo</option>
                    <option value="Đang xử lý" <?php echo $status === 'Đang xử lý' ? 'selected' : ''; ?>>Đang xử lý</option>
                    <option value="Đang giao" <?php echo $status === 'Đang giao' ? 'selected' : ''; ?>>Đang giao</option>
                    <option value="Chờ xác nhận giao lại" <?php echo $status === 'Chờ xác nhận giao lại' ? 'selected' : ''; ?>>Chờ xác nhận giao lại</option>
                    <option value="Đã giao" <?php echo $status === 'Đã giao' ? 'selected' : ''; ?>>Đã giao</option>
                    <option value="Đã hủy" <?php echo $status === 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
                    <option value="Giao không thành công" <?php echo $status === 'Giao không thành công' ? 'selected' : ''; ?>>Giao không thành công</option>
                </select>
            </div>
            <!-- Lọc theo ngày -->
            <div class="col-md-4 mb-2">
                <label for="dateFilter" class="form-label">Lọc theo ngày</label>
                <input type="date" class="form-control date-filter" id="dateFilter" name="date" value="<?php echo htmlspecialchars($date); ?>" onchange="filterOrders()">
            </div>
        </div>
    </form>

    <!-- Bảng danh sách đơn hàng -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="Manager_table" style="background-color: #ff5722; color: antiquewhite;">
                <tr>
                    <th>STT</th>
                    <th>Mã vận đơn</th>
                    <th>Người gửi</th>
                    <th>Người nhận</th>
                    <th>Thu hộ - COD (VNĐ)</th>
                    <th>Tổng phí (VNĐ)</th>
                    <th>Bên trả phí</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="orderTableBody">
                <?php
                if ($result->num_rows > 0) {
                    $stt = 1;
                    while ($row = $result->fetch_assoc()) {
                        // Chuẩn hóa trạng thái để gán lớp CSS
                        $status_class = '';
                        switch ($row['tenTrangThai']) {
                            case 'Đã tạo':
                                $status_class = 'status-waitconfirm';
                                break;
                            case 'Đang xử lý':
                                    $status_class = 'status-waitconfirm';
                                    break;
                            case 'Đang giao':
                                $status_class = 'status-delivering';
                                break;
                            case 'Chờ xác nhận giao lại':
                                $status_class = 'status-reconfirm';
                                break;
                            case 'Đã giao':
                                $status_class = 'status-completed';
                                break;
                            case 'Giao không thành công':
                                $status_class = 'status-reconfirm';
                                break;
                            case 'Đã hủy':
                                $status_class = 'status-canceled';
                                break;
                        }
                ?>
                        <tr data-status="<?php echo htmlspecialchars($row['tenTrangThai']); ?>" data-date="<?php echo date('Y-m-d', strtotime($row['ngayTaoDon'])); ?>">
                            <td><?php echo $stt++; ?></td>
                            <td><?php echo htmlspecialchars($row['maVanDon']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenNguoiGui']); ?></td>
                            <td><?php echo htmlspecialchars($row['tenNguoiNhan']); ?></td>
                            <td><?php echo number_format($row['COD'], 0, ',', '.'); ?></td>
                            <td><?php echo number_format($row['tongPhi'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($row['benTraPhi']); ?></td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['tenTrangThai']); ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="showOrderDetails('<?php echo htmlspecialchars($row['maVanDon']); ?>')">Chi tiết</button>
                                <a href="index.php?url=update-order&maVanDon=<?php echo urlencode($row['maVanDon']); ?>" class="btn btn-sm btn-warning">Sửa</a>
                            </td>
                        </tr>
                <?php
                    }
                } else {
                    echo '<tr><td colspan="9" class="text-center">Không có đơn hàng nào.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showOrderDetails(maVanDon) {
    window.location.href = 'index.php?url=detail-orders&&maVanDon=' + encodeURIComponent(maVanDon);
}
</script> 