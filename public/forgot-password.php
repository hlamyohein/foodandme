<?php
session_start();
include("config/db.php");
include("functions.php"); // sendSMS() / sendOTP() / sendResetLink()

$message = "";

// ---------------------------
// 1. HANDLE FORM SUBMISSION
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier']);

    if (empty($identifier)) {
        $message = "<div class='notice error'> Please enter your email or phone number.</div>";
    } else {
        // ---------------------------
        // 2. CHECK IF USER EXISTS
        // ---------------------------
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE phone=?");
        }
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $name = $user['name'];
            $email = $user['email'];
            $phone = $user['phone'];

            // ---------------------------
            // 3. EMAIL RESET FLOW (No DB token)
            // ---------------------------
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['email'] = $email;
                if (sendResetLink($email, $name)) {
                    $message = "<div class='notice success'>
                                    Reset link sent to your email: <b>$email</b>. Check your inbox.
                                </div>";
                } else {
                    $message = "<div class='notice error'> Failed to send reset link. Try again.</div>";
                }

            } else {
                // ---------------------------
                // 4. PHONE OTP FLOW
                // ---------------------------
                $otp = rand(100000, 999999);
                $_SESSION['phone'] = $phone;
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_time'] = time();

                if (sendSMS($phone, $otp)) {
                    $message = "<div class='notice success'> 
                                    OTP sent to your phone: <b>$phone</b>. Enter it in the next step.
                                </div>";
                } else {
                    $message = "<div class='notice error'> Failed to send OTP. Try again.</div>";
                }
            }

        } else {
            $message = "<div class='notice error'> This account is not registered.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password</title>
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
<h2>Forgot Password</h2>
<p>Enter your registered email or phone number to reset your password.</p>

<?php if (!empty($message)) echo $message; ?>

<form method="POST" class="auth-form">
    <input type="text" name="identifier" placeholder="Email or Phone" required>
    <button type="submit">Send Reset Link / OTP</button>
</form>

</div>
</body>
</html>
