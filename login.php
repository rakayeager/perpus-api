<?php
/**
 * File: login.php
 * Deskripsi: Halaman autentikasi login administrator Perpustakaan API
 * Desain: Editorial Split-Screen Modern, Responsive, & Interactive
 */

session_start();

// Jika admin sudah dalam keadaan login, langsung arahkan ke dashboard
if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'koneksi.php';

$error_message = "";
$username_input = "";

// Ambil statistik real-time untuk ditampilkan pada hero card
$total_buku = 0;
$total_kategori = 0;
try {
    $q_buku = $koneksi->query("SELECT COUNT(*) AS total FROM books");
    if ($q_buku && $row = $q_buku->fetch_assoc()) {
        $total_buku = (int)$row['total'];
    }
    $q_kat = $koneksi->query("SELECT COUNT(DISTINCT kategori) AS total FROM books");
    if ($q_kat && $row = $q_kat->fetch_assoc()) {
        $total_kategori = (int)$row['total'];
    }
} catch (Exception $e) {
    // Abaikan jika query statistik bermasalah
}

// Cek apakah form dikirim melalui metode POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $username_input = $username;

    // Validasi input
    if (empty($username) || empty($password)) {
        $error_message = "Username dan kata sandi wajib diisi!";
    } else {
        try {
            // Gunakan Prepared Statement untuk mencegah SQL Injection
            $stmt = $koneksi->prepare("SELECT id, username, password FROM admins WHERE username = ? LIMIT 1");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Mendukung password dengan hash password_verify() maupun teks biasa
                $is_valid = false;
                if (password_verify($password, $admin['password'])) {
                    $is_valid = true;
                } elseif ($password === $admin['password']) {
                    $is_valid = true;
                }

                if ($is_valid) {
                    $_SESSION['admin'] = $admin['username'];
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['nama_lengkap'] = ucfirst($admin['username']);

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Kata sandi yang Anda masukkan tidak sesuai!";
                }
            } else {
                $error_message = "Akun administrator dengan username tersebut tidak ditemukan!";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Terjadi gangguan sistem koneksi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator &bull; Perpustakaan API</title>
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-page: #F6F4ED;
            --bg-surface: #FFFFFF;
            --dark-hero: #16171B;
            --dark-surface: #1E2025;
            --dark-card: #282A30;
            --accent-primary: #1F2024;
            --accent-hover: #0E0F12;
            --accent-terracotta: #E0533C;
            --accent-emerald: #10B981;
            --text-heading: #121316;
            --text-body: #494C52;
            --text-muted: #8E929A;
            --border-warm: #EAE6DB;
            --border-focus: #1F2024;
            --shadow-subtle: 0 4px 20px rgba(0, 0, 0, 0.04);
            --shadow-card: 0 20px 45px -12px rgba(22, 23, 27, 0.08);
            --shadow-hero: 0 25px 50px -15px rgba(0, 0, 0, 0.25);
            --radius-outer: 32px;
            --radius-inner: 16px;
            --radius-btn: 999px;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-main);
            -webkit-font-smoothing: antialiased;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle decorative background wallpaper effect */
        body::before {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(224, 83, 60, 0.06) 0%, transparent 70%);
            top: -150px;
            left: -150px;
            z-index: 0;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(31, 32, 36, 0.04) 0%, transparent 70%);
            bottom: -200px;
            right: -200px;
            z-index: 0;
            pointer-events: none;
        }

        /* Container Master Split Screen */
        .login-master-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1080px;
            background: var(--bg-surface);
            border: 1px solid var(--border-warm);
            border-radius: var(--radius-outer);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            min-height: 680px;
        }

        /* ====================================================
           PANE KIRI: Editorial Dark Hero Showcase
           ==================================================== */
        .hero-pane {
            background: linear-gradient(145deg, var(--dark-hero) 0%, #191A1E 60%, #121316 100%);
            color: #FFFFFF;
            padding: 3.5rem 3.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Ambient Glow & Texture on Hero */
        .hero-pane::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -120px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(224, 83, 60, 0.25) 0%, rgba(224, 83, 60, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-pane::after {
            content: '';
            position: absolute;
            bottom: -150px;
            left: -100px;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-top-nav {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            padding: 0.45rem 1rem;
            border-radius: var(--radius-btn);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #FAF8F2;
        }

        .brand-badge i {
            color: var(--accent-terracotta);
            font-size: 0.95rem;
        }

        .system-status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.78rem;
            color: #A0AEC0;
            background: rgba(0, 0, 0, 0.35);
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius-btn);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: var(--accent-emerald);
            box-shadow: 0 0 10px var(--accent-emerald);
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
            margin: 2.5rem 0;
        }

        .hero-headline {
            font-size: 2.35rem;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.03em;
            color: #FFFFFF;
            margin-bottom: 1rem;
        }

        .hero-headline span {
            background: linear-gradient(120deg, #FFFFFF 30%, #E2E8F0 60%, rgba(224, 83, 60, 0.9) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-description {
            font-size: 0.96rem;
            color: #CBD5E1;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 440px;
        }

        /* Floating 3D Showcase Card inside Hero */
        .hero-stats-card {
            background: rgba(30, 32, 37, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-inner);
            padding: 1.4rem 1.6rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            box-shadow: var(--shadow-hero);
            position: relative;
        }

        .hero-stat-item {
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            padding-right: 0.5rem;
        }

        .hero-stat-item:last-child {
            border-right: none;
            padding-right: 0;
        }

        .stat-val {
            font-size: 1.45rem;
            font-weight: 800;
            color: #FFFFFF;
            line-height: 1.1;
            margin-bottom: 0.2rem;
        }

        .stat-lbl {
            font-size: 0.72rem;
            font-weight: 600;
            color: #94A3B8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Hero Quote / Bottom Pill */
        .hero-footer {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.5rem;
        }

        .hero-quote-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-terracotta), #F97316);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .hero-quote-text {
            font-size: 0.82rem;
            color: #94A3B8;
            line-height: 1.4;
            font-style: italic;
        }

        /* ====================================================
           PANE KANAN: Elegant Clean Login Form
           ==================================================== */
        .form-pane {
            background-color: var(--bg-surface);
            padding: 3.5rem 3.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .mobile-brand-logo {
            display: none;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1.5rem;
        }

        .mobile-brand-logo .logo-icon {
            width: 38px;
            height: 38px;
            background: var(--accent-primary);
            color: #FFFFFF;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .mobile-brand-logo span {
            font-size: 1.1rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }

        .form-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            font-size: 0.92rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Alert Box */
        .alert-animated {
            background-color: #FEF2F2;
            border: 1px solid #FCA5A5;
            color: #991B1B;
            padding: 0.9rem 1.1rem;
            border-radius: var(--radius-inner);
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            animation: shakeAlert 0.4s cubic-bezier(.36,.07,.19,.97) both;
        }

        .alert-animated i {
            color: #DC2626;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        @keyframes shakeAlert {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }

        /* Input Controls */
        .field-group {
            margin-bottom: 1.35rem;
        }

        .field-label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.55rem;
        }

        .field-label {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-heading);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .field-label span.req {
            color: var(--accent-terracotta);
        }

        .link-forgot {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s ease;
            cursor: pointer;
        }

        .link-forgot:hover {
            color: var(--text-heading);
            text-decoration: underline;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 1.1rem;
            color: #9CA3AF;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .form-input {
            width: 100%;
            height: 52px;
            background-color: var(--bg-page);
            border: 1.5px solid var(--border-warm);
            border-radius: var(--radius-inner);
            padding: 0 1.1rem 0 2.85rem;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-heading);
            outline: none;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-input::placeholder {
            color: #9CA3AF;
            font-weight: 400;
        }

        .form-input:focus {
            background-color: #FFFFFF;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 4px rgba(31, 32, 36, 0.08);
        }

        .form-input:focus + .input-icon-left,
        .input-wrapper:focus-within .input-icon-left {
            color: var(--text-heading);
        }

        /* Toggle Password Button */
        .btn-toggle-pwd {
            position: absolute;
            right: 0.85rem;
            background: none;
            border: none;
            color: #9CA3AF;
            font-size: 1rem;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 8px;
            transition: color 0.2s ease, background 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-toggle-pwd:hover {
            color: var(--text-heading);
            background: rgba(0, 0, 0, 0.05);
        }

        /* Checkbox Row */
        .form-options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.6rem;
            margin-top: -0.25rem;
        }

        .custom-check {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            color: var(--text-body);
            cursor: pointer;
            user-select: none;
        }

        .custom-check input[type="checkbox"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 1.5px solid var(--border-warm);
            border-radius: 5px;
            background: var(--bg-page);
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            display: grid;
            place-content: center;
        }

        .custom-check input[type="checkbox"]::before {
            content: "\f00c";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 11px;
            color: #FFFFFF;
            transform: scale(0);
            transition: 0.15s transform ease-in-out;
        }

        .custom-check input[type="checkbox"]:checked {
            background-color: var(--accent-primary);
            border-color: var(--accent-primary);
        }

        .custom-check input[type="checkbox"]:checked::before {
            transform: scale(1);
        }

        /* Submit Button */
        .btn-login-submit {
            width: 100%;
            height: 52px;
            background: var(--accent-primary);
            color: #FFFFFF;
            border: none;
            border-radius: var(--radius-btn);
            font-size: 0.96rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 4px 14px rgba(31, 32, 36, 0.18);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-login-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        }

        .btn-login-submit:active {
            transform: translateY(0);
        }

        .btn-login-submit i {
            transition: transform 0.2s ease;
        }

        .btn-login-submit:hover i {
            transform: translateX(4px);
        }

        /* Quick Demo Account Auto-Fill Pill */
        .quick-demo-pill {
            margin-top: 1.5rem;
            background-color: #F8FAFC;
            border: 1px dashed #CBD5E1;
            border-radius: var(--radius-inner);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #475569;
            transition: all 0.2s ease;
        }

        .quick-demo-pill:hover {
            background-color: #F1F5F9;
            border-color: #94A3B8;
        }

        .demo-badge-btn {
            background: #FFFFFF;
            border: 1px solid #CBD5E1;
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-btn);
            font-weight: 700;
            font-size: 0.75rem;
            color: var(--accent-primary);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.2s ease;
        }

        .demo-badge-btn:hover {
            background: var(--accent-primary);
            color: #FFFFFF;
            border-color: var(--accent-primary);
        }

        /* Footer Copyright & Security Tag */
        .form-footer-meta {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.25rem;
            border-top: 1px solid var(--border-warm);
            padding-top: 1.25rem;
        }

        .security-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: #64748B;
        }

        .security-chip i {
            color: var(--accent-emerald);
        }

        /* Modal Tooltip Sederhana untuk Info Lupa Password */
        .modal-toast {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1F2024;
            color: #FFFFFF;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-size: 0.85rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            z-index: 9999;
            max-width: 340px;
            animation: fadeInToast 0.3s ease;
        }

        @keyframes fadeInToast {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ====================================================
           RESPONSIVE DESIGN (TABLET & MOBILE)
           ==================================================== */
        @media (max-width: 960px) {
            .login-master-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
                border-radius: 24px;
            }

            .hero-pane {
                display: none; /* Fokus penuh ke form saat layar kecil */
            }

            .mobile-brand-logo {
                display: flex;
            }

            .form-pane {
                padding: 2.75rem 2rem;
            }

            .form-title {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 1rem;
            }

            .form-pane {
                padding: 2.25rem 1.4rem;
            }

            .quick-demo-pill {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .demo-badge-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- Container Master Split Screen -->
    <main class="login-master-wrapper">
        
        <!-- ==============================================
             PANE KIRI: Editorial Dark Hero Showcase
             ============================================== -->
        <aside class="hero-pane">
            <!-- Top Nav Hero -->
            <div class="hero-top-nav">
                <div class="brand-badge">
                    <i class="fa-solid fa-book-bookmark"></i>
                    <span>Perpus API Gateway</span>
                </div>
                <div class="system-status-indicator" title="Sistem Server Berjalan Normal">
                    <span class="status-dot"></span>
                    <span>Online v2.4</span>
                </div>
            </div>

            <!-- Content Hero -->
            <div class="hero-content">
                <h1 class="hero-headline">
                    Akses Terpusat <span>Katalog &amp; Koleksi</span> Buku Digital.
                </h1>
                <p class="hero-description">
                    Portal otentikasi administrator untuk pengelolaan inventaris pustaka, ekspor laporan resmi, dan distribusi data melalui REST API terintegrasi.
                </p>

                <!-- 3D Stat Bar Card -->
                <div class="hero-stats-card">
                    <div class="hero-stat-item">
                        <div class="stat-val"><?php echo $total_buku > 0 ? $total_buku : '10+'; ?></div>
                        <div class="stat-lbl">Judul Buku</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="stat-val"><?php echo $total_kategori > 0 ? $total_kategori : '4+'; ?></div>
                        <div class="stat-lbl">Kategori</div>
                    </div>
                    <div class="hero-stat-item">
                        <div class="stat-val">JSON</div>
                        <div class="stat-lbl">REST API</div>
                    </div>
                </div>
            </div>

            <!-- Bottom Quote -->
            <footer class="hero-footer">
                <div class="hero-quote-avatar">
                    <i class="fa-solid fa-quote-left"></i>
                </div>
                <div class="hero-quote-text">
                    "Buku adalah lentera peradaban, dan setiap halaman adalah jendela menuju wawasan tanpa batas."
                </div>
            </footer>
        </aside>

        <!-- ==============================================
             PANE KANAN: Form Autentikasi
             ============================================== -->
        <section class="form-pane">
            
            <!-- Mobile Brand Logo -->
            <div class="mobile-brand-logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-book-bookmark"></i>
                </div>
                <span>Perpustakaan API</span>
            </div>

            <div class="form-header">
                <h2 class="form-title">Selamat Datang 👋</h2>
                <p class="form-subtitle">Masukkan username dan kata sandi akun administrator Anda untuk melanjutkan.</p>
            </div>

            <!-- Notifikasi Pesan Kesalahan dengan Animasi Shake -->
            <?php if (!empty($error_message)): ?>
                <div class="alert-animated" role="alert">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?php echo htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="login.php" method="POST" autocomplete="off" id="loginForm">
                
                <!-- Field Username -->
                <div class="field-group">
                    <div class="field-label-row">
                        <label for="username" class="field-label">
                            Username <span class="req">*</span>
                        </label>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-regular fa-user input-icon-left"></i>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="Contoh: admin" 
                            value="<?php echo htmlspecialchars($username_input); ?>"
                            required 
                            autofocus
                        >
                    </div>
                </div>

                <!-- Field Password -->
                <div class="field-group">
                    <div class="field-label-row">
                        <label for="password" class="field-label">
                            Kata Sandi <span class="req">*</span>
                        </label>
                        <span class="link-forgot" id="btnForgotPwd" title="Petunjuk Pemulihan Akun">Lupa kata sandi?</span>
                    </div>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock input-icon-left"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Masukkan kata sandi Anda" 
                            required
                        >
                        <button type="button" class="btn-toggle-pwd" id="btnTogglePassword" aria-label="Lihat Kata Sandi" title="Tampilkan/Sembunyikan">
                            <i class="fa-regular fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Options Row: Remember Me -->
                <div class="form-options-row">
                    <label class="custom-check">
                        <input type="checkbox" id="rememberMe">
                        <span>Ingat sesi masuk saya</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login-submit" id="btnSubmit">
                    <span id="btnText">Masuk ke Sistem</span>
                    <i class="fa-solid fa-arrow-right" id="btnIcon"></i>
                </button>
            </form>

            <!-- Quick Demo Helper Card -->
            <div class="quick-demo-pill">
                <div>
                    <strong style="color:#0F172A; display:block; margin-bottom:2px;">Akun Administrator Demo</strong>
                    <span>User: <code>admin</code> &bull; Password: <code>admin</code></span>
                </div>
                <button type="button" class="demo-badge-btn" id="btnQuickFill">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Isi Otomatis
                </button>
            </div>

            <!-- Footer Info -->
            <div class="form-footer-meta">
                <span class="security-chip">
                    <i class="fa-solid fa-shield-halved"></i> MySQLi Secure Prepared
                </span>
                <span>&bull;</span>
                <span>&copy; <?php echo date('Y'); ?> SMK RPL</span>
            </div>
        </section>
    </main>

    <!-- Toast Modal Bantuan Lupa Password -->
    <div class="modal-toast" id="toastForgot">
        <div style="font-weight:700; margin-bottom:0.35rem; display:flex; align-items:center; gap:0.4rem;">
            <i class="fa-solid fa-circle-info" style="color:var(--accent-terracotta);"></i> Informasi Akun
        </div>
        <p style="font-size:0.8rem; line-height:1.4; color:#CBD5E1;">
            Untuk keamanan, reset akun admin dilakukan langsung oleh pengelola database perpustakaan pada tabel <code>admins</code> di database MySQL.
        </p>
    </div>

    <!-- Script Interaktif Frontend -->
    <script>
        // 1. Toggle Tampilkan / Sembunyikan Password
        const passwordInput = document.getElementById('password');
        const btnTogglePassword = document.getElementById('btnTogglePassword');
        const toggleIcon = document.getElementById('toggleIcon');

        btnTogglePassword.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
                btnTogglePassword.setAttribute('aria-label', 'Sembunyikan Kata Sandi');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
                btnTogglePassword.setAttribute('aria-label', 'Lihat Kata Sandi');
            }
        });

        // 2. Tombol Cepat Isi Otomatis Kredensial Demo
        const btnQuickFill = document.getElementById('btnQuickFill');
        const usernameInput = document.getElementById('username');
        const btnSubmit = document.getElementById('btnSubmit');

        btnQuickFill.addEventListener('click', function() {
            usernameInput.value = 'admin';
            passwordInput.value = 'admin';
            
            // Efek visual highlight pada input
            [usernameInput, passwordInput].forEach(el => {
                el.style.backgroundColor = '#EFF6FF';
                el.style.borderColor = '#3B82F6';
                setTimeout(() => {
                    el.style.backgroundColor = '';
                    el.style.borderColor = '';
                }, 700);
            });

            btnSubmit.focus();
        });

        // 3. Efek Loading Saat Form Dikirim
        const loginForm = document.getElementById('loginForm');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');

        loginForm.addEventListener('submit', function() {
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.85';
            btnSubmit.style.cursor = 'wait';
            btnText.textContent = 'Memverifikasi...';
            btnIcon.className = 'fa-solid fa-spinner fa-spin';
        });

        // 4. Toast Pemberitahuan "Lupa Kata Sandi"
        const btnForgotPwd = document.getElementById('btnForgotPwd');
        const toastForgot = document.getElementById('toastForgot');
        let toastTimeout = null;

        btnForgotPwd.addEventListener('click', function() {
            toastForgot.style.display = 'block';
            if (toastTimeout) clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => {
                toastForgot.style.display = 'none';
            }, 5000);
        });

        // Tutup toast jika diklik
        toastForgot.addEventListener('click', function() {
            this.style.display = 'none';
        });
    </script>
</body>
</html>
