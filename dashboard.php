<?php
require_once __DIR__ . '/config.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
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
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link active">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-people-fill"></i> Data Balita
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-person-hearts"></i> Data Ibu Hamil
            </a>
            <a href="<?= BASE_URL ?>/pemeriksaan.php" class="nav-link">
                <i class="bi bi-clipboard2-pulse-fill"></i> Pemeriksaan
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-calendar-event-fill"></i> Jadwal Posyandu
            </a>

            <div class="nav-label">Lainnya</div>
            <a href="#" class="nav-link">
                <i class="bi bi-bar-chart-line-fill"></i> Laporan
            </a>
            <a href="#" class="nav-link">
                <i class="bi bi-gear-fill"></i> Pengaturan
            </a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-toggle" id="btnToggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Dashboard</span>
            </div>
            <div class="user-info">
                <div class="text-end d-none d-sm-block">
                    <div class="user-name"><?= htmlspecialchars(get_user_display_name()) ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <div class="dropdown">
                    <div class="avatar" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?= strtoupper(substr(get_user_display_name(), 0, 1)) ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="content-area">
            <div class="welcome-card mb-4">
                <h2>Selamat Datang, <?= htmlspecialchars(get_user_display_name()) ?>!</h2>
                <p>Sistem Informasi <?= APP_NAME ?> &mdash; Kelola data posyandu dengan mudah dan efisien.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="placeholder-card">
                        <i class="bi bi-people"></i>
                        <h5 class="text-muted mb-1">Data Balita</h5>
                        <p class="small mb-0">Modul ini akan segera tersedia.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="placeholder-card">
                        <i class="bi bi-person-hearts"></i>
                        <h5 class="text-muted mb-1">Data Ibu Hamil</h5>
                        <p class="small mb-0">Modul ini akan segera tersedia.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="placeholder-card">
                        <i class="bi bi-clipboard2-pulse"></i>
                        <h5 class="text-muted mb-1">Pemeriksaan</h5>
                        <p class="small mb-0">Modul ini akan segera tersedia.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="placeholder-card">
                        <i class="bi bi-calendar-event"></i>
                        <h5 class="text-muted mb-1">Jadwal Posyandu</h5>
                        <p class="small mb-0">Modul ini akan segera tersedia.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
</body>
</html>
