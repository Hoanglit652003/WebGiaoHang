<?php
// assign.php
require 'header.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require '../config/conn.php';

$message = '';
$messageType = '';

// Xử lý phân công khi submit form
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  !empty($_POST['orders']) &&
  !empty($_POST['staff_id'])
) {
  $staffId = (int)$_POST['staff_id'];
  $orders = $_POST['orders'];

  $mysqli->begin_transaction();
  try {
    $stmtUpdate = $mysqli->prepare(
      "UPDATE DonHang 
           SET id_nhanVien = ?, id_trangThai = 2 
           WHERE maVanDon = ?"
    );

    $stmtNotify = $mysqli->prepare(
      "INSERT INTO ThongBao (id_NhanVien, noiDung, ngayTao, trangThai) VALUES (?, ?, NOW(), 'Chưa đọc')"
    );

    // Thêm câu lệnh chuẩn bị cho việc lưu lịch sử trạng thái
    $stmtLichSu = $mysqli->prepare(
      "INSERT INTO LichSu_TrangThai (maVanDon, id_trangThai, mocThoiGian, diaDiem, HIMnotes) 
           VALUES (?, ?, NOW(), 'Thành phố Hồ Chí Minh', ?)"
    );

    foreach ($orders as $md) {
      // Cập nhật trạng thái đơn hàng
      $stmtUpdate->bind_param('is', $staffId, $md);
      $stmtUpdate->execute();

      // Thêm thông báo
      $msgNotify = "Bạn được phân công đơn hàng $md";
      $stmtNotify->bind_param('is', $staffId, $msgNotify);
      $stmtNotify->execute();

      // Lưu lịch sử trạng thái
      $idTrangThai = 2; // Trạng thái "Đã phân công"
      $ghiChu = "Đã phân công cho nhân viên ID: $staffId";
      $stmtLichSu->bind_param("sis", $md, $idTrangThai, $ghiChu);
      $stmtLichSu->execute();
    }

    $mysqli->commit();
    $message = 'Phân công thành công!';
    $messageType = 'success';
  } catch (Exception $e) {
    $mysqli->rollback();
    $message = 'Lỗi: Không thể cập nhật. Vui lòng thử lại!';
    $messageType = 'error';
  }
}

// Lấy đơn chờ phân công
$sql = "
    SELECT D.maVanDon,
           DC.diaChiNguoiNhan AS diaChiNhan,
           S.tenSanPham AS loaiHang,
           D.ngayTaoDon
    FROM DonHang D
    JOIN NguoiNhan N ON D.id_nguoiNhan = N.id_nguoiNhan
    JOIN DiaChi DC ON N.id_diaChi = DC.id_diaChi
    LEFT JOIN SanPham S ON D.maVanDon = S.maVanDon
    WHERE D.id_trangThai = 1
    ORDER BY D.ngayTaoDon DESC
";
$result = $mysqli->query($sql);

