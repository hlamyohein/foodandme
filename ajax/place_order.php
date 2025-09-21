<?php
session_start();
require_once 'config/db.php';

if(!isset($_POST['item_id'])){
    echo "No item selected.";
    exit;
}

$item_id = intval($_POST['item_id']);

// Initialize cart if not exists
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

// Check if item already in cart
if(isset($_SESSION['cart'][$item_id])){
    $_SESSION['cart'][$item_id] += 1; // increment quantity
} else {
    $_SESSION['cart'][$item_id] = 1; // first time
}

echo "Item added to cart!";
