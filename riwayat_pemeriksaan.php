<?php
require_once __DIR__ . '/config.php';
require_auth();

$nik = isset($_GET['nik']) ? trim($_GET['nik']) : '';

if (empty($nik)) {
    die("NIK tidak valid.");
}

// Fetch Balita
$stmtBalita = $pdo->prepare("SELECT * FROM balita WHERE nik = :nik");
$stmtBalita->execute([':nik' => $nik]);
$balita = $stmtBalita->fetch();

if (!$balita) {
    die("Data balita tidak ditemukan.");
}

// Fetch Pemeriksaan History
$stmtPem = $pdo->prepare("SELECT * FROM pemeriksaan WHERE balita_nik = :nik ORDER BY created_at DESC");
$stmtPem->execute([':nik' => $nik]);
$riwayat = $stmtPem->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemeriksaan - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
    <style>
        .profile-card, .history-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-buruk { background: #ffe3e3; color: #dc3545; border: 1px solid #dc3545; }
        .status-kurang { background: #fff3cd; color: #fd7e14; border: 1px solid #fd7e14; }
        .status-baik { background: #d1e7dd; color: #198754; border: 1px solid #198754; }
        .status-lebih { background: #e0cffc; color: #6f42c1; border: 1px solid #6f42c1; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-heart-pulse"></i></div>
            <span><?= APP_NAME ?></span>
        </div>
        <nav>
            <div class="nav-label">Menu Utama</div>
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
            <a href="<?= BASE_URL ?>/data_balita.php" class="nav-link active"><i class="bi bi-people-fill"></i> Data Balita</a>
            <a href="<?= BASE_URL ?>/pemeriksaan.php" class="nav-link"><i class="bi bi-clipboard2-pulse-fill"></i> Pemeriksaan</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-toggle" id="btnToggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Riwayat Pemeriksaan</span>
            </div>
            <div class="user-info">
                <div class="text-end d-none d-sm-block">
                    <div class="user-name"><?= htmlspecialchars(get_user_display_name()) ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <div class="dropdown">
                    <div class="avatar" role="button" data-bs-toggle="dropdown"><?= strtoupper(substr(get_user_display_name(), 0, 1)) ?></div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="content-area">
            <div class="mb-3">
                <a href="<?= BASE_URL ?>/data_balita.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Data Balita
                </a>
            </div>

            <div class="profile-card">
                <h5 class="mb-4"><i class="bi bi-person-vcard text-primary me-2"></i> Profil Balita</h5>
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">NIK</div>
                        <div class="fw-bold fs-5"><?= htmlspecialchars($balita['nik']) ?></div>
                    </div>
                    <div class="col-md-4 col-6 mb-3">
                        <div class="text-muted small">Nama Lengkap</div>
                        <div class="fw-bold fs-5"><?= htmlspecialchars($balita['nama']) ?></div>
                    </div>
                    <div class="col-md-2 col-6 mb-3">
                        <div class="text-muted small">Tanggal Lahir</div>
                        <div class="fw-bold"><?= date('d M Y', strtotime($balita['dob'])) ?></div>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <div class="text-muted small">Jenis Kelamin</div>
                        <div class="fw-bold"><?= $balita['gender'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
                    </div>
                </div>
            </div>

            <div class="history-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="bi bi-clock-history text-primary me-2"></i> Riwayat Pemeriksaan Gizi</h5>
                    <a href="<?= BASE_URL ?>/pemeriksaan.php" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i> Pemeriksaan Baru</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Umur (Bln)</th>
                                <th>BB (kg)</th>
                                <th>TB (cm)</th>
                                <th>Status Gizi</th>
                                <th style="min-width: 250px;">Rekomendasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($riwayat) > 0): ?>
                                <?php foreach ($riwayat as $row): 
                                    $statusClass = 'status-baik';
                                    $fuzzy = $row['fuzzy_label'] ?? '';
                                    if (strpos($fuzzy, 'Buruk') !== false) $statusClass = 'status-buruk';
                                    elseif (strpos($fuzzy, 'Kurang') !== false) $statusClass = 'status-kurang';
                                    elseif (strpos($fuzzy, 'Lebih') !== false) $statusClass = 'status-lebih';
                                ?>
                                    <tr>
                                        <td><strong><?= date('d M Y', strtotime($row['created_at'])) ?></strong></td>
                                        <td><?= $row['age_months'] ?></td>
                                        <td><?= number_format($row['weight'], 2) ?></td>
                                        <td><?= number_format($row['height'], 1) ?></td>
                                        <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['fuzzy_label']) ?></span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['recommendation']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-clipboard-x fs-1 d-block mb-2"></i>
                                        Belum ada riwayat pemeriksaan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
