<?php
// api/update_order_status.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'error'=>'not_allowed']); exit; }
$allowedRoles = ['vendor','delivery','admin'];
if (!in_array($_SESSION['role'], $allowedRoles)) { echo json_encode(['success'=>false,'error'=>'not_allowed']); exit; }

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

$valid = ['preparing','ready','on_the_way','delivered','canceled'];
if (!$order_id || !in_array($status, $valid)) { echo json_encode(['success'=>false,'error'=>'bad_request']); exit; }

try {
    $pdo->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?")->execute([$status, $order_id]);

    // If delivered -> cleanup tracking rows
    if ($status === 'delivered') {
        $pdo->prepare("DELETE FROM driver_locations WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("UPDATE delivery_tracking SET status = 'delivered', lat = NULL, lng = NULL WHERE order_id = ?")->execute([$order_id]);
        $pdo->prepare("UPDATE delivery SET live_location = NULL WHERE order_id = ?")->execute([$order_id]);
    }

    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'server_error']);
}
