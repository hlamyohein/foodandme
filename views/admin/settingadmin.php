<?php 
session_start();
include "includes/header.php";
//require __DIR__ . '../../config/db.php';
require_once '../../config/db.php';
// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: adminlogin.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Only admin can access
if (!$user || $user['role'] !== 'admin') {
    die("Access denied. Admins only.");
}

// Message for profile
$adminMessage = '';

// ======================
// Handle admin profile form
// ======================
if (isset($_POST['admin_profile'])) {
    $name         = trim($_POST['name']);
    $phone        = trim($_POST['phone']);
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');

    // Update name and phone first
    $stmt = $pdo->prepare("UPDATE users SET name=?, phone=? WHERE user_id=?");
    $stmt->execute([$name, $phone, $user_id]);

    // Handle password change
    if (!empty($new_password)) {
        if (empty($old_password)) {
            $adminMessage = "Please enter your old password to change it.";
        } else {
            $dbPassword = $user['password'];

            // Detect if stored password is plain text (not hashed)
            $isHashed = preg_match('/^\$2y\$/', $dbPassword);

            if (!$isHashed) {
                // Upgrade plain-text password to hashed version
                if ($old_password === $dbPassword) {
                    $dbPassword = password_hash($dbPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
                    $stmt->execute([$dbPassword, $user_id]);
                    $user['password'] = $dbPassword;
                }
            }

            if (!password_verify($old_password, $user['password'])) {
                $adminMessage = "Old password is incorrect.";
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
                $stmt->execute([$new_hash, $user_id]);
                $adminMessage = "Password changed successfully!";
            }
        }
    } else {
        if (empty($adminMessage)) $adminMessage = "Profile updated successfully!";
    }

    // Refresh user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Profile</title>
<style>
body, input, select, textarea, button, label {
    font-family: Arial, sans-serif;
}
body { background: #f5f5f5; padding: 30px; }
.container { max-width: 600px; margin: 0 auto 30px; background: #fff; padding: 30px; border-radius: 15px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
h2 { text-align: center; margin-bottom: 25px; }
label { font-weight: 600; display: block; margin-top: 10px; }
input, select, button { width: 100%; padding: 10px 12px; margin-top: 5px; margin-bottom: 15px; border-radius: 8px; border: 1px solid #ccc; font-size: 16px; }
button { background: #2196f3; color: white; border: none; cursor: pointer; transition: background 0.2s ease; }
button:hover { background: #1976d2; }
.message { text-align: center; padding: 10px; margin-bottom: 20px; border-radius: 8px; }
.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
</head>
<body>

<!-- Admin Profile -->
<div class="container">
<h2>Admin Profile</h2>

<?php if ($adminMessage): ?>
    <div class="message <?= strpos($adminMessage, 'successfully') !== false ? 'success' : 'error' ?>">
        <?= htmlspecialchars($adminMessage) ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="admin_profile" value="1">

    <label>Admin Name</label>
    <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>

    <label>Phone</label>
    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>

    <label>Old Password</label>
    <input type="password" name="old_password" placeholder="Enter old password to change password">

    <label>New Password</label>
    <input type="password" name="new_password" placeholder="Leave blank if not changing">

    <button type="submit">Save Profile</button>
</form>
</div>

</body>
</html>
