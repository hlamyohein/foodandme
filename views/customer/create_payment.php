<?php
session_start();
require_once '../../config/db.php';

// --- Helpers ---
function abort($msg) {
    echo "<p style='color:red;'>".htmlspecialchars($msg)."</p>";
    echo "<p><a href='dashboard.php'>Back to Restaurants</a></p>";
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Accept order_id & method (GET for initial redirect from place_order.php)
// POST will be used to simulate provider callback (action=simulate)
$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : 0;
$raw_method = isset($_REQUEST['method']) ? trim($_REQUEST['method']) : '';
$action = $_POST['action'] ?? ($_GET['action'] ?? 'view');

if ($order_id <= 0) abort('Invalid order id.');

// Fetch order and verify ownership
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) abort('Order not found.');
if ((int)$order['user_id'] !== $user_id) abort('This order does not belong to you.');

// Map incoming method strings to payments.method enum values
$method_map = [
    'kpay' => 'KPay',
    'wavepay' => 'Wave',
    'kpay.' => 'KPay',
    'wavepay.' => 'Wave'
];

// Normalize method (GET/POST)
$method_key = strtolower(preg_replace('/[^a-z0-9]/i', '', $raw_method));
$method = $method_map[$method_key] ?? null;

// If method not provided or invalid, allow choosing from form
if (!$method && $action === 'view') {
    // If no method passed, show a page asking user to choose
    ?>
    <!doctype html>
    <html>
    <head><meta charset="utf-8"><title>Choose Payment</title></head>
    <body>
      <h2>Choose Payment Method</h2>
      <p>Order #<?= htmlspecialchars($order_id) ?> — Total: <strong><?= number_format($order['total_amount'], 0) ?> MMK</strong></p>
      <form method="GET">
        <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
        <button name="method" value="kpay" type="submit">Pay with KPay</button>
        <button name="method" value="wavepay" type="submit">Pay with WavePay</button>
        <a href="checkout.php" style="margin-left:12px;">Back to Checkout</a>
      </form>
    </body>
    </html>
    <?php
    exit;
}

// At this point we have $method (KPay or Wave)
if (!$method) abort('Invalid or unsupported payment method.');

// Ensure there is a payment record for this order & method in pending or create one
try {
    // Try to find an existing pending payment for this order & method
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ? AND method = ? ORDER BY payment_id DESC LIMIT 1");
    $stmt->execute([$order_id, $method]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        // Insert new payment (pending)
        $insert = $pdo->prepare("INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, ?, 'pending')");
        $insert->execute([$order_id, $order['total_amount'], $method]);
        $payment_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    abort('Database error: '.$e->getMessage());
}

// Handle POST simulation (simulate provider returning success/failure)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $do = $_POST['action'];
    if ($do === 'simulate_success') {
        // Mark payment success, update order payment_status
        $transaction_id = uniqid(strtolower($method).'_', true);
        $pdo->beginTransaction();
        try {
            $upd = $pdo->prepare("UPDATE payments SET status = 'success', transaction_id = ?, amount = ? WHERE payment_id = ?");
            $upd->execute([$transaction_id, $order['total_amount'], $payment['payment_id']]);

            $upd2 = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ?");
            $upd2->execute([$order_id]);

            $pdo->commit();

            // Redirect to order success
            header("Location: order_success.php?order_id=" . (int)$order_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Failed to finalize payment: " . $e->getMessage();
        }
    } elseif ($do === 'simulate_fail') {
        // Mark payment failed
        try {
            $upd = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE payment_id = ?");
            $upd->execute([$payment['payment_id']]);
            $error = 'Payment failed (simulated). You can try again or choose Cash on Delivery.';
        } catch (PDOException $e) {
            $error = "Failed to update payment status: " . $e->getMessage();
        }
    }
}

// Show a simple simulated payment page
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Complete Payment — <?= htmlspecialchars($method) ?></title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    .payment-wrap{max-width:720px;margin:30px auto;padding:20px;border:1px solid #eee;border-radius:8px;background:#fff;}
    .provider{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
    .provider img{height:48px;}
    .summary{margin-top:12px;padding:12px;border:1px dashed #ddd;background:#fafafa;}
    .btn{padding:10px 14px;border:0;border-radius:6px;cursor:pointer;}
    .btn-success{background:#28a745;color:#fff;margin-right:10px;}
    .btn-fail{background:#dc3545;color:#fff;}
  </style>
</head>
<body>
  <div class="payment-wrap">
    <h2>Pay with <?= htmlspecialchars($method) ?></h2>

    <div class="provider">
      <?php if (strtolower($method) === 'kpay'): ?>
        <img src="../../assets/images/kpay.jpg" alt="KPay">
      <?php elseif (strtolower($method) === 'wave' || strtolower($method) === 'wavepay'): ?>
        <img src="../../assets/images/wavepay.jpg" alt="WavePay">
      <?php endif; ?>
      <div>
        <div style="font-weight:600;"><?= htmlspecialchars($method) ?></div>
        <div style="font-size:13px;color:#666;">Simulated payment screen (demo). Transaction will not contact a real gateway.</div>
      </div>
    </div>

    <div class="summary">
      <div><strong>Order ID:</strong> <?= (int)$order_id ?></div>
      <div><strong>Amount:</strong> <?= number_format($order['total_amount'],0) ?> MMK</div>
      <div><strong>Payment record #:</strong> <?= (int)$payment['payment_id'] ?></div>
      <div><strong>Payment status:</strong> <?= htmlspecialchars($payment['status']) ?></div>
    </div>

    <?php if (!empty($error)): ?>
      <div style="margin-top:12px;color:#b00;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" style="margin-top:16px;">
      <input type="hidden" name="order_id" value="<?= (int)$order_id ?>">
      <input type="hidden" name="payment_id" value="<?= (int)$payment['payment_id'] ?>">
      <button type="submit" name="action" value="simulate_success" class="btn btn-success">Simulate Success</button>
      <button type="submit" name="action" value="simulate_fail" class="btn btn-fail">Simulate Failure</button>
      <a href="checkout.php" style="margin-left:12px;">Back to Checkout</a>
    </form>

    <hr style="margin:16px 0;">
    <div style="font-size:13px;color:#666;">
      Note: This page is a simulator. For real integration you must call the KPay/WavePay SDK or redirect to their hosted payment page and handle server callbacks to update `payments` / `orders`.
    </div>
  </div>
</body>
</html>
