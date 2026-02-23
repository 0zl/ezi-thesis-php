<?php

try {
    // 1. Coba koneksi langsung ke database (Untuk cPanel / Hosting)
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // Jika gagal karena database belum ada (misal di local XAMPP), coba buat databasenya
        if ($e->getCode() == 1049) { // 1049 is Unknown database
            $pdo_server = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Koneksi ulang ke database yang baru dibuat
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            // Error lain (kredensial salah, host mati, dll)
            throw $e;
        }
    }

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Buat tabel otomatis jika belum ada
    
    // Tabel: balita
    $sql_balita = "CREATE TABLE IF NOT EXISTS `balita` (
        `nik` VARCHAR(16) PRIMARY KEY,
        `nama` VARCHAR(100) NOT NULL,
        `dob` DATE NOT NULL,
        `gender` ENUM('L', 'P') NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql_balita);

    // Tabel: pemeriksaan
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

    die("Database Connection / Setup Failed: " . $e->getMessage());
}
