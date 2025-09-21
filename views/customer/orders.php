<?php
// Fetch user's orders, joined with restaurant name for display
$orderStmt = $pdo->prepare("
    SELECT 
        o.order_id, 
        o.total_amount, 
        o.order_status, 
        o.created_at, 
        r.name AS restaurant_name,
        r.restaurant_id
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.restaurant_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$orderStmt->execute([$_SESSION['user_id']]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Orders & reordering</h2>

<?php if (empty($orders)): ?>
    <p>You haven't placed any orders yet.</p>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <h3><?= htmlspecialchars($order['restaurant_name']) ?></h3>
                <span class="order-status"><?= htmlspecialchars(ucfirst($order['order_status'])) ?></span>
            </div>
            <div class="order-details">
                <strong>Order Date:</strong> <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?><br>
                <strong>Total:</strong> <?= number_format($order['total_amount']) ?> MMK
            </div>
            <div class="order-items">
                <strong>Items:</strong>
                <ul>
                    <?php
// Fetch items for this specific order, joining menu_items to get the name
$itemStmt = $pdo->prepare("
    SELECT oi.quantity, mi.name AS item_name
    FROM order_items oi
    JOIN menu_items mi ON oi.item_id = mi.item_id
    WHERE oi.order_id = ?
");
$itemStmt->execute([$order['order_id']]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($items as $item) {
    echo '<li>' . htmlspecialchars($item['quantity']) . ' x ' . htmlspecialchars($item['item_name']) . '</li>';
}

?>

                </ul>
            </div>
            <a href="restaurants.php?id=<?= $order['restaurant_id'] ?>" class="reorder-btn">Reorder</a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>