<?php
session_start();
require '../config/conn.php';
$mysqli = require '../config/conn.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}
$userId = $_SESSION['user_id'];

// 1) Đánh dấu đã đọc
$sqlUpd = "UPDATE thongbao 
           SET trangThai = 'Đã đọc' 
           WHERE Id_NhanVien = ? 
             AND trangThai = 'Chưa đọc'";
$stmt = $mysqli->prepare($sqlUpd);
$stmt->bind_param("i", $userId);
$stmt->execute();

// 2) Lấy danh sách thông báo (chưa đọc lên đầu, mới nhất lên đầu)
$sql = "SELECT Id_ThongBao, noiDung, ngayTao, trangThai 
        FROM thongbao 
        WHERE Id_NhanVien = ? 
        ORDER BY 
          CASE WHEN trangThai = 'Chưa đọc' THEN 0 ELSE 1 END,
          ngayTao DESC
        LIMIT 20";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$notifs = [];
while($r = $res->fetch_assoc()) {
    // Format lại ngày để dễ đọc
    $r['ngayTao'] = date('d/m/Y H:i', strtotime($r['ngayTao']));
    $notifs[] = $r;
}

header('Content-Type: application/json');
echo json_encode($notifs);
exit;
