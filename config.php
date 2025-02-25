<?php
// Update with your own DB credentials
$host = 'localhost';
$db   = 'dance_competition_db';
$user = 'root';
$pass = '';

// Create a new PDO connection (recommended)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    // Set error mode for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
