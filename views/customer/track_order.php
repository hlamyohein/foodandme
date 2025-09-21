<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Track Order #<?= htmlspecialchars($order_id) ?></title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <style>
    body { font-family: sans-serif; margin: 0; padding: 12px; }
    #map{height:70vh; border-radius:8px;}
    #status { margin: 8px 0; font-weight: bold; }
  </style>
</head>
<body>
  <h2>Order #<?= htmlspecialchars($order_id) ?></h2>
  <div id="status">Loading...</div>
  <div id="map"></div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const orderId = <?= $order_id ?: 0 ?>;
    const map = L.map('map').setView([16.8409,96.1735], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let driverMarker = null;
    let timer = null;

    async function update() {
      try {
        const res = await fetch('../../api/get_driver_location.php?order_id=' + orderId);
        const j = await res.json();
        if (!j.success) { document.getElementById('status').textContent = 'No tracking data yet.'; return; }
        const d = j.data;
        document.getElementById('status').textContent = 'Status: ' + (d.order_status || 'pending') + (d.rider_name ? ' — Rider: ' + d.rider_name : '');
        if (d.lat && d.lng) {
          const lat = parseFloat(d.lat);
          const lng = parseFloat(d.lng);
          if (!driverMarker) {
            driverMarker = L.marker([lat, lng]).addTo(map).bindPopup('Rider');
            map.setView([lat, lng], 14);
          } else {
            driverMarker.setLatLng([lat, lng]);
            // pan smoothly
            map.panTo([lat, lng]);
          }
        }
        if (d.order_status === 'delivered') {
          document.getElementById('status').textContent += ' — Delivered. Tracking stopped.';
          if (timer) clearInterval(timer);
        }
      } catch (err) {
        console.error(err);
        document.getElementById('status').textContent = 'Network error while fetching tracking.';
      }
    }

    if (orderId > 0) {
      update();
      timer = setInterval(update, 5000);
    } else {
      document.getElementById('status').textContent = 'Invalid order id.';
    }
  </script>
</body>
</html>
