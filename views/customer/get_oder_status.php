<?php
session_start();
//require_once(__DIR__ . '/../restaurant/confignew.php');
require_once '../../config/db.php';
header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT order_status, cancellation_reason FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order_info) {
        echo json_encode(['success' => true, 'status' => $order_info['order_status'], 'cancellation_reason' => $order_info['cancellation_reason']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
