<?php
// user_crud.php
require_once __DIR__ . "../../config/db.php";
header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        // === Add User ===
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['username'],
                $_POST['email'],
                password_hash($_POST['password'], PASSWORD_BCRYPT),
                $_POST['role'] ?? 'customer'
            ]);
            $id = $pdo->lastInsertId();
            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $id,
                    "username" => $_POST['username'],
                    "email" => $_POST['email'],
                    "role" => $_POST['role'] ?? 'customer',
                    "created_at" => date("Y-m-d")
                ]
            ]);
            exit;
        }

        // === Update User ===
        if ($action === 'update') {
            if (!empty($_POST['password'])) {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, password=?, role=? WHERE user_id=?");
                $stmt->execute([
                    $_POST['username'],
                    $_POST['email'],
                    password_hash($_POST['password'], PASSWORD_BCRYPT),
                    $_POST['role'] ?? 'customer',
                    $_POST['user_id']
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE user_id=?");
                $stmt->execute([
                    $_POST['username'],
                    $_POST['email'],
                    $_POST['role'] ?? 'customer',
                    $_POST['user_id']
                ]);
            }
            echo json_encode([
                "success" => true,
                "user" => [
                    "id" => $_POST['user_id'],
                    "username" => $_POST['username'],
                    "email" => $_POST['email'],
                    "role" => $_POST['role'] ?? 'customer'
                ]
            ]);
            exit;
        }

        // === Delete User ===
        if ($action === 'delete') {
            $id = $_POST['user_id'];
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
            echo json_encode(["success" => true, "id" => $id]);
            exit;
        }
    }

    echo json_encode(["success" => false, "message" => "Invalid request"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
