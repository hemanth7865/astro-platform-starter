<?php
// Run this once to create the tables in your database
require_once 'config.php';

try {
    // Users table: id, name, email, password, role (dancer, choreographer, admin)
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('dancer','choreographer','admin') NOT NULL
    )");

    // Competitions table: id, name, date, location, entry_fee, status (open/closed)
    $pdo->exec("CREATE TABLE IF NOT EXISTS competitions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        date DATE NOT NULL,
        location VARCHAR(100) NOT NULL,
        entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('open','closed') NOT NULL DEFAULT 'open'
    )");

    // Participants table: linking dancers to competitions
    // payment_status can be 'pending' or 'paid'
    $pdo->exec("CREATE TABLE IF NOT EXISTS participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competition_id INT NOT NULL,
        user_id INT NOT NULL,
        payment_status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
        FOREIGN KEY (competition_id) REFERENCES competitions(id),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Results table: choreographers judge the participants
    // score from 0-100, plus optional feedback
    $pdo->exec("CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        competition_id INT NOT NULL,
        participant_id INT NOT NULL,
        choreographer_id INT NOT NULL,
        score INT NOT NULL,
        feedback TEXT,
        FOREIGN KEY (competition_id) REFERENCES competitions(id),
        FOREIGN KEY (participant_id) REFERENCES participants(id),
        FOREIGN KEY (choreographer_id) REFERENCES users(id)
    )");

    echo "Tables created successfully!";
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
