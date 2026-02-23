<?php
/**
 * API Endpoint: Menghapus data balita beserta riwayat pemeriksaannya.
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

    if (empty($input['nik'])) {
        http_response_code(400);
        echo json_encode(['error' => 'NIK wajib diisi.']);
        exit;
    }

    $nik = trim($input['nik']);

    // Mulai transaksi
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM balita WHERE nik = :nik");
    $stmt->execute([':nik' => $nik]);


    $deleted = $stmt->rowCount();

    $pdo->commit();

    if ($deleted > 0) {
        echo json_encode(['success' => true, 'message' => 'Data balita dan riwayat berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data balita tidak ditemukan.']);
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
