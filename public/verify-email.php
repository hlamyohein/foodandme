<?php
session_start();
include("config.php");

if (!isset($_SESSION['email'])) {
    echo "No email to verify.";
    exit;
}

$email = $_SESSION['email'];

if (isset($_POST['verify'])) {
    $otp   = $_POST['otp'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND verification_code=? AND is_verified=0");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $update = $conn->prepare("UPDATE users SET is_verified=1 WHERE email=?");
        $update->bind_param("s", $email);
        $update->execute();

        unset($_SESSION['email']);

        header("Location: register-success.php");
        exit();
    } else {
        echo "Invalid OTP or already verified.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify Email</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="verify-page">
    <div class="verify-box">
    <h2>Email Verification</h2>
    <p>We’ve sent a 6-digit code to your email.</p>
    <form method="POST" action="">
      <input type="text" name="otp" placeholder="Enter verification code" required>
      <button type="submit" class="verify-btn" name="verify">Verify</button>
    </form>
    <p class="note">Didn’t get the code? <a href="#" style="color:#00e6bf;">Resend</a></p>
  </div>
</body>
</html>
