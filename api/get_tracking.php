<?php
session_start();
// In a real app, you'd verify the user is allowed to see this order
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id) {
    die("Order ID is required.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?= $order_id ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; background-color: #f8f9fa; }
        .container { max-width: 900px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        #map { height: 500px; border-radius: 8px; }
        .status-bar { padding: 15px; background: #e9ecef; border-radius: 8px; text-align: center; margin-bottom: 15px; font-weight: 500; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Tracking Order #<?= $order_id ?></h1>
        <div id="status-bar">Connecting...</div>
        <div id="map"></div>
    </div>

    <script>
        const orderId = <?= $order_id ?>;
        const statusBar = document.getElementById('status-bar');

        // Initialize map to a default location (e.g., Yangon)
        const map = L.map('map').setView([16.8409, 96.1735], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let driverMarker = null;
        let customerMarker = null;
        let restaurantMarker = null;
        let pollingInterval;

        // --- Custom Icons ---
        const restaurantIcon = L.icon({ iconUrl: 'https://img.icons8.com/plasticine/100/restaurant.png', iconSize: [50, 50], popupAnchor: [0, -25] });
        const homeIcon = L.icon({ iconUrl: 'https://img.icons8.com/plasticine/100/home.png', iconSize: [40, 40], popupAnchor: [0, -20] });
        const driverIcon = L.icon({ iconUrl: 'https://img.icons8.com/emoji/48/delivery-scooter-emoji.png', iconSize: [48, 48] });

        async function fetchTrackingData() {
            try {
                // *** FIX: Calling the correct API file ***
                const response = await fetch(`../../api/get_live_location.php?order_id=${orderId}`);
                if (!response.ok) throw new Error('Network response was not ok');

                const data = await response.json();

                if (data.success) {
                    const { lat, lng, status, customer, restaurant } = data.data;

                    // Update status bar
                    statusBar.textContent = `Status: ${status.replace('_', ' ')}`;
                    statusBar.style.backgroundColor = (status === 'delivered') ? '#d4edda' : '#cfe2ff';

                    // Add/update restaurant marker
                    if (restaurant && !restaurantMarker) {
                        restaurantMarker = L.marker([restaurant.lat, restaurant.lng], { icon: restaurantIcon }).addTo(map)
                            .bindPopup(`<b>${restaurant.name}</b>`);
                    }

                    // Add/update customer marker
                    if (customer && !customerMarker) {
                        customerMarker = L.marker([customer.lat, customer.lng], { icon: homeIcon }).addTo(map)
                            .bindPopup('<b>Your Location</b>');
                         // Fit map to show both restaurant and customer
                        if (restaurantMarker) {
                            map.fitBounds([restaurantMarker.getLatLng(), customerMarker.getLatLng()], { padding: [50, 50] });
                        }
                    }

                    // Add/update driver marker
                    if (lat && lng) {
                        const driverPos = [parseFloat(lat), parseFloat(lng)];
                        if (!driverMarker) {
                            driverMarker = L.marker(driverPos, { icon: driverIcon }).addTo(map).bindPopup('Your Rider');
                        } else {
                            driverMarker.setLatLng(driverPos);
                        }
                        map.panTo(driverPos, { animate: true });
                    }

                    if (status === 'delivered') {
                        clearInterval(pollingInterval);
                        statusBar.textContent = "Order Delivered! Thank you.";
                    }

                } else {
                    statusBar.textContent = data.message || 'Waiting for driver...';
                }
            } catch (error) {
                console.error("Fetch error:", error);
                statusBar.textContent = 'Connection error. Retrying...';
            }
        }

        // Start polling
        fetchTrackingData();
        pollingInterval = setInterval(fetchTrackingData, 5000); // Poll every 5 seconds
    </script>
</body>
</html>
