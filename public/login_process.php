<?php
session_start();
require_once 'config/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_or_phone = trim($_POST['email_or_phone']);
    $pwd = $_POST['pwd'];

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? ");
    $stmt->execute([$email_or_phone]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($pwd, $user['password'])) {
        // Login success
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // Determine the redirection page based on user role
        switch ($user['role']) {
            case 'admin':
                header("Location: views/admin/dashboard.php");
                exit;
                break;
                
            case 'customer':
                header("Location: views/customer/dashboard.php");
                exit;
                break;
                
            case 'vendor':
                // Fetch the restaurant_id for the owner and store it in the session
                $stmt_restaurant = $pdo->prepare("SELECT restaurant_id FROM restaurants WHERE user_id = ?");
                $stmt_restaurant->execute([$user['user_id']]);
                $restaurant = $stmt_restaurant->fetch(PDO::FETCH_ASSOC);

                if ($restaurant) {
                    $_SESSION['restaurant_id'] = $restaurant['restaurant_id'];
                    header("Location: views/restaurant/index.php");
                    exit;
                } else {
                    // Handle case where an owner has no linked restaurant
                    $error = "Your account is not linked to a restaurant.";
                    session_unset();
                    session_destroy();
                }
                break;
                
            case 'delivery':
                header("Location: views/rider/dashboard.php");
                exit;
                break;
                
            default:
                // Default redirection for other roles or general cases
                header("Location: index.php");
                exit;
                break;
        }
    } else {
        // Login failed
        $error = "Invalid email/phone or password.";
    }
}

// If login failed, redirect back to login page with error
if (!empty($error)) {
    $_SESSION['error'] = $error;
    header("Location: index.php");
    exit;
}
?>