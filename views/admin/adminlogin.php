<?php
session_start();
require_once "../../config/db.php"; // Adjust path if needed

$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Safely get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($email && $password) {
        // Prepare query to check email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify user and hashed password
        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'admin') {
                // Save session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Access denied. Admins only.";
            }
        } else {
            $message = "Invalid email or password.";
        }
    } else {
        $message = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5; }
        form { background: #fff; border: 1px solid #ccc; padding: 20px; border-radius: 10px; width: 300px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; border-radius: 5px; border: none; background-color: #007BFF; color: #fff; font-size: 16px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .msg { color: red; margin-top: 10px; text-align: center; }
        h2 { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>

<form method="post">
    <h2>Admin Login</h2>

    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <button type="submit">Login</button>

    <?php if ($message): ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
</form>

</body>
</html>
