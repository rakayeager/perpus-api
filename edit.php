<?php
require_once 'koneksi.php';

/**
 * File: edit.php
 * Deskripsi: Halaman Edit Koleksi Buku dengan tema Editorial Minimalis dan upload/ganti sampul
 */

session_start();

// 1. Validasi autentikasi admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Hubungkan ke database
require_once 'koneksi.php';

// Ambil parameter ID buku dari URL[cite: 1]
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit();
}

$error_message = '';

// Ambil data buku saat ini dari database berdasarkan ID[cite: 1]
$stmt = $koneksi->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: index.php");
    exit();
}

$buku = $result->fetch_assoc();
$stmt->close();

// Proses saat formulir update dikirim melalui POST[cite: 1]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_buku    = trim($_POST['kode_buku'] ?? '');
    $judul        = trim($_POST['judul'] ?? '');
    $penulis      = trim($_POST['penulis'] ?? '');
    $kategori     = trim($_POST['kategori'] ?? '');
    $tahun_terbit = trim($_POST['tahun_terbit'] ?? '');
    $penerbit     = trim($_POST['penerbit'] ?? '');
    $current_year = (int) date('Y');

    // 1. Validasi field wajib[cite: 1]
    if (empty($kode_buku) || empty($judul) || empty($penulis) || empty($kategori) || empty($tahun_terbit) || empty($penerbit)) {
        $error_message = "Semua kolom bertanda bintang (*) wajib diisi!";
    } 
    // 2. Validasi rentang tahun terbit[cite: 1]
    elseif (!is_numeric($tahun_terbit) || (int)$tahun_terbit < 1900 || (int)$tahun_terbit > $current_year) {
        $error_message = "Tahun terbit harus berupa angka valid antara 1900 hingga {$current_year}!";
    } 
    else {
        try {
            // 3. Pengecekan kode buku unik (kecuali milik buku ini sendiri)
            $stmt_check = $koneksi->prepare("SELECT id FROM books WHERE kode_buku = ? AND id != ? LIMIT 1");
            $stmt_check->bind_param("si", $kode_buku, $id);
            $stmt_check->execute();
            $check_res = $stmt_check->get_result();

            if ($check_res && $check_res->num_rows > 0) {
                $error_message = "Kode buku '{$kode_buku}' sudah digunakan oleh buku lain!";
                $stmt_check->close();
            } else {
                $stmt_check->close();

                // 4. Pengelolaan file cover baru jika ada yang diunggah
                $nama_cover = $buku['cover']; // default tetap gunakan cover lama

                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp  = $_FILES['cover']['tmp_name'];
                    $file_name = $_FILES['cover']['name'];
                    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

                    if (in_array($file_ext, $allowed)) {
                        if (!is_dir('uploads')) {
                            mkdir('uploads', 0777, true);
                        }

                        // Buat nama unik file baru
                        $new_cover_name = 'cover_' . time() . '_' . uniqid() . '.' . $file_ext;

                        if (move_uploaded_file($file_tmp, 'uploads/' . $new_cover_name)) {
                            // Hapus cover lama dari folder uploads jika ada
                            if (!empty($buku['cover']) && file_exists('uploads/' . $buku['cover'])) {
                                unlink('uploads/' . $buku['cover']);
                            }
                            $nama_cover = $new_cover_name;
                        }
                    } else {
                        $error_message = "Format sampul harus berupa JPG, JPEG, PNG, atau WEBP!";
                    }
                }

                // 5. Eksekusi query UPDATE data buku jika validasi lolos[cite: 1]
                if (empty($error_message)) {
                    $stmt_up = $koneksi->prepare(
                        "UPDATE books SET kode_buku = ?, judul = ?, penulis = ?, kategori = ?, tahun_terbit = ?, penerbit = ?, cover = ? WHERE id = ?"
                    );
                    $stmt_up->bind_param("ssssissi", $kode_buku, $judul, $penulis, $kategori, $tahun_terbit, $penerbit, $nama_cover, $id);

                    if ($stmt_up->execute()) {
                        $stmt_up->close();
                        $_SESSION['flash'] = [
                            'type'    => 'success',
                            'message' => 'Data buku "' . htmlspecialchars($judul) . '" berhasil diperbarui!'
                        ];
                        header("Location: index.php");
                        exit();
                    } else {
                        $error_message = "Gagal memperbarui data buku di database.";
                        $stmt_up->close();
                    }
                }
            }
        } catch (Exception $e) {
            $error_message = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Koleksi Buku - Perpustakaan API</title>
    <!-- Pemanggilan stylesheet dengan time() untuk mencegah caching file CSS -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-editorial-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-editorial-card {
            background-color: #FFFFFF;
            border: 1.5px solid var(--border-paper, #ECE8DE);
            border-radius: 28px;
            padding: 2.5rem 3rem;
            box-shadow: var(--shadow-card, 0 15px 35px rgba(0, 0, 0, 0.04));
        }
        .form-title-group {
            margin-bottom: 2rem;
            border-bottom: 1.5px solid var(--border-paper, #ECE8DE);
            padding-bottom: 1.25rem;
        }
        .form-title-group h2 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-black, #161618);
            margin-bottom: 0.35rem;
        }
        .form-title-group p {
            color: var(--text-muted, #8E929A);
            font-size: 0.95rem;
        }
        .field-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        .field-unit {
            margin-bottom: 1.35rem;
        }
        .field-unit label {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-black, #161618);
            margin-bottom: 0.5rem;
        }
        .field-unit input {
            width: 100%;
            padding: 0.85rem 1.2rem;
            background-color: var(--bg-main, #F8F6F0);
            border: 1.5px solid var(--border-paper, #ECE8DE);
            border-radius: 14px;
            font-size: 0.95rem;
            color: var(--text-black, #161618);
            outline: none;
            transition: all 0.2s ease;
        }
        .field-unit input:focus {
            background-color: #FFFFFF;
            border-color: var(--accent-dark, #1F2024);
            box-shadow: 0 0 0 4px rgba(31, 32, 36, 0.08);
        }
        .file-upload-box {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            background-color: var(--bg-main, #F8F6F0);
            border: 1.5px dashed var(--border-paper, #ECE8DE);
            border-radius: 16px;
            padding: 1.25rem;
        }
        .preview-box {
            width: 75px;
            height: 105px;
            border-radius: 8px;
            background-color: #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid var(--border-paper, #ECE8DE);
        }
        .preview-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-box i {
            color: var(--text-muted, #8E929A);
            font-size: 1.75rem;
        }
        .file-input-meta {
            flex-grow: 1;
        }
        .file-input-meta input[type="file"] {
            font-size: 0.85rem;
            color: var(--text-body, #494C52);
        }
        .file-hint {
            display: block;
            margin-top: 0.4rem;
            font-size: 0.78rem;
            color: var(--text-muted, #8E929A);
        }
        .actions-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1.5px solid var(--border-paper, #ECE8DE);
        }
        .btn-cancel {
            background: transparent;
            border: 1.5px solid var(--border-paper, #ECE8DE);
            color: var(--text-body, #494C52);
            padding: 0.8rem 1.8rem;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-cancel:hover {
            background-color: var(--bg-main, #F8F6F0);
            color: var(--text-black, #161618);
        }
        .btn-submit {
            background-color: var(--accent-dark, #1F2024);
            color: #FFFFFF;
            border: none;
            padding: 0.85rem 2rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background-color: #000000;
            transform: translateY(-2px);
        }
        .req-star {
            color: var(--accent-red, #E0533C);
        }
        @media (max-width: 640px) {
            .field-grid { grid-template-columns: 1fr; gap: 0; }
            .form-editorial-card { padding: 1.5rem; }
            .file-upload-box { flex-direction: column; align-items: flex-start; }
            .actions-bar { flex-direction: column-reverse; }
            .btn-cancel, .btn-submit { width: 100%; text-align: center; justify-content: center; }
        }
    </style>
</head>
<body class="editorial-body">

    <div class="app-container">
        <!-- Sidebar Minimalis Sesuai Mockup[cite: 1] -->
        <aside class="sidebar-pill">
            <div class="brand-icon">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <ul class="nav-icons">
                <li><a href="index.php" title="Koleksi Buku"><i class="fa-solid fa-book-open"></i></a></li>
                <li><a href="dashboard.php" title="Statistik"><i class="fa-solid fa-chart-pie"></i></a></li>
                <li><a href="tambah.php" title="Tambah Buku"><i class="fa-solid fa-circle-plus"></i></a></li>
                <li><a href="export.php" title="Pusat Cetak & Unduh Laporan (PDF & Excel)"><i class="fa-solid fa-file-export"></i></a></li>
                <li class="nav-bottom"><a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin logout?');" title="Logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a></li>
            </ul>
        </aside>

        <!-- Main Viewport -->
        <div class="main-viewport">
            <header class="app-header">
                <div class="search-input-wrap">
                    <span class="headline-sub" style="margin: 0; font-weight: 700; color: var(--text-black);">Formulir Edit Data Buku</span>
                </div>
                <div class="header-profile">
                    <div class="user-chip">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80" alt="Avatar" class="avatar-sm">
                        <span class="user-text"><?php echo htmlspecialchars($_SESSION['admin']); ?></span>
                    </div>
                </div>
            </header>

            <main class="content-area" style="padding: 0;">
                <div class="form-editorial-wrapper">
                    <div class="form-editorial-card">
                        <div class="form-title-group">
                            <h2>Perbarui Informasi Buku</h2>
                            <p>Ubah detail koleksi buku perpustakaan yang tersimpan di sistem.</p>
                        </div>

                        <!-- Pesan Kesalahan Jika Validasi Gagal[cite: 1] -->
                        <?php if (!empty($error_message)): ?>
                            <div class="flash-alert flash-danger">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span><?php echo htmlspecialchars($error_message); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Formulir Edit Data[cite: 1] -->
                        <form action="edit.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
                            <div class="field-grid">
                                <div class="field-unit">
                                    <label for="kode_buku">Kode Buku <span class="req-star">*</span></label>
                                    <input 
                                        type="text" 
                                        id="kode_buku" 
                                        name="kode_buku" 
                                        value="<?php echo htmlspecialchars($buku['kode_buku']); ?>" 
                                        required 
                                        autofocus
                                    >
                                </div>
                                <div class="field-unit">
                                    <label for="kategori">Kategori <span class="req-star">*</span></label>
                                    <input 
                                        type="text" 
                                        id="kategori" 
                                        name="kategori" 
                                        value="<?php echo htmlspecialchars($buku['kategori']); ?>" 
                                        required
                                    >
                                </div>
                            </div>

                            <div class="field-unit">
                                <label for="judul">Judul Lengkap Buku <span class="req-star">*</span></label>
                                <input 
                                    type="text" 
                                    id="judul" 
                                    name="judul" 
                                    value="<?php echo htmlspecialchars($buku['judul']); ?>" 
                                    required
                                >
                            </div>

                            <div class="field-grid">
                                <div class="field-unit">
                                    <label for="penulis">Nama Penulis <span class="req-star">*</span></label>
                                    <input 
                                        type="text" 
                                        id="penulis" 
                                        name="penulis" 
                                        value="<?php echo htmlspecialchars($buku['penulis']); ?>" 
                                        required
                                    >
                                </div>
                                <div class="field-unit">
                                    <label for="tahun_terbit">Tahun Terbit <span class="req-star">*</span></label>
                                    <input 
                                        type="number" 
                                        id="tahun_terbit" 
                                        name="tahun_terbit" 
                                        min="1900" 
                                        max="<?php echo date('Y'); ?>" 
                                        value="<?php echo htmlspecialchars($buku['tahun_terbit']); ?>" 
                                        required
                                    >
                                </div>
                            </div>

                            <div class="field-unit">
                                <label for="penerbit">Penerbit <span class="req-star">*</span></label>
                                <input 
                                    type="text" 
                                    id="penerbit" 
                                    name="penerbit" 
                                    value="<?php echo htmlspecialchars($buku['penerbit']); ?>" 
                                    required
                                >
                            </div>

                            <!-- Upload dan Pratinjau Sampul Saat Ini / Baru -->
                            <div class="field-unit">
                                <label>Foto Sampul (Cover Buku)</label>
                                <div class="file-upload-box">
                                    <div class="preview-box" id="previewContainer">
                                        <?php if (!empty($buku['cover']) && file_exists('uploads/' . $buku['cover'])): ?>
                                            <img id="imgPreview" src="uploads/<?php echo htmlspecialchars($buku['cover']); ?>" alt="Sampul Buku">
                                            <i class="fa-solid fa-book" id="defaultIcon" style="display: none;"></i>
                                        <?php else: ?>
                                            <img id="imgPreview" alt="Pratinjau Sampul" style="display: none;">
                                            <i class="fa-solid fa-book" id="defaultIcon"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="file-input-meta">
                                        <input type="file" id="coverInput" name="cover" accept=".jpg,.jpeg,.png,.webp">
                                        <span class="file-hint">Biarkan kosong jika tidak ingin mengganti sampul. Format yang didukung: JPG, PNG, WEBP.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi[cite: 1] -->
                            <div class="actions-bar">
                                <a href="index.php" class="btn-cancel"><i class="fa-solid fa-arrow-left"></i> Batal</a>
                                <button type="submit" class="btn-submit"><i class="fa-solid fa-pen-to-square"></i> Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Script Live Preview Gambar Sampul -->
    <script>
        const coverInput = document.getElementById('coverInput');
        const imgPreview = document.getElementById('imgPreview');
        const defaultIcon = document.getElementById('defaultIcon');

        coverInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    imgPreview.style.display = 'block';
                    if (defaultIcon) defaultIcon.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>