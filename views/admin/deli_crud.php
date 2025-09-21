<?php
header('Content-Type: application/json');
// The path should be relative to the deli_crud.php file's location
require_once __DIR__ . '/../../config/db.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the posted data
$action = isset($_POST['action']) ? $_POST['action'] : '';
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId === 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

try {
    if ($action === 'approve') {
        // Set the user's status to 1 (approved)
        $stmt = $pdo->prepare("UPDATE users SET status = 1 WHERE user_id = ? AND role = 'delivery'");
        $stmt->execute([$userId]);
        
        if ($stmt->rowCount() > 0) {
            // Optional: Send a success notification back to the rider
            echo json_encode(['success' => true, 'message' => 'Rider has been approved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not find the rider or they are already approved.']);
        }

    } elseif ($action === 'reject') {
        // Delete the user record
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'delivery'");
        $stmt->execute([$userId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Rider has been rejected and removed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not find the rider to reject.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}