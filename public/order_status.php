<?php
session_start();
require_once 'config/db.php';
$order_id = $_GET['id'] ?? 0;
?>
<h2>Track Order #<?= $order_id ?></h2>
<div id="map" style="height: 400px;"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
let map = L.map('map').setView([16.805, 96.18], 14); // default center
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let userMarker = L.marker([16.805, 96.18]).addTo(map).bindPopup("You");

let deliveryMarker = L.marker([16.805, 96.18]).addTo(map).bindPopup("Delivery");

// Poll delivery location every 5 seconds
setInterval(()=>{
    fetch('ajax/track_order.php?order_id=<?= $order_id ?>')
    .then(res => res.json())
    .then(data => {
        if(data.lat && data.lng){
            deliveryMarker.setLatLng([data.lat, data.lng]);
            map.panTo([data.lat, data.lng]);
        }
    });
}, 5000);
</script>
