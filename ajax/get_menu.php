<?php
require_once '../config/db.php';

$restaurant_id = $_GET['restaurant_id'] ?? 0;

if(!$restaurant_id){
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT item_id, name, description, price FROM menu_items WHERE restaurant_id=? AND is_available=1");
$stmt->execute([$restaurant_id]);
$menu_items = $stmt->fetchAll();

echo json_encode($menu_items);
