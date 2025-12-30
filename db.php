<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ejust_notes';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create tables
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    program VARCHAR(10) NOT NULL,
    level INT NOT NULL,
    enrollmentYear INT NOT NULL,
    profilePicture TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    courseCode VARCHAR(20) NOT NULL,
    description TEXT,
    fileName VARCHAR(255) NOT NULL,
    filePath VARCHAR(255) NOT NULL,
    uploaderId INT NOT NULL,
    uploaderName VARCHAR(100) NOT NULL,
    uploadDate DATE DEFAULT (CURRENT_DATE),
    FOREIGN KEY (uploaderId) REFERENCES users(id) ON DELETE CASCADE
)");
?>