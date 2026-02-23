<?php
/**
 * Data Loader — Memuat dataset CSV standar pertumbuhan WHO ke dalam array PHP.
 */

function load_std_age(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $file = __DIR__ . '/../data/std_age.csv';
    if (!file_exists($file)) {
        throw new RuntimeException("Dataset not found: $file");
    }

    $cache = [];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle); // Lewati header

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 10) continue;
        // Bersihkan spasi dari semua nilai
        $row = array_map('trim', $row);
        if ($row[0] === '' || $row[1] === '') continue;

        $cache[] = [
            'gender'     => $row[0],
            'index_type' => $row[1],
            'age_months' => (int) $row[2],
            'sd_n3'      => (float) $row[3],
            'sd_n2'      => (float) $row[4],
            'sd_n1'      => (float) $row[5],
            'median'     => (float) $row[6],
            'sd_p1'      => (float) $row[7],
            'sd_p2'      => (float) $row[8],
            'sd_p3'      => (float) $row[9],
        ];
    }
    fclose($handle);
    return $cache;
}

function load_std_height(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $file = __DIR__ . '/../data/std_height.csv';
    if (!file_exists($file)) {
        throw new RuntimeException("Dataset not found: $file");
    }

    $cache = [];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle); // Lewati header

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 10) continue;
        $row = array_map('trim', $row);
        if ($row[0] === '' || $row[1] === '') continue;

        $cache[] = [
            'gender'     => $row[0],
            'index_type' => $row[1],
            'height_cm'  => (float) $row[2],
            'sd_n3'      => (float) $row[3],
            'sd_n2'      => (float) $row[4],
            'sd_n1'      => (float) $row[5],
            'median'     => (float) $row[6],
            'sd_p1'      => (float) $row[7],
            'sd_p2'      => (float) $row[8],
            'sd_p3'      => (float) $row[9],
        ];
    }
    fclose($handle);
    return $cache;
}