// Lấy danh sách nhân viên giao hàng và thống kê
$staffs = $mysqli->query(
  "SELECT id_nhanVien, tenNhanVien, viTri FROM NhanVien WHERE viTri = 'Giao hàng'"
);
$staffList = [];
$staffStats = [];
while ($s = $staffs->fetch_assoc()) {
  $sid = $s['id_nhanVien'];
  // Đếm pending
  $st1 = $mysqli->prepare("SELECT COUNT(*) FROM DonHang WHERE id_nhanVien = ? AND id_trangThai != 4");
  $st1->bind_param('i', $sid);
  $st1->execute();
  $st1->bind_result($pending);
  $st1->fetch();
  $st1->close();
  // Đếm done
  $st2 = $mysqli->prepare("SELECT COUNT(*) FROM DonHang WHERE id_nhanVien = ? AND id_trangThai = 4");
  $st2->bind_param('i', $sid);
  $st2->execute();
  $st2->bind_result($done);
  $st2->fetch();
  $st2->close();
  $perf = ($done + $pending) > 0 ? round($done / ($done + $pending) * 100, 1) : 0;

  $staffList[] = $s;
  $staffStats[$sid] = [
    'name'        => $s['tenNhanVien'],
    'position'    => $s['viTri'],
    'pending'     => $pending,
    'done'        => $done,
    'performance' => $perf
  ];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Phân công đơn hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/orders.css">
</head>

<body>
  <div class="content-container">
    <h2>Đơn hàng chờ phân công</h2>
    <form id="assignForm" method="post">
      <?php if ($result->num_rows === 0): ?>
        <p class="no-orders">Không có đơn hàng nào.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Chọn</th>
              <th>Mã đơn</th>
              <th>Địa chỉ</th>
              <th>Loại hàng</th>
              <th>Ngày tạo</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><input type="checkbox" name="orders[]" value="<?= htmlspecialchars($row['maVanDon'] ?? '') ?>"></td>
                <td><?= htmlspecialchars($row['maVanDon'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['diaChiNhan'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['loaiHang'] ?? '') ?></td>
                <td><?= date('d/m/Y', strtotime($row['ngayTaoDon'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php endif; ?>
      <div class="form-group">
        <label for="staffSelect">Phân công cho:</label>
        <select name="staff_id" id="staffSelect" required>
          <option value="">-- Chọn nhân viên giao hàng --</option>
          <?php foreach ($staffList as $s): ?>
            <option value="<?= $s['id_nhanVien'] ?>">
              <?= htmlspecialchars($s['tenNhanVien'] ?? '') ?>
              (<?= $staffStats[$s['id_nhanVien']]['pending'] ?? 0 ?> đơn đang xử lý)
            </option>
          <?php endforeach; ?>
        </select>
        <a id="detailLink" href="#" style="display:none;">Xem chi tiết nhân viên</a>
      </div>
      <button type="submit" id="assignButton">
        <span class="spinner" id="submitSpinner"></span>
        Phân công đơn hàng
      </button>
    </form>
  </div>

  <!-- Assignment Result Modal -->
  <?php if ($message): ?>
    <div id="assignModal" class="modal">
      <div class="modal-content <?= $messageType ?>">
        <span class="close" id="closeAssign">&times;</span>
        <p><?= htmlspecialchars($message ?? '') ?></p>
      </div>
    </div>
  <?php endif; ?>

  <!-- Staff Detail Modal -->
  <div id="staffModal" class="modal">
    <div class="modal-content">
      <span class="close" id="closeStaff">&times;</span>
      <div class="modal-header">Chi tiết nhân viên</div>
      <div class="modal-body" id="staffModalBody"></div>
    </div>
  </div>

  <script>
    // Pass PHP stats to JS
    const staffStats = <?= json_encode($staffStats) ?>;

    // Elements
    const staffSelect = document.getElementById('staffSelect');
    const detailLink = document.getElementById('detailLink');
    const staffModal = document.getElementById('staffModal');
    const staffBody = document.getElementById('staffModalBody');
    const closeStaff = document.getElementById('closeStaff');
    const assignButton = document.getElementById('assignButton');
    const submitSpinner = document.getElementById('submitSpinner');

    // Show detail link on select change
    staffSelect.addEventListener('change', function() {
      if (this.value) {
        detailLink.style.display = 'inline';
      } else {
        detailLink.style.display = 'none';
      }
    });

    // Open staff detail modal
    detailLink.addEventListener('click', function(e) {
      e.preventDefault();
      const sid = staffSelect.value;
      const st = staffStats[sid];
      if (st) {
        staffBody.innerHTML = `
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-label">Đang xử lý</div>
          <div class="stat-value">${st.pending}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Đã giao</div>
          <div class="stat-value">${st.done}</div>
        </div>
        <div class="stat-item">
          <div class="stat-label">Hiệu suất</div>
          <div class="stat-value">${st.performance}%</div>
        </div>
      </div>
      <ul>
        <li><strong>Tên:</strong> ${st.name}</li>
        <li><strong>Vị trí:</strong> ${st.position}</li>
      </ul>
    `;
        staffModal.style.display = 'block';
      }
    });

    // Close staff modal
    closeStaff.onclick = () => staffModal.style.display = 'none';
    window.addEventListener('click', e => {
      if (e.target === staffModal) staffModal.style.display = 'none';
    });

    // Assignment result modal
    const assignModal = document.getElementById('assignModal');
    const closeAssign = document.getElementById('closeAssign');
    if (assignModal) {
      assignModal.style.display = 'block';
      closeAssign.onclick = () => assignModal.style.display = 'none';
      window.onclick = e => {
        if (e.target === assignModal) assignModal.style.display = 'none';
      };
    }

    // Form validation and submission
    document.getElementById('assignForm').addEventListener('submit', function(e) {
      const checkedOrders = document.querySelectorAll('input[name="orders[]"]:checked');
      if (checkedOrders.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một đơn hàng để phân công!');
        return;
      }

      if (!confirm(`Bạn có chắc chắn muốn phân công ${checkedOrders.length} đơn hàng cho nhân viên này?`)) {
        e.preventDefault();
        return;
      }

      // Show loading state
      assignButton.disabled = true;
      submitSpinner.style.display = 'inline-block';
      assignButton.innerHTML = '<span class="spinner" id="submitSpinner"></span> Đang xử lý...';
    });
  </script>

  <?php require 'footer.php'; ?>