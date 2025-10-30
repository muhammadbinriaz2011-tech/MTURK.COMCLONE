<?php
// Database configuration
$host = 'localhost'; // Update to your actual DB host if needed
$db = 'dbaognfqzuem2o';
$user = 'ukrfhh29eellf';
$pass = 'jua2ursxz7gb';
 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
