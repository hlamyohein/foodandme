<?php

$host = "127.0.0.1";
$user = "root"; // Replace with your actual database username
$pass = ""; // Replace with your actual database password
$db   = "foodapp";

$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error){
    die("Connection failed! " . $conn->connect_error);
}
?>