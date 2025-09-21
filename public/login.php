<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login | Food&Me</title>
<link rel="stylesheet" href="style.css"> <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
    /* Add some basic styles for the error message */
    .error-message {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
        color: #a94442;
        background-color: #f2dede;
        border-color: #ebccd1;
        text-align: center;
    }
</style>
</head>
<body class="login-page">
    <form class="container" action="login_process.php" method="POST">
        <h1 class="login-title">Login to Your Account</h1>
        
        <?php
        if (isset($_SESSION['error'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']); // Clear the error message after displaying it
        }
        ?>

        <section class="input-box">
            <input type="text" name="email_or_phone" placeholder="Email or Phone Number" required>
            <i class='bx bxs-user'></i>
        </section>

        <section class="input-box">
            <input type="password" name="pwd" id="pwd" placeholder="Password" required>
            <i class='bx bxs-lock-alt'></i>
        </section>

        <section class="showPass-forgot-box">
            <div class="showPass">
                <input type="checkbox" id="showPass"> <label for="showPass" style="cursor:pointer;">Show Password</label>
            </div>
            <a class="forgot-password" href="forgot-password.php">
                Forgot password?
            </a>
        </section>

        <button class="login-button" type="submit">Login</button>

        <h5 class="dont-have-an-account">
            Don't have an account?
            <a href="registration.php"><b>Register</b></a>
        </h5>
         <h5 class="dont-have-an-account">
            Want to be a Partner?
            <a href="partners.php"><b>Join Us</b></a>
        </h5>
    </form>

<script>
    const showPassCheckbox = document.getElementById('showPass');
    showPassCheckbox.addEventListener('change', function() {
        const pwdInput = document.getElementById('pwd');
        pwdInput.type = this.checked ? 'text' : 'password';
    });
</script>
</body>
</html>