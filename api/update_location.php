<?php
// api/update_location.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery') {
    echo json_encode(['success' => false, 'error' => 'not_allowed']);
    exit;
}
$delivery_boy_id = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$lat = isset($input['lat']) ? (float)$input['lat'] : null;
$lng = isset($input['lng']) ? (float)$input['lng'] : null;

if (!$order_id || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'error' => 'missing']);
    exit;
}

try {
    // verify this order is assigned to this rider
    $chk = $pdo->prepare("SELECT delivery_id FROM delivery WHERE order_id = ? AND delivery_boy_id = ?");
    $chk->execute([$order_id, $delivery_boy_id]);
    if (!$chk->fetch()) {
        echo json_encode(['success' => false, 'error' => 'not_assigned']);
        exit;
    }

    // upsert driver_locations (PRIMARY KEY order_id) using INSERT ... ON DUPLICATE KEY
    $up = $pdo->prepare("INSERT INTO driver_locations (order_id, lat, lng) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE lat = VALUES(lat), lng = VALUES(lng), updated_at = CURRENT_TIMESTAMP()");
    $up->execute([$order_id, $lat, $lng]);

    // update delivery_tracking
    $up2 = $pdo->prepare("UPDATE delivery_tracking SET lat = ?, lng = ?, delivery_boy_id = ?, status = 'on_the_way' WHERE order_id = ?");
    $up2->execute([$lat, $lng, $delivery_boy_id, $order_id]);

    // update delivery.live_location (optional)
    $pdo->prepare("UPDATE delivery SET live_location = ? WHERE order_id = ?")->execute([$lat . ',' . $lng, $order_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'detail' => $e->getMessage()]);
}
