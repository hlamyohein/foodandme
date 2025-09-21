<?php
// api/get_assigned_deliveries.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery') {
    echo json_encode(['success' => false, 'error' => 'not_allowed']);
    exit;
}
$uid = (int)$_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
    SELECT o.order_id, o.delivery_address, o.total_amount, o.order_status, o.lat, o.lng, r.name AS restaurant_name
    FROM delivery d
    JOIN orders o ON o.order_id = d.order_id
    JOIN restaurants r ON r.restaurant_id = o.restaurant_id
    WHERE d.delivery_boy_id = ? AND o.order_status IN ('preparing','on_the_way','ready')
    ORDER BY o.created_at DESC
    ");
    $stmt->execute([$uid]);
    $deliveries = $stmt->fetchAll();
    echo json_encode(['success' => true, 'deliveries' => $deliveries]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error']);
}
