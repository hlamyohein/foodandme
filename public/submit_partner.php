<?php
// submit_partner.php
// Ensure you have your database connection file
include "includes/db_connection.php"; // Adjust this to your connection file

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$role = $_POST['role'] ?? '';
// A default password for new users. In a real app, you'd send a password reset link.
$defaultPassword = password_hash("Welcome123!", PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    if ($role === 'vendor') {
        // --- VENDOR REGISTRATION for your schema ---
        // 1. Create a user with the 'vendor' role
        $userStmt = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'vendor')"
        );
        $userStmt->execute([
            $_POST['owner_name'],
            $_POST['email'],
            $_POST['phone'],
            $defaultPassword
        ]);
        $newUserId = $pdo->lastInsertId();

        // 2. Create a restaurant linked to the new user
        $restoStmt = $pdo->prepare(
            "INSERT INTO restaurants (user_id, name, address, phone) VALUES (?, ?, ?, ?)"
        );
        $restoStmt->execute([
            $newUserId,
            $_POST['restaurant_name'],
            $_POST['address'],
            $_POST['phone']
        ]);
        $newRestaurantId = $pdo->lastInsertId();

        // 3. Create notification for admin using your notifications table structure
        $title = "New Vendor Application";
        $message = htmlspecialchars($_POST['restaurant_name']) . " has applied to be a partner.";
        $notiStmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, restaurant_id, title, message) VALUES (?, ?, ?, ?)"
        );
        // This notification is associated with the new vendor's user_id and restaurant_id
        $notiStmt->execute([$newUserId, $newRestaurantId, $title, $message]);

    } elseif ($role === 'rider') {
        // --- RIDER REGISTRATION for your schema ---
        // 1. Create a user with the 'delivery' role
        $userStmt = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'delivery')"
        );
        $userStmt->execute([
            $_POST['rider_name'],
            $_POST['rider_email'],
            $_POST['rider_phone'],
            $defaultPassword
        ]);
        $newUserId = $pdo->lastInsertId();

        // 2. Create notification for admin
        $title = "New Rider Application";
        $message = "A new rider, " . htmlspecialchars($_POST['rider_name']) . ", has applied.";
        $notiStmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)"
        );
        $notiStmt->execute([$newUserId, $title, $message]);

    } else {
        throw new Exception('Invalid partner role specified.');
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Application submitted successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    // In a real app, you would log this error instead of echoing it
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

?>

