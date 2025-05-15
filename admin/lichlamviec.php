<?php
// lichlamviec.php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conn.php';
require 'header.php';

$actionMessage = '';
// Thêm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $mysqli->prepare("INSERT INTO lichlamviec (id_NhanVien, ngayLamViec, thoiGianBatDau, thoiGianKetThuc, ghiChu) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $_POST['Id_NhanVien'], $_POST['ngayLamViec'], $_POST['thoiGianBatDau'], $_POST['thoiGianKetThuc'], $_POST['ghiChu']);
    $stmt->execute();
    $actionMessage = "Thêm lịch làm việc thành công!";
}

// Xóa
if (isset($_GET['delete'])) {
    $stmt = $mysqli->prepare("DELETE FROM lichlamviec WHERE id_lichLamViec = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $actionMessage = "Đã xóa lịch làm việc!";
}

// Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    // 1. Cập nhật lịch làm việc
    $stmt = $mysqli->prepare("UPDATE lichlamviec SET ngayLamViec=?, thoiGianBatDau=?, thoiGianKetThuc=?, ghiChu=? WHERE id_lichLamViec=?");
    $stmt->bind_param("ssssi", $_POST['ngayLamViec'], $_POST['thoiGianBatDau'], $_POST['thoiGianKetThuc'], $_POST['ghiChu'], $_POST['Id_LichLamViec']);
    $stmt->execute();

    // 2. Lấy id_NhanVien từ lịch làm việc vừa sửa
    $stmt = $mysqli->prepare("SELECT id_NhanVien FROM lichlamviec WHERE id_lichLamViec = ?");
    $stmt->bind_param("i", $_POST['Id_LichLamViec']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $idNhanVien = $row['id_NhanVien'];

    // 3. Tạo thông báo kèm thời gian chi tiết
    $ngayTao = date("Y-m-d H:i:s");
    $ngayLam = $_POST['ngayLamViec'];
    $batDau = $_POST['thoiGianBatDau'];
    $ketThuc = $_POST['thoiGianKetThuc'];

    $noiDung = "🗓 Lịch làm việc ngày $ngayLam từ $batDau đến $ketThuc của bạn đã được cập nhật.";
    $trangThai = "Chưa đọc";

    $stmt = $mysqli->prepare("INSERT INTO thongbao (id_NhanVien, noiDung, ngayTao, trangThai) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $idNhanVien, $noiDung, $ngayTao, $trangThai);
    $stmt->execute();

    $actionMessage = "Cập nhật thành công!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $tenNhanVien = $mysqli->real_escape_string($_POST['tenNhanVien']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $soDienThoai = $mysqli->real_escape_string($_POST['soDienThoai']);
    $diaChi = $mysqli->real_escape_string($_POST['diaChi']);
    $password = $mysqli->real_escape_string($_POST['password']);
    $viTri = $mysqli->real_escape_string($_POST['viTri']);
    
    $stmt = $mysqli->prepare("INSERT INTO nhanvien (tenNhanVien, email, soDienThoai, diaChi, phanQuyen, password, viTri) VALUES (?, ?, ?, ?, 2, ?, ?)");
    $stmt->bind_param("ssssss", $tenNhanVien, $email, $soDienThoai, $diaChi, $password, $viTri);
    
    if ($stmt->execute()) {
        $actionMessage = "Thêm nhân viên mới thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi thêm nhân viên!";
    }
}

// Xóa nhân viên
if (isset($_GET['delete_employee'])) {
    $id = (int)$_GET['delete_employee'];
    // Xóa lịch làm việc của nhân viên trước
    $stmt = $mysqli->prepare("DELETE FROM lichlamviec WHERE id_NhanVien = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    // Sau đó xóa nhân viên
    $stmt = $mysqli->prepare("DELETE FROM nhanvien WHERE id_nhanVien = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $actionMessage = "Đã xóa nhân viên thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi xóa nhân viên!";
    }
}

// Cập nhật thông tin nhân viên
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $id = (int)$_POST['Id_nhanVien'];
    $tenNhanVien = $mysqli->real_escape_string($_POST['tenNhanVien']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $soDienThoai = $mysqli->real_escape_string($_POST['soDienThoai']);
    $diaChi = $mysqli->real_escape_string($_POST['diaChi']);
    $viTri = $mysqli->real_escape_string($_POST['viTri']);
    
    $stmt = $mysqli->prepare("UPDATE nhanvien SET tenNhanVien=?, email=?, soDienThoai=?, diaChi=?, viTri=? WHERE id_nhanVien=?");
    $stmt->bind_param("sssssi", $tenNhanVien, $email, $soDienThoai, $diaChi, $viTri, $id);
    
    if ($stmt->execute()) {
        $actionMessage = "Cập nhật thông tin nhân viên thành công!";
    } else {
        $actionMessage = "Có lỗi xảy ra khi cập nhật thông tin!";
    }
}

// Danh sách nhân viên
$nhanvien = $mysqli->query("SELECT * FROM nhanvien ORDER BY tenNhanVien");

// Nếu chọn nhân viên
$lichlamviec = [];
$selectedStaff = null;
if (isset($_GET['staff_id'])) {
    $sid = (int)$_GET['staff_id'];
    $selectedStaff = $mysqli->query("SELECT * FROM nhanvien WHERE id_nhanVien=$sid")->fetch_assoc();
    $lichlamviec = $mysqli->query("SELECT * FROM lichlamviec WHERE id_NhanVien=$sid ORDER BY ngayLamViec DESC");
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý lịch làm việc</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/lichlamviec.css">
</head>
<body>
<div class="container">
    <h2>Quản lý lịch làm việc nhân viên</h2>

    <?php if ($actionMessage): ?>
        <div class="alert"><?= $actionMessage ?></div>
    <?php endif; ?>

    <div class="section">
        <h3>Thêm nhân viên mới</h3>
        <button class="btn" onclick="document.getElementById('addEmployeeModal').style.display='block'">Thêm nhân viên mới</button>
    </div>

    <div class="section">
        <h3>Danh sách nhân viên</h3>
        <div class="grid">
            <?php while ($nv = $nhanvien->fetch_assoc()): ?>
                <div class="employee-card">
                    <div class="employee-name"><?= htmlspecialchars($nv['tenNhanVien']) ?></div>
                    <div class="employee-actions">
                        <button class="btn btn-edit" onclick="openEditModal(<?= htmlspecialchars(json_encode($nv)) ?>)">Sửa</button>
                        <a class="btn btn-danger" href="?delete_employee=<?= $nv['id_nhanVien'] ?>" onclick="return confirm('Bạn có chắc muốn xóa nhân viên này?')">Xóa</a>
                    </div>
                    <a class="btn" href="?staff_id=<?= $nv['id_nhanVien'] ?>">Xem lịch làm việc</a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <?php if ($selectedStaff): ?>
        <div class="section">
            <h3>Lịch làm việc của: <?= htmlspecialchars($selectedStaff['tenNhanVien']) ?></h3>

            <form method="post" class="form-group">
                <input type="hidden" name="Id_NhanVien" value="<?= $selectedStaff['id_nhanVien'] ?>">
                <div class="form-group">
                    <label>Ngày làm việc:</label>
                    <input type="date" name="ngayLamViec" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Bắt đầu:</label>
                    <input type="time" name="thoiGianBatDau" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Kết thúc:</label>
                    <input type="time" name="thoiGianKetThuc" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Ghi chú:</label>
                    <textarea name="ghiChu" class="form-control"></textarea>
                </div>
                <button type="submit" name="add" class="btn">Thêm lịch</button>
            </form>

            <?php if ($lichlamviec && $lichlamviec->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Thời gian</th>
                            <th>Ghi chú</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $lichlamviec->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['ngayLamViec'] ?></td>
                            <td><?= $row['thoiGianBatDau'] ?> - <?= $row['thoiGianKetThuc'] ?></td>
                            <td><?= htmlspecialchars($row['ghiChu']) ?></td>
                            <td>
                                <a class="btn btn-danger" href="?staff_id=<?= $selectedStaff['id_nhanVien'] ?>&delete=<?= $row['id_lichLamViec'] ?>" onclick="return confirm('Xóa lịch này?')">Xóa</a>
                                <details>
                                    <summary class="btn btn-edit">Sửa</summary>
                                    <form method="post" class="form-group">
                                        <input type="hidden" name="Id_LichLamViec" value="<?= $row['id_lichLamViec'] ?>">
                                        <div class="form-group">
                                            <label>Ngày:</label>
                                            <input type="date" name="ngayLamViec" value="<?= $row['ngayLamViec'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Bắt đầu:</label>
                                            <input type="time" name="thoiGianBatDau" value="<?= $row['thoiGianBatDau'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Kết thúc:</label>
                                            <input type="time" name="thoiGianKetThuc" value="<?= $row['thoiGianKetThuc'] ?>" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Ghi chú:</label>
                                            <input name="ghiChu" value="<?= htmlspecialchars($row['ghiChu']) ?>" class="form-control">
                                        </div>
                                        <button type="submit" name="edit" class="btn">Cập nhật</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Không có lịch làm việc nào.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal thêm nhân viên -->
<div id="addEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('addEmployeeModal').style.display='none'">&times;</span>
        <h3>Thêm nhân viên mới</h3>
        <form method="post" class="form-group">
            <div class="form-group">
                <label>Tên nhân viên:</label>
                <input type="text" name="tenNhanVien" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="soDienThoai" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="diaChi" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Vị trí:</label>
                <select name="viTri" class="form-control" required>
                    <option value="">-- Chọn vị trí --</option>
                    <option value="Quản lý">Quản lý</option>
                    <option value="Giao hàng">Giao hàng</option>
                </select>
            </div>
            <button type="submit" name="add_employee" class="btn">Thêm nhân viên</button>
        </form>
    </div>
</div>

<!-- Modal sửa nhân viên -->
<div id="editEmployeeModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editEmployeeModal').style.display='none'">&times;</span>
        <h3>Sửa thông tin nhân viên</h3>
        <form method="post" class="form-group">
            <input type="hidden" name="Id_nhanVien" id="edit_id_nhanVien">
            <div class="form-group">
                <label>Tên nhân viên:</label>
                <input type="text" name="tenNhanVien" id="edit_tenNhanVien" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="soDienThoai" id="edit_soDienThoai" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Địa chỉ:</label>
                <textarea name="diaChi" id="edit_diaChi" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label>Vị trí:</label>
                <select name="viTri" id="edit_viTri" class="form-control" required>
                    <option value="">-- Chọn vị trí --</option>
                    <option value="Quản lý">Quản lý</option>
                    <option value="Giao hàng">Giao hàng</option>
                </select>
            </div>
            <button type="submit" name="edit_employee" class="btn">Cập nhật</button>
        </form>
    </div>
</div>

<script>
function openEditModal(employee) {
    document.getElementById('edit_id_nhanVien').value = employee.id_nhanVien;
    document.getElementById('edit_tenNhanVien').value = employee.tenNhanVien;
    document.getElementById('edit_email').value = employee.email;
    document.getElementById('edit_soDienThoai').value = employee.soDienThoai;
    document.getElementById('edit_diaChi').value = employee.diaChi;
    document.getElementById('edit_viTri').value = employee.viTri;
    document.getElementById('editEmployeeModal').style.display = 'block';
}

// Đóng modal khi click bên ngoài
window.onclick = function(event) {
    if (event.target == document.getElementById('addEmployeeModal')) {
        document.getElementById('addEmployeeModal').style.display = "none";
    }
    if (event.target == document.getElementById('editEmployeeModal')) {
        document.getElementById('editEmployeeModal').style.display = "none";
    }
}
</script>

<?php require 'footer.php'; ?>
</body>
</html>
