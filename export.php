<?php
/**
 * File: export.php
 * Deskripsi: Halaman Terpadu Cetak & Unduh Laporan Data Buku (PDF & Excel dalam 1 Halaman)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi sesi login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once 'koneksi.php';

$user_role   = $_SESSION['role'] ?? 'admin';
$nama_admin  = $_SESSION['nama_lengkap'] ?? $_SESSION['admin'];
$kategori    = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$action      = isset($_GET['action']) ? trim($_GET['action']) : '';

// 1. Query Data Buku Sesuai Kategori
if (!empty($kategori)) {
    $stmt = $koneksi->prepare("SELECT * FROM books WHERE LOWER(TRIM(kategori)) = LOWER(TRIM(?)) ORDER BY id ASC");
    $stmt->bind_param("s", $kategori);
    $stmt->execute();
    $result_data = $stmt->get_result();
    $label_laporan = "Kategori: " . htmlspecialchars($kategori);
    $file_slug = preg_replace('/[^a-zA-Z0-9_-]/', '_', $kategori);
} else {
    $result_data = $koneksi->query("SELECT * FROM books ORDER BY id ASC");
    $label_laporan = "Semua Kategori";
    $file_slug = "semua_kategori";
}

$buku_list = [];
if ($result_data && $result_data->num_rows > 0) {
    while ($row = $result_data->fetch_assoc()) {
        $buku_list[] = $row;
    }
}
$total_data = count($buku_list);

// 2. Aksi: UNDUH EXCEL LANGSUNG JIKA ACTION = EXCEL
if ($action === 'excel') {
    $nama_file = "laporan_buku_" . $file_slug . "_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$nama_file\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
    </head>
    <body>
        <table border="1">
            <tr>
                <th colspan="7" style="background-color: #F8F6F0; font-size: 13pt; font-weight: bold; text-align: center; height: 38px;">
                    LAPORAN DATA BUKU PERPUSTAKAAN (<?php echo strtoupper($label_laporan); ?>)
                </th>
            </tr>
            <tr>
                <th colspan="7" style="text-align: center; font-size: 9pt; color: #555555; height: 26px;">
                    Waktu Unduh: <?php echo date('d-m-Y H:i:s'); ?> | Petugas: <?php echo htmlspecialchars($nama_admin); ?> | Total Data: <?php echo $total_data; ?> Buku
                </th>
            </tr>
            <tr style="background-color: #1F2024; color: #FFFFFF; font-weight: bold; height: 32px;">
                <th style="width: 45px; text-align: center;">NO</th>
                <th style="width: 110px; text-align: center;">KODE BUKU</th>
                <th style="width: 260px;">JUDUL BUKU</th>
                <th style="width: 190px;">PENULIS</th>
                <th style="width: 140px;">KATEGORI</th>
                <th style="width: 85px; text-align: center;">TAHUN</th>
                <th style="width: 190px;">PENERBIT</th>
            </tr>
            <?php if ($total_data > 0): ?>
                <?php $no = 1; foreach ($buku_list as $buku): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $no++; ?></td>
                        <td style="text-align: center;">'<?php echo htmlspecialchars($buku['kode_buku']); ?></td>
                        <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                        <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                        <td><?php echo htmlspecialchars($buku['kategori']); ?></td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($buku['tahun_terbit']); ?></td>
                        <td><?php echo htmlspecialchars($buku['penerbit']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 12px;">Tidak ada koleksi pada kategori ini.</td>
                </tr>
            <?php endif; ?>
        </table>
    </body>
    </html>
    <?php
    exit();
}

// 3. Ambil Kategori Unik untuk Filter Dropdown
$kategori_options = [];
$q_cats = $koneksi->query("SELECT DISTINCT kategori FROM books WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
if ($q_cats) {
    while ($c = $q_cats->fetch_assoc()) {
        $kategori_options[] = $c['kategori'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Laporan &bull; Unduh PDF &amp; Excel - Perpustakaan API</title>
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #F8F6F0;
            --bg-white: #FFFFFF;
            --text-black: #161618;
            --text-body: #494C52;
            --text-muted: #8E929A;
            --accent-red: #E0533C;
            --accent-dark: #1F2024;
            --accent-green: #10B981;
            --border-paper: #ECE8DE;
            --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.04);
            --shadow-paper: 0 15px 35px rgba(0, 0, 0, 0.07);
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

        /* Sidebar Navigasi */
        .sidebar-pill {
            width: 80px;
            background-color: var(--bg-white);
            border-right: 1px solid var(--border-paper);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem 0;
            position: sticky;
            top: 0;
            height: 100vh;
            flex-shrink: 0;
        }

        .brand-icon {
            font-size: 1.5rem;
            color: var(--accent-red);
            margin-bottom: 2.5rem;
        }

        .nav-icons {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
            align-items: center;
        }

        .nav-icons li a {
            color: var(--text-muted);
            font-size: 1.25rem;
            text-decoration: none;
            transition: all 0.2s ease;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-icons li.active a,
        .nav-icons li a:hover {
            color: var(--accent-dark);
            background-color: var(--bg-main);
        }

        .nav-bottom {
            margin-top: auto;
        }

        .nav-bottom a:hover {
            color: var(--accent-red) !important;
        }

        /* Main Viewport */
        .main-viewport {
            flex-grow: 1;
            padding: 2.5rem 3.5rem;
            max-width: 1400px;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }

        .header-title-box h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-black);
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }

        .header-title-box p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: var(--bg-white);
            border: 1px solid var(--border-paper);
            padding: 0.4rem 1rem 0.4rem 0.5rem;
            border-radius: 30px;
        }

        .avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Control & Filter Card */
        .export-hub-card {
            background-color: var(--bg-white);
            border: 1px solid var(--border-paper);
            border-radius: 20px;
            padding: 1.8rem 2.2rem;
            box-shadow: var(--shadow-card);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .hub-filter-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .hub-filter-group label {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-black);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .select-category {
            padding: 0.65rem 1.25rem;
            border-radius: 12px;
            border: 1.5px solid var(--border-paper);
            background-color: var(--bg-main);
            color: var(--text-black);
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .select-category:focus {
            background-color: #FFFFFF;
            border-color: var(--accent-dark);
        }

        .hub-actions-group {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
        }

        .btn-action-hub {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.4rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-excel {
            background-color: #10B981;
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
        }

        .btn-excel:hover {
            background-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16, 185, 129, 0.35);
        }

        .btn-pdf {
            background-color: #EF4444;
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
        }

        .btn-pdf:hover {
            background-color: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.35);
        }

        .btn-back-katalog {
            background-color: var(--bg-main);
            color: var(--text-black);
            border: 1px solid var(--border-paper);
        }

        .btn-back-katalog:hover {
            background-color: #EDE8DE;
        }

        /* Pratinjau Kertas Dokumen Resmi (Document Paper) */
        .doc-paper-container {
            max-width: 960px;
            margin: 0 auto;
        }

        .doc-paper {
            background: #FFFFFF;
            border-radius: 16px;
            border: 1px solid var(--border-paper);
            padding: 3.5rem 3.5rem;
            box-shadow: var(--shadow-paper);
            position: relative;
        }

        .doc-badge-preview {
            position: absolute;
            top: 1.5rem;
            right: 1.8rem;
            background: #FEF3C7;
            color: #92400E;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            border: 1px solid #FCD34D;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        /* Kop Laporan */
        .kop-header {
            text-align: center;
            border-bottom: 2.5px solid #161618;
            padding-bottom: 1.25rem;
            margin-bottom: 1.8rem;
        }

        .kop-header h2 {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #161618;
            margin-bottom: 0.25rem;
        }

        .kop-header p {
            font-size: 0.9rem;
            color: #555555;
            letter-spacing: 0.02em;
        }

        /* Meta Informasi Laporan */
        .meta-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 0.88rem;
            color: #333333;
            background-color: #FAF8F5;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border: 1px solid var(--border-paper);
        }

        /* Tabel Laporan */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
            margin-bottom: 2.5rem;
        }

        .report-table th {
            background-color: #ECE8DE;
            color: #161618;
            border: 1px solid #D5CEBD;
            padding: 10px 12px;
            font-weight: 700;
            text-align: left;
        }

        .report-table td {
            border: 1px solid #D5CEBD;
            padding: 9px 12px;
            color: #2D3748;
            vertical-align: middle;
        }

        .report-table tr:nth-child(even) {
            background-color: #FAF8F5;
        }

        .text-center {
            text-align: center;
        }

        /* Kolom Tanda Tangan */
        .signature-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 2rem;
            font-size: 0.88rem;
        }

        .signature-box {
            width: 240px;
            text-align: center;
        }

        .signature-space {
            height: 65px;
        }

        /* ==============================================
           PRINT MEDIA QUERIES (HANYA MENCETAK DOKUMEN)
           ============================================== */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
                color: #000 !important;
            }

            .sidebar-pill,
            .app-header,
            .export-hub-card,
            .doc-badge-preview {
                display: none !important;
            }

            .main-viewport {
                padding: 0 !important;
                max-width: 100% !important;
            }

            .doc-paper-container {
                max-width: 100% !important;
                margin: 0 !important;
            }

            .doc-paper {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                padding: 0 !important;
            }

            .report-table th {
                background-color: #ECE8DE !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .meta-bar {
                background-color: #F3F4F6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media (max-width: 900px) {
            .app-container {
                flex-direction: column;
            }

            .sidebar-pill {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                padding: 1rem 1.5rem;
                position: relative;
            }

            .nav-icons {
                flex-direction: row;
                gap: 0.8rem;
                width: auto;
            }

            .main-viewport {
                padding: 1.5rem;
            }

            .doc-paper {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar Navigasi -->
        <aside class="sidebar-pill">
            <div class="brand-icon">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <ul class="nav-icons">
                <li><a href="index.php" title="Koleksi Buku &amp; Katalog 3D"><i class="fa-solid fa-house"></i></a></li>
                <li><a href="dashboard.php" title="Statistik &amp; Analisis"><i class="fa-solid fa-chart-pie"></i></a></li>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="tambah.php" title="Tambah Buku"><i class="fa-solid fa-circle-plus"></i></a></li>
                <?php endif; ?>
                <!-- Menu Aktif: Laporan Terpadu (PDF & Excel) -->
                <li class="active"><a href="export.php" title="Pusat Cetak &amp; Unduh Laporan (PDF &amp; Excel)"><i class="fa-solid fa-file-export"></i></a></li>
                <li class="nav-bottom">
                    <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" title="Logout">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Area Konten Utama -->
        <main class="main-viewport">
            
            <!-- Topbar Header -->
            <header class="app-header">
                <div class="header-title-box">
                    <h1>Pusat Laporan &amp; Ekspor Data</h1>
                    <p>Unduh data inventaris buku dalam format PDF atau Excel dalam 1 halaman terpadu.</p>
                </div>
                <div class="user-chip">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="avatar-sm">
                    <span style="font-weight:700; color:#161618; font-size:0.9rem;"><?php echo htmlspecialchars($nama_admin); ?></span>
                </div>
            </header>

            <!-- Card Kontrol Filter & Dua Tombol Ekspor -->
            <section class="export-hub-card">
                
                <!-- Form Filter Kategori -->
                <form method="GET" action="export.php" id="filterForm" class="hub-filter-group">
                    <label for="selectKategori">
                        <i class="fa-solid fa-filter"></i> Filter Kategori:
                    </label>
                    <select name="kategori" id="selectKategori" class="select-category" onchange="this.form.submit()">
                        <option value="">-- Semua Kategori (Total <?php echo count($buku_list); ?> Buku) --</option>
                        <?php foreach ($kategori_options as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($kategori === $cat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <!-- Tombol Unduh PDF dan Excel dalam 1 Baris Aksi -->
                <div class="hub-actions-group">
                    <!-- Tombol 1: Unduh Excel Langsung -->
                    <a href="export.php?action=excel<?php echo !empty($kategori) ? '&kategori=' . urlencode($kategori) : ''; ?>" class="btn-action-hub btn-excel" title="Download File Spreadsheet Excel">
                        <i class="fa-solid fa-file-excel"></i> Unduh Excel (.xls)
                    </a>

                    <!-- Tombol 2: Cetak / Simpan PDF -->
                    <button type="button" onclick="window.print()" class="btn-action-hub btn-pdf" title="Cetak atau Simpan Sebagai PDF">
                        <i class="fa-solid fa-file-pdf"></i> Cetak / Unduh PDF
                    </button>

                    <!-- Tombol Kembali ke Katalog -->
                    <a href="index.php" class="btn-action-hub btn-back-katalog">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Katalog
                    </a>
                </div>
            </section>

            <!-- Pratinjau Dokumen Kertas Laporan Resmi (Live Document Preview) -->
            <div class="doc-paper-container">
                <article class="doc-paper">
                    
                    <div class="doc-badge-preview">
                        <i class="fa-solid fa-eye"></i> Pratinjau Dokumen Siap Cetak
                    </div>

                    <!-- KOP Resmi -->
                    <div class="kop-header">
                        <h2>PERPUSTAKAAN SEKOLAH</h2>
                        <p>Laporan Resmi Inventaris Koleksi Data Buku</p>
                    </div>

                    <!-- Meta Informasi Laporan -->
                    <div class="meta-bar">
                        <div>Filter Data: <strong><?php echo $label_laporan; ?></strong></div>
                        <div>Total: <strong><?php echo $total_data; ?> Buku</strong></div>
                        <div>Tanggal: <strong><?php echo date('d-m-Y H:i'); ?> WIB</strong></div>
                    </div>

                    <!-- Tabel Data Laporan -->
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">NO</th>
                                <th width="12%" class="text-center">KODE</th>
                                <th width="30%">JUDUL BUKU</th>
                                <th width="20%">PENULIS</th>
                                <th width="15%">KATEGORI</th>
                                <th width="8%" class="text-center">TAHUN</th>
                                <th width="10%">PENERBIT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_data > 0): ?>
                                <?php $no = 1; foreach ($buku_list as $buku): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $no++; ?></td>
                                        <td class="text-center"><strong><?php echo htmlspecialchars($buku['kode_buku']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($buku['judul']); ?></td>
                                        <td><?php echo htmlspecialchars($buku['penulis']); ?></td>
                                        <td><?php echo htmlspecialchars($buku['kategori']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($buku['tahun_terbit']); ?></td>
                                        <td><?php echo htmlspecialchars($buku['penerbit']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding: 2rem; color: #888;">
                                        Tidak ada koleksi buku pada kategori ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Tanda Tangan Pengesahan -->
                    <div class="signature-row">
                        <div class="signature-box">
                            <p>Petugas Perpustakaan,</p>
                            <div class="signature-space"></div>
                            <p><strong>( <?php echo htmlspecialchars($nama_admin); ?> )</strong></p>
                        </div>
                    </div>
                </article>
            </div>
        </main>
    </div>

    <!-- Script jika diakses dengan action=print atau parameter cetak otomatis -->
    <?php if ($action === 'print' || $action === 'pdf'): ?>
        <script>
            window.addEventListener('load', () => {
                setTimeout(() => { window.print(); }, 400);
            });
        </script>
    <?php endif; ?>
</body>
</html>
