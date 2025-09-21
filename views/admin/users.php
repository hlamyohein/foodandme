<?php 
// users.php
session_start();
include "includes/header.php";
//require __DIR__ . '../../config/db.php';
require_once '../../config/db.php';
// ===== Fetch All Users =====
$stmt = $pdo->query("SELECT * FROM users where role='customer' ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #fff7f0;
            margin: 0;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            text-align: center;
            color: #e67e22;
            font-weight: bold;
        }
        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 10px;
            overflow: hidden;
        }
        thead th {
            background: #e67e22;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 12px;
            text-align: center;
        }
        tbody td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #f3d4b6;
            color: #333;
        }
        tbody tr:nth-child(even) {
            background: #fff3e5;
        }
        tbody tr:hover {
            background: #ffe5cc;
            transition: background 0.2s ease-in-out;
        }
        .status-active {
            background: #2ecc71;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }
        .status-inactive {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 18px;
            background: #e67e22;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .back-btn:hover {
            background: #cf711f;
        }
    </style>
</head>
<body>
<div class="main-content">
    <h1>👥 Users View</h1>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['user_id'] ?></td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= ucfirst($u['role']) ?></td>
                        <td><?= htmlspecialchars($u['address']) ?></td>
                        <td>
                            <?php if (strtolower($u['is_verified']) === '1'): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date("Y-m-d", strtotime($u['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
