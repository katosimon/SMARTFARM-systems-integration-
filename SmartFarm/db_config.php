<?php
// WAMP or XAMP Database Configuration
$host = 'localhost';
$db   = 'farm'; // this is the new database name
$user = 'root';       
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Shows the errors clearly
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Returns data as arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // for Security
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Connection failed: " . $e->getMessage());
}
?>
