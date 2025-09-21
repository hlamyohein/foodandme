<?php
session_start();
session_destroy();
header('Location: /foodandme/index.php');
exit;
?>