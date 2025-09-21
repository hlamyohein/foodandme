<?php
header('Content-Type: application/json');
require 'config/db.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if($order_id <= 0){
    echo json_encode(['status' => 'invalid']);
    exit;
}

$stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id=?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

$status = $order['order_status'] ?? 'pending';

echo json_encode(['status' => $status]);
?>
