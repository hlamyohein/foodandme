<?php
// api/get_unassigned_orders.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php'; // uses your config/db.php that defines $pdo

try {
    // Orders that are pending/preparing and not present in delivery table
    $sql = "
    SELECT o.order_id, o.delivery_address, o.total_amount, o.lat, o.lng, o.created_at, r.name AS restaurant_name
    FROM orders o
    LEFT JOIN delivery d ON d.order_id = o.order_id
    JOIN restaurants r ON r.restaurant_id = o.restaurant_id
    WHERE (o.order_status IN ('pending','preparing') OR o.order_status = '' OR o.order_status IS NULL)
      AND (d.delivery_id IS NULL)
    ORDER BY o.created_at DESC
    LIMIT 50
    ";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll();
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
