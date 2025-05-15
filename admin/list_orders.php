<?php
// assign.php
require 'header.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require '../config/conn.php';

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$searchOrderId = isset($_GET['search_order_id']) ? trim($_GET['search_order_id']) : '';

// Base SQL query
$sql = "SELECT 
    D.maVanDon,
    S.tenSanPham,
    N.tenNguoiNhan,
    N.soDienThoai,
    DC.diaChiNguoiNhan,
    D.ngayTaoDon,
    T.tenTrangThai,
    D.id_trangThai
FROM DonHang D
JOIN NguoiNhan N ON D.id_nguoiNhan = N.id_nguoiNhan
JOIN DiaChi DC ON N.id_diaChi = DC.id_diaChi
LEFT JOIN SanPham S ON D.maVanDon = S.maVanDon
JOIN TrangThai T ON D.id_trangThai = T.id_trangThai
WHERE 1=1";

// Add order ID search
if (!empty($searchOrderId)) {
    $searchOrderId = $mysqli->real_escape_string($searchOrderId);
    $sql .= " AND D.maVanDon LIKE '%$searchOrderId%'";
}

// Add status filter
if (!empty($statusFilter)) {
    $sql .= " AND D.id_trangThai = " . intval($statusFilter);
}

// Add date range filter
if (!empty($dateFrom)) {
    $dateFrom = date('Y-m-d 00:00:00', strtotime($dateFrom));
    $sql .= " AND D.ngayTaoDon >= '$dateFrom'";
}
if (!empty($dateTo)) {
    $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo));
    $sql .= " AND D.ngayTaoDon <= '$dateTo'";
}

$sql .= " ORDER BY D.ngayTaoDon DESC";

$orders = $mysqli->query($sql);

// Get all statuses for filter dropdown
$statuses = $mysqli->query("SELECT id_trangThai, tenTrangThai FROM TrangThai ORDER BY id_trangThai");

$message = '';
$messageType = '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách đơn hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/orders.css">
</head>

<body>
    <div class="content-wrapper">
        <div class="card-orders">
            <div class="card-header">
                <h3><i class="bi bi-truck"></i> Danh sách đơn hàng</h3>
            </div>

            <!-- Filter Form -->
            <div class="filter-container">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <?php if (!empty($searchOrderId)): ?>
                            <input type="hidden" name="search_order_id" value="<?= htmlspecialchars($searchOrderId) ?>">
                        <?php endif; ?>
                        <div class="filter-group">
                            <label for="status">Trạng thái:</label>
                            <select name="status" id="status" class="form-select" style="width: 50%;">
                                <option value="">Tất cả trạng thái</option>
                                <?php while ($status = $statuses->fetch_assoc()): ?>
                                    <option value="<?= $status['id_trangThai'] ?>" <?= $statusFilter == $status['id_trangThai'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($status['tenTrangThai']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from">Từ ngày:</label>
                            <input type="date" name="date_from" id="date_from" value="<?= $dateFrom ?>" class="form-control">
                        </div>
                        <div class="filter-group">
                            <label for="date_to">Đến ngày:</label>
                            <input type="date" name="date_to" id="date_to" value="<?= $dateTo ?>" class="form-control">
                        </div>
                        <div class="search-container">
                            <div class="search-form">
                                <div class="search-input">
                                    <i class="bi bi-search"></i>
                                    <input type="text"
                                        name="search_order_id"
                                        placeholder="Tìm kiếm mã vận đơn"
                                        value="<?= htmlspecialchars($searchOrderId) ?>"
                                        autocomplete="off">
                                </div>
                                <button type="submit" class="btn-filter btn-primary">
                                    <i class="bi bi-funnel"></i> Lọc
                                </button>
                                <?php if (!empty($statusFilter) || !empty($dateFrom) || !empty($dateTo) || !empty($searchOrderId)): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['status' => '', 'date_from' => '', 'date_to' => '', 'search_order_id' => ''])) ?>"
                                        class="btn-filter btn-secondary">
                                        <i class="bi bi-x-circle"></i> Xóa bộ lọc
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
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
                        <td colspan="7" style="text-align: center;">Không có đơn hàng nào</td>
                    </tr>
                    <?php
                else:
                    while ($don = $orders->fetch_assoc()):
                        $statusClass = '';
                        switch ($don['id_trangThai']) {
                            case 1:
                                $statusClass = 'status-pending';
                                break;
                            case 2:
                                $statusClass = 'status-processing';
                                break;
                            case 3:
                                $statusClass = 'status-delivering';
                                break;
                            case 4:
                                $statusClass = 'status-completed';
                                break;
                            case 5:
                                $statusClass = 'status-cancelled';
                                break;
                        }
                    ?>
                        <tr>
                            <td class="bold"><b><?= htmlspecialchars($don['maVanDon'] ?? '') ?></b></td>
                            <td><?= htmlspecialchars($don['tenSanPham'] ?? 'N/A') ?></td>
                            <td>
                                <b><?= htmlspecialchars($don['tenNguoiNhan'] ?? 'N/A') ?></b><br>
                                <span style="color:#666;font-size:14px">
                                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($don['soDienThoai'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($don['diaChiNguoiNhan'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($don['ngayTaoDon'])) ?></td>
                            <td>
                                <span class="badge bg-<?php
                                                        echo match ($don['tenTrangThai']) {
                                                            'Đã tạo' => 'warning',
                                                            'Đang xử lý' => 'info',
                                                            'Đang giao' => 'primary',
                                                            'Đã giao' => 'success',
                                                            'Giao không thành công' => 'danger',
                                                            'Đã hủy' => 'danger',
                                                            default => 'secondary'
                                                        };
                                                        ?>">
                                    <?php echo htmlspecialchars($don['tenTrangThai']); ?>
                                </span>
                                <!-- <span class="status-label <?= $statusClass ?>"><?= htmlspecialchars($don['tenTrangThai'] ?? 'N/A') ?></span></td> -->
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="detail_listOrder.php?id=<?= htmlspecialchars($don['maVanDon']) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit_order.php?id=<?= htmlspecialchars($don['maVanDon']) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>
                <?php
                    endwhile;
                endif;
                ?>
            </table>
        </div>
    </div>

    <script>
        // Set max date for date inputs to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date_from').max = today;
        document.getElementById('date_to').max = today;

        // Validate date range
        document.getElementById('date_from').addEventListener('change', function() {
            document.getElementById('date_to').min = this.value;
        });

        document.getElementById('date_to').addEventListener('change', function() {
            document.getElementById('date_from').max = this.value;
        });
    </script>
</body>

</html>