<?php
/**
 * File: index.php
 * Deskripsi: Halaman Utama Katalog & Tabel Buku dengan Form Export Berdasarkan Kategori
 */

session_start();

// Proteksi sesi login
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once 'koneksi.php';

$user_role = $_SESSION['role'] ?? 'admin';
$nama_user = $_SESSION['nama_lengkap'] ?? $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Buku - Perpustakaan API</title>
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
            --border-paper: #ECE8DE;
            --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.04);
            --shadow-book-3d: 0 25px 35px -10px rgba(0, 0, 0, 0.35);
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

        /* Sidebar */
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

        /* Main Viewport */
        .main-viewport {
            margin-left: 80px;
            flex-grow: 1;
            padding: 1.5rem 3rem 3rem 3rem;
            min-width: 0;
        }

        .app-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .search-input-wrap {
            position: relative;
            width: 380px;
        }

        .search-input-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-input-wrap input {
            width: 100%;
            background: transparent;
            border: none;
            border-bottom: 1.5px solid var(--border-paper);
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            font-size: 0.95rem;
            color: var(--text-black);
            outline: none;
        }

        .header-profile {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .btn-toggle-view {
            background-color: transparent;
            border: 1.5px solid var(--border-paper);
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            color: var(--text-black);
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Editorial Layout */
        .editorial-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 3.5rem;
            align-items: flex-start;
        }

        .greeting-box {
            margin-bottom: 2.5rem;
        }

        .headline-title {
            font-size: 3rem;
            font-weight: 800;
            color: var(--text-black);
            line-height: 1.15;
            margin-bottom: 0.5rem;
        }

        .headline-sub {
            font-size: 1rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .btn-pill-dark {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background-color: var(--accent-dark);
            color: #FFFFFF;
            text-decoration: none;
            padding: 0.85rem 1.8rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .popular-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .popular-header h3 {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-black);
        }

        .filter-dropdown select {
            background-color: var(--bg-white);
            border: 1.5px solid var(--border-paper);
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.85rem;
            outline: none;
        }

        .book-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 1.5rem;
        }

        .book-card-item {
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .book-card-item:hover {
            transform: translateY(-6px);
        }

        .book-cover-3d-wrap {
            perspective: 600px;
            margin-bottom: 0.85rem;
        }

        .book-cover-surface {
            width: 100%;
            aspect-ratio: 2 / 3;
            border-radius: 4px 8px 8px 4px;
            background-color: #2D3748;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            box-shadow: -6px 10px 18px rgba(0, 0, 0, 0.15);
            transform: rotateY(-10deg);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            color: #fff;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            position: relative;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .book-card-item:hover .book-cover-surface {
            transform: rotateY(0deg) scale(1.02);
            box-shadow: 0 14px 24px rgba(0, 0, 0, 0.22);
        }

        .cover-text-fallback {
            padding: 0.5rem;
            color: #FFFFFF;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1.3;
            text-align: center;
            word-break: break-word;
        }

        .book-info-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-black);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-info-author {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* 3D Spotlight */
        .spotlight-card {
            background-color: var(--bg-white);
            border-radius: 28px;
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-paper);
        }

        .book-3d-stage {
            perspective: 1200px;
            height: 240px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .open-book-3d {
            width: 320px;
            height: 200px;
            display: flex;
            transform-style: preserve-3d;
            transform: rotateX(12deg) rotateY(-4deg);
            box-shadow: var(--shadow-book-3d);
        }

        .page {
            flex: 1;
            height: 100%;
            position: relative;
            background-color: #FAF8F2;
        }

        .page-left {
            border-radius: 6px 0 0 6px;
            border-right: 1px solid #D5CEBD;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .page-content h5 {
            font-size: 0.7rem;
            color: var(--accent-red);
            margin-bottom: 0.3rem;
        }

        .page-content h3 {
            font-size: 1.1rem;
            color: var(--text-black);
            margin-bottom: 0.5rem;
        }

        .page-content p {
            font-size: 0.72rem;
            color: #6C707A;
        }

        .page-right {
            border-radius: 0 6px 6px 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #2D3748;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
            padding: 1rem;
            text-align: center;
            position: relative;
        }

        .page-right::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 15px;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.25), transparent);
            pointer-events: none;
            z-index: 2;
        }

        .spotlight-meta h2 {
            font-size: 1.5rem;
            color: var(--text-black);
            margin-bottom: 0.25rem;
        }

        .spotlight-meta p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .badge-chip {
            background-color: var(--bg-main);
            color: var(--text-black);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            display: inline-block;
            margin-right: 0.3rem;
        }

        .spot-action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-spot-edit {
            flex: 1;
            background-color: var(--accent-dark);
            color: #FFFFFF;
            text-align: center;
            padding: 0.65rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
        }

        .btn-spot-delete {
            flex: 1;
            background-color: transparent;
            border: 1.5px solid var(--border-paper);
            color: var(--accent-red);
            padding: 0.65rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
        }

        /* Tabel & Bar Download */
        .table-container-card {
            background-color: var(--bg-white);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-card);
            border: 1px solid var(--border-paper);
            margin-top: 3rem;
        }

        .export-filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: var(--bg-main);
            padding: 1rem 1.25rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-paper);
        }

        .export-form-wrap {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .export-form-wrap select {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            border: 1.5px solid var(--border-paper);
            background: #FFF;
            outline: none;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .btn-exp-excel {
            background-color: #10B981;
            color: #FFF;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .btn-exp-pdf {
            background-color: #EF4444;
            color: #FFF;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .classic-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .classic-table th {
            background-color: var(--bg-main);
            color: var(--text-black);
            padding: 0.85rem 1rem;
            font-weight: 700;
            text-align: left;
            border-bottom: 1.5px solid var(--border-paper);
        }

        .classic-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-paper);
            vertical-align: middle;
        }

        .table-thumb {
            width: 42px;
            height: 58px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            display: block;
        }

        .table-thumb-placeholder {
            width: 42px;
            height: 58px;
            background-color: #2D3748;
            color: #A0AEC0;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        .row-actions {
            display: flex;
            gap: 0.4rem;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            text-decoration: none;
            cursor: pointer;
        }

        .action-edit { background: #FEF3C7; color: #B45309; }
        .action-delete { background: #FEE2E2; color: #DC2626; }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar[cite: 1, 4] -->
        <aside class="sidebar-pill">
            <div class="brand-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <ul class="nav-icons">
                <li class="active"><a href="index.php" title="Koleksi Buku"><i class="fa-solid fa-house"></i></a></li>
                <li><a href="dashboard.php" title="Statistik"><i class="fa-solid fa-chart-pie"></i></a></li>
                <?php if ($user_role === 'admin'): ?>
                    <li><a href="tambah.php" title="Tambah Buku"><i class="fa-solid fa-circle-plus"></i></a></li>
                <?php endif; ?>
                <li><a href="export.php" title="Pusat Cetak & Unduh Laporan (PDF & Excel)"><i class="fa-solid fa-file-export"></i></a></li>
                <li class="nav-bottom"><a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></li>
            </ul>
        </aside>

        <!-- Main Viewport[cite: 4] -->
        <div class="main-viewport">
            <header class="app-header">
                <div class="search-input-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchInput" placeholder="Cari judul buku, penulis, kode...">
                </div>
                <div class="header-profile">
                    <button type="button" id="toggleTableBtn" class="btn-toggle-view"><i class="fa-solid fa-table-cells"></i> Mode Tabel Ujian</button>
                    <div class="user-chip">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="avatar-sm">
                        <span style="font-weight:700; color:#161618;"><?php echo htmlspecialchars($nama_user); ?></span>
                    </div>
                </div>
            </header>

            <!-- Katalog Grid & 3D -->
            <div class="editorial-grid">
                <section>
                    <div class="greeting-box">
                        <h1 class="headline-title">Katalog Koleksi<br>Buku Perpustakaan</h1>
                        <p class="headline-sub">Pilih buku untuk melihat preview detail secara interaktif.</p>
                        <?php if ($user_role === 'admin'): ?>
                            <a href="tambah.php" class="btn-pill-dark"><i class="fa-solid fa-plus"></i> Tambah Buku Baru</a>
                        <?php endif; ?>
                    </div>

                    <div class="popular-header">
                        <h3>Semua Koleksi</h3>
                        <div class="filter-dropdown">
                            <select id="kategoriFilter">
                                <option value="">Semua Kategori</option>
                            </select>
                        </div>
                    </div>

                    <div class="book-row" id="bookRow"></div>
                </section>

                <section>
                    <div class="spotlight-card">
                        <div class="book-3d-stage">
                            <div class="open-book-3d">
                                <div class="page page-left">
                                    <div class="page-content">
                                        <h5 id="stageLeftCategory">KATEGORI</h5>
                                        <h3 id="stageLeftTitle">Judul Buku</h3>
                                        <p id="stageLeftExcerpt">Pilih buku di samping untuk melihat preview.</p>
                                    </div>
                                </div>
                                <div class="page page-right" id="stageRightCover">
                                    <i class="fa-solid fa-book" style="font-size:2rem;"></i>
                                </div>
                            </div>
                        </div>

                        <div class="spotlight-meta">
                            <h2 id="spotTitle">Pilih Buku</h2>
                            <p id="spotAuthor">Penulis Buku</p>
                            <div>
                                <span class="badge-chip" id="spotCategory">Kategori</span>
                                <span class="badge-chip" id="spotCode">Kode</span>
                                <span class="badge-chip" id="spotYear">Tahun</span>
                            </div>
                            <div style="margin-top:1rem; font-size:0.9rem;">Penerbit: <strong id="spotPublisher">-</strong></div>

                            <?php if ($user_role === 'admin'): ?>
                                <div class="spot-action-buttons">
                                    <a href="#" id="spotEditBtn" class="btn-spot-edit"><i class="fa-solid fa-pen-to-square"></i> Edit Data</a>
                                    <button type="button" id="spotDeleteBtn" class="btn-spot-delete"><i class="fa-solid fa-trash"></i> Hapus</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Tabel Data Buku & Bar Cetak Sesuai Kategori[cite: 1] -->
            <section class="table-container-card" id="tableSection" style="display: none;">
                
                <!-- Form Ekspor Kategori Terpadu -->
                <div class="export-filter-bar">
                    <div>
                        <h4 style="color:#161618; font-size:1.05rem;">Pusat Cetak &amp; Unduh Laporan Buku</h4>
                        <p style="font-size:0.8rem; color:#8E929A;">Pilih kategori dan unduh laporan dalam format PDF atau Excel dalam 1 halaman:</p>
                    </div>
                    
                    <form method="GET" action="export.php" target="_blank" class="export-form-wrap">
                        <select name="kategori">
                            <option value="">-- Semua Kategori --</option>
                            <?php
                                // Ambil kategori unik langsung dari tabel database books[cite: 1]
                                $q_opt =$koneksi->query("SELECT DISTINCT kategori FROM books WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
                                if ($q_opt) {
                                    while ($k =$q_opt->fetch_assoc()) {
                                        echo '<option value="' . htmlspecialchars($k['kategori']) . '">' . htmlspecialchars($k['kategori']) . '</option>';
                                    }
                                }
                            ?>
                        </select>
                        <button type="submit" name="action" value="excel" class="btn-exp-excel">
                            <i class="fa-solid fa-file-excel"></i> Unduh Excel
                        </button>
                        <button type="submit" name="action" value="print" class="btn-exp-pdf">
                            <i class="fa-solid fa-file-pdf"></i> Unduh PDF
                        </button>
                        <a href="export.php" class="btn-exp-hub" style="background:#1F2024; color:#FFF; text-decoration:none; padding:0.6rem 1.1rem; border-radius:10px; font-weight:700; font-size:0.85rem; display:inline-flex; align-items:center; gap:0.4rem;">
                            <i class="fa-solid fa-file-export"></i> Buka Halaman Laporan
                        </a>
                    </form>
                </div>

                <table class="classic-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>SAMPUL</th>
                            <th>KODE</th>
                            <th>JUDUL BUKU</th>
                            <th>PENULIS</th>
                            <th>KATEGORI</th>
                            <th>TAHUN</th>
                            <th>PENERBIT</th>
                            <?php if ($user_role === 'admin'): ?><th>AKSI</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="classicTableBody"></tbody>
                </table>
            </section>
        </div>
    </div>

    <!-- Script Fetch API & Logic[cite: 1] -->
    <script>
        const userRole = "<?php echo $user_role; ?>";
        let booksData = [];

        const bookRow = document.getElementById('bookRow');
        const classicTableBody = document.getElementById('classicTableBody');
        const searchInput = document.getElementById('searchInput');
        const kategoriFilter = document.getElementById('kategoriFilter');
        const toggleTableBtn = document.getElementById('toggleTableBtn');
        const tableSection = document.getElementById('tableSection');

        // Elemen Spotlight
        const spotTitle = document.getElementById('spotTitle');
        const spotAuthor = document.getElementById('spotAuthor');
        const spotCategory = document.getElementById('spotCategory');
        const spotCode = document.getElementById('spotCode');
        const spotYear = document.getElementById('spotYear');
        const spotPublisher = document.getElementById('spotPublisher');
        const stageLeftTitle = document.getElementById('stageLeftTitle');
        const stageLeftCategory = document.getElementById('stageLeftCategory');
        const stageLeftExcerpt = document.getElementById('stageLeftExcerpt');
        const stageRightCover = document.getElementById('stageRightCover');
        const spotEditBtn = document.getElementById('spotEditBtn');
        const spotDeleteBtn = document.getElementById('spotDeleteBtn');

        async function fetchBooks() {
            try {
                const res = await fetch('data_json.php'); // Ambil JSON API
                booksData = await res.json();
                
                // Isi filter popular dropdown
                const cats = [...new Set(booksData.map(b => b.kategori).filter(Boolean))].sort();
                kategoriFilter.innerHTML = '<option value="">Semua Kategori</option>';
                cats.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    kategoriFilter.appendChild(opt);
                });

                renderCards(booksData);
                renderTable(booksData);
                if (booksData.length > 0) showSpotlight(booksData[0]);
            } catch (e) {
                console.error("Gagal load API", e);
            }
        }

        function renderCards(list) {
            bookRow.innerHTML = '';
            if (!list || list.length === 0) {
                bookRow.innerHTML = '<p style="color:var(--text-muted); font-size:0.95rem; grid-column: 1 / -1; padding: 1.5rem 0;">Tidak ada koleksi buku yang sesuai.</p>';
                return;
            }
            list.forEach(b => {
                const div = document.createElement('div');
                div.className = 'book-card-item';
                div.onclick = () => showSpotlight(b);

                const hasCover = b.cover && b.cover.trim() !== '';
                const coverStyle = hasCover 
                    ? `background-image: url('uploads/${encodeURIComponent(b.cover)}');` 
                    : '';
                const fallbackContent = hasCover 
                    ? '' 
                    : `<span class="cover-text-fallback">${escapeHtml(b.judul)}</span>`;

                div.innerHTML = `
                    <div class="book-cover-3d-wrap">
                        <div class="book-cover-surface" style="${coverStyle}">
                            ${fallbackContent}
                        </div>
                    </div>
                    <div class="book-info-title" title="${escapeHtml(b.judul)}">${escapeHtml(b.judul)}</div>
                    <div class="book-info-author">${escapeHtml(b.penulis)}</div>
                `;
                bookRow.appendChild(div);
            });
        }

        function showSpotlight(b) {
            spotTitle.textContent = b.judul;
            spotAuthor.textContent = "Oleh: " + b.penulis;
            spotCategory.textContent = b.kategori;
            spotCode.textContent = b.kode_buku;
            spotYear.textContent = b.tahun_terbit;
            spotPublisher.textContent = b.penerbit;
            stageLeftTitle.textContent = b.judul;
            stageLeftCategory.textContent = (b.kategori || '').toUpperCase();
            if (stageLeftExcerpt) {
                stageLeftExcerpt.textContent = `Buku "${b.judul}" karya ${b.penulis}, diterbitkan oleh ${b.penerbit} (${b.tahun_terbit}).`;
            }

            if (stageRightCover) {
                if (b.cover && b.cover.trim() !== '') {
                    stageRightCover.style.backgroundImage = `url('uploads/${encodeURIComponent(b.cover)}')`;
                    stageRightCover.style.backgroundSize = 'cover';
                    stageRightCover.style.backgroundPosition = 'center';
                    stageRightCover.innerHTML = '';
                } else {
                    stageRightCover.style.backgroundImage = 'none';
                    stageRightCover.innerHTML = `<i class="fa-solid fa-book" style="font-size:2.2rem; margin-bottom:0.5rem;"></i><span style="font-size:0.8rem; font-weight:600; display:block; padding:0 0.5rem;">${escapeHtml(b.judul)}</span>`;
                }
            }

            if (userRole === 'admin') {
                spotEditBtn.href = `edit.php?id=${b.id}`;
                spotDeleteBtn.onclick = () => {
                    if (confirm(`Yakin ingin menghapus ${b.judul}?`)) {
                        window.location.href = `hapus.php?id=${b.id}`;
                    }
                };
            }
        }

        function renderTable(list) {
            classicTableBody.innerHTML = '';
            if (!list || list.length === 0) {
                const colSpan = userRole === 'admin' ? 9 : 8;
                classicTableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center; color:var(--text-muted); padding:2rem;">Data buku tidak ditemukan.</td></tr>`;
                return;
            }
            list.forEach((b, i) => {
                const tr = document.createElement('tr');
                let act = '';
                if (userRole === 'admin') {
                    act = `
                        <td>
                            <div class="row-actions">
                                <a href="edit.php?id=${b.id}" class="action-btn action-edit" title="Edit Data"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus.php?id=${b.id}" onclick="return confirm('Hapus data buku ${escapeHtml(b.judul)}?')" class="action-btn action-delete" title="Hapus Data"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    `;
                }

                const hasCover = b.cover && b.cover.trim() !== '';
                const coverThumb = hasCover
                    ? `<img src="uploads/${encodeURIComponent(b.cover)}" alt="${escapeHtml(b.judul)}" class="table-thumb" onerror="this.onerror=null; this.outerHTML='<div class=\\'table-thumb-placeholder\\'><i class=\\'fa-solid fa-book\\'></i></div>';">`
                    : `<div class="table-thumb-placeholder"><i class="fa-solid fa-book"></i></div>`;

                tr.innerHTML = `
                    <td>${i + 1}</td>
                    <td>${coverThumb}</td>
                    <td><code>${escapeHtml(b.kode_buku)}</code></td>
                    <td><strong>${escapeHtml(b.judul)}</strong></td>
                    <td>${escapeHtml(b.penulis)}</td>
                    <td>${escapeHtml(b.kategori)}</td>
                    <td>${escapeHtml(b.tahun_terbit)}</td>
                    <td>${escapeHtml(b.penerbit)}</td>
                    ${act}
                `;
                classicTableBody.appendChild(tr);
            });
        }

        function escapeHtml(s) {
            return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // Event Pencarian & Filter
        function runFilter() {
            const q = searchInput.value.toLowerCase().trim();
            const c = kategoriFilter.value;
            const res = booksData.filter(b => {
                const matchQ = (b.judul || '').toLowerCase().includes(q) || (b.penulis || '').toLowerCase().includes(q) || (b.kode_buku || '').toLowerCase().includes(q);
                const matchC = !c || b.kategori === c;
                return matchQ && matchC;
            });
            renderCards(res);
            renderTable(res);
            if (res.length > 0) {
                showSpotlight(res[0]);
            }
        }

        searchInput.addEventListener('input', runFilter);
        kategoriFilter.addEventListener('change', runFilter);

        toggleTableBtn.addEventListener('click', () => {
            tableSection.style.display = tableSection.style.display === 'none' ? 'block' : 'none';
        });

        document.addEventListener('DOMContentLoaded', fetchBooks);
    </script>
</body>
</html>