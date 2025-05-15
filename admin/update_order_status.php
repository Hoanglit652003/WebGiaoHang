<?php
require '../config/conn.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if required data is present
if (!isset($_POST['orderId']) || !isset($_POST['orderStatus'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$orderId = $_POST['orderId'];
$newStatus = $_POST['orderStatus'];

// Validate status
if (!in_array($newStatus, ['1', '2', '3', '4', '5'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Update order status
    $stmt = $mysqli->prepare("UPDATE DonHang SET id_trangThai = ? WHERE maVanDon = ?");
    $stmt->bind_param('is', $newStatus, $orderId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$mysqli->close();
?> 