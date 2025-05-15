<?php
// thongke.php

// 1. Include header (đảm bảo đã load Bootstrap & Bootstrap Icons)
include 'header.php';
?>

<?php
// 3. Kết nối database
$host     = 'localhost';
$db       = 'quanly_giaohang';
$user     = 'root';
$pass     = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo "<div class='alert alert-danger'>Kết nối DB thất bại: " . $e->getMessage() . "</div>";
    exit;
}

// 4. Các truy vấn thống kê
$totalOrders    = $pdo->query("SELECT COUNT(*) FROM donhang")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM khachhang")->fetchColumn();
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM nhanvien")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(tongTien),0) FROM doanhthu")->fetchColumn();

$newOrdersStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM donhang 
    WHERE ngayTaoDon >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$newOrdersStmt->execute();
$newOrders = $newOrdersStmt->fetchColumn();

$revenueDataStmt = $pdo->query("
    SELECT ngayTinh, SUM(tongTien) AS doanhThu 
    FROM doanhthu
    WHERE ngayTinh >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY ngayTinh
    ORDER BY ngayTinh ASC
");
$labels = [];
$data   = [];
while ($row = $revenueDataStmt->fetch()) {
    $labels[] = $row['ngayTinh'];
    $data[]   = $row['doanhThu'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="stylesheet" href="./css/dashboard.css">
</head>
<body>
  <div class="container-fluid py-4">
  <!-- Tiêu đề -->
  <div class="row" style="margin-bottom: 20px;">
    <div class="col">
      <h2>Dashboard</h2>
      <small class="text-muted">Tổng quan nhanh</small>
    </div>
  </div>

  <!-- Cards số liệu chính -->
  <div class="row dashboard-stats mb-4">
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-orders h-100">
        <div class="card-body">
          <i class="bi bi-receipt-cutoff"></i>
          <a href="list_orders.php">
            <h3><?= number_format($totalOrders) ?></h3>
            <small>Số đơn hàng</small>
          </a>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-customers h-100">
        <div class="card-body">
          <i class="bi bi-people"></i>
          <div>
            <h3><?= number_format($totalCustomers) ?></h3>
            <small>Khách hàng</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-employees h-100">
        <div class="card-body">
          <i class="bi bi-person-badge"></i>
          <div>
            <h3><?= number_format($totalEmployees) ?></h3>
            <small>Nhân viên</small>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-3">
      <div class="card text-white stat-revenue h-100">
        <div class="card-body">
          <i class="bi bi-currency-dollar"></i>
          <div>
            <h3><?= number_format($totalRevenue, 0) ?>₫</h3>
            <small>Tổng doanh thu</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card đơn mới 7 ngày -->
  <div class="row mb-5">
    <div class="col-md-4">
      <div class="card stat-new-orders h-100">
        <div class="card-body">
          <h5 class="card-title">Đơn mới (7 ngày)</h5>
          <p class="card-text"><?= number_format($newOrders) ?></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Biểu đồ doanh thu 30 ngày -->
  <div class="row">
    <div class="col">
      <div class="card chart-card">
        <div class="card-header">
          Doanh Thu Theo Ngày (30 ngày gần nhất)
        </div>
        <div class="card-body">
          <canvas id="revenueChart" height="100"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
// 5. Include footer và Chart.js
include 'footer.php';
?>
<!-- Nếu footer chưa include Chart.js thì thêm: -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('revenueChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [{
        label: 'Doanh thu',
        data: <?= json_encode($data, JSON_NUMERIC_CHECK) ?>,
        fill: false,
        tension: 0.4,
        borderWidth: 2
      }]
    },
    options: {
      scales: {
        x: { 
          display: true,
          title: { display: true, text: 'Ngày' }
        },
        y: {
          display: true,
          title: { display: true, text: 'Tổng tiền (₫)' }
        }
      },
      plugins: {
        legend: { display: false }
      }
    }
  });
</script>
</body>
</html>