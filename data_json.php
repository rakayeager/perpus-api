<?php
/**
 * File: data_json.php
 * Deskripsi: Endpoint API untuk menyajikan data buku perpustakaan dalam format JSON
 */

// 1. Hubungkan ke database menggunakan file koneksi
require_once 'koneksi.php';

// 2. Set header respon agar dibaca sebagai format JSON oleh browser dan JavaScript fetch
header('Content-Type: application/json; charset=utf-8');

// Inisialisasi array kosong untuk menampung data
$data_buku = [];

try {
    // 3. Query untuk mengambil semua data buku terurut berdasarkan id terkecil ke terbesar
    $query = "SELECT id, kode_buku, judul, penulis, kategori, tahun_terbit, penerbit, cover FROM books ORDER BY id ASC";
    $result = $koneksi->query($query);

    // 4. Masukkan baris hasil query ke dalam array
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data_buku[] = $row;
        }
    }

} catch (Exception $e) {
    // Catat log jika query bermasalah
    error_log("Error API data_json.php: " . $e->getMessage());
}

// 5. Cetak data dalam format JSON murni
echo json_encode($data_buku);
exit();
?>