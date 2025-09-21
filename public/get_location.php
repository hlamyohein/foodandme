<?php
require 'config/db.php'; 
header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT lat, lng FROM driver_locations WHERE order_id = :order_id LIMIT 1");
    $stmt->execute([':order_id' => $order_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            'success' => true,
            'lat' => (float)$row['lat'],
            'lng' => (float)$row['lng']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Driver not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
