<?php
/**
 * API Endpoint: Menganalisa status gizi.
 * Menerima data melalui POST, mengembalikan respons JSON.
 */
header('Content-Type: application/json; charset=utf-8');

// Hanya izinkan POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../includes/nutrition_utils.php';
require_once __DIR__ . '/../includes/fuzzy_logic.php';

try {
    // Ambil input (mendukung form data dan JSON body)
    $input = $_POST;
    if (empty($input)) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
    }

    // Validasi field yang wajib diisi
    $required = ['nama', 'dob', 'gender', 'weight', 'height', 'measure_mode'];
    foreach ($required as $field) {
        if (empty($input[$field]) && $input[$field] !== '0') {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required."]);
            exit;
        }
    }

    $nama         = trim($input['nama']);
    $dob          = trim($input['dob']);
    $gender_label = trim($input['gender']);
    $weight       = (float) $input['weight'];
    $height       = (float) $input['height'];
    $mode_label   = trim($input['measure_mode']);

    // Pemetaan label ke kode
    $gender_code = ($gender_label === 'Laki-laki') ? 'L' : 'P';
    $mode_code   = ($mode_label === 'Berdiri') ? 'standing' : 'recumbent';

    // Validasi rentang nilai
    if ($height > 200 || $height < 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Tinggi badan tidak valid (Range: 10cm - 200cm).']);
        exit;
    }
    if ($weight > 100 || $weight < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Berat badan tidak valid (Range: 1kg - 100kg).']);
        exit;
    }

    // 1. Hitung Z-scores
    $z_results = get_z_scores($gender_code, $dob, $weight, $height, $mode_code);

    $age_months       = $z_results['age_months'];
    $corrected_height = $z_results['corrected_height'];
    $z_bb_u           = $z_results['z_bb_u'];
    $z_tb_u           = $z_results['z_tb_u'];
    $z_bb_tb          = $z_results['z_bb_tb'];

    // Periksa apakah data di luar jangkauan standar
    if ($z_bb_u === null || $z_tb_u === null || $z_bb_tb === null) {
        echo json_encode([
            'success'          => true,
            'age_months'       => $age_months,
            'corrected_height' => $corrected_height,
            'z_scores'         => [],
            'status'           => 'Tidak Dapat Dianalisa',
            'recommendation'   => 'Data diluar jangkauan standar.',
            'fuzzy_score'      => null,
            'fuzzy_label'      => 'Tidak Dapat Dianalisa',
        ]);
        exit;
    }

    // 2. Inferensi Fuzzy
    $fuzzy_result = fuzzy_predict($z_bb_u, $z_tb_u, $z_bb_tb);
    $fuzzy_score = $fuzzy_result['score'];
    $fuzzy_label = $fuzzy_result['label'];

    // 3. Rekomendasi
    $rekomendasi = '';
    if (str_starts_with($fuzzy_label, 'Gizi Buruk')) {
        $rekomendasi = 'SEGERA RUJUK KE PUSKESMAS/RS. Perlu penanganan medis segera.';
    } elseif ($fuzzy_label === 'Gizi Kurang') {
        $rekomendasi = 'Perlu Pemberian Makanan Tambahan (PMT) pemulihan dan konseling gizi rutin.';
    } elseif ($fuzzy_label === 'Gizi Baik') {
        $rekomendasi = 'Pertahankan pola asuh dan pola makan yang baik. Pantau pertumbuhan di posyandu setiap bulan.';
    } elseif (str_starts_with($fuzzy_label, 'Gizi Lebih')) {
        $rekomendasi = 'Konsultasikan diet seimbang. Kurangi makanan manis/berlemak, tingkatkan aktivitas fisik.';
    }

    // 4. Data grafik pertumbuhan
    $chart_tb_u  = get_growth_chart_data($gender_code);
    $chart_bb_u  = get_weight_chart_data($gender_code);
    $chart_bb_tb = get_wfh_chart_data($gender_code, $mode_code);

    // 5. Susun respons
    echo json_encode([
        'success'          => true,
        'nama'             => $nama,
        'age_months'       => $age_months,
        'corrected_height' => $corrected_height,
        'z_scores'         => [
            ['index' => 'BB/U (Berat/Umur)',   'value' => $z_bb_u,  'info' => 'Indikator Berat Badan'],
            ['index' => 'TB/U (Tinggi/Umur)',  'value' => $z_tb_u,  'info' => 'Indikator Stunting'],
            ['index' => 'BB/TB (Berat/Tinggi)','value' => $z_bb_tb, 'info' => 'Indikator Wasting'],
        ],
        'fuzzy_score'      => $fuzzy_score,
        'fuzzy_label'      => $fuzzy_label,
        'status'           => "$fuzzy_label ($fuzzy_score/100)",
        'recommendation'   => $rekomendasi,
        'charts'           => [
            'tb_u'  => $chart_tb_u,
            'bb_u'  => $chart_bb_u,
            'bb_tb' => $chart_bb_tb,
        ],
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
