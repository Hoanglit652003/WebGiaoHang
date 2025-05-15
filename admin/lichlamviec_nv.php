<?php
// lichlamviec_nv.php
if (session_status() === PHP_SESSION_NONE) session_start();
require '../config/conn.php';
require 'header1.php';

// Kiểm tra đăng nhập và quyền truy cập
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 2) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Lấy lịch làm việc của nhân viên
$stmt = $mysqli->prepare("
    SELECT * FROM LichLamViec 
    WHERE Id_NhanVien = ? 
    ORDER BY ngayLamViec DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch làm việc của tôi</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/lichlamviec_nv.css">
    <link rel="stylesheet" href="./css/footer.css">
</head>
<body>
    <div class="container" style="min-height: 70vh;">
        <h2>Lịch làm việc của tôi</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Thời gian</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="date"><?= date('d/m/Y', strtotime($row['ngayLamViec'])) ?></td>
                            <td class="time"><?= $row['thoiGianBatDau'] ?> - <?= $row['thoiGianKetThuc'] ?></td>
                            <td class="note"><?= htmlspecialchars($row['ghiChu']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-schedule">
                <p>Bạn chưa có lịch làm việc nào.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php require 'footer.php'; ?> 