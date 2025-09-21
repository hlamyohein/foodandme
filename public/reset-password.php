<?php
session_start();
include("config/db.php");
include("functions.php"); // sendSMS() / sendResetEmail()

$message = "";
$showForm = false;
$email_or_phone = "";

// ---------------------------
// 1. USE UTC FOR EVERYTHING
// ---------------------------
date_default_timezone_set('UTC');        // PHP in UTC
$conn->query("SET time_zone = '+00:00'"); // MySQL in UTC

// ---------------------------
// 2. IDENTIFIER DETECTION
// ---------------------------
if (isset($_SESSION['email']) || isset($_SESSION['phone'])) {
    $email_or_phone = $_SESSION['email'] ?? $_SESSION['phone'];
    $showForm = true;
}

// ---------------------------
// 3. HANDLE PASSWORD RESET
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['password'], $_POST['confirm'], $_POST['identifier'])) {
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);
    $identifier = trim($_POST['identifier']);

    if (strlen($password) < 6) {
        $message = "<div class='notice error'>Password must be at least 6 characters long.</div>";
        $showForm = true;
    } elseif ($password !== $confirm) {
        $message = "<div class='notice error'>Passwords do not match.</div>";
        $showForm = true;
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $conn->prepare("
            UPDATE users 
            SET password=? 
            WHERE email=? OR phone=?
        ");
        $update->bind_param("sss", $hash, $identifier, $identifier);

        if ($update->execute()) {
            unset($_SESSION['email'], $_SESSION['phone']);
            $message = "<div class='notice success'>
                            Password reset successfully.<br>
                            <a href='login.php' class='btn-link'>← Back to Login</a>
                        </div>";
            $showForm = false;
        } else {
            $message = "<div class='notice error'>Failed to reset password. Try again.</div>";
            $showForm = true;
        }
    }
}

// ---------------------------
// 4. RESEND OTP FOR PHONE (UI ONLY)
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['resend_otp'])) {
    if (isset($_SESSION['phone'])) {
        $phone = $_SESSION['phone'];
        $otp = rand(100000, 999999);
        sendSMS($phone, $otp); // simulate sending OTP
        $_SESSION['otp_time'] = time();
        $message = "<div class='notice success'>New OTP sent to your phone: <b>$phone</b>.</div>";
        $showForm = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reset Password</title>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
<style>
.auth-box { max-width: 400px; margin:50px auto; padding:30px; background: rgba(255,255,255,0.1); border-radius: 12px; text-align:center; }
input { width:100%; padding:10px; margin:8px 0; border-radius:5px; border:1px solid #ccc; }
button { padding:10px 20px; border:none; border-radius:5px; background:#00e6bf; color:#fff; cursor:pointer; }
.notice { padding:10px; margin:10px 0; border-radius:8px; }
.success { background:#d4f8e8; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
</style>
</head>
<body class="auth-page">

<div class="auth-box">
    <h2>Reset Password</h2>
    <p>Set your new password below</p>

    <?php if (!empty($message)) echo $message; ?>

    <?php if ($showForm): ?>
        <form method="POST" class="auth-form">
            <input type="password" name="password" placeholder="New Password" required>
            <input type="password" name="confirm" placeholder="Confirm Password" required>
            <input type="hidden" name="identifier" value="<?php echo htmlspecialchars($email_or_phone); ?>">
            <button type="submit" class="btn-primary">Reset Password</button>
        </form>

        <?php if (isset($_SESSION['phone'])): ?>
        <form method="POST" style="margin-top:10px;">
            <button type="submit" class="btn-primary" name="resend_otp">Resend OTP</button>
        </form>
        <p style="margin-top:8px; font-size:13px; color:#ddd;">OTP is valid for 10 minutes.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
