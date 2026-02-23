<?php
/**
 * Tes Otomatis untuk Mesin Nutrisi.
 * Jalankan: php tests/test_nutrition.php
 */

// Inisialisasi
require_once __DIR__ . '/../includes/data_loader.php';
require_once __DIR__ . '/../includes/nutrition_utils.php';
require_once __DIR__ . '/../includes/fuzzy_logic.php';

$passed = 0;
$failed = 0;

function assert_eq($test_name, $expected, $actual, $tolerance = null) {
    global $passed, $failed;
    if ($tolerance !== null) {
        if (abs($expected - $actual) <= $tolerance) {
            echo "  PASS: $test_name (expected ~$expected, got $actual)\n";
            $passed++;
        } else {
            echo "  FAIL: $test_name (expected ~$expected ± $tolerance, got $actual)\n";
            $failed++;
        }
    } elseif ($expected === $actual) {
        echo "  PASS: $test_name\n";
        $passed++;
    } else {
        echo "  FAIL: $test_name (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")\n";
        $failed++;
    }
}

// =============================================
echo "=== Test 1: Age Calculation ===\n";
// =============================================

// 2020-01-01 -> 2020-02-01 = 1 bulan
assert_eq('usia 1 bulan', 1, calculate_age_months('2020-01-01', '2020-02-01'));
// 2020-01-01 -> 2021-01-01 = 12 months
assert_eq('usia 12 bulan', 12, calculate_age_months('2020-01-01', '2021-01-01'));
// 2020-01-01 -> 2020-01-15 = 0 months
assert_eq('usia 0 bulan', 0, calculate_age_months('2020-01-01', '2020-01-15'));

// =============================================
echo "\n=== Test 2: Koreksi Tinggi Badan ===\n";
// =============================================

// < 24 bulan diukur berdiri -> +0.7
assert_eq('<24m standing +0.7', 80.7, correct_height(23, 80.0, 'standing'));
// < 24 bulan diukur telentang -> tidak berubah
assert_eq('<24m recumbent unchanged', 80.0, correct_height(23, 80.0, 'recumbent'));
// >= 24 bulan diukur telentang -> -0.7
assert_eq('>=24m recumbent -0.7', 89.3, correct_height(24, 90.0, 'recumbent'));
// >= 24 bulan diukur berdiri -> tidak berubah
assert_eq('>=24m standing unchanged', 90.0, correct_height(24, 90.0, 'standing'));

// =============================================
echo "\n=== Test 3: Formula Z-Score ===\n";
// =============================================

// median=10, sd_n1=9, sd_p1=11
assert_eq('Z=0 pada median', 0.0, calculate_z_score(10, 10, 9, 11));
assert_eq('Z=1 at sd_p1', 1.0, calculate_z_score(11, 10, 9, 11));
assert_eq('Z=-1 at sd_n1', -1.0, calculate_z_score(9, 10, 9, 11));
assert_eq('Z=2 di atas median', 2.0, calculate_z_score(12, 10, 9, 11));

// =============================================
echo "\n=== Test 4: Fungsi Keanggotaan ===\n";
// =============================================

// trapmf [0, 0, 15, 25] at x=0 -> 1.0
assert_eq('trapmf center', 1.0, trapmf(0, 0, 0, 15, 25));
// trapmf [0, 0, 15, 25] at x=10 -> 1.0 (in the flat top)
assert_eq('trapmf flat top', 1.0, trapmf(10, 0, 0, 15, 25));
// trapmf [0, 0, 15, 25] at x=20 -> 0.5
assert_eq('trapmf slope', 0.5, trapmf(20, 0, 0, 15, 25));
// trimf [20, 35, 55] at x=35 -> 1.0
assert_eq('trimf peak', 1.0, trimf(35, 20, 35, 55));
// trimf [20, 35, 55] at x=20 -> 0.0
assert_eq('trimf left edge', 0.0, trimf(20, 20, 35, 55));

// =============================================
echo "\n=== Test 5: Integrasi Z-Score (Laki-laki, 0 bulan, BB=3.3kg) ===\n";
// =============================================

