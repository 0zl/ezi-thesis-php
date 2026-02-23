<?php
/**
 * Mesin Logika Fuzzy — Inferensi tipe Mamdani untuk klasifikasi gizi.
 * Implementasi:
 *  - Fungsi keanggotaan segitiga dan trapesium
 *  - Defuzzifikasi Centroid
 */

/**
 * Fungsi keanggotaan Segitiga.
 */
function trimf(float $x, float $a, float $b, float $c): float {
    if ($x <= $a || $x >= $c) return 0.0;
    if ($x <= $b) return ($b === $a) ? 1.0 : ($x - $a) / ($b - $a);
    return ($c === $b) ? 1.0 : ($c - $x) / ($c - $b);
}

/**
 * Fungsi keanggotaan Trapesium.
 */
function trapmf(float $x, float $a, float $b, float $c, float $d): float {
    if ($x >= $b && $x <= $c) return 1.0;
    if ($x < $a || $x > $d) return 0.0;
    if ($x < $b) return ($b == $a) ? 1.0 : ($x - $a) / ($b - $a);
    return ($d == $c) ? 1.0 : ($d - $x) / ($d - $c);
}

/**
 * Evaluasi semua fungsi keanggotaan untuk semua input.
 */
function evaluate_memberships(float $bb_u, float $tb_u, float $bb_tb): array {
    return [
        // Keanggotaan BB/U
        'bb_u_sangat_kurang' => trapmf($bb_u, -5, -5, -3.0, -2.8),
        'bb_u_kurang'        => trimf($bb_u, -3.5, -2.5, -1.5),
        'bb_u_normal'        => trapmf($bb_u, -2.5, -1, 1, 2.5),
        'bb_u_risiko_lebih'  => trapmf($bb_u, 1.5, 3, 5, 5),

        // Keanggotaan TB/U
        'tb_u_sangat_pendek' => trapmf($tb_u, -5, -5, -3.5, -3),
        'tb_u_pendek'        => trimf($tb_u, -3.5, -2.5, -1.5),
        'tb_u_normal'        => trapmf($tb_u, -2.5, -1, 2, 3.5),
        'tb_u_tinggi'        => trapmf($tb_u, 2.5, 4, 5, 5),

        // Keanggotaan BB/TB
        'bb_tb_sangat_kurus' => trapmf($bb_tb, -5, -5, -3.5, -3),
        'bb_tb_kurus'        => trimf($bb_tb, -3.5, -2.5, -1.5),
        'bb_tb_normal'       => trapmf($bb_tb, -2.5, -1, 1, 2.5),
        'bb_tb_gemuk'        => trapmf($bb_tb, 1.5, 3, 5, 5),
    ];
}

/**
 * Jalankan inferensi fuzzy dan kembalikan (skor, label).
 * Menggunakan inferensi Mamdani dengan defuzzifikasi centroid.
 */
