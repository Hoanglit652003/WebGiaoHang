<?php
require 'header1.php';
// session_start();

// Kiểm tra đăng nhập và vai trò nhân viên giao hàng
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 2) {
  header('Location: login.php');
  exit;
}

$staffId = $_SESSION['user_id'];

// Xử lý cập nhật trạng thái khi nhấn "Bắt đầu giao hàng"
// Xử lý cập nhật trạng thái khi nhấn "Bắt đầu giao hàng"
if (isset($_POST['start_delivery']) && isset($_GET['maVanDon'])) {
  $maVanDon = $_GET['maVanDon'];
  $idTrangThai = 3; // Trạng thái "Đang giao"
  
  // Cập nhật trạng thái đơn hàng
  $updateStmt = $mysqli->prepare(
    "UPDATE donhang SET id_trangThai = ? WHERE maVanDon = ? AND id_nhanVien = ?"
  );
  $updateStmt->bind_param("isi", $idTrangThai, $maVanDon, $staffId);
  
  if ($updateStmt->execute()) {
    // Thêm vào lịch sử trạng thái
    $stmtLichSu = $mysqli->prepare(
      "INSERT INTO LichSu_TrangThai (maVanDon, id_trangThai, mocThoiGian, diaDiem, HIMnotes) 
       VALUES (?, ?, NOW(), 'Thành phố Hồ Chí Minh', ?)"
    );
    $ghiChu = "Đang giao hàng"; // Ghi chú cho trạng thái mới
    $stmtLichSu->bind_param("sis", $maVanDon, $idTrangThai, $ghiChu);
    
    if ($stmtLichSu->execute()) {
      // Hiển thị thông báo và reload trang khi cả hai thao tác đều thành công
      echo "<script>alert('Đã cập nhật trạng thái đơn hàng thành Đang giao.'); window.location.href = window.location.pathname;</script>";
    } else {
      echo "<script>alert('Cập nhật trạng thái thành công nhưng không lưu được lịch sử. Vui lòng kiểm tra lại.');</script>";
    }
  } else {
    echo "<script>alert('Không thể cập nhật trạng thái. Vui lòng thử lại.');</script>";
  }
}

// First, let's check the orders directly
$debugQuery = "SELECT * FROM DonHang WHERE id_nhanVien = ?";
$debugStmt = $mysqli->prepare($debugQuery);
$debugStmt->bind_param("i", $staffId);
$debugStmt->execute();
$debugResult = $debugStmt->get_result();

echo "<!-- Debug: Direct DonHang query found " . $debugResult->num_rows . " orders -->";
if ($row = $debugResult->fetch_assoc()) {
  echo "<!-- Debug: Sample order data: " . print_r($row, true) . " -->";
}

// Now let's try the full query with LEFT JOINs to see where data might be missing
$query = "SELECT D.maVanDon, D.ngayTaoDon, DC.diaChiNguoiNhan, DC.tinh_tp, DC.quan_huyen, DC.phuong_xa, 
            T.tenTrangThai, S.tenSanPham,
            NG.tenNguoiGui, NG.sdtNguoiGui, NG.diaChiNguoiGui,
            NN.tenNguoiNhan, NN.soDienThoai,
            D.giaTriHang, D.COD, P.tongPhi, P.benTraPhi, P.phiDichVu
     FROM DonHang D
     LEFT JOIN NguoiNhan N ON D.id_nguoiNhan = N.id_nguoiNhan
     LEFT JOIN DiaChi DC ON N.id_diaChi = DC.id_diaChi
     LEFT JOIN SanPham S ON D.maVanDon = S.maVanDon
     LEFT JOIN TrangThai T ON D.id_trangThai = T.id_trangThai
     LEFT JOIN NguoiGui NG ON D.id_nguoiGui = NG.id_nguoiGui
     LEFT JOIN NguoiNhan NN ON D.id_nguoiNhan = NN.id_nguoiNhan
     LEFT JOIN Phi P ON D.maVanDon = P.maVanDon
     WHERE D.id_nhanVien = ?
       AND D.id_trangThai IN (2, 3, 4, 5)
     ORDER BY D.ngayTaoDon DESC";

// Debug: Print the staff ID and query
echo "<!-- Debug: Staff ID = " . $staffId . " -->";
echo "<!-- Debug: Query = " . $query . " -->";

$stmtList = $mysqli->prepare($query);
$stmtList->bind_param("i", $staffId);
$stmtList->execute();
$resultList = $stmtList->get_result();

