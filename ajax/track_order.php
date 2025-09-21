<?php
require_once '../config/db.php';
$order_id = $_GET['order_id'] ?? 0;

$stmt = $pdo->prepare("SELECT lat, lng FROM delivery WHERE order_id=? ORDER BY updated_at DESC LIMIT 1");
$stmt->execute([$order_id]);
$location = $stmt->fetch();

echo json_encode($location ?? ['lat'=>null,'lng'=>null]);
