<?php
header('Content-Type: application/json');
session_start();
include '../config/db.php';

$adminUserId = 4; 

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$countStmt->execute([$adminUserId]);
$unreadCount = $countStmt->fetchColumn();

$notifStmt = $pdo->prepare("SELECT notification_id, title, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$adminUserId]);
$notifications = $notifStmt->fetchAll();

echo json_encode([
    'count' => $unreadCount,
    'notifications' => $notifications
]);