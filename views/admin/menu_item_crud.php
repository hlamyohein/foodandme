<?php
// menu_item_crud.php
require_once __DIR__ . "../../config/db.php";
header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        // === Add Item ===
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO menu_items 
                (restaurant_id, name, description, price, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['restaurant_id'],
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['price'],
                $_POST['status'] ?? 'available'
            ]);

            $id = $pdo->lastInsertId();
            $res = $pdo->prepare("SELECT name FROM restaurants WHERE restaurant_id = ?");
            $res->execute([$_POST['restaurant_id']]);
            $restaurant_name = $res->fetchColumn();

            echo json_encode([
                "success" => true,
                "menu_item" => [
                    "id" => $id,
                    "restaurant_id" => $_POST['restaurant_id'],
                    "restaurant_name" => $restaurant_name,
                    "name" => $_POST['name'],
                    "description" => $_POST['description'] ?? '',
                    "price" => $_POST['price'],
                    "status" => $_POST['status'] ?? 'available',
                    "created_at" => date("Y-m-d")
                ]
            ]);
            exit;
        }

        // === Update Item ===
        if ($action === 'update') {
            $stmt = $pdo->prepare("UPDATE menu_items 
                SET restaurant_id=?, name=?, description=?, price=?, status=? 
                WHERE menu_item_id=?");
            $stmt->execute([
                $_POST['restaurant_id'],
                $_POST['name'],
                $_POST['description'] ?? '',
                $_POST['price'],
                $_POST['status'] ?? 'available',
                $_POST['menu_item_id']
            ]);

            $res = $pdo->prepare("SELECT name FROM restaurants WHERE restaurant_id = ?");
            $res->execute([$_POST['restaurant_id']]);
            $restaurant_name = $res->fetchColumn();

            echo json_encode([
                "success" => true,
                "menu_item" => [
                    "id" => $_POST['menu_item_id'],
                    "restaurant_id" => $_POST['restaurant_id'],
                    "restaurant_name" => $restaurant_name,
                    "name" => $_POST['name'],
                    "description" => $_POST['description'] ?? '',
                    "price" => $_POST['price'],
                    "status" => $_POST['status'] ?? 'available'
                ]
            ]);
            exit;
        }

        // === Delete Item ===
        if ($action === 'delete') {
            $id = $_POST['menu_item_id'];
            $pdo->prepare("DELETE FROM menu_items WHERE menu_item_id = ?")->execute([$id]);
            echo json_encode(["success" => true, "id" => $id]);
            exit;
        }
    }

    echo json_encode(["success" => false, "message" => "Invalid request"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