function fuzzy_predict(float $bb_u_val, float $tb_u_val, float $bb_tb_val): array {
    // Batasi input ke rentang [-5, 5]
    $bb_u_val  = max(-5, min(5, $bb_u_val));
    $tb_u_val  = max(-5, min(5, $tb_u_val));
    $bb_tb_val = max(-5, min(5, $bb_tb_val));

    $m = evaluate_memberships($bb_u_val, $tb_u_val, $bb_tb_val);

    // --- Jalankan 12 aturan fuzzy ---

    $rules = [];

    // Rule 1: BB/TB sangat_kurus -> gizi_buruk
    $rules[] = ['strength' => $m['bb_tb_sangat_kurus'], 'output' => 'gizi_buruk'];

    // Rule 2: BB/TB kurus AND BB/U sangat_kurang -> gizi_buruk
    $rules[] = ['strength' => min($m['bb_tb_kurus'], $m['bb_u_sangat_kurang']), 'output' => 'gizi_buruk'];

    // Rule 3: BB/TB kurus AND BB/U kurang -> gizi_kurang
    $rules[] = ['strength' => min($m['bb_tb_kurus'], $m['bb_u_kurang']), 'output' => 'gizi_kurang'];

    // Rule 4: BB/TB kurus AND BB/U normal -> gizi_kurang
    $rules[] = ['strength' => min($m['bb_tb_kurus'], $m['bb_u_normal']), 'output' => 'gizi_kurang'];

    // Rule 5: BB/TB gemuk -> gizi_lebih
    $rules[] = ['strength' => $m['bb_tb_gemuk'], 'output' => 'gizi_lebih'];

    // Rule 6: BB/U risiko_lebih AND BB/TB normal -> gizi_lebih
    $rules[] = ['strength' => min($m['bb_u_risiko_lebih'], $m['bb_tb_normal']), 'output' => 'gizi_lebih'];

    // Rule 7: BB/TB normal AND TB/U sangat_pendek -> gizi_buruk
    $rules[] = ['strength' => min($m['bb_tb_normal'], $m['tb_u_sangat_pendek']), 'output' => 'gizi_buruk'];

    // Rule 8: BB/TB normal AND TB/U pendek -> gizi_kurang
    $rules[] = ['strength' => min($m['bb_tb_normal'], $m['tb_u_pendek']), 'output' => 'gizi_kurang'];

    // Rule 9: BB/TB normal AND TB/U normal AND BB/U normal -> gizi_baik
    $rules[] = ['strength' => min($m['bb_tb_normal'], $m['tb_u_normal'], $m['bb_u_normal']), 'output' => 'gizi_baik'];

    // Rule 10: BB/TB normal AND TB/U tinggi -> gizi_baik
    $rules[] = ['strength' => min($m['bb_tb_normal'], $m['tb_u_tinggi']), 'output' => 'gizi_baik'];

    // Rule 11 (fallback): BB/U kurang AND BB/TB normal AND TB/U normal -> gizi_kurang
    $rules[] = ['strength' => min($m['bb_u_kurang'], $m['bb_tb_normal'], $m['tb_u_normal']), 'output' => 'gizi_kurang'];

    // --- Agregasi: ambil firing strength maksimal untuk setiap kelas output ---
    $output_strengths = [
        'gizi_buruk'  => 0,
        'gizi_kurang' => 0,
        'gizi_baik'   => 0,
        'gizi_lebih'  => 0,
    ];

    foreach ($rules as $rule) {
        if ($rule['strength'] > $output_strengths[$rule['output']]) {
            $output_strengths[$rule['output']] = $rule['strength'];
        }
    }

    // --- Defuzzifikasi Centroid ---
    $numerator = 0.0;
    $denominator = 0.0;

    for ($x = 0; $x <= 100; $x++) {
        // Evaluasi setiap MF output pada titik x, dibatasi oleh firing strength-nya
        $gizi_buruk_mf  = min($output_strengths['gizi_buruk'],  trapmf($x, 0, 0, 15, 25));
        $gizi_kurang_mf = min($output_strengths['gizi_kurang'], trimf($x, 20, 35, 55));
        $gizi_baik_mf   = min($output_strengths['gizi_baik'],   trimf($x, 50, 65, 85));
        $gizi_lebih_mf  = min($output_strengths['gizi_lebih'],  trapmf($x, 80, 90, 100, 100));

        // Agregasi: maksimal dari semua MF yang dipotong pada titik ini
        $aggregated = max($gizi_buruk_mf, $gizi_kurang_mf, $gizi_baik_mf, $gizi_lebih_mf);

        $numerator += $x * $aggregated;
        $denominator += $aggregated;
    }

    // Fallback jika tidak ada aturan yang terpenuhi
    $score = ($denominator == 0) ? 50.0 : $numerator / $denominator;

    // --- Tentukan label berdasarkan skor tegas (crisp score) ---
    if ($score <= 25) {
        $label = 'Gizi Buruk';
    } elseif ($score <= 50) {
        $label = 'Gizi Kurang';
    } elseif ($score <= 80) {
        $label = 'Gizi Baik';
    } else {
        $label = 'Gizi Lebih';
    }

    // Penyesuaian untuk kasus ekstrem
    if ($bb_tb_val <= -3) {
        $label = 'Gizi Buruk (Sangat Kurus)';
    }
    if ($bb_tb_val >= 2) {
        $label = 'Gizi Lebih (Gemuk)';
    }

    return [
        'score' => round($score, 2),
        'label' => $label,
    ];
}
