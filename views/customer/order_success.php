<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
//require_once(__DIR__ . '/../restaurant/confignew.php');
require_once '../../config/db.php';
$order_info = null;
if ($order_id > 0) {
    $stmt = $pdo->prepare("SELECT order_status, cancellation_reason FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_info = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Status</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <!-- <style>
        .cancellation-reason {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #e74c3c;
            background-color: #fce7e7;
            color: #c0392b;
            border-radius: 8px;
        }
    </style> -->
</head>
<body>
<div class="container" style="text-align: center; padding-top: 50px;">
    <?php if ($order_info && $order_info['order_status'] === 'accepted'): ?>
        <h1>Order Accepted</h1>
        <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been accepted. We will notify you once it's on its way.</p>
    <?php elseif ($order_info && $order_info['order_status'] === 'canceled'): ?>
        <h1>Order Canceled</h1>
        <p>Your order #<?php echo htmlspecialchars($order_id); ?> has been canceled.</p>
        <div class="cancellation-reason">
            <p><strong>Reason:</strong> <?php echo htmlspecialchars($order_info['cancellation_reason']); ?></p>
        </div>
        <p>We apologize for the inconvenience.</p>
    <?php else: ?>
        <h1>Thank You!</h1>
        <h2>Your order has been placed successfully.</h2>
        <p>Order ID: <strong><?php echo htmlspecialchars($order_id); ?></strong></p>
        <p>We will notify you once the restaurant accepts your order.</p>
    <?php endif; ?>

    <p>
        <a id="track-link" href="track_order.php?order_id=<?= $order_id ?>" class="checkout-btn" style="display: none; padding:10px 20px;">
            Track Order
        </a>
    </p>

    <br>
    <a href="dashboard.php" class="checkout-btn" style="display: inline-block; width: auto; padding: 10px 20px;">Back to Restaurants</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const orderId = <?= $order_id ?: 0 ?>;
    const statusContainer = document.querySelector('.container h1');
    const statusParagraph = document.querySelector('.container p');
    const trackLink = document.getElementById('track-link');

    if (orderId > 0) {
        function checkOrderStatus() {
            fetch(`get_order_status.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newStatus = data.status;
                        const cancellationReason = data.cancellation_reason;

                        // Update the UI based on the new status
                        if (newStatus === 'accepted') {
                            statusContainer.textContent = 'Order Accepted';
                            statusParagraph.textContent = 'Your order has been accepted. We will notify you once it\'s on its way.';
                            if (trackLink) {
                                trackLink.style.display = 'inline-block';
                            }
                        } else if (newStatus === 'preparing') {
                             statusContainer.textContent = 'Order Preparing';
                             statusParagraph.textContent = 'The restaurant is preparing your order.';
                             if (trackLink) {
                                 trackLink.style.display = 'inline-block';
                             }
                        } else if (newStatus === 'ready') {
                             statusContainer.textContent = 'Order Ready';
                             statusParagraph.textContent = 'Your order is ready for pickup.';
                             if (trackLink) {
                                 trackLink.style.display = 'inline-block';
                             }
                        } else if (newStatus === 'on_the_way') {
                             statusContainer.textContent = 'Order On The Way';
                             statusParagraph.textContent = 'Your order is on the way for delivery.';
                             if (trackLink) {
                                 trackLink.style.display = 'inline-block';
                             }
                        } else if (newStatus === 'delivered') {
                            statusContainer.textContent = 'Order Delivered';
                            statusParagraph.textContent = 'Your order has been delivered!';
                             if (trackLink) {
                                 trackLink.style.display = 'none';
                             }
                        } else if (newStatus === 'canceled') {
                             statusContainer.textContent = 'Order Canceled';
                             statusParagraph.textContent = 'Your order was canceled. Reason: ' + (cancellationReason || 'Not specified');
                             if (trackLink) {
                                 trackLink.style.display = 'none';
                             }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking order status:', error);
                });
        }

        // Check order status every 5 seconds
        setInterval(checkOrderStatus, 5000);

        // Initial status check on page load
        checkOrderStatus();
    }
});
</script>
</body>
</html>