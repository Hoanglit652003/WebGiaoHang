<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$mysqli = require '../config/conn.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Lấy số thông báo chưa đọc
$userId = $_SESSION['user_id'];
$sqlCount = "SELECT COUNT(*) AS cnt FROM thongbao 
             WHERE id_NhanVien = $userId AND trangThai = 'Chưa đọc'";
$resCount = $mysqli->query($sqlCount);
$rowCount = $resCount->fetch_assoc();
$unreadCount = (int)$rowCount['cnt'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý giao hàng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/header1.css">
  
</head>
<body>
  <!-- Topbar -->
  <header class="topbar">
    <div class="logo">
      <i class="bi bi-truck"></i>
      <span>NHÂN VIÊN GIAO HÀNG</span>
    </div>
    <div class="actions">
      <?php if ($_SESSION['role'] === 2): ?>
        <div class="notification" id="notif">
          <i class="bi bi-bell bell-icon"></i>
          <?php if($unreadCount > 0): ?>
            <span class="count"><?php echo $unreadCount; ?></span>
          <?php endif; ?>
          <div id="notif-dropdown" class="dropdown-content"></div>
        </div>
      <?php endif; ?>
      <a href="logout.php" class="logout">
        <i class="bi bi-box-arrow-right"></i>
        <span>Đăng xuất</span>
      </a>
    </div>
  </header>

  <!-- Sidebar -->
  <aside class="sidebar">
    <?php if ($_SESSION['role'] === 1): ?>
      
    <?php else: ?>
      <a href="my_orders.php">
        <i class="bi bi-box"></i>
        <span>Đơn hàng của tôi</span>
      </a>
      <a href="donhang_danggiao.php">
        <i class="bi bi-truck"></i>
        <span>Đơn hàng đang giao</span>
      </a>
      <a href="lichlamviec_nv.php">
        <i class="bi bi-calendar3"></i>
        <span>Xem lịch làm việc</span>
      </a>
    <?php endif; ?>
  </aside>

  <!-- Main Content -->
  <main class="main-content">

  <script>
  document.getElementById('notif').addEventListener('click', function(e) {
    e.stopPropagation();
    const dd = document.getElementById('notif-dropdown');
    if (dd.style.display === 'block') {
      dd.style.display = 'none';
      return;
    }
    fetch('notifications.php')
      .then(res => res.json())
      .then(data => {
        dd.innerHTML = '';
        if (data.length === 0) {
          dd.innerHTML = '<div class="item">Không có thông báo mới</div>';
        } else {
          data.forEach(n => {
            const isUnread = n.trangThai === 'Chưa đọc';
            dd.innerHTML += `<div class="item ${isUnread ? 'unread' : ''}">
                              <p>${n.noiDung}</p>
                              <small>${n.ngayTao}</small>
                            </div>`;
          });
        }
        dd.style.display = 'block';
        const cnt = document.querySelector('.notification .count');
        if (cnt) cnt.remove();
      })
      .catch(console.error);
  });
  window.addEventListener('click', function(e) {
    const dd = document.getElementById('notif-dropdown');
    if (dd.style.display === 'block' && !dd.contains(e.target)) {
      dd.style.display = 'none';
    }
  });
</script>
