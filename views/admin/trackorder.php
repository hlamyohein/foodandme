<?php
session_start();
//require __DIR__ . '../../config/db.php';
require_once '../../config/db.php';
if (!isset($_GET['order_id'])) {
    die("Order ID missing.");
}

$order_id = $_GET['order_id'];

// Fetch the latest location for this order
$stmt = $pdo->prepare("
    SELECT lat, lng, last_update 
    FROM delivery_tracking 
    WHERE order_id = ? 
    ORDER BY last_update DESC 
    LIMIT 1
");
$stmt->execute([$order_id]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$location) {
    $lat = 0;
    $lng = 0;
    $last_update = "No location available";
} else {
    $lat = $location['lat'];
    $lng = $location['lng'];
    $last_update = $location['last_update'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order #<?= htmlspecialchars($order_id) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #fdf6f0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            margin: 0;
            padding: 40px;
        }
        .card {
            background: #fff;
            max-width: 90%;
            width: 1200px;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            padding: 30px;
            animation: fadeIn 0.5s ease-in-out;
            text-align: center;
        }
        h2 {
            color: #e67e22;
            margin-top: 0;
            font-size: 1.8rem;
        }
        #last-update {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }
        #map {
            height: 600px;
            width: 100%;
            border-radius: 14px;
            box-shadow: 0 2px 14px rgba(0,0,0,0.08);
            border: 2px solid #f5d6b0;
            margin-bottom: 20px;
        }
        .btn-return {
            display: inline-block;
            background: #e67e22;
            color: white;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            transition: background 0.3s ease;
        }
        .btn-return:hover {
            background: #cf711f;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>🚚 Live Location for Order #<?= htmlspecialchars($order_id) ?></h2>
        <p id="last-update">Last Update: <?= htmlspecialchars($last_update) ?></p>
        <div id="map"></div>

        <!-- Return Button -->
        <a href="deliveries.php" class="btn-return">⬅ Return to Deliveries</a>
    </div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        let map = L.map('map').setView([<?= $lat ?>, <?= $lng ?>], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([<?= $lat ?>, <?= $lng ?>]).addTo(map)
            .bindPopup("Delivery Location").openPopup();

        setInterval(() => {
            fetch('get_location.php?order_id=<?= $order_id ?>')
            .then(response => response.json())
            .then(data => {
                if (data.lat && data.lng) {
                    marker.setLatLng([parseFloat(data.lat), parseFloat(data.lng)]);
                    map.setView([parseFloat(data.lat), parseFloat(data.lng)]);
                    document.getElementById('last-update').innerText = "Last Update: " + data.last_update;
                }
            });
        }, 10000);
    </script>
</body>
</html>
