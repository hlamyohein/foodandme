<?php
// views/rider/dashboard.php
session_start();

// Security check: ensure logged in and role delivery
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery') {
    header('Location: ../../login.php');
    exit;
}

$delivery_boy_name = htmlspecialchars($_SESSION['name'] ?? 'Rider');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Delivery Dashboard</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <style>
        body { font-family: sans-serif; background: #f4f6f8; margin: 0; }
        .header { background: #333; color: white; padding: 15px 20px; display:flex; justify-content:space-between; align-items:center; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        #map { height: 500px; border-radius: 8px; }
        .btn { padding: 8px 12px; border-radius: 6px; border: none; cursor: pointer; font-weight: bold; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-ghost { background: #e9ecef; color: #333; }
        .order-list table { width: 100%; border-collapse: collapse; }
        .order-list th, .order-list td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; font-size: 13px; }
        .order-list .actions { text-align: right; }
        .small { font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <strong>Delivery Dashboard</strong>
        <span>Welcome, <?= $delivery_boy_name ?> &nbsp;|&nbsp; <a href="../../logout.php" style="color:white;">Logout</a></span>
    </div>

    <div class="container">
        <div class="card">
            <h3>New Orders Nearby</h3>
            <div id="new-orders-list" class="order-list">Loading new orders...</div>
        </div>

        <div class="card">
            <h3>My Active Deliveries</h3>
            <div id="active-deliveries-list" class="order-list">Loading active deliveries...</div>
        </div>

        <div class="card" style="grid-column: 1 / -1;">
            <h3>Live Map</h3>
            <div id="map"></div>
            <p style="margin-top:10px">
                <button id="start-tracking" class="btn btn-success">Start Sending My Location</button>
                <button id="stop-tracking" class="btn btn-danger" disabled>Stop Sending</button>
                <span id="tracking-status" style="margin-left:12px; font-size: 14px;">Not sending location.</span>
            </p>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // --- Map & global vars ---
    const map = L.map('map').setView([16.8409, 96.1735], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

    let watchId = null;
    let activeOrder = null; // one active order simplification
    let meMarker = L.marker(map.getCenter(), { title: 'You' }).addTo(map).bindPopup('You');

    const newOrdersList = document.getElementById('new-orders-list');
    const activeDeliveriesList = document.getElementById('active-deliveries-list');
    const startBtn = document.getElementById('start-tracking');
    const stopBtn = document.getElementById('stop-tracking');
    const statusEl = document.getElementById('tracking-status');

    // --- Fetch unassigned orders (visible for all riders) ---
    async function fetchNewOrders() {
        try {
            const res = await fetch('../../api/get_unassigned_orders.php');
            const j = await res.json();
            if (!j.success) { newOrdersList.innerHTML = "<em>Error loading new orders.</em>"; return; }
            const orders = j.orders || [];
            if (orders.length === 0) {
                newOrdersList.innerHTML = "<em>No new orders nearby.</em>";
                return;
            }
            newOrdersList.innerHTML = '<table><thead><tr><th>Order</th><th>Address</th><th>Amount</th><th class="actions">Action</th></tr></thead><tbody>' +
              orders.map(o => `
                <tr id="order-row-${o.order_id}">
                  <td>#${o.order_id} <div class="small">${o.restaurant_name || ''}</div></td>
                  <td>${o.delivery_address || ''}</td>
                  <td>${o.total_amount || ''}</td>
                  <td class="actions"><button class="btn btn-primary" onclick="acceptOrder(${o.order_id}, this)">Accept</button></td>
                </tr>
              `).join('') + '</tbody></table>';
        } catch (err) {
            console.error(err);
            newOrdersList.innerHTML = "<em>Network error.</em>";
        }
    }

    // --- Accept an order ---
    async function acceptOrder(orderId, btn) {
        if (!confirm('Accept order #' + orderId + '?')) return;
        btn.disabled = true;
        try {
            const res = await fetch('../../api/accept_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'order_id=' + encodeURIComponent(orderId)
            });
            const j = await res.json();
            if (j.success) {
                // Remove from new orders immediately
                const row = document.getElementById('order-row-' + orderId);
                if (row) row.remove();
                // Refresh assigned deliveries
                await fetchActiveDeliveries();
                alert('Accepted order #' + orderId + '. Start tracking when ready.');
            } else {
                alert('Could not accept: ' + (j.error || 'unknown'));
                btn.disabled = false;
            }
        } catch (err) {
            console.error(err);
            alert('Network error');
            btn.disabled = false;
        }
    }

    // --- Fetch deliveries assigned to this rider ---
    async function fetchActiveDeliveries() {
        try {
            const response = await fetch('../../api/get_assigned_deliveries.php');
            const data = await response.json();
            if (data.success && data.deliveries && data.deliveries.length > 0) {
                activeOrder = data.deliveries[0]; // simplification: show first
                renderActiveDelivery();
            } else {
                activeOrder = null;
                activeDeliveriesList.innerHTML = "No active deliveries.";
            }
        } catch (err) {
            console.error(err);
            activeDeliveriesList.innerHTML = "Error loading active deliveries.";
        }
    }

    function renderActiveDelivery() {
        if (!activeOrder) return;
        activeDeliveriesList.innerHTML = `
            <table>
                <tr><th>Order ID</th><td>#${activeOrder.order_id}</td></tr>
                <tr><th>Restaurant</th><td>${activeOrder.restaurant_name || ''}</td></tr>
                <tr><th>Address</th><td>${activeOrder.delivery_address || ''}</td></tr>
                <tr><th>Status</th><td id="active-order-status">${activeOrder.order_status || ''}</td></tr>
                <tr><th>Action</th><td>
                    <button class="btn btn-success" onclick="startDelivering()">Start Delivering</button>
                    <button class="btn btn-ghost" onclick="cancelActiveDelivery()">Cancel</button>
                </td></tr>
            </table>`;
    }

    function cancelActiveDelivery() {
        if (!confirm('Cancel active delivery?')) return;
        // This is a local UX cancel - proper cancel should call API to unassign.
        if (watchId) { navigator.geolocation.clearWatch(watchId); watchId = null; }
        activeOrder = null;
        fetchNewOrders();
        fetchActiveDeliveries();
        startBtn.disabled = false;
        stopBtn.disabled = true;
        statusEl.textContent = 'Not sending location.';
    }

    // --- Start/Stop geolocation tracking (sends to server) ---
    startBtn.addEventListener('click', () => {
        startDelivering();
    });
    stopBtn.addEventListener('click', () => {
        if (watchId) navigator.geolocation.clearWatch(watchId);
        watchId = null;
        startBtn.disabled = false;
        stopBtn.disabled = true;
        statusEl.textContent = 'Not sending location.';
    });

    function startDelivering() {
        if (!activeOrder) {
            alert("Please accept an order before starting to track.");
            return;
        }
        if (navigator.geolocation) {
            if (watchId) { alert('Already tracking'); return; }
            watchId = navigator.geolocation.watchPosition(sendLocation, handleError, { enableHighAccuracy: true, maximumAge: 2000, timeout: 8000 });
            startBtn.disabled = true;
            stopBtn.disabled = false;
            statusEl.textContent = 'Sending location...';
        } else {
            alert("Geolocation not supported.");
        }
    }

    async function sendLocation(position) {
    const { latitude, longitude } = position.coords;
    console.log('GEOPOSITION ->', latitude, longitude, 'time:', new Date().toISOString());

    // update map UI
    meMarker.setLatLng([latitude, longitude]);
    map.panTo([latitude, longitude]);

    if (!activeOrder) {
        console.warn('No activeOrder set — not sending to server');
        return;
    }

    try {
        const res = await fetch('../../api/update_location.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',        // important: send PHP session cookie
            body: JSON.stringify({
                order_id: activeOrder.order_id,
                lat: latitude,
                lng: longitude
            })
        });

        const txt = await res.text();
        let json;
        try { json = JSON.parse(txt); } catch (e) { json = null; }

        console.log('update_location response status=', res.status, 'body=', json ?? txt);

        if (!res.ok || (json && json.success === false)) {
            console.warn('Server responded with error', json ?? txt);
            statusEl.textContent = 'Error sending location (server).';
        } else {
            statusEl.textContent = 'Sending location... (last sent ' + new Date().toLocaleTimeString() + ')';
        }
    } catch (err) {
        console.error('Network or fetch error sending location:', err);
        statusEl.textContent = 'Network error sending location.';
    }
}


    function handleError(error) {
        console.warn('Geolocation Error:', error);
    }

    // polling
    fetchNewOrders();
    fetchActiveDeliveries();
    setInterval(fetchNewOrders, 8000);
    setInterval(fetchActiveDeliveries, 8000);
    </script>
</body>
</html>
