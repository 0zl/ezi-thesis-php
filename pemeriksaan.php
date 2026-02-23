<?php
require_once __DIR__ . '/config.php';
require_auth();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemeriksaan Gizi - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js"></script>
    <style>
        .result-section { display: none; }
        .result-section.show { display: block; }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        .status-buruk  { background: linear-gradient(135deg, #dc3545, #c82333); color: #fff; }
        .status-kurang { background: linear-gradient(135deg, #fd7e14, #e8590c); color: #fff; }
        .status-baik   { background: linear-gradient(135deg, #28a745, #1e7e34); color: #fff; }
        .status-lebih  { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: #fff; }
        .z-score-table th { background: #f8f9fa; }
        .chart-tab-btn.active { background: var(--primary) !important; color: #fff !important; border-color: var(--primary) !important; }
        .chart-tab-btn { transition: all 0.2s; }
        .recommendation-box {
            border-left: 4px solid var(--primary);
            background: rgba(37, 99, 235, 0.05);
            padding: 16px 20px;
            border-radius: 0 8px 8px 0;
        }
        .example-btn {
            cursor: pointer;
            transition: all 0.15s;
        }
        .example-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .input-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
        .result-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; }
        #loadingSpinner { display: none; }
        #loadingSpinner.show { display: flex; }
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
            <a href="#" class="nav-link"><i class="bi bi-people-fill"></i> Data Balita</a>
            <a href="#" class="nav-link"><i class="bi bi-person-hearts"></i> Data Ibu Hamil</a>
            <a href="<?= BASE_URL ?>/pemeriksaan.php" class="nav-link active"><i class="bi bi-clipboard2-pulse-fill"></i> Pemeriksaan</a>
            <a href="#" class="nav-link"><i class="bi bi-calendar-event-fill"></i> Jadwal Posyandu</a>
            <div class="nav-label">Lainnya</div>
            <a href="#" class="nav-link"><i class="bi bi-bar-chart-line-fill"></i> Laporan</a>
            <a href="#" class="nav-link"><i class="bi bi-gear-fill"></i> Pengaturan</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn-toggle" id="btnToggle"><i class="bi bi-list"></i></button>
                <span class="page-title">Pemeriksaan Gizi</span>
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
            <div class="row g-4">
                <!-- Kolom Input -->
                <div class="col-lg-4">
                    <div class="input-card">
                        <h5 class="mb-3"><i class="bi bi-pencil-square text-primary"></i> Input Data Balita</h5>
                        <form id="formAnalyze">
                            <div class="mb-3">
                                <label for="nama" class="form-label">Nama Balita</label>
                                <input type="text" class="form-control" id="nama" name="nama" placeholder="Masukkan nama lengkap" required>
                            </div>
                            <div class="mb-3">
                                <label for="dob" class="form-label">Tanggal Lahir</label>
                                <input type="date" class="form-control" id="dob" name="dob" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="genderL" value="Laki-laki" checked>
                                        <label class="form-check-label" for="genderL">Laki-laki</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gender" id="genderP" value="Perempuan">
                                        <label class="form-check-label" for="genderP">Perempuan</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="weight" class="form-label">Berat Badan (kg)</label>
                                    <input type="number" step="0.01" class="form-control" id="weight" name="weight" placeholder="0.00" required>
                                </div>
                                <div class="col-6">
                                    <label for="height" class="form-label">Tinggi Badan (cm)</label>
                                    <input type="number" step="0.1" class="form-control" id="height" name="height" placeholder="0.0" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Posisi Pengukuran</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="measure_mode" id="modeRecumbent" value="Terlentang" checked>
                                        <label class="form-check-label" for="modeRecumbent">Terlentang</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="measure_mode" id="modeStanding" value="Berdiri">
                                        <label class="form-check-label" for="modeStanding">Berdiri</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2" id="btnAnalyze">
                                <i class="bi bi-search"></i> Analisa Status Gizi
                            </button>
                        </form>

                        <hr class="my-3">
                        <p class="text-muted small mb-2"><i class="bi bi-lightbulb"></i> Contoh Kasus (Klik untuk isi otomatis):</p>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn btn-outline-success btn-sm example-btn" onclick="fillExample('Budi (Sehat)', 'Laki-laki', 12.2, 87.1, 'Berdiri')">
                                <i class="bi bi-emoji-smile"></i> Budi — Gizi Baik
                            </button>
                            <button class="btn btn-outline-danger btn-sm example-btn" onclick="fillExample('Asep (Gizi Buruk)', 'Laki-laki', 8.0, 75.0, 'Berdiri')">
                                <i class="bi bi-emoji-frown"></i> Asep — Gizi Buruk
                            </button>
                            <button class="btn btn-outline-warning btn-sm example-btn" onclick="fillExample('Putri (Gizi Lebih)', 'Perempuan', 18.0, 87.1, 'Berdiri')">
                                <i class="bi bi-emoji-neutral"></i> Putri — Gizi Lebih
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kolom Hasil -->
                <div class="col-lg-8">
                    <!-- Spinner Loading -->
                    <div id="loadingSpinner" class="justify-content-center align-items-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-3 text-muted">Menganalisa data...</span>
                    </div>

                    <!-- Tampilan Error -->
                    <div id="errorMessage" class="alert alert-danger" style="display:none;"></div>

                    <!-- Hasil -->
                    <div id="resultSection" class="result-section">
                        <div class="result-card mb-4">
                            <h5 class="mb-3"><i class="bi bi-clipboard2-check text-primary"></i> Hasil Analisa</h5>

                            <div class="row mb-3">
                                <div class="col-sm-4">
                                    <div class="text-muted small">Usia</div>
                                    <div class="fw-bold" id="outAge">—</div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="text-muted small">Tinggi Terkoreksi</div>
                                    <div class="fw-bold" id="outCorrHeight">—</div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="text-muted small">Status Gizi</div>
                                    <div id="outStatus">—</div>
                                </div>
                            </div>

                            <!-- Tabel Z-Score -->
                            <table class="table table-sm table-bordered z-score-table mb-3">
                                <thead>
                                    <tr>
                                        <th>Indeks</th>
                                        <th>Nilai Z-Score (SD)</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="outZTable"></tbody>
                            </table>

                            <!-- Rekomendasi -->
                            <div class="recommendation-box" id="outRecommendation">—</div>
                        </div>

                        <!-- Grafik Pertumbuhan -->
                        <div class="result-card">
                            <h5 class="mb-3"><i class="bi bi-graph-up text-primary"></i> Kurva Pertumbuhan (Standar WHO)</h5>
                            <div class="btn-group mb-3 d-flex" role="group">
                                <button class="btn btn-outline-secondary chart-tab-btn active flex-fill" onclick="showChart('tb_u', this)">
                                    <i class="bi bi-rulers"></i> TB/U
                                </button>
                                <button class="btn btn-outline-secondary chart-tab-btn flex-fill" onclick="showChart('bb_u', this)">
                                    <i class="bi bi-speedometer"></i> BB/U
                                </button>
                                <button class="btn btn-outline-secondary chart-tab-btn flex-fill" onclick="showChart('bb_tb', this)">
                                    <i class="bi bi-aspect-ratio"></i> BB/TB
                                </button>
                            </div>
                            <div style="position:relative; height:400px;">
                                <canvas id="growthChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder (sebelum analisa pertama) -->
                    <div id="placeholderSection" class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard2-pulse" style="font-size:4rem; opacity:0.3;"></i>
                        <p class="mt-3">Masukkan data balita dan klik <strong>Analisa</strong> untuk melihat hasil.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
    <script>
    // --- State Global ---
    let lastResult = null;
    let growthChartInstance = null;
    let currentChartType = 'tb_u';

    // --- Pengisi data contoh ---
    function fillExample(nama, gender, weight, height, mode) {
        document.getElementById('nama').value = nama;
        // Set Tanggal Lahir ke 2 tahun yang lalu
        const d = new Date();
        d.setFullYear(d.getFullYear() - 2);
        document.getElementById('dob').value = d.toISOString().split('T')[0];
        document.querySelector(`input[name="gender"][value="${gender}"]`).checked = true;
        document.getElementById('weight').value = weight;
        document.getElementById('height').value = height;
        document.querySelector(`input[name="measure_mode"][value="${mode}"]`).checked = true;
    }

    // --- Submit Form ---
    document.getElementById('formAnalyze').addEventListener('submit', async function(e) {
        e.preventDefault();

        const form = new FormData(this);
        const data = Object.fromEntries(form.entries());

        // State UI
        document.getElementById('loadingSpinner').classList.add('show');
        document.getElementById('resultSection').classList.remove('show');
        document.getElementById('placeholderSection').style.display = 'none';
        document.getElementById('errorMessage').style.display = 'none';
        document.getElementById('btnAnalyze').disabled = true;

        try {
            const resp = await fetch('<?= BASE_URL ?>/api/analyze.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await resp.json();

            if (!resp.ok || result.error) {
                throw new Error(result.error || 'Terjadi kesalahan server.');
            }

            lastResult = result;
            displayResults(result);

        } catch (err) {
            document.getElementById('errorMessage').textContent = err.message;
            document.getElementById('errorMessage').style.display = 'block';
        } finally {
            document.getElementById('loadingSpinner').classList.remove('show');
            document.getElementById('btnAnalyze').disabled = false;
        }
    });

    // --- Tampilkan hasil ---
    function displayResults(r) {
        document.getElementById('outAge').textContent = r.age_months + ' bulan';
        document.getElementById('outCorrHeight').textContent = r.corrected_height + ' cm';

        // Badge Status
        let statusClass = 'status-baik';
        if (r.fuzzy_label && r.fuzzy_label.includes('Buruk')) statusClass = 'status-buruk';
        else if (r.fuzzy_label && r.fuzzy_label.includes('Kurang')) statusClass = 'status-kurang';
        else if (r.fuzzy_label && r.fuzzy_label.includes('Lebih')) statusClass = 'status-lebih';

        document.getElementById('outStatus').innerHTML =
            `<span class="status-badge ${statusClass}">${r.status}</span>`;

        // Tabel Z-score
        const tbody = document.getElementById('outZTable');
        tbody.innerHTML = '';
        if (r.z_scores && r.z_scores.length) {
            r.z_scores.forEach(z => {
                const tr = document.createElement('tr');
                const valFormatted = z.value !== null ? z.value.toFixed(2) : '—';
                tr.innerHTML = `<td>${z.index}</td><td class="text-center fw-bold">${valFormatted}</td><td>${z.info}</td>`;
                tbody.appendChild(tr);
            });
        }

        // Rekomendasi
        document.getElementById('outRecommendation').innerHTML =
            `<i class="bi bi-info-circle text-primary me-1"></i> <strong>Rekomendasi:</strong> ${r.recommendation}`;

        // Tampilkan hasil
        document.getElementById('resultSection').classList.add('show');

        // Render grafik default
        currentChartType = 'tb_u';
        document.querySelectorAll('.chart-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.chart-tab-btn').classList.add('active');
        renderChart('tb_u');
    }

    // --- Render Grafik ---
    function showChart(type, btn) {
        currentChartType = type;
        document.querySelectorAll('.chart-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        renderChart(type);
    }

    function renderChart(type) {
        if (!lastResult || !lastResult.charts) return;

        const chartData = lastResult.charts[type];
        if (!chartData) return;

        if (growthChartInstance) {
            growthChartInstance.destroy();
        }

        const ctx = document.getElementById('growthChart').getContext('2d');

        const isWfh = type === 'bb_tb';
        const xData = isWfh ? chartData.height : chartData.age;
        const xLabel = isWfh ? 'Tinggi Badan (cm)' : 'Umur (Bulan)';
        const yLabel = type === 'tb_u' ? 'Tinggi Badan (cm)' : 'Berat Badan (kg)';

        // Posisi anak
        let childX, childY;
        if (type === 'tb_u') {
            childX = lastResult.age_months;
            childY = lastResult.corrected_height;
        } else if (type === 'bb_u') {
            childX = lastResult.age_months;
            childY = parseFloat(document.getElementById('weight').value);
        } else {
            childX = lastResult.corrected_height;
            childY = parseFloat(document.getElementById('weight').value);
        }

        // Urutan dataset menentukan lapisan (layer) - Anak paling atas
        const datasets = [
            {
                label: '+3 SD', data: xData.map((x,i) => ({x, y: chartData.sd_p3[i]})),
                borderColor: '#dc3545', borderWidth: 1.5, pointRadius: 0,
                showLine: true, fill: false,
                borderDash: [4, 3],
            },
            {
                label: '+2 SD', data: xData.map((x,i) => ({x, y: chartData.sd_p2[i]})),
                borderColor: '#fd7e14', borderWidth: 1.5, pointRadius: 0,
                showLine: true,
                fill: { target: 0, above: 'rgba(220, 53, 69, 0.10)' }, // zona merah antara +2SD dan +3SD
            },
            {
                label: 'Median (0 SD)', data: xData.map((x,i) => ({x, y: chartData.median[i]})),
                borderColor: '#28a745', borderWidth: 2.5, pointRadius: 0,
                showLine: true,
                fill: { target: 1, above: 'rgba(40, 167, 69, 0.08)' }, // zona hijau muda antara median dan +2SD
            },
            {
                label: '-2 SD', data: xData.map((x,i) => ({x, y: chartData.sd_n2[i]})),
                borderColor: '#fd7e14', borderWidth: 1.5, pointRadius: 0,
                showLine: true,
                fill: { target: 2, above: 'rgba(40, 167, 69, 0.08)' }, // zona hijau muda antara -2SD dan median
            },
            {
                label: '-3 SD', data: xData.map((x,i) => ({x, y: chartData.sd_n3[i]})),
                borderColor: '#dc3545', borderWidth: 1.5, pointRadius: 0,
                showLine: true,
                fill: { target: 3, above: 'rgba(253, 126, 20, 0.12)' }, // zona oranye antara -3SD dan -2SD
                borderDash: [4, 3],
            },
            {
                label: lastResult.nama || 'Anak',
                data: [{x: childX, y: childY}],
                borderColor: '#2563eb',
                backgroundColor: '#2563eb',
                pointRadius: 10,
                pointStyle: 'circle',
                pointBorderWidth: 3,
                pointBorderColor: '#fff',
                showLine: false,
                order: -1, // render paling atas
            }
        ];

        growthChartInstance = new Chart(ctx, {
            type: 'scatter',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 15, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: (${context.parsed.x}, ${context.parsed.y})`;
                            }
                        }
                    }
                },
                scales: {
                    x: { title: { display: true, text: xLabel }, type: 'linear' },
                    y: { title: { display: true, text: yLabel } }
                }
            }
        });
    }
    </script>
</body>
</html>
