<?php
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ================= CONFIG =================
define('GMAIL_USER', 'shadyjames49@gmail.com'); 
define('GMAIL_APP_PASSWORD', 'cofrhylpnlmoivnm');

// Twilio config
define('TWILIO_SID', 'AC3eceeb455a19f4599478246e9a249a60');
define('TWILIO_TOKEN', 'dfc3fe9573ad58a1a064da1a6ac43238');
define('TWILIO_NUMBER', '+959674894474');

// ---------------- PHPMailer Setup ----------------
function getMailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = GMAIL_USER;
    $mail->Password   = GMAIL_APP_PASSWORD;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->setFrom(GMAIL_USER, 'Food Delivery Service');
    return $mail;
}

// ---------------- SEND EMAIL OTP ----------------
function sendOTP($toEmail, $toName, $otp) {
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification OTP';
        $mail->Body = "
            <p>Hello <b>{$toName}</b>,</p>
            <p>Your OTP for verification is:</p>
            <h2 style='color:#00e6bf;'>{$otp}</h2>
            <p>If you didn’t request this, ignore this email.</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// ---------------- SEND SMS OTP VIA TWILIO ----------------
function sendSMS($toPhone, $otp) {
    $url = "https://api.twilio.com/2010-04-01/Accounts/".TWILIO_SID."/Messages.json";

    $data = [
        "From" => TWILIO_NUMBER,
        "To"   => $toPhone, 
        "Body" => "Your OTP code is: {$otp}"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_SID.":".TWILIO_TOKEN);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("SMS OTP sent to {$toPhone}: {$otp}");
        return true;
    } else {
        error_log("Twilio SMS Error ({$httpCode}): " . $response);
        return false;
    }
}

// ---------------- SEND RESET LINK EMAIL ----------------
function sendResetLink($toEmail, $toName) {
    try {
        $mail = getMailer();
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request';

        // Direct link to reset password page without token
        $link = "http://localhost/foodandme/reset-password.php";

        $mail->Body = "
            <p>Hello <b>{$toName}</b>,</p>
            <p>You requested to reset your password. Click the link below to set a new password:</p>
            <a href='{$link}'>Reset Password</a>
            <p>If you didn’t request this, ignore this email.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reset Link Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
