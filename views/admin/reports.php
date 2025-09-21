<?php
session_start();
require_once "../../config/db.php";
include "includes/header.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT r.review_id, u.name AS user_name, res.name AS restaurant_name, 
           r.rating, r.comment, r.status
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.user_id
    LEFT JOIN restaurants res ON r.restaurant_id = res.restaurant_id
    ORDER BY r.review_id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Reviews</title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f3f6fb;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    h2 {
      color: #ff6600;
      font-size: 26px;
      margin-bottom: 20px;
      text-align: center;
    }
    .review-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .review-table th, .review-table td {
      padding: 14px 18px;
      text-align: left;
      font-size: 14px;
    }
    .review-table th {
      background: linear-gradient(90deg, #ff8800, #ff6600);
      color: #fff;
      font-weight: 600;
    }
    .review-table tr:nth-child(even) {
      background: #fafafa;
    }
    .review-table tr:hover {
      background: #fff3e6;
      transition: 0.3s;
    }

   /* Rating badges */
.rating {
  font-weight: bold;
  padding: 4px 10px;
  border-radius: 8px;
  font-size: 13px;
  display: inline-block;
  min-width: 40px;
  text-align: center;
}
.rating-good { background: #4CAF50; color: #fff; }    /* green */
.rating-average { background: #FFEB3B; color: #333; } /* yellow */
.rating-bad { background: #F44336; color: #fff; }     /* red */

/* Whole row highlight */
.review-good { background: #e8f5e9 !important; }      /* light green */
.review-average { background: #fffde7 !important; }   /* light yellow */
.review-bad { background: #ffebee !important; }       /* light red */

/* Hover effects */
.review-good:hover { background: #c8e6c9 !important; }
.review-average:hover { background: #fff9c4 !important; }
.review-bad:hover { background: #ffcdd2 !important; }


    /* Status Styles */
    .status {
      font-weight: bold;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 13px;
    }
    .status-active { background: #e6ffe6; color: #2e7d32; }
    .status-inactive { background: #ffe6e6; color: #c62828; }

    .comment {
      max-width: 300px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  </style>
</head>
<body>
<div class="main-content">
<h2>📋 Customer Reviews</h2>

<table class="review-table">
  <thead>
    <tr>
      <th>ID</th>
      <th>User</th>
      <th>Restaurant</th>
      <th>Rating</th>
      <th>Comment</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
  <?php if ($reviews): ?>
    <?php foreach ($reviews as $review): ?>
      <?php 
        // Rating class
        if ($review['rating'] >= 4) {
            $ratingClass = "rating-good";   // green
            $rowClass = "review-good";
        } elseif ($review['rating'] == 3) {
            $ratingClass = "rating-average"; // yellow
            $rowClass = "review-average";
        } else {
            $ratingClass = "rating-bad";    // red
            $rowClass = "review-bad";
        }
      ?>
      <tr class="<?= $rowClass ?>">
        <td><?= htmlspecialchars($review['review_id']) ?></td>
        <td><?= htmlspecialchars($review['user_name'] ?? 'Unknown') ?></td>
        <td><?= htmlspecialchars($review['restaurant_name'] ?? 'Unknown') ?></td>
        <td>
          <span class="rating <?= $ratingClass ?>">
            <?= htmlspecialchars($review['rating']) ?>/5
          </span>
        </td>
        <td class="comment" title="<?= htmlspecialchars($review['comment']) ?>">
          <?= htmlspecialchars($review['comment']) ?>
        </td>
        <td>
          <span class="status <?= $review['status'] == 'active' ? 'status-active' : 'status-inactive' ?>">
            <?= htmlspecialchars(ucfirst($review['status'])) ?>
          </span>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="6" style="text-align:center; padding:20px;">No reviews found.</td></tr>
  <?php endif; ?>
</tbody>

</table>
    </div>
</body>
</html>
