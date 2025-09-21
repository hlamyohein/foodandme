<?php
session_start();
require_once '../config/db.php';

$customerLat = $_SESSION['lat'] ?? 16.805;
$customerLng = $_SESSION['lng'] ?? 96.18;
$maxDistance = 5; // km

$sql = "
SELECT restaurant_id, name, address, lat, lng, cuisine_type,
    (6371 * acos(
        cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) +
        sin(radians(?)) * sin(radians(lat))
    )) AS distance
FROM restaurants
WHERE status='active'
HAVING distance <= ?
ORDER BY distance ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customerLat, $customerLng, $customerLat, $maxDistance]);
$restaurants = $stmt->fetchAll();

echo json_encode($restaurants);
