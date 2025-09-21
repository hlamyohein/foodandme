<?php
// api/accept_order.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery') {
    echo json_encode(['success' => false, 'error' => 'not_allowed']);
    exit;
}

$delivery_boy_id = (int)$_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'missing_order']);
    exit;
}

try {
    // Use transaction to avoid race conditions
    $pdo->beginTransaction();

    // Check if already assigned
    $check = $pdo->prepare("SELECT delivery_id FROM delivery WHERE order_id = ? FOR UPDATE");
    $check->execute([$order_id]);
    $existing = $check->fetch();
    if ($existing) {
        $pdo->commit();
        echo json_encode(['success' => false, 'error' => 'already_assigned']);
        exit;
    }

    // assign delivery
    $ins = $pdo->prepare("INSERT INTO delivery (order_id, delivery_boy_id, status) VALUES (?, ?, 'assigned')");
    $ins->execute([$order_id, $delivery_boy_id]);

    // update delivery_tracking row (create if missing)
    $chkTrack = $pdo->prepare("SELECT order_id FROM delivery_tracking WHERE order_id = ?");
    $chkTrack->execute([$order_id]);
    if (!$chkTrack->fetch()) {
        $pdo->prepare("INSERT INTO delivery_tracking (order_id, delivery_boy_id, status) VALUES (?, ?, 'on_the_way')")->execute([$order_id, $delivery_boy_id]);
    } else {
        $pdo->prepare("UPDATE delivery_tracking SET delivery_boy_id = ?, status = 'on_the_way' WHERE order_id = ?")->execute([$delivery_boy_id, $order_id]);
    }

    // update order status to preparing (restaurant may mark ready)
    $pdo->prepare("UPDATE orders SET order_status = 'preparing' WHERE order_id = ?")->execute([$order_id]);

    // notify customer (optional)
    $custStmt = $pdo->prepare("SELECT user_id FROM orders WHERE order_id = ?");
    $custStmt->execute([$order_id]);
    $custId = $custStmt->fetchColumn();
    if ($custId) {
        $insNot = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $insNot->execute([$custId, "Order #$order_id accepted", "A rider has accepted your order."]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'detail' => $e->getMessage()]);
}
