<?php
session_start();
define('IS_VALID_ENTRY_POINT', true);
require_once '../../config/db.php';

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$like = '%' . $q . '%';
$sql = "
    SELECT DISTINCT r.restaurant_id, r.name, r.cuisine_type, r.logo, r.lat, r.lng, r.preparation_time
    FROM restaurants r
    LEFT JOIN menu_items m ON m.restaurant_id = r.restaurant_id
    WHERE r.status = 'active'
      AND (
        r.name LIKE :like
        OR r.cuisine_type LIKE :like
        OR m.name LIKE :like
      )
    ORDER BY r.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':like', $like, PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($results);
