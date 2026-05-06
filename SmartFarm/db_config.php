<?php
// WAMP Database Configuration
$host = 'localhost';
$db   = 'farm'; // Ensure this matches your phpMyAdmin DB name
$user = 'root';        // Default WAMP username
$pass = '';            // Default WAMP password (empty)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Shows errors clearly
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns data as arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Security feature
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}
?>
