 <?php
$host = '127.0.0.1';
$dbname = 'foodapp';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_errno) {
    die("MySQLi connection failed: " . $conn->connect_error);
}
$conn->set_charset($charset); 
