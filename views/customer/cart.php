<?php
session_start();

require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo "<div class='cart-box'><p>Please <a href='../login.php'>login</a> to add items.</p></div>";
    // always output an item-count so front-end can read it
    echo "<div id='cart-item-count' style='display:none;'>0</div>";
    exit;
}

$action   = $_POST['action'] ?? 'view';
$itemId   = isset($_POST['id']) ? (int)$_POST['id'] : null;
$newQty   = isset($_POST['qty']) ? (int)$_POST['qty'] : null;
$options  = isset($_POST['options']) ? json_decode($_POST['options'], true) : [];

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'restaurant_id' => null,
        'items' => []
    ];
}

function fetchMenuItem(PDO $pdo, int $itemId) {
    $sql = "SELECT item_id, restaurant_id, name, price FROM menu_items WHERE item_id = ? AND is_available = 1";
    $st = $pdo->prepare($sql);
    $st->execute([$itemId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function fetchOptionDetails(PDO $pdo, $optionId, $valueId) {
    $sql = "SELECT mo.option_name, ovo.value_name, ovo.price_modifier 
            FROM menu_options mo 
            JOIN option_values ovo ON mo.option_id = ovo.option_id 
            WHERE mo.option_id = ? AND ovo.value_id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$optionId, $valueId]);
    return $st->fetch(PDO::FETCH_ASSOC);
}

switch ($action) {
    case 'add_with_qty':
        if (!$itemId || !$newQty || $newQty <= 0) break;
        $item = fetchMenuItem($pdo, $itemId);
        if (!$item) break;

        $currentRes = $_SESSION['cart']['restaurant_id'];
        if ($currentRes !== null && $currentRes != $item['restaurant_id']) {
            // simple behavior: clear existing cart if user chooses a different restaurant
            $_SESSION['cart']['items'] = [];
            $_SESSION['cart']['restaurant_id'] = $item['restaurant_id'];
        }
        if ($currentRes === null) {
            $_SESSION['cart']['restaurant_id'] = $item['restaurant_id'];
        }

        // Calculate additional price from options
        $optionPrice = 0;
        $optionDetails = [];
        
        foreach ($options as $optionId => $selectedOptions) {
            foreach ($selectedOptions as $opt) {
                $optionInfo = fetchOptionDetails($pdo, $optionId, $opt['value_id']);
                if ($optionInfo) {
                    $optionPrice += $optionInfo['price_modifier'];
                    $optionDetails[] = [
                        'option_id' => $optionId,
                        'option_name' => $optionInfo['option_name'],
                        'value_id' => $opt['value_id'],
                        'value_name' => $opt['value_name'],
                        'price_modifier' => $optionInfo['price_modifier']
                    ];
                }
            }
        }

        // Create a unique key that includes the item ID and selected options
        $optionsKey = md5($itemId . json_encode($optionDetails));
        
        if (isset($_SESSION['cart']['items'][$optionsKey])) {
            $_SESSION['cart']['items'][$optionsKey]['qty'] += $newQty;
        } else {
            $_SESSION['cart']['items'][$optionsKey] = [
                'id' => (int)$item['item_id'],
                'name' => $item['name'],
                'base_price' => (float)$item['price'],
                'price' => (float)$item['price'] + $optionPrice,
                'qty' => $newQty,
                'restaurant_id' => (int)$item['restaurant_id'],
                'options' => $optionDetails,
                'options_key' => $optionsKey
            ];
        }
        break;

    case 'update_qty':
        $itemKey = isset($_POST['key']) ? $_POST['key'] : null;
        if ($itemKey === null || $newQty === null) break;
        if (isset($_SESSION['cart']['items'][$itemKey])) {
            if ($newQty <= 0) {
                unset($_SESSION['cart']['items'][$itemKey]);
                if (empty($_SESSION['cart']['items'])) {
                    $_SESSION['cart']['restaurant_id'] = null;
                }
            } else {
                $_SESSION['cart']['items'][$itemKey]['qty'] = $newQty;
            }
        }
        break;

    case 'remove':
        $itemKey = isset($_POST['key']) ? $_POST['key'] : null;
        if ($itemKey !== null && isset($_SESSION['cart']['items'][$itemKey])) {
            unset($_SESSION['cart']['items'][$itemKey]);
            if (empty($_SESSION['cart']['items'])) {
                $_SESSION['cart']['restaurant_id'] = null;
            }
        }
        break;

    case 'clear':
        $_SESSION['cart'] = ['restaurant_id' => null, 'items' => []];
        break;

    case 'view':
    default:
        // nothing to change, just render below
        break;
}

function renderCartHtml(array $cart) {
    $items = $cart['items'];
    $count = 0;
    foreach ($items as $it) {
        $count += (int)$it['qty'];
    }

    if (empty($items)) {
        echo "<div class='cart-box'><h3>Your Cart</h3><p>Your cart is empty.</p></div>";
        echo "<div id='cart-item-count' style='display:none;'>0</div>";
        return;
    }

    $subtotal = 0;
    foreach ($items as $it) {
        $subtotal += $it['price'] * $it['qty'];
    }

    $deliveryFee = 1500;
    $total = $subtotal + $deliveryFee;

    echo "<div class='cart-box'>";
    echo "<h2>Your Items</h2>";
    echo "<ul class='cart-list'>";
    foreach ($items as $key => $it) {
        $lineTotal = $it['price'] * $it['qty'];
        $name = htmlspecialchars($it['name']);
        $id = (int)$it['id'];
        $qty = (int)$it['qty'];

        echo "<li class='cart-line'>";
        echo "<div class='cart-line-main'>";
        echo "<span class='item-name'>{$name}</span>";
        
        // Display options if any
        if (!empty($it['options'])) {
            echo "<div class='item-options'>";
            foreach ($it['options'] as $option) {
                $modifier = $option['price_modifier'] > 0 ? " (+" . number_format($option['price_modifier']) . " MMK)" : "";
                echo "<div class='option-line'>{$option['value_name']}{$modifier}</div>";
            }
            echo "</div>";
        }
        
        echo "</div>";
        echo "<div class='cart-line-ctrl'>";
        echo "<button class='qty-btn' onclick='updateQty(\"{$key}\", " . ($qty - 1) . ")'>-</button>";
        echo "<span class='qty'>{$qty}</span>";
        echo "<button class='qty-btn' onclick='updateQty(\"{$key}\", " . ($qty + 1) . ")'>+</button>";
        echo "<span class='line-total'>" . number_format($lineTotal, 0) . " MMK</span>";
        echo "<button class='remove-btn' onclick='removeFromCart(\"{$key}\")' title='Remove'>×</button>";
        echo "</div>";
        echo "</li>";
    }
    echo "</ul>";

    echo "<div class='cart-summary'>
            <div class='subtotal'><span>Subtotal</span><strong>" . number_format($subtotal, 0) . " MMK</strong></div>
            <div class='delivery'><span>Delivery Fee</span><strong>" . number_format($deliveryFee, 0) . " MMK</strong></div>
            <div class='total'><span>Total</span><strong>" . number_format($total, 0) . " MMK</strong></div>
          </div>
          <a href='checkout.php' class='review'>Review payment and address</a>";
    echo "</div>";

    // hidden count for JS
    echo "<div id='cart-item-count' style='display:none;'>" . intval($count) . "</div>";
}

renderCartHtml($_SESSION['cart']);