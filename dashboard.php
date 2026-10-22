<?php
require_once 'koneksi.php';

/**
 * File: dashboard.php
 * Deskripsi: Halaman Dashboard & Analytics dengan Sidebar Ikon, 4 Kartu Statistik, dan Grafik Chart.js
 */

session_start();

// Validasi otentikasi login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once 'koneksi.php';

// Inisialisasi variabel statistik default[cite: 1]
$total_buku     = 0;
$total_kategori = 0;
$total_penulis  = 0;
$tahun_terbaru  = "-";

// Data penampung grafik
$kategori_labels = [];
$kategori_totals = [];

$tahun_labels = [];
$tahun_totals = [];

try {
    // 1. Total Seluruh Buku[cite: 1]
    $q_total = $koneksi->query("SELECT COUNT(*) AS total FROM books");
    if ($q_total) {
        $total_buku = $q_total->fetch_assoc()['total'] ?? 0;
    }

    // 2. Total Kategori Unik[cite: 1]
    $q_kategori = $koneksi->query("SELECT COUNT(DISTINCT kategori) AS total FROM books");
    if ($q_kategori) {
        $total_kategori = $q_kategori->fetch_assoc()['total'] ?? 0;
    }

    // 3. Total Penulis Unik[cite: 1]
    $q_penulis = $koneksi->query("SELECT COUNT(DISTINCT penulis) AS total FROM books");
    if ($q_penulis) {
        $total_penulis = $q_penulis->fetch_assoc()['total'] ?? 0;
    }

    // 4. Tahun Terbit Paling Baru[cite: 1]
    $q_tahun = $koneksi->query("SELECT MAX(tahun_terbit) AS terbaru FROM books");
    if ($q_tahun) {
        $tahun_row = $q_tahun->fetch_assoc();
        $tahun_terbaru = $tahun_row['terbaru'] ?? "-";
    }

    // 5. Query Data Grafik Batang: Jumlah Buku per Kategori
    $q_chart_cat = $koneksi->query("SELECT kategori, COUNT(*) as total FROM books GROUP BY kategori ORDER BY total DESC");
    if ($q_chart_cat) {
        while ($r = $q_chart_cat->fetch_assoc()) {
            $kategori_labels[] = $r['kategori'];
            $kategori_totals[] = (int)$r['total'];
        }
    }

    // 6. Query Data Grafik Donut: Jumlah Buku per Tahun Terbit
    $q_chart_yr = $koneksi->query("SELECT tahun_terbit, COUNT(*) as total FROM books GROUP BY tahun_terbit ORDER BY tahun_terbit ASC");
    if ($q_chart_yr) {
        while ($r = $q_chart_yr->fetch_assoc()) {
            $tahun_labels[] = $r['tahun_terbit'];
            $tahun_totals[] = (int)$r['total'];
        }
    }

} catch (Exception $e) {
    error_log("Gagal memuat statistik: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Analytics - Perpustakaan API</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CDN Library Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --bg-main: #F8F6F0;
            --bg-white: #FFFFFF;
            --text-black: #161618;
            --text-body: #494C52;
            --text-muted: #8E929A;
            --border-paper: #ECE8DE;
            --accent-red: #E0533C;
            --accent-dark: #1F2024;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-body);
            overflow-x: hidden;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Navigasi Ramping Sesuai Gambar */
        .sidebar-pill {
            width: 80px;
            background-color: var(--bg-main);
            border-right: 1.5px solid var(--border-paper);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
        }

        .brand-icon {
            font-size: 1.8rem;
            color: var(--text-black);
            margin-bottom: 2.5rem;
        }

        .nav-icons {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
            align-items: center;
        }

        .nav-icons li a {
            width: 46px;
            height: 46px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--text-muted);
            font-size: 1.2rem;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .nav-icons li.active a,
        .nav-icons li a:hover {
            background-color: var(--accent-red);
            color: #FFFFFF;
            box-shadow: 0 6px 16px rgba(224, 83, 60, 0.35);
        }

        .nav-icons .nav-bottom {
            margin-top: auto;
        }

        /* Viewport Utama */
        .main-viewport {
            margin-left: 80px;
            flex-grow: 1;
            padding: 1.5rem 3rem 3rem 3rem;
            min-width: 0;
        }

        /* Header Atas */
        .dash-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .dash-topbar-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-black);
        }

        .dash-user-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dash-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dash-username {
            font-weight: 700;
            color: var(--text-black);
            font-size: 0.95rem;
        }

        /* Hero Text */
        .dash-header-hero {
            margin-bottom: 2.2rem;
        }

        .dash-header-hero h1 {
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-black);
            letter-spacing: -0.02em;
            margin: 0 0 0.4rem 0;
        }

        .dash-header-hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
            margin: 0;
        }

        /* 4 Kartu Statistik */
        .stat-grid-clean {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--bg-white);
            border-radius: 24px;
            padding: 1.6rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-paper);
        }

        .stat-box-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .icon-blue   { background-color: #EEF4FF; color: #3563E9; }
        .icon-pink   { background-color: #FDF2F2; color: #E0533C; }
        .icon-green  { background-color: #EDFAF3; color: #10B981; }
        .icon-yellow { background-color: #FEF8EC; color: #F59E0B; }

        .stat-box-info h4 {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            font-weight: 800;
            margin: 0 0 0.35rem 0;
        }

        .stat-box-info .stat-number {
            font-size: 2rem;
            font-weight: 900;
            color: var(--text-black);
            line-height: 1;
        }

        /* Container Grafik Statistik */
        .charts-row {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: var(--bg-white);
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-paper);
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .chart-header h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-black);
            margin: 0;
        }

        .chart-header span {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .chart-canvas-wrap {
            position: relative;
            flex-grow: 1;
            min-height: 250px;
        }

        /* Box Aksi Cepat */
        .dash-actions-card {
            background: var(--bg-white);
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border-paper);
        }

        .dash-actions-card h3 {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--text-black);
            margin: 0 0 1.25rem 0;
        }

        .action-pills-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-act-dark {
            background-color: var(--accent-dark);
            color: #FFFFFF;
            padding: 0.85rem 1.8rem;
            border-radius: 30px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: transform 0.2s;
        }

        .btn-act-dark:hover {
            transform: translateY(-2px);
        }

        .btn-act-pill {
            background-color: transparent;
            color: var(--text-black);
            border: 1.5px solid var(--border-paper);
            padding: 0.85rem 1.8rem;
            border-radius: 30px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.2s;
        }

        .btn-act-pill:hover {
            background-color: var(--bg-main);
            border-color: #D5CEBD;
        }

        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .sidebar-pill {
                width: 100%;
                height: auto;
                position: relative;
                flex-direction: row;
                padding: 1rem;
            }
            .brand-icon {
                margin-bottom: 0;
                margin-right: 1.5rem;
            }
            .nav-icons {
                flex-direction: row;
            }
            .main-viewport {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar Navigasi Ikon (Pill)[cite: 1] -->
        <aside class="sidebar-pill">
            <div class="brand-icon">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <ul class="nav-icons">
                <li><a href="index.php" title="Koleksi Buku"><i class="fa-solid fa-house"></i></a></li>
                <li class="active"><a href="dashboard.php" title="Statistik & Analisis"><i class="fa-solid fa-chart-pie"></i></a></li>
                <li><a href="tambah.php" title="Tambah Buku"><i class="fa-solid fa-circle-plus"></i></a></li>
                <li><a href="export.php" title="Pusat Cetak & Unduh Laporan (PDF & Excel)"><i class="fa-solid fa-file-export"></i></a></li>
                <li class="nav-bottom">
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" title="Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Area Konten Utama[cite: 4] -->
        <div class="main-viewport">
            <!-- Top Bar Profil[cite: 4] -->
            <header class="dash-topbar">
                <div class="dash-topbar-title">Dashboard & Analytics</div>
                <div class="dash-user-profile">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="dash-avatar">
                    <span class="dash-username"><?php echo htmlspecialchars($_SESSION['admin']); ?></span>
                </div>
            </header>

            <!-- Judul Header[cite: 4] -->
            <section class="dash-header-hero">
                <h1>Statistik Koleksi Buku</h1>
                <p>Ringkasan inventaris database perpustakaan yang tersimpan di sistem.</p>
            </section>

            <!-- 4 Kartu Statistik Angka[cite: 1, 4] -->
            <section class="stat-grid-clean">
                <div class="stat-box">
                    <div class="stat-box-icon icon-blue">
                        <i class="fa-solid fa-book-bookmark"></i>
                    </div>
                    <div class="stat-box-info">
                        <h4>TOTAL BUKU</h4>
                        <div class="stat-number"><?php echo $total_buku; ?></div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-box-icon icon-pink">
                        <i class="fa-solid fa-tag"></i>
                    </div>
                    <div class="stat-box-info">
                        <h4>KATEGORI</h4>
                        <div class="stat-number"><?php echo $total_kategori; ?></div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-box-icon icon-green">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div class="stat-box-info">
                        <h4>DATA PENULIS</h4>
                        <div class="stat-number"><?php echo $total_penulis; ?></div>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-box-icon icon-yellow">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div class="stat-box-info">
                        <h4>TERBIT TERBARU</h4>
                        <div class="stat-number"><?php echo $tahun_terbaru; ?></div>
                    </div>
                </div>
            </section>

            <!-- Visualisasi Grafik Baru -->
            <section class="charts-row">
                <!-- Grafik Batang Kategori -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sebaran Buku per Kategori</h3>
                        <span>Distribusi Koleksi</span>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Grafik Donut Tahun Terbit -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Koleksi per Tahun Terbit</h3>
                        <span>Periode Rilis</span>
                    </div>
                    <div class="chart-canvas-wrap">
                        <canvas id="yearChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Box Aksi Cepat[cite: 4] -->
            <section class="dash-actions-card">
                <h3>Aksi Cepat</h3>
                <div class="action-pills-wrap">
                    <a href="tambah.php" class="btn-act-dark">
                        <i class="fa-solid fa-plus"></i> Tambah Koleksi Buku
                    </a>
                    <a href="index.php" class="btn-act-pill">
                        <i class="fa-solid fa-book-open"></i> Buka Katalog 3D & Tabel
                    </a>
                    <a href="export.php" class="btn-act-pill">
                        <i class="fa-solid fa-file-export"></i> Laporan (PDF &amp; Excel)
                    </a>
                    <a href="data_json.php" target="_blank" class="btn-act-pill">
                        <i class="fa-solid fa-code"></i> Uji API JSON
                    </a>
                </div>
            </section>
        </div>
    </div>

    <!-- Script Inisialisasi Chart.js -->
    <script>
        const kategoriLabels = <?php echo json_encode($kategori_labels); ?>;
        const kategoriTotals = <?php echo json_encode($kategori_totals); ?>;

        const tahunLabels = <?php echo json_encode($tahun_labels); ?>;
        const tahunTotals = <?php echo json_encode($tahun_totals); ?>;

        // Inisialisasi Grafik Batang Kategori
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'bar',
            data: {
                labels: kategoriLabels,
                datasets: [{
                    label: 'Jumlah Buku',
                    data: kategoriTotals,
                    backgroundColor: '#1F2024',
                    borderRadius: 10,
                    borderSkipped: false,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#8E929A' },
                        grid: { color: '#ECE8DE' }
                    },
                    x: {
                        ticks: { color: '#161618', font: { weight: '600' } },
                        grid: { display: false }
                    }
                }
            }
        });

        // Inisialisasi Grafik Donut Tahun Terbit
        const ctxYear = document.getElementById('yearChart').getContext('2d');
        new Chart(ctxYear, {
            type: 'doughnut',
            data: {
                labels: tahunLabels,
                datasets: [{
                    data: tahunTotals,
                    backgroundColor: [
                        '#E0533C',
                        '#3563E9',
                        '#10B981',
                        '#F59E0B',
                        '#8B5CF6',
                        '#64748B'
                    ],
                    borderWidth: 3,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#161618',
                            font: { weight: '600', size: 11 },
                            padding: 12,
                            boxWidth: 12,
                            boxHeight: 12
                        }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>