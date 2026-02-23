<?php
require_once __DIR__ . '/config.php';
require_auth();

// Pengaturan Paginasi
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$limit = 10;
$offset = ($page - 1) * $limit;

// Pengaturan Pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = '%' . $search . '%';

// Pembuatan Query
$whereClause = "";
$params = [];

if ($search !== '') {
    $whereClause = "WHERE nik LIKE :search OR nama LIKE :search";
    $params[':search'] = $searchParam;
}

// Ambil total data untuk paginasi
$countQuery = "SELECT COUNT(*) FROM balita $whereClause";
$stmtCount = $pdo->prepare($countQuery);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Ambil data
$dataQuery = "SELECT * FROM balita $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmtData = $pdo->prepare($dataQuery);


$stmtData->execute($params);
$balitaList = $stmtData->fetchAll();

// Fungsi pembantu tautan paginasi
function getPageUrl($pageNum) {
    global $search;
    $params = ['page' => $pageNum];
    if ($search !== '') {
        $params['search'] = $search;
    }
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Balita - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
    <style>
        .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
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
                <span class="page-title">Data Balita</span>
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
            <div class="table-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="bi bi-list-columns text-primary me-2"></i> Daftar Balita Terdaftar</h5>
                    <a href="<?= BASE_URL ?>/pemeriksaan.php" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i> Tambah Data / Pemeriksaan</a>
                </div>

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Cari NIK atau Nama..." value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-outline-secondary" type="submit">Cari</button>
                        </div>
                    </div>
                    <?php if ($search !== ''): ?>
                        <div class="col-auto">
                            <a href="<?= BASE_URL ?>/data_balita.php" class="btn btn-outline-danger">Reset</a>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>NIK</th>
                                <th>Nama Lengkap</th>
                                <th>L/P</th>
                                <th>Tanggal Lahir</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($balitaList) > 0): ?>
                                <?php foreach ($balitaList as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['nik']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['nama']) ?></td>
                                        <td><?= $row['gender'] === 'L' ? '<span class="badge bg-info">Laki-laki</span>' : '<span class="badge bg-warning text-dark">Perempuan</span>' ?></td>
                                        <td><?= date('d M Y', strtotime($row['dob'])) ?></td>
                                        <td><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/riwayat_pemeriksaan.php?nik=<?= urlencode($row['nik']) ?>" class="btn btn-sm btn-primary" title="Riwayat Pemeriksaan">
                                                <i class="bi bi-clock-history"></i> Riwayat
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleteBalita('<?= htmlspecialchars($row['nik']) ?>', '<?= htmlspecialchars($row['nama']) ?>')" title="Hapus Data">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Tidak ada data yang ditemukan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginasi -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= getPageUrl($page - 1) ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= getPageUrl($i) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= getPageUrl($page + 1) ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="mt-3 text-muted small text-center">
                    Menampilkan <?= count($balitaList) ?> dari total <?= $totalRecords ?> data.
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
    <script>
    async function deleteBalita(nik, nama) {
        if (!confirm(`Apakah Anda yakin ingin menghapus data balita "${nama}" (NIK: ${nik}) beserta seluruh riwayat pemeriksaannya?\n\nTindakan ini tidak dapat dibatalkan.`)) {
            return;
        }

        try {
            const resp = await fetch('<?= BASE_URL ?>/api/delete_balita.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nik: nik })
            });
            const res = await resp.json();
            
            if(!resp.ok || res.error) {
                throw new Error(res.error || 'Gagal menghapus data.');
            }
            
            alert('Data berhasil dihapus!');
            location.reload(); // Reload halaman untuk update tabel
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }
    </script>
</body>
</html>
