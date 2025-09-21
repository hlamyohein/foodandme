<?php
// deliveries.php
session_start();
include "includes/header.php"; // Sidebar included
//require __DIR__ . '../../config/db.php'; // database connection
require_once '../../config/db.php';
// ===== Fetch All Deliveries =====
$stmt = $pdo->query("
    SELECT d.*, o.order_id, u.name AS rider_name, dl.updated_at AS last_update
    FROM delivery d
    LEFT JOIN orders o ON d.order_id = o.order_id
    LEFT JOIN users u ON d.delivery_boy_id = u.user_id
    LEFT JOIN driver_locations dl ON d.order_id = dl.order_id
    ORDER BY d.delivery_id DESC
");
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deliveries Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: #fff8f0; 
            margin: 0; 
            padding: 20px; 
        }
        h1 { 
            margin-bottom: 20px; 
            color: #d35400; 
            text-align: center;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background: #fff; 
            margin-top: 20px; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
        }
        th { 
            background: #e67e22;
            color: #fff; 
            font-size: 15px;
        }
        tr:nth-child(even) { 
            background: #fff3e0; 
        }
        tr:hover {
            background: #ffe0b2;
            transition: background 0.2s ease;
        }
        .btn-track { 
            background: #e67e22; 
            color:#fff; 
            border:none; 
            border-radius: 6px; 
            padding:6px 12px; 
            font-size: 13px; 
            text-decoration: none; 
            display: inline-block;
        }
        .btn-track:hover { 
            background: #d35400; 
            color: #fff; 
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <h1>Deliveries Management</h1>

        <!-- Deliveries Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order ID</th>
                    <th>Rider</th>
                    <th>Status</th>
                    <th>Delivery Date</th>
                    <th>Created</th>
                    <th>Track</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deliveries as $d): ?>
                <tr data-id="<?= $d['delivery_id'] ?>">
                    <td><?= $d['delivery_id'] ?></td>
                    <td><?= $d['order_id'] ?></td>
                    <td><?= htmlspecialchars($d['rider_name']) ?></td>
                    <td><?= ucfirst($d['status']) ?></td>
                    <td>
                        <?= isset($d['last_update']) && $d['last_update'] != null 
                            ? date("Y-m-d H:i", strtotime($d['last_update'])) 
                            : 'No update' 
                        ?>
                    </td>
                    <td>
                        <?= isset($d['created_at']) && $d['created_at'] != null
                            ? date("Y-m-d", strtotime($d['created_at']))
                            : 'N/A'
                        ?>
                    </td>
                    <td>
                        <a href="trackorder.php?order_id=<?= $d['order_id'] ?>" class="btn-track">🚚 Track Order</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
