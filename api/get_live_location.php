<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

try {
    // *** FIX: Simplified and more robust query ***
    // We get all necessary info in one go.
    $stmt = $pdo->prepare("
        SELECT 
            dt.lat, dt.lng, dt.status,
            o.lat as customer_lat, o.lng as customer_lng,
            r.name as restaurant_name, r.lat as restaurant_lat, r.lng as restaurant_lng
        FROM orders o
        LEFT JOIN delivery_tracking dt ON o.order_id = dt.order_id
        LEFT JOIN restaurants r ON o.restaurant_id = r.restaurant_id
        WHERE o.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $responseData = [
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'status' => $row['status'] ?? 'pending',
            'customer' => ['lat' => $row['customer_lat'], 'lng' => $row['customer_lng']],
            'restaurant' => ['name' => $row['restaurant_name'], 'lat' => $row['restaurant_lat'], 'lng' => $row['restaurant_lng']]
        ];
        echo json_encode(['success' => true, 'data' => $responseData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
