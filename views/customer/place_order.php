<?php
session_start();
// Make sure this path is correct based on your file structure
require_once '../../config/db.php';
// CORRECTED PATH to find config.php in the restaurant folder
//require_once(__DIR__ . '../../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$cart = $_SESSION['cart'] ?? null;
if (!$cart || empty($cart['items'])) {
    die("Your cart is empty.");
}

$user_id = (int)$_SESSION['user_id'];
$restaurant_id = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;
$delivery_address = trim($_POST['delivery_address'] ?? '');
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$payment_method = $_POST['payment_method'] ?? 'cod';

if (!$restaurant_id || !$delivery_address || $lat === null || $lng === null) {
    die("Invalid order data.");
}

// Fetch restaurant lat/lng from DB
$stmt = $pdo->prepare("SELECT lat, lng FROM restaurants WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$rest = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$rest) {
    die("Invalid restaurant selected.");
}
$restaurant_lat = $rest['lat'] !== null ? (float)$rest['lat'] : null;
$restaurant_lng = $rest['lng'] !== null ? (float)$rest['lng'] : null;

// Calculate subtotal and total
$subtotal = 0;
foreach ($cart['items'] as $it) {
    $subtotal += $it['qty'] * $it['price'];
}

function calculate_delivery_fee($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return 0;
    }
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $km = $miles * 1.609344;
    return round($km * 0.5); // 50 cents per km
}

$delivery_fee = calculate_delivery_fee($restaurant_lat, $restaurant_lng, $lat, $lng);
$total_amount = $subtotal + $delivery_fee;

// Payment / order_status values compatible with your schema
$payment_status = 'unpaid';
$order_status = 'pending';

// Insert order
$stmt = $pdo->prepare("INSERT INTO orders (user_id, restaurant_id, delivery_address, total_amount, payment_status, order_status, lat, lng, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$user_id, $restaurant_id, $delivery_address, $total_amount, $payment_status, $order_status, $lat, $lng, 0]);
$order_id = $pdo->lastInsertId();
// Insert order items
$itemInsertStmt = $pdo->prepare("INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cart['items'] as $it) {
    $item_id = (int)$it['id'];
    $qty = (int)$it['qty'];
    $price = $it['price'];
    $itemInsertStmt->execute([$order_id, $item_id, $qty, $price]);
}

// Create a notification for the new order
// Get the user's name
$user_stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);
$customer_name = $user['name'] ?? 'Unknown Customer';

// Call the notification function
$notification_title = "New Order Received!";
$notification_desc = "A new order (#ORD-{$order_id}) has been placed by {$customer_name}.";


// Clear cart
unset($_SESSION['cart']);

// Redirect to success page
header("Location: order_success.php?order_id=" . $order_id);
exit;