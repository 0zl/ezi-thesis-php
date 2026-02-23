<?php
// includes/db.php

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'posyandu_db';

try {
    // Connect to MySQL server first to check/create the database
    $pdo_server = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if it doesn't exist
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Fetch objects by default can be useful, but let's stick to ASSOC for standard arrays
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-create tables if they don't exist
    
    // Table: balita
    $sql_balita = "CREATE TABLE IF NOT EXISTS `balita` (
        `nik` VARCHAR(16) PRIMARY KEY,
        `nama` VARCHAR(100) NOT NULL,
        `dob` DATE NOT NULL,
        `gender` ENUM('L', 'P') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_balita);

    // Table: pemeriksaan
    $sql_pemeriksaan = "CREATE TABLE IF NOT EXISTS `pemeriksaan` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `balita_nik` VARCHAR(16) NOT NULL,
        `weight` FLOAT NOT NULL,
        `height` FLOAT NOT NULL,
        `measure_mode` ENUM('standing', 'recumbent') NOT NULL,
        `age_months` INT NOT NULL,
        `z_bb_u` FLOAT,
        `z_tb_u` FLOAT,
        `z_bb_tb` FLOAT,
        `fuzzy_score` FLOAT,
        `fuzzy_label` VARCHAR(50),
        `recommendation` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`balita_nik`) REFERENCES `balita`(`nik`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_pemeriksaan);

} catch (PDOException $e) {
    // In a real production app, log this error instead of echoing it directly.
    die("Database Connection / Setup Failed: " . $e->getMessage());
}
