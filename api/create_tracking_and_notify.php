<?php
// api/create_tracking_and_notify.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'missing_order']);
    exit;
}

try {
    // Create delivery_tracking row if not exists
    $stmt = $pdo->prepare("SELECT order_id FROM delivery_tracking WHERE order_id = ?");
    $stmt->execute([$order_id]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO delivery_tracking (order_id, status) VALUES (?, 'pending')")->execute([$order_id]);
    }

    // Insert notification for all delivery users (optional). If you want 1-notif-per-rider.
    $users = $pdo->query("SELECT user_id FROM users WHERE role = 'delivery'")->fetchAll();
    $insNot = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    foreach ($users as $u) {
        $insNot->execute([$u['user_id'], "New Order #$order_id", "A new order is available (#$order_id). Please accept if you can deliver."]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'detail' => $e->getMessage()]);
}