// Debug: Check if we got any results
if ($resultList->num_rows === 0) {
  echo "<!-- Debug: No results found for staff ID " . $staffId . " -->";

  // Debug: Let's check each table separately
  $tables = [
    'DonHang' => "SELECT * FROM DonHang WHERE id_nhanVien = " . $staffId,
    'NguoiNhan' => "SELECT * FROM NguoiNhan",
    'DiaChi' => "SELECT * FROM DiaChi",
    'SanPham' => "SELECT * FROM SanPham",
    'TrangThai' => "SELECT * FROM TrangThai"
  ];

  foreach ($tables as $table => $sql) {
    $result = $mysqli->query($sql);
    echo "<!-- Debug: Table $table has " . $result->num_rows . " rows -->";
    if ($row = $result->fetch_assoc()) {
      echo "<!-- Debug: Sample $table data: " . print_r($row, true) . " -->";
    }
  }
}

// Debug: Print the first row if exists
if ($row = $resultList->fetch_assoc()) {
  echo "<!-- Debug: First row data: " . print_r($row, true) . " -->";
  // Reset the result pointer
  $resultList->data_seek(0);
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders</title>
  <link rel="stylesheet" href="./css/my_orders.css">
  <link rel="stylesheet" href="./css/footer.css">
</head>

<body>
  <div class="content-container">
    <h2>Đơn hàng của tôi</h2>

    <table class="table-orders">
      <tr>
        <th>Mã đơn</th>
        <th>Sản phẩm</th>
        <th>Địa chỉ</th>
        <th>Ngày tạo</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
      </tr>
      <?php
      if ($resultList->num_rows > 0):
        while ($row = $resultList->fetch_assoc()):
      ?>
          <tr>
            <td><?= htmlspecialchars($row['maVanDon']) ?></td>
            <td><?= htmlspecialchars($row['tenSanPham'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars(($row['diaChiNguoiNhan'] ?? '') . ', ' . ($row['phuong_xa'] ?? '') . ', ' . ($row['quan_huyen'] ?? '') . ', ' . ($row['tinh_tp'] ?? '')) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($row['ngayTaoDon'])) ?></td>
            <td>
              <?php
              $status = $row['tenTrangThai'] ?? 'Không xác định';
              $statusClass = '';
              if ($status === 'Đang giao') $statusClass = 'status-danggiao';
              elseif ($status === 'Đã giao') $statusClass = 'status-dagiao';
              elseif ($status === 'Đang xử lý') $statusClass = 'status-phancong';
              elseif ($status === 'Giao không thành công') $statusClass = 'status-khongthanhcong';
              ?>
              <span class="status-label <?= $statusClass ?>">
                <?= htmlspecialchars($status) ?>
              </span>
            </td>
            <td>
              <button
                class="btn btn-primary view-detail"
                data-ma="<?= htmlspecialchars($row['maVanDon']) ?>"
                data-product="<?= htmlspecialchars($row['tenSanPham'] ?? 'N/A') ?>"
                data-address="<?= htmlspecialchars(($row['diaChiNguoiNhan'] ?? '') . ', ' . ($row['phuong_xa'] ?? '') . ', ' . ($row['quan_huyen'] ?? '') . ', ' . ($row['tinh_tp'] ?? '')) ?>"
                data-date="<?= date('d/m/Y H:i', strtotime($row['ngayTaoDon'])) ?>"
                data-status="<?= htmlspecialchars($row['tenTrangThai'] ?? 'Không xác định') ?>"
                data-sender="<?= htmlspecialchars($row['tenNguoiGui'] ?? 'N/A') ?>"
                data-sender-phone="<?= htmlspecialchars($row['sdtNguoiGui'] ?? 'N/A') ?>"
                data-sender-address="<?= htmlspecialchars($row['diaChiNguoiGui'] ?? 'N/A') ?>"
                data-receiver="<?= htmlspecialchars($row['tenNguoiNhan'] ?? 'N/A') ?>"
                data-receiver-phone="<?= htmlspecialchars($row['soDienThoai'] ?? 'N/A') ?>"
                data-giatri="<?= number_format($row['giaTriHang'] ?? 0, 0, ',', '.') ?>"
                data-cod="<?= number_format($row['COD'] ?? 0, 0, ',', '.') ?>"
                data-tongphi="<?= number_format($row['tongPhi'] ?? 0, 0, ',', '.') ?>"
                data-phidichvu="<?= number_format($row['phiDichVu'] ?? 0, 0, ',', '.') ?>"
                data-bentraphi="<?= htmlspecialchars($row['benTraPhi'] ?? 'N/A') ?>">Xem chi tiết</button>
            </td>
          </tr>
        <?php
        endwhile;
      else:
        ?>
        <tr>
          <td colspan="6" style="text-align: center;">Không có đơn hàng nào</td>
        </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- Popup Modal -->
  <div id="detailModal" class="modal">
    <div class="modal-content">
      <span class="btn-close" id="modalClose">&times;</span>
      <div class="modal-header">
        <h3>Chi tiết đơn hàng</h3>
      </div>
      <div class="modal-body" id="modalBody"></div>
    </div>
  </div>

  <script>
    // Khởi tạo kết nối WebSocket đến server Node.js
    const socket = new WebSocket('ws://localhost:8081');

    socket.addEventListener('open', () => {
      console.log('WebSocket connected');
    });

    socket.addEventListener('error', (err) => {
      console.error('WebSocket error:', err);
    });

    // Gửi vị trí liên tục khi có thay đổi
    if (navigator.geolocation) {
      navigator.geolocation.watchPosition(
        pos => {
          const {
            latitude: lat,
            longitude: lng
          } = pos.coords;
          const payload = {
            staffId: <?= $staffId ?>,
            timestamp: new Date().toISOString(),
            lat,
            lng
          };
          if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify(payload));
          }
        },
        err => {
          console.error('Error getting location:', err.code, err.message);
        }, {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0
        }
      );
    } else {
      console.error('Geolocation không được hỗ trợ');
    }
    socket.addEventListener('close', () => {
      console.log('Socket closed, retry in 3s');
      setTimeout(initWebSocket, 3000);
    });


    // xử lý modal giao hàng
    const modal = document.getElementById('detailModal');
    const bodyDiv = document.getElementById('modalBody');
    const closeBtn = document.getElementById('modalClose');

    // Đóng modal
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', e => {
      if (e.target === modal) modal.style.display = 'none';
    });

    // Xem chi tiết
    document.querySelectorAll('.view-detail').forEach(btn => {
      btn.addEventListener('click', () => {
        const ma = btn.dataset.ma;
        const product = btn.dataset.product;
        const address = btn.dataset.address;
        const date = btn.dataset.date;
        const status = btn.dataset.status;
        const sender = btn.dataset.sender;
        const senderPhone = btn.dataset.senderPhone;
        const senderAddress = btn.dataset.senderAddress;
        const receiver = btn.dataset.receiver;
        const receiverPhone = btn.dataset.receiverPhone;
        const giaTri = btn.dataset.giatri;
        const cod = btn.dataset.cod;
        const tongPhi = btn.dataset.tongphi;
        const phiDichVu = btn.dataset.phidichvu;
        const benTraPhi = btn.dataset.bentraphi;

        let html = `
      <ul>
        <li><strong>Mã đơn:</strong> ${ma}</li>
        <li><strong>Loại hàng:</strong> ${product}</li>
        <li><strong>Ngày tạo:</strong> ${date}</li>
        <li><strong>Trạng thái:</strong> ${status}</li>
        <li><strong>Thông tin người gửi:</strong></li>
        <ul>
          <li>Tên: ${sender}</li>
          <li>Số điện thoại: ${senderPhone}</li>
          <li>Địa chỉ: ${senderAddress}</li>
        </ul>
        <li><strong>Thông tin người nhận:</strong></li>
        <ul>
          <li>Tên: ${receiver}</li>
          <li>Số điện thoại: ${receiverPhone}</li>
          <li>Địa chỉ: ${address}</li>
        </ul>
        <li><strong>Thông tin thanh toán:</strong></li>
        <ul>
          <li>Giá trị hàng: ${giaTri} VNĐ</li>
          <li>Phí COD: ${cod} VNĐ</li>
          <li>Phí dịch vụ: ${phiDichVu} VNĐ</li>
          <li>Tổng phí: ${tongPhi} VNĐ</li>
          <li>Bên trả phí: ${benTraPhi}</li>
        </ul>
      </ul>
    `;
        if (status === 'Đang xử lý') {
          html += `
        <form method="post" action="?maVanDon=${encodeURIComponent(ma)}">
          <button class="btn btn-success" type="submit" name="start_delivery">Bắt đầu giao hàng</button>
        </form>
      `;
        }
        bodyDiv.innerHTML = html;
        modal.style.display = 'block';
      });
    });
  </script>
  <?php require 'footer.php'; ?>
</body>

</html>