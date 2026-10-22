<?php
/**
 * File: koneksi.php
 * Deskripsi: Konfigurasi dan inisialisasi koneksi database MySQL menggunakan MySQLi
 */

// Konfigurasi parameter koneksi database
$host     = "localhost";     // Host database server (default XAMPP: localhost)
$username = "root";          // Username database (default XAMPP: root)
$password = "";              // Password database (default XAMPP: kosong)
$database = "perpus_api";    // Nama database yang digunakan

// Mengaktifkan mode laporan error MySQLi agar melempar exception (mysqli_sql_exception)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Membuat koneksi ke server database MySQL
    $koneksi = new mysqli($host, $username, $password, $database);

    // Mengatur set karakter ke utf8mb4 agar mendukung semua simbol & karakter
    $koneksi->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Tangani jika koneksi gagal dan tampilkan pesan error yang informatif
    die("<h3>Koneksi Database Gagal!</h3>" .
        "<p>Pesan Kesalahan: " . htmlspecialchars($e->getMessage()) . "</p>" .
        "<p>Pastikan XAMPP (MySQL Service) aktif dan database <strong>" . htmlspecialchars($database) . "</strong> sudah dibuat.</p>");
}
?>