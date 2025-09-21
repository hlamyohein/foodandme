<?php
// api/get_driver_location.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'missing']);
    exit;
}

try {
    $stmt = $pdo->prepare("
    SELECT o.order_id, o.order_status, o.delivery_address, dt.delivery_boy_id, dl.lat, dl.lng, u.name AS rider_name
    FROM orders o
    LEFT JOIN delivery_tracking dt ON dt.order_id = o.order_id
    LEFT JOIN driver_locations dl ON dl.order_id = o.order_id
    LEFT JOIN users u ON u.user_id = dt.delivery_boy_id
    WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $data = $stmt->fetch();
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'not_found']);
        exit;
    }

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
