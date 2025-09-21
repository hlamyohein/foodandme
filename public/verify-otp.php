<?php
session_start();
include("config/db.php");
include("functions.php"); // sendSMS() / sendResetEmail()

// ---------------------------
// 1. USE UTC FOR EVERYTHING
// ---------------------------
date_default_timezone_set('UTC'); // PHP in UTC
$conn->query("SET time_zone = '+00:00'"); // MySQL in UTC

$message = "";
$email = $_SESSION['email'] ?? null;
$phone = $_SESSION['phone'] ?? null;

// Ensure identifier exists
if (!$email && !$phone) {
    echo "No verification request found.";
    exit;
}

// Initialize OTP time if not set
if (!isset($_SESSION['otp_time'])) {
    $_SESSION['otp_time'] = time();
}

// ---------------------------
// 2. HANDLE OTP VERIFICATION
// ---------------------------
if (isset($_POST['verify'])) {
    $otp = trim($_POST['otp']);
    $storedOtp = $_SESSION['otp'] ?? '';
    $otpTime = $_SESSION['otp_time'] ?? 0;

    if (!$storedOtp) {
        $message = "<div class='notice error'>No OTP found. Please resend.</div>";
    } elseif ((time() - $otpTime) > 60) { // OTP valid for 1 minute
        unset($_SESSION['otp'], $_SESSION['otp_time']);
        $message = "<div class='notice error'>OTP expired. Please request a new one.</div>";
    } elseif ((string)$otp === (string)$storedOtp) {
        // OTP verified
        if ($email) {
            $update = $conn->prepare("UPDATE users SET is_verified=1 WHERE email=?");
            $update->bind_param("s", $email);
        } else {
            $update = $conn->prepare("UPDATE users SET is_verified=1 WHERE phone=?");
            $update->bind_param("s", $phone);
        }
        $update->execute();

        unset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['email'], $_SESSION['phone']);
        header("Location: register-success.php");
        exit;
    } else {
        $message = "<div class='notice error'>Invalid OTP.</div>";
    }
}

// ---------------------------
// 3. RESEND OTP
// ---------------------------
if (isset($_POST['resend'])) {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time(); // store generation time

    if ($email) {
        sendOTP($email, "User", $otp);
    } else {
        sendSMS($phone, $otp);
    }

    $message = "<div class='notice success'>New OTP sent to your " . ($email ? "email" : "phone") . ".</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<link rel="stylesheet" href="style.css">
<style>
.notice { padding:10px; margin:10px 0; border-radius:8px; }
.success { background:#d4f8e8; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
.btn-primary { padding:10px 20px; background:#00e6bf; border:none; border-radius:5px; color:#fff; cursor:pointer; }
input { padding:10px; margin:5px 0; width:100%; }
.verify-box { background: rgba(0,0,0,0.7); padding: 30px 40px; border-radius: 15px; text-align: center; width: 350px; color:#fff; }
.note { font-size:13px; color:#ddd; margin-top:5px; }
</style>
</head>
<body class="verify-page">
<div class="verify-box">
    <h2>OTP Verification</h2>
    <p>We’ve sent a code to your <?php echo $email ? "email <b>$email</b>" : "phone <b>$phone</b>"; ?>.</p>

    <?php if (!empty($message)) echo $message; ?>

    <!-- Verify OTP Form -->
    <form method="POST">
        <input type="text" name="otp" placeholder="Enter OTP" required>
        <button type="submit" name="verify" class="btn-primary">Verify</button>
    </form>

    <!-- Resend OTP Form -->
    <form method="POST" style="margin-top:10px;">
        <button type="submit" name="resend" class="btn-primary">Resend OTP</button>
    </form>
    <p class="note">OTP is valid for 1 minute.</p>
</div>
</body>
</html>
