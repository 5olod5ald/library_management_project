<?php
$servername = "localhost";
$username = "root";   // أو php_user لو انتي فعلاً عاملها
$password = "";       // فاضي في XAMPP دايمًا
$dbname = "LibraryDB";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
