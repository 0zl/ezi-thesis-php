<?php
/**
 * Utilitas Nutrisi — Perhitungan Z-score mengikuti standar WHO/Kemenkes.
 */

require_once __DIR__ . '/data_loader.php';

/**
 * Hitung usia dalam bulan penuh antara tanggal lahir dan tanggal kunjungan.
 */
function calculate_age_months(string $dob, string $visit_date = null): int {
    $dob_dt = new DateTime($dob);
    $visit_dt = new DateTime($visit_date ?? 'today');
    $diff = $dob_dt->diff($visit_dt);
    return ($diff->y * 12) + $diff->m;
}

/**
 * Koreksi tinggi badan berdasarkan mode pengukuran dan usia (PMK No. 2 2020).
 * - Usia < 24 bulan: jika diukur berdiri, tambah 0.7cm
 * - Usia >= 24 bulan: jika diukur telentang, kurang 0.7cm
 */
function correct_height(int $age_months, float $height, string $measure_mode): float {
    if ($measure_mode === 'standing' && $age_months < 24) {
        return $height + 0.7;
    } elseif ($measure_mode === 'recumbent' && $age_months >= 24) {
        return $height - 0.7;
    }
    return $height;
}

/**
 * Hitung Z-score individu menggunakan formula WHO.
 */
function calculate_z_score(float $value, float $median, float $sd_neg1, float $sd_pos1): float {
    if ($value == $median) return 0.0;

    if ($value < $median) {
        $divisor = $median - $sd_neg1;
        return ($divisor == 0) ? 0.0 : ($value - $median) / $divisor;
    }

    // nilai > median
    $divisor = $sd_pos1 - $median;
    return ($divisor == 0) ? 0.0 : ($value - $median) / $divisor;
}

/**
 * Mencari baris dalam array data yang sesuai dengan kriteria.
 */
function find_row(array $data, array $criteria): ?array {
    foreach ($data as $row) {
        $match = true;
        foreach ($criteria as $key => $value) {
            if (!isset($row[$key]) || $row[$key] != $value) {
                $match = false;
                break;
            }
        }
        if ($match) return $row;
    }
    return null;
}

/**
 * Fungsi utama: menghitung semua Z-score untuk seorang anak.
 */
function get_z_scores(
    string $gender,
    string $dob,
    float $weight,
    float $height,
    string $measure_mode,
    string $visit_date = null
): array {
    $df_age = load_std_age();
    $df_height = load_std_height();

    $age_months = calculate_age_months($dob, $visit_date);
    $corrected_height = correct_height($age_months, $height, $measure_mode);

    // --- 1. BB/U (Berat Badan menurut Umur) ---
    $row_bb_u = find_row($df_age, [
        'gender' => $gender,
        'index_type' => 'BB_U',
        'age_months' => $age_months,
    ]);

    $z_bb_u = null;
    if ($row_bb_u) {
        $z_bb_u = round(calculate_z_score(
            $weight, $row_bb_u['median'], $row_bb_u['sd_n1'], $row_bb_u['sd_p1']
        ), 2);
    }

    // --- 2. TB/U atau PB/U (Tinggi/Panjang Badan menurut Umur) ---
    $index_type_len = ($age_months < 24) ? 'PB_U' : 'TB_U';

    $row_tb_u = find_row($df_age, [
        'gender' => $gender,
        'index_type' => $index_type_len,
        'age_months' => $age_months,
    ]);

    $z_tb_u = null;
    if ($row_tb_u) {
        $z_tb_u = round(calculate_z_score(
            $corrected_height, $row_tb_u['median'], $row_tb_u['sd_n1'], $row_tb_u['sd_p1']
        ), 2);
    }

    // --- 3. BB/TB atau BB/PB (Berat Badan menurut Tinggi/Panjang Badan) ---
    $index_type_wfh = ($age_months < 24) ? 'BB_PB' : 'BB_TB';

    // Bulatkan tinggi ke 0.5 terdekat untuk pencarian
    $lookup_height = round($corrected_height * 2) / 2;

    // Jika anak sangat pendek, gunakan tabel PB_U
    if ($index_type_wfh === 'BB_TB' && $lookup_height < 65.0) {
        $index_type_wfh = 'BB_PB';
    }

    // Cari kecocokan persis
    $row_bb_tb = find_row($df_height, [
        'gender' => $gender,
        'index_type' => $index_type_wfh,
        'height_cm' => $lookup_height,
    ]);

    $ref_median = null;
    $ref_sd_n1 = null;
    $ref_sd_p1 = null;

    if ($row_bb_tb) {
        $ref_median = $row_bb_tb['median'];
        $ref_sd_n1 = $row_bb_tb['sd_n1'];
        $ref_sd_p1 = $row_bb_tb['sd_p1'];
    } else {
        // Interpolasi: cari entri terdekat di bawah dan di atas
        $lower = null;
        $upper = null;
        foreach ($df_height as $row) {
            if ($row['gender'] !== $gender || $row['index_type'] !== $index_type_wfh) continue;

            if ($row['height_cm'] < $lookup_height) {
                if ($lower === null || $row['height_cm'] > $lower['height_cm']) {
                    $lower = $row;
                }
            } elseif ($row['height_cm'] > $lookup_height) {
                if ($upper === null || $row['height_cm'] < $upper['height_cm']) {
                    $upper = $row;
                }
            }
        }

        if ($lower && $upper) {
            $h1 = $lower['height_cm'];
            $h2 = $upper['height_cm'];
            $frac = ($lookup_height - $h1) / ($h2 - $h1);

            $ref_median = $lower['median'] + ($upper['median'] - $lower['median']) * $frac;
            $ref_sd_n1 = $lower['sd_n1'] + ($upper['sd_n1'] - $lower['sd_n1']) * $frac;
            $ref_sd_p1 = $lower['sd_p1'] + ($upper['sd_p1'] - $lower['sd_p1']) * $frac;
        }
    }

    $z_bb_tb = null;
    if ($ref_median !== null) {
        $z_bb_tb = round(calculate_z_score($weight, $ref_median, $ref_sd_n1, $ref_sd_p1), 2);
    }

    return [
        'age_months'       => $age_months,
        'corrected_height' => $corrected_height,
        'z_bb_u'           => $z_bb_u,
        'z_tb_u'           => $z_tb_u,
        'z_bb_tb'          => $z_bb_tb,
    ];
}

