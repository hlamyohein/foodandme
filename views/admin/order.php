<?php
// orders.php
session_start();
include "includes/header.php"; 
// require __DIR__ . '../../config/db.php';
require_once '../../config/db.php'; 

// ===== Pagination =====
$limit = 20; 
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ===== Filters =====
$where = [];
$params = [];

if (!empty($_GET['status'])) {
    $where[] = "o.order_status = ?";
    $params[] = $_GET['status'];
}

if (!empty($_GET['search'])) {
    $where[] = "(u.name LIKE ? OR r.name LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ===== Count total rows =====
$countSql = "
    SELECT COUNT(*) 
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN restaurants r ON o.restaurant_id = r.restaurant_id
    $whereSQL
";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// ===== Fetch paginated orders =====
$sql = "
    SELECT o.order_id, u.name AS user_name, r.name AS restaurant_name,
           o.total_amount, o.payment_status, o.order_status, o.created_at,
           p.method AS payment_method,
           db.name AS delivery_name
    FROM orders o
    JOIN users u ON o.user_id = u.user_id
    JOIN restaurants r ON o.restaurant_id = r.restaurant_id
    LEFT JOIN payments p ON o.order_id = p.order_id
    LEFT JOIN delivery d ON o.order_id = d.order_id
    LEFT JOIN users db ON d.delivery_boy_id = db.user_id
    $whereSQL
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Status list =====
$statuses = ['pending','preparing','on_the_way','ready','delivered','canceled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Orders - Admin</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; background: #f1f3f6; margin: 0; }
        .container { padding: 30px; }
        h1 { margin-bottom: 20px; font-size: 28px; color: #333; }

        /* Card */
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 14px 16px; text-align: left; }
        th { background: #e67e22; color: #fff; font-size: 14px; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover { background: #f1f5ff; }
        td { font-size: 14px; color: #333; }

        /* Badges */
        .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    color: #fff;
    text-transform: capitalize;
    white-space: nowrap;  /* ✅ Prevents breaking into 2 lines */
}

        .badge.pending { background: #f6c23e; }
        .badge.preparing { background: #36b9cc; }
        .badge.on_the_way { background: #4e73df; }
        .badge.ready { background: #1cc88a; }
        .badge.delivered { background: #20c997; }
        .badge.canceled { background: #e74a3b; }

        .badge.paid { background: #1cc88a; }
        .badge.unpaid { background: #e74a3b; }

        .badge.kpay { background: #6f42c1; }
        .badge.wave { background: #fd7e14; }
        .badge.Paypal { background: #17a2b8; }
        .badge.card {background: #ad17b8ff; }

        .badge.unassigned { background: #6c757d; }

        /* Filters */
        form { margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        select, input[type="text"], button {
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            background: #4e73df;
            color: #fff;
            cursor: pointer;
            border: none;
            transition: 0.2s;
        }
        button:hover { background: #2e59d9; }

        /* Pagination */
        .pagination { margin-top: 20px; text-align: center; }
        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 2px;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
            transition: 0.2s;
        }
        .pagination a.active { background: #4e73df; color: #fff; }
        .pagination a:hover { background: #2e59d9; color: #fff; }
    </style>
</head>
<body>
    <div class="main-content">
    <div class="container">
        <div class="card">
            <h1>All Orders</h1>

            <!-- Filters -->
            <form method="get">
                <select name="status">
                    <option value="">-- Filter by Status --</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s ?>" <?= (isset($_GET['status']) && $_GET['status']==$s) ? 'selected' : '' ?>>
                            <?= ucfirst(str_replace("_"," ",$s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Search by user, restaurant, or order ID" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit">Filter</button>
            </form>

            <!-- Orders Table -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Restaurant</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Payment Type</th>
                        <th>Delivery Name</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>#<?= $o['order_id'] ?></td>
                                <td><?= htmlspecialchars($o['user_name']) ?></td>
                                <td><?= htmlspecialchars($o['restaurant_name']) ?></td>
                                <td><?= number_format($o['total_amount'], 2) ?></td>
                                <td><span class="badge <?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                                <td>
                                    <?php if (!empty($o['payment_method'])): ?>
                                        <span class="badge <?= strtolower($o['payment_method']) ?>">
                                            <?= ucfirst($o['payment_method']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge unassigned">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $o['delivery_name'] ? htmlspecialchars($o['delivery_name']) : '<span class="badge unassigned">Unassigned</span>' ?>
                                </td>
                                <td><span class="badge <?= $o['order_status'] ?>"><?= ucfirst(str_replace("_"," ",$o['order_status'])) ?></span></td>
                                <td><?= date("Y-m-d H:i", strtotime($o['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php if ($totalPages > 1): ?>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                            $query = $_GET;
                            $query['page'] = $i;
                            $url = '?' . http_build_query($query);
                        ?>
                        <a href="<?= $url ?>" class="<?= ($i == $page) ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
