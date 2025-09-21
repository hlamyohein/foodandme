<?php
// /foodandme/api/submit_partner_request.php

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php'; // Adjust path to your DB connection

// --- Basic Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$required_fields = ['role', 'name', 'email', 'phone', 'password'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit;
    }
}

// --- Data Collection ---
$role = $_POST['role'];
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);

// This is the admin's user_id who will receive the notification
$adminUserId = 4; // Assuming admin user_id is 4

$pdo->beginTransaction();

try {
    // --- Step 1: Create the User entry ---
    // For all partners, is_email_verified and status start at 0 (unverified and pending)
    $stmt = $pdo->prepare(
        "INSERT INTO users (name, email, phone, password, role,  is_verified) 
         VALUES (?, ?, ?, ?, ?,  0)"
    );
    $stmt->execute([$name, $email, $phone, $password, $role]);
    $newUserId = $pdo->lastInsertId();

    $notificationTitle = '';
    
    // --- Step 2: Handle Role-Specific Logic ---
    if ($role === 'vendor') {
        $restaurantName = trim($_POST['restaurant_name']);
        $restaurantAddress = trim($_POST['address']);
        $cuisineType = trim($_POST['cuisine_type']);

        $restoStmt = $pdo->prepare(
            "INSERT INTO restaurants (user_id, name, address, cuisine_type, status) 
             VALUES (?, ?, ?, ?, 'inactive')"
        );
        $restoStmt->execute([$newUserId, $restaurantName, $restaurantAddress, $cuisineType]);
        $notificationTitle = 'New Vendor Request';

    } elseif ($role === 'delivery') {
        $riderAddress = trim($_POST['rider_address']);
        
        // Update the user's main address field
        $addrStmt = $pdo->prepare("UPDATE users SET address = ? WHERE user_id = ?");
        $addrStmt->execute([$riderAddress, $newUserId]);
        $notificationTitle = 'New Rider Request';
        
    } else {
        throw new Exception('Invalid role specified.');
    }

    // --- Step 3: Create Notification for Admin ---
    // Storing the new user ID in the message makes it easy for the admin panel to fetch details
    $notificationMessage = json_encode(['new_user_id' => $newUserId]);

    $notiStmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)"
    );
    $notiStmt->execute([$adminUserId, $notificationTitle, $notificationMessage]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully! Please check your email to verify your account.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    // Check for duplicate email error (error code 1062)
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(['success' => false, 'message' => 'This email is already registered.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>