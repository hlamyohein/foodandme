<?php
// dashboard.php
session_start();
include "includes/header.php"; // contains sidebar + user info

// ===== General Stats =====
$totalRestaurants = $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$completedDeliveries = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='delivered'")->fetchColumn();
$pendingDeliveries = $pdo->query("SELECT COUNT(*) FROM delivery WHERE status='assigned'")->fetchColumn();
$totalMenuItems = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$unreadNotifications = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read='0'")->fetchColumn();

// ===== Orders by Status =====
$statuses = ['pending','preparing','on_the_way','ready','delivered','canceled'];
$orderStatusCounts = [];
foreach ($statuses as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_status=?");
    $stmt->execute([$status]);
    $orderStatusCounts[$status] = $stmt->fetchColumn();
}

// ===== Top 5 Menu Items =====
$stmt = $pdo->query("
    SELECT mi.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    GROUP BY oi.item_id
    ORDER BY total_sold DESC
    LIMIT 5
");
$topItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Orders Over Time =====
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM orders
    GROUP BY month
    ORDER BY month
");
$ordersOverTime = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Recent Orders =====
$stmt = $pdo->query("
    SELECT o.order_id, u.name as user_name, r.name as restaurant_name, 
           o.total_amount, o.order_status, o.created_at
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN restaurants r ON o.restaurant_id = r.restaurant_id
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$userName = isset($user['name']) && $user['name'] !== '' ? htmlspecialchars($user['name']) : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FoodApp Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }

        /* ===== Top Header Bar ===== */
        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 10px 20px;
            border-bottom: 1px solid #ddd;
            margin-left: 250px;
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 900;
            transition: margin-left 0.3s ease;
        }
        .sidebar.minimized ~ .top-header { margin-left: 80px; }

        .top-header h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .search-bar {
            width: 250px;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 14px;
            transition: box-shadow 0.2s ease;
        }
        .search-bar:focus { box-shadow: 0 0 5px rgba(0,0,0,0.15); }

        .notification { position: relative; cursor: pointer; }
        .notification .bell { font-size: 22px; color: #ff6a00; }
        .notification .badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #e74a3b;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 50%;
        }

        /* ===== Main Content ===== */
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .sidebar.minimized ~ .main-content { margin-left: 80px; }

        /* ===== Cards ===== */
        .cards { display: grid; grid-template-columns: repeat(auto-fill,minmax(220px,1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        .card h3 { margin: 0; font-size: 14px; color: #555; }
        .card p { font-size: 24px; margin: 10px 0 0; font-weight: bold; }

        .pending { border-left: 5px solid #f6c23e; }
        .preparing { border-left: 5px solid #36b9cc; }
        .on_the_way { border-left: 5px solid #4e73df; }
        .ready { border-left: 5px solid #1cc88a; }
        .delivered { border-left: 5px solid #20c997; }
        .canceled { border-left: 5px solid #e74a3b; }

        /* ===== Charts ===== */
        .charts-line {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .chart-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 15px;
            flex: 1 1 30%;
            min-width: 250px;
            height: 250px;
        }
        .chart-container.pie {
            height: 250px;
            width: 250px;
            flex: none;
            margin: 0 auto;
        }
        canvas { width: 100% !important; height: 100% !important; }

        /* ===== Table ===== */
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; }
        th { background: #1e1e2d; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; color: #fff; font-weight: bold; }
        .badge.pending { background: #f6c23e; }
        .badge.preparing { background: #36b9cc; }
        .badge.on_the_way { background: #4e73df; }
        .badge.ready { background: #1cc88a; }
        .badge.delivered { background: #20c997; }
        .badge.canceled { background: #e74a3b; }

        .btn { display: inline-block; margin-top: 10px; padding: 10px 16px; background: #4e73df; color: #fff; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; transition: background 0.2s ease-in-out; }
        .btn:hover { background: #2e59d9; }

        /* ADD THIS CSS TO YOUR EXISTING <style> TAG */

/* Notification Dropdown */
.notification-wrapper { position: relative; }
.notification { cursor: pointer; } /* Make sure the original is still clickable */
.notification-dropdown {
    display: none; /* Hidden by default */
    position: absolute;
    top: 50px;
    right: 0;
    width: 320px;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    z-index: 1000;
    border: 1px solid #eee;
}
.notification-dropdown.show { display: block; }
.dropdown-header { padding: 15px; border-bottom: 1px solid #f0f0f0; }
.dropdown-header h3 { margin: 0; font-size: 16px; }
#notification-list { list-style: none; padding: 0; margin: 0; max-height: 400px; overflow-y: auto; }
#notification-list li { padding: 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background-color 0.2s; }
#notification-list li:last-child { border-bottom: none; }
#notification-list li:hover { background-color: #f9f9f9; }
#notification-list .title { font-weight: 600; color: #333; display: block; }
#notification-list .time { font-size: 12px; color: #888; display: block; margin-top: 4px; }

/* Modal for Notification Details */
.modal-overlay {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    z-index: 2000;
    justify-content: center;
    align-items: center;
}
.modal-overlay.show { display: flex; }
.modal-content {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    position: relative;
}
.modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 15px; margin-bottom: 20px; }
.modal-header h2 { margin: 0; }
#modal-close-btn { font-size: 28px; font-weight: bold; cursor: pointer; color: #777; }
.modal-body p { line-height: 1.6; }
.modal-body hr { border: 0; border-top: 1px solid #eee; margin: 15px 0; }
    </style>
</head>
<body>

<!-- Top Header -->
<div class="top-header">
    <h2>Hello, <?= $userName ?> 👋</h2>
    <input type="text" class="search-bar" placeholder="Search here...">
    <div class="notification-wrapper">
    <div class="notification" id="notification-bell">
        <span class="bell">&#128276;</span>
        <span class="badge" id="notification-badge" style="display: none;">0</span>
    </div>
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="dropdown-header">
            <h3>Notifications</h3>
        </div>
        <ul id="notification-list">
            <li>No new notifications</li>
        </ul>
    </div>
</div>

<div id="notification-modal-overlay" class="modal-overlay">
    <div id="notification-modal" class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Notification Details</h2>
            <span id="modal-close-btn">&times;</span>
        </div>
        <div class="modal-body" id="modal-body">
            </div>
    </div>
</div>
</div>

<div class="main-content">
    <h1>Dashboard</h1>

    <!-- General Stats -->
    <div class="cards">
        <div class="card"><h3>Total Restaurants</h3><p><?= $totalRestaurants ?></p></div>
        <div class="card"><h3>Total Orders</h3><p><?= $totalOrders ?></p></div>
        <div class="card"><h3>Completed Deliveries</h3><p><?= $completedDeliveries ?></p></div>
        <div class="card"><h3>Pending Deliveries</h3><p><?= $pendingDeliveries ?></p></div>
        <div class="card"><h3>Menu Items</h3><p><?= $totalMenuItems ?></p></div>
        <div class="card"><h3>Unread Notifications</h3><p><?= $unreadNotifications ?></p></div>
    </div>

    <!-- Orders by Status -->
    <h2>Orders by Status</h2>
    <div class="cards">
        <div class="card pending"><h3>Pending</h3><p><?= $orderStatusCounts['pending'] ?></p></div>
        <div class="card preparing"><h3>Preparing</h3><p><?= $orderStatusCounts['preparing'] ?></p></div>
        <div class="card on_the_way"><h3>On The Way</h3><p><?= $orderStatusCounts['on_the_way'] ?></p></div>
        <div class="card ready"><h3>Ready</h3><p><?= $orderStatusCounts['ready'] ?></p></div>
        <div class="card delivered"><h3>Delivered</h3><p><?= $orderStatusCounts['delivered'] ?></p></div>
        <div class="card canceled"><h3>Canceled</h3><p><?= $orderStatusCounts['canceled'] ?></p></div>
    </div>

    <!-- Charts -->
    <div class="charts-line">
        <div class="chart-container"><canvas id="topItemsChart"></canvas></div>
        <div class="chart-container"><canvas id="ordersOverTimeChart"></canvas></div>
        <div class="chart-container pie"><canvas id="ordersByStatusChart"></canvas></div>
    </div>

    <!-- Recent Orders -->
    <h2>Recent Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>User</th>
                <th>Restaurant</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $order): ?>
            <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['user_name']) ?></td>
                <td><?= htmlspecialchars($order['restaurant_name']) ?></td>
                <td><?= number_format($order['total_amount'], 2) ?></td>
                <td><span class="badge <?= $order['order_status'] ?>"><?= ucfirst(str_replace("_"," ",$order['order_status'])) ?></span></td>
                <td><?= date("Y-m-d H:i", strtotime($order['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="order.php" class="btn">View All Orders</a>
</div>

<script>
    // Top 5 Items Chart
    new Chart(document.getElementById('topItemsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topItems, 'name')) ?>,
            datasets: [{
                label: 'Items Sold',
                data: <?= json_encode(array_column($topItems, 'total_sold')) ?>,
                backgroundColor: ['#4e73df','#e74a3b','#1cc88a','#f6c23e','#36b9cc']
            }]
        }
    });

    // Orders Over Time Chart
    new Chart(document.getElementById('ordersOverTimeChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($ordersOverTime, 'month')) ?>,
            datasets: [{
                label: 'Orders',
                data: <?= json_encode(array_column($ordersOverTime, 'count')) ?>,
                borderColor: '#4e73df',
                fill: true,
                backgroundColor: 'rgba(78,115,223,0.1)'
            }]
        }
    });

    // Orders by Status Pie Chart
    new Chart(document.getElementById('ordersByStatusChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_keys($orderStatusCounts)) ?>,
            datasets: [{
                label: 'Orders by Status',
                data: <?= json_encode(array_values($orderStatusCounts)) ?>,
                backgroundColor: ['#f6c23e','#36b9cc','#4e73df','#1cc88a','#20c997','#e74a3b']
            }]
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bell = document.getElementById('notification-bell');
        const badge = document.getElementById('notification-badge');
        const dropdown = document.getElementById('notification-dropdown');
        const notificationList = document.getElementById('notification-list');

        const modalOverlay = document.getElementById('notification-modal-overlay');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');
        const modalCloseBtn = document.getElementById('modal-close-btn');

        // Function to fetch notifications from the server
        function fetchNotifications() {
            fetch('/foodandme/api/fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    // Update badge
                    if (data.count > 0) {
                        badge.textContent = data.count;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }

                    // Update dropdown list
                    notificationList.innerHTML = ''; // Clear previous list
                    if (data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const li = document.createElement('li');
                            li.dataset.id = notif.notification_id; // Store ID for click events
                            
                            // Format time nicely
                            const date = new Date(notif.created_at);
                            const timeAgo = Math.round((new Date() - date) / (1000 * 60)); // minutes ago
                            const timeString = timeAgo < 60 ? `${timeAgo}m ago` : `${Math.floor(timeAgo/60)}h ago`;

                            li.innerHTML = `<span class="title">${notif.title}</span><span class="time">${timeString}</span>`;
                            notificationList.appendChild(li);
                        });
                    } else {
                        notificationList.innerHTML = '<li>No new notifications</li>';
                    }
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        // --- Event Listeners ---

        // Toggle dropdown when bell is clicked
        bell.addEventListener('click', (event) => {
            event.stopPropagation();
            dropdown.classList.toggle('show');
        });

        // Close dropdown if clicked outside
        document.addEventListener('click', () => {
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        // Open modal when a notification is clicked
        notificationList.addEventListener('click', function(event) {
            const li = event.target.closest('li');
            if (li && li.dataset.id) {
                const notifId = li.dataset.id;
                fetch(`/foodandme/api/get_notification_details.php?id=${notifId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        modalTitle.textContent = data.title;
                        modalBody.innerHTML = data.details_html;
                        modalOverlay.classList.add('show');
                        // Refresh notifications in the background since one was just read
                        fetchNotifications(); 
                    } else {
                        alert(data.message);
                    }
                });
            }
        });

        // Close modal
        function closeModal() {
             modalOverlay.classList.remove('show');
        }
        modalCloseBtn.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', function(event) {
            if (event.target === modalOverlay) { // Only close if overlay is clicked, not content
                closeModal();
            }
        });


        // --- Initial Load & Polling ---
        fetchNotifications(); // Fetch notifications on page load
        setInterval(fetchNotifications, 15000); // And then check every 15 seconds
    });
</script>
<script>
    // Place this script in your admin page's main HTML file or a linked JS file

$(document).ready(function() {
    let currentUserId = 0; // Variable to store the user ID from the notification

    // When a notification is clicked to show details
    $('.dropdown-menu').on('click', '.notification-item', function(e) {
        e.preventDefault();
        const notifId = $(this).data('id');

        fetch(`api/get_notification_details.php?id=${notifId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#notificationModalLabel').text(data.title);
                    $('#notificationModalBody').html(data.details_html);
                    
                    // Store the user ID and clear previous buttons
                    currentUserId = data.userId;
                    $('#notificationModalFooter').empty();
                    
                    // Add Approve and Reject buttons if it's a Rider/Vendor request
                    if (data.title.includes('Request')) {
                         $('#notificationModalFooter').html(`
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-danger action-btn" data-action="reject">Reject</button>
                            <button type="button" class="btn btn-success action-btn" data-action="approve">Approve</button>
                         `);
                    }

                    $('#notificationModal').modal('show');
                } else {
                    alert('Error: ' + data.message);
                }
            });
    });

    // Handle clicks on the dynamic action buttons in the modal footer
    $('#notificationModalFooter').on('click', '.action-btn', function() {
        const action = $(this).data('action'); // 'approve' or 'reject'
        
        if (currentUserId === 0) {
            alert('Could not identify the user. Please try again.');
            return;
        }

        // Prepare data for the API call
        const formData = new FormData();
        formData.append('user_id', currentUserId);
        formData.append('action', action);

        // Call your new CRUD API
        fetch('api/deli_crud.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message); // Or use a nicer notification library like SweetAlert
                $('#notificationModal').modal('hide');
                // You might want to refresh the notifications or the pending list here
                fetchNotifications(); 
            } else {
                alert('An error occurred: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('A network error occurred.');
        });
    });
});
</script>
</body>
</html>
