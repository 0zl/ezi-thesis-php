<?php
/**
 * API Endpoint: Menyimpan hasil pemeriksaan gizi.
 * Menerima data melalui POST JSON.
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_auth();

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? [];

    $required = ['nik', 'nama', 'dob', 'gender', 'weight', 'height', 'measure_mode', 'age_months'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required."]);
            exit;
        }
    }

    $nik = trim($input['nik']);
    if (!preg_match('/^\d{16}$/', $nik)) {
        http_response_code(400);
        echo json_encode(['error' => 'NIK harus 16 digit angka.']);
        exit;
    }

    $nama = trim($input['nama']);
    $dob = trim($input['dob']);
    
    // Petakan jenis kelamin ke L/P
    $gender_raw = trim($input['gender']);
    $gender = ($gender_raw === 'Laki-laki' || $gender_raw === 'L') ? 'L' : 'P';
    
    // Petakan mode pengukuran
    $mode_raw = trim($input['measure_mode']);
    $mode = ($mode_raw === 'Berdiri' || $mode_raw === 'standing') ? 'standing' : 'recumbent';
    
    $weight = (float) $input['weight'];
    $height = (float) $input['height'];
    $age_months = (int) $input['age_months'];

    // Ambil nilai z_scores
    $z_bb_u = null;
    $z_tb_u = null;
    $z_bb_tb = null;

    if (isset($input['z_scores']) && is_array($input['z_scores'])) {
        foreach ($input['z_scores'] as $zs) {
            if (str_starts_with($zs['index'] ?? '', 'BB/U')) $z_bb_u = $zs['value'];
            if (str_starts_with($zs['index'] ?? '', 'TB/U')) $z_tb_u = $zs['value'];
            if (str_starts_with($zs['index'] ?? '', 'BB/TB')) $z_bb_tb = $zs['value'];
        }
    }

    $fuzzy_score = isset($input['fuzzy_score']) && $input['fuzzy_score'] !== null ? (float) $input['fuzzy_score'] : null;
    $fuzzy_label = $input['fuzzy_label'] ?? null;
    $recommendation = $input['recommendation'] ?? null;

    $pdo->beginTransaction();

    // 1. Simpan atau perbarui data balita berdasarkan NIK
    $stmt_balita = $pdo->prepare("
        INSERT INTO balita (nik, nama, dob, gender) 
        VALUES (:nik, :nama, :dob, :gender)
        ON DUPLICATE KEY UPDATE 
            nama = VALUES(nama), 
            dob = VALUES(dob), 
            gender = VALUES(gender)
    ");
    
    $stmt_balita->execute([
        ':nik' => $nik,
        ':nama' => $nama,
        ':dob' => $dob,
        ':gender' => $gender
    ]);

    // 2. Simpan Data Pemeriksaan
    $stmt_pem = $pdo->prepare("
        INSERT INTO pemeriksaan 
        (balita_nik, weight, height, measure_mode, age_months, z_bb_u, z_tb_u, z_bb_tb, fuzzy_score, fuzzy_label, recommendation)
        VALUES 
        (:nik, :w, :h, :mode, :age, :z_bb_u, :z_tb_u, :z_bb_tb, :fs, :fl, :rec)
    ");

    $stmt_pem->execute([
        ':nik' => $nik,
        ':w' => $weight,
        ':h' => $height,
        ':mode' => $mode,
        ':age' => $age_months,
        ':z_bb_u' => $z_bb_u,
        ':z_tb_u' => $z_tb_u,
        ':z_bb_tb' => $z_bb_tb,
        ':fs' => $fuzzy_score,
        ':fl' => $fuzzy_label,
        ':rec' => $recommendation
    ]);

    $inserted_id = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Data pemeriksaan berhasil disimpan',
        'pemeriksaan_id' => $inserted_id
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
