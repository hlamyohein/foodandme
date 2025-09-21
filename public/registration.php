

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("config/db.php");
include("functions.php");

if (isset($_POST['register'])) {
    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $pass    = $_POST['pwd'];
    $re_pass = $_POST['cpwd'];

    if (strlen($pass) < 6) {
        echo " Password must be at least 6 characters!";
        exit();
    }

    if ($pass !== $re_pass) {
        echo " Passwords do not match!";
        exit();
    }

    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
    $otp = rand(100000, 999999); // 6-digit OTP

    $check = $pdo->prepare("SELECT * FROM users WHERE email=? OR phone=?");
    $check->execute([$email, $phone]);
    $result = $check->fetch();

    if ($result) {
        echo " Email or Phone already registered!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)");

        if ($stmt->execute([$name, $email, $phone, $hashed_pass])) {
            if (sendOTP($email, $name, $otp)) {
                $_SESSION['email'] = $email;
                $_SESSION['otp'] = $otp;
                header("Location: verify-otp.php");
                exit;
            } else {
                echo "<p style='color: red;'> Failed to send email.</p>";
            }
        } else {
            echo " Error: " . $stmt->errorInfo()[2];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Food Delivery Service Registration</title>
<link rel="stylesheet" href="style.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class="registration-page">
    <form method="POST" class="container">
        <h1 class="register-title">Register Your Account</h1>

        <section class="input-box">
            <input type="text" name="name" placeholder="Full Name" required>
            <i class='bx bxs-user'></i>
        </section>

        <section class="input-box">
            <input type="email" name="email" placeholder="Email" required>
            <i class='bx bxs-envelope'></i>
        </section>

        <section class="input-box">
            <input type="text" name="phone" placeholder="Phone Number" required>
            <i class='bx bxs-phone'></i>
        </section>

        <section class="input-box">
            <input type="password" name="pwd" id="pwd" placeholder="Password" required>
            <i class='bx bxs-lock-alt'></i>
        </section>

        <section class="input-box">
            <input type="password" name="cpwd" id="cpwd" placeholder="Confirm Password" required>
            <i class='bx bxs-lock-alt'></i>
        </section>

        <section class="showPass-forgot-box">
            <div class="showPass">
                <input type="checkbox" id="showPass"><h4> Show Password</h4>
            </div>
        </section>

        <span id="message" style="margin:15px 0 10px 0; font-size:14px;"></span>
        
        <button class="login-button" type="submit" name="register" id="registerBtn" disabled>
            Register
        </button>

        <script>
            document.getElementById('showPass').addEventListener('change', function() {
                var pwd = document.getElementById('pwd');
                var cpwd = document.getElementById('cpwd');
                var type = this.checked ? 'text' : 'password';
                pwd.type = type;
                cpwd.type = type;
            });

            var password = document.getElementById('pwd');
            var confirm_password = document.getElementById('cpwd');
            var message = document.getElementById('message');
            var registerBtn = document.getElementById('registerBtn');

            function checkPasswordMatch() {
                if (password.value === "" && confirm_password.value === "") {
                    message.textContent = "";
                    registerBtn.disabled = true;
                    return;
                }

                if (password.value.length < 6) {
                    message.style.color = 'red';
                    message.textContent = " Password must be at least 6 characters!";
                    registerBtn.disabled = true;
                    return;
                }

                if (password.value === confirm_password.value) {
                    message.style.color = 'green';
                    message.textContent = " Passwords match.";
                    registerBtn.disabled = false;
                } else {
                    message.style.color = 'red';
                    message.textContent = " Passwords do not match!";
                    registerBtn.disabled = true;
                }
            }

            password.addEventListener('keyup', checkPasswordMatch);
            confirm_password.addEventListener('keyup', checkPasswordMatch);
        </script>
    </form>
</body>
</html>
