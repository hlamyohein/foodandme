<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

$notificationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$adminUserId = 4; // Your admin user ID

if ($notificationId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
    exit;
}

$pdo->beginTransaction();

try {
    // Step 1: Get the notification message
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $adminUserId]);
    $notification = $stmt->fetch();

    if (!$notification) {
        throw new Exception("Notification not found.");
    }

    // Step 2: Mark it as read
    $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $updateStmt->execute([$notificationId]);
    
    $pdo->commit();

    // Step 3: Fetch the partner's details
    $messageData = json_decode($notification['message'], true);
    $newUserId = $messageData['new_user_id'];

    $detailsHTML = '';
    // FIXED: Initialize the variable here, so it always exists.
    $userIdForAction = null; 
    
    // Fetch user details
    $userStmt = $pdo->prepare("SELECT name, email, phone, address, role, created_at FROM users WHERE user_id = ?");
    $userStmt->execute([$newUserId]);
    $user = $userStmt->fetch();

    if ($user) {
        // FIXED: Assign the user ID here, making it available for both roles.
        $userIdForAction = $newUserId;

        if ($user['role'] === 'vendor') {
            // Fetch restaurant details
            $restoStmt = $pdo->prepare("SELECT name, address, cuisine_type FROM restaurants WHERE user_id = ?");
            $restoStmt->execute([$newUserId]);
            $restaurant = $restoStmt->fetch();

            $detailsHTML = "
                <h3>Vendor Application Details</h3>
                <p><strong>Applicant Name:</strong> {$user['name']}</p>
                <p><strong>Applicant Email:</strong> {$user['email']}</p>
                <p><strong>Applicant Phone:</strong> {$user['phone']}</p>
                <hr>
                <p><strong>Restaurant Name:</strong> {$restaurant['name']}</p>
                <p><strong>Restaurant Address:</strong> {$restaurant['address']}</p>
                <p><strong>Cuisine Type:</strong> {$restaurant['cuisine_type']}</p>
                <hr>
                <p><em>Submitted On: " . date('M j, Y, g:i a', strtotime($user['created_at'])) . "</em></p>
            ";
        } else { // It's a rider
             $detailsHTML = "
                <h3>Rider Application Details</h3>
                <p><strong>Applicant Name:</strong> {$user['name']}</p>
                <p><strong>Applicant Email:</strong> {$user['email']}</p>
                <p><strong>Applicant Phone:</strong> {$user['phone']}</p>
                <p><strong>Address:</strong> {$user['address']}</p>
                <hr>
                <p><em>Submitted On: " . date('M j, Y, g:i a', strtotime($user['created_at'])) . "</em></p>
            ";
        }
    }

    echo json_encode([
        'success' => true, 
        'title' => $notification['title'], 
        'details_html' => $detailsHTML,
        'userId' => $userIdForAction // This will now work for both roles
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}