$result = get_z_scores('L', '2023-01-01', 3.3, 50.0, 'recumbent', '2023-01-01');
assert_eq('age_months=0', 0, $result['age_months']);
assert_eq('z_bb_u=0.0 (berat di median)', 0.0, $result['z_bb_u'], 0.01);

// =============================================
echo "\n=== Test 6: Logika Fuzzy - Kasus Diketahui ===\n";
// =============================================

// Case: Budi (Sehat) - Male 24m, 12.2kg, 87.1cm standing
// Ekspektasi: Z-score dalam rentang normal -> Gizi Baik
$r_budi = get_z_scores('L', '2024-02-23', 12.2, 87.1, 'standing', '2026-02-23');
echo "  Budi z_bb_u={$r_budi['z_bb_u']}, z_tb_u={$r_budi['z_tb_u']}, z_bb_tb={$r_budi['z_bb_tb']}\n";
if ($r_budi['z_bb_u'] !== null && $r_budi['z_tb_u'] !== null && $r_budi['z_bb_tb'] !== null) {
    $fuzzy_budi = fuzzy_predict($r_budi['z_bb_u'], $r_budi['z_tb_u'], $r_budi['z_bb_tb']);
    echo "  Budi fuzzy: {$fuzzy_budi['label']} ({$fuzzy_budi['score']}/100)\n";
    assert_eq('Budi adalah Gizi Baik', true, str_contains($fuzzy_budi['label'], 'Baik'));
} else {
    echo "  LEWATI: Z-score Budi di luar jangkauan\n";
}

// Case: Asep (Gizi Buruk) - Male 24m, 8.0kg, 75.0cm standing
$r_asep = get_z_scores('L', '2024-02-23', 8.0, 75.0, 'standing', '2026-02-23');
echo "  Asep z_bb_u={$r_asep['z_bb_u']}, z_tb_u={$r_asep['z_tb_u']}, z_bb_tb={$r_asep['z_bb_tb']}\n";
if ($r_asep['z_bb_u'] !== null && $r_asep['z_tb_u'] !== null && $r_asep['z_bb_tb'] !== null) {
    $fuzzy_asep = fuzzy_predict($r_asep['z_bb_u'], $r_asep['z_tb_u'], $r_asep['z_bb_tb']);
    echo "  Asep fuzzy: {$fuzzy_asep['label']} ({$fuzzy_asep['score']}/100)\n";
    assert_eq('Asep adalah Gizi Buruk', true, str_contains($fuzzy_asep['label'], 'Buruk'));
} else {
    echo "  LEWATI: Z-score Asep di luar jangkauan\n";
}

// Case: Putri (Gizi Lebih) - Female 24m, 18.0kg, 87.1cm standing
$r_putri = get_z_scores('P', '2024-02-23', 18.0, 87.1, 'standing', '2026-02-23');
echo "  Putri z_bb_u={$r_putri['z_bb_u']}, z_tb_u={$r_putri['z_tb_u']}, z_bb_tb={$r_putri['z_bb_tb']}\n";
if ($r_putri['z_bb_u'] !== null && $r_putri['z_tb_u'] !== null && $r_putri['z_bb_tb'] !== null) {
    $fuzzy_putri = fuzzy_predict($r_putri['z_bb_u'], $r_putri['z_tb_u'], $r_putri['z_bb_tb']);
    echo "  Putri fuzzy: {$fuzzy_putri['label']} ({$fuzzy_putri['score']}/100)\n";
    assert_eq('Putri adalah Gizi Lebih', true, str_contains($fuzzy_putri['label'], 'Lebih'));
} else {
    echo "  LEWATI: Z-score Putri di luar jangkauan\n";
}

// =============================================
echo "\n=== RINGKASAN ===\n";
echo "Passed: $passed, Failed: $failed\n";
echo ($failed === 0) ? "SEMUA TES BERHASIL!\n" : "BEBERAPA TES GAGAL!\n";
exit($failed > 0 ? 1 : 0);
