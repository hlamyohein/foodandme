<?php
session_start();
require_once "../../config/db.php";

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    if ($action === "edit_restaurant") {
        $id     = $_POST['restaurant_id'];
        $name   = $_POST['name'];
        $addr   = $_POST['address'];
        $phone  = $_POST['phone'];
        $cuisine= $_POST['cuisine_type'];
        $status = $_POST['status'];

        $stmt = $pdo->prepare("UPDATE restaurants 
                               SET name=?, address=?, phone=?, cuisine_type=?, status=? 
                               WHERE restaurant_id=?");
        $stmt->execute([$name, $addr, $phone, $cuisine, $status, $id]);

        echo json_encode(["success"=>true]);
        exit;
    }

    if ($action === "delete") { // deactivate
        $id = $_POST['restaurant_id'];
        $stmt = $pdo->prepare("UPDATE restaurants SET status='inactive' WHERE restaurant_id=?");
        $stmt->execute([$id]);
        echo json_encode(["success"=>true]);
        exit;
    }

    if ($action === "activate") {
        $id = $_POST['restaurant_id'];
        $stmt = $pdo->prepare("UPDATE restaurants SET status='active' WHERE restaurant_id=?");
        $stmt->execute([$id]);
        echo json_encode(["success"=>true]);
        exit;
    }

    echo json_encode(["success"=>false,"message"=>"Invalid action"]);

} catch (Exception $e) {
    echo json_encode(["success"=>false,"message"=>$e->getMessage()]);
}