/**
 * Ambil data grafik pertumbuhan untuk TB/U.
 */
function get_growth_chart_data(string $gender): array {
    $df_age = load_std_age();

    $result = ['age' => [], 'sd_n3' => [], 'sd_n2' => [], 'median' => [], 'sd_p2' => [], 'sd_p3' => []];

    foreach ($df_age as $row) {
        if ($row['gender'] !== $gender) continue;

        $is_pb_u = ($row['index_type'] === 'PB_U' && $row['age_months'] >= 0 && $row['age_months'] < 24);
        $is_tb_u = ($row['index_type'] === 'TB_U' && $row['age_months'] >= 24 && $row['age_months'] <= 60);

        if (!$is_pb_u && !$is_tb_u) continue;

        $result['age'][]    = $row['age_months'];
        $result['sd_n3'][]  = $row['sd_n3'];
        $result['sd_n2'][]  = $row['sd_n2'];
        $result['median'][] = $row['median'];
        $result['sd_p2'][]  = $row['sd_p2'];
        $result['sd_p3'][]  = $row['sd_p3'];
    }

    return $result;
}

/**
 * Ambil data grafik pertumbuhan untuk BB/U.
 */
function get_weight_chart_data(string $gender): array {
    $df_age = load_std_age();

    $result = ['age' => [], 'sd_n3' => [], 'sd_n2' => [], 'median' => [], 'sd_p2' => [], 'sd_p3' => []];

    foreach ($df_age as $row) {
        if ($row['gender'] !== $gender) continue;
        if ($row['index_type'] !== 'BB_U') continue;
        if ($row['age_months'] < 0 || $row['age_months'] > 60) continue;

        $result['age'][]    = $row['age_months'];
        $result['sd_n3'][]  = $row['sd_n3'];
        $result['sd_n2'][]  = $row['sd_n2'];
        $result['median'][] = $row['median'];
        $result['sd_p2'][]  = $row['sd_p2'];
        $result['sd_p3'][]  = $row['sd_p3'];
    }

    return $result;
}

/**
 * Ambil data grafik pertumbuhan untuk BB/TB atau BB/PB.
 */
function get_wfh_chart_data(string $gender, string $mode): array {
    $df_height = load_std_height();

    $index_type = ($mode === 'recumbent') ? 'BB_PB' : 'BB_TB';

    $result = ['height' => [], 'sd_n3' => [], 'sd_n2' => [], 'median' => [], 'sd_p2' => [], 'sd_p3' => []];

    foreach ($df_height as $row) {
        if ($row['gender'] !== $gender) continue;
        if ($row['index_type'] !== $index_type) continue;

        $result['height'][] = $row['height_cm'];
        $result['sd_n3'][]  = $row['sd_n3'];
        $result['sd_n2'][]  = $row['sd_n2'];
        $result['median'][] = $row['median'];
        $result['sd_p2'][]  = $row['sd_p2'];
        $result['sd_p3'][]  = $row['sd_p3'];
    }

    return $result;
}
