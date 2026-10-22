<?php
require_once 'koneksi.php';

/**
 * File: hapus.php
 * Deskripsi: Hapus buku beserta file fisik cover-nya
 */

session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

require_once 'koneksi.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'ID data buku tidak valid!'];
    header("Location: index.php");
    exit();
}

try {
    // Ambil info cover untuk dihapus dari folder uploads/
    $stmt_get = $koneksi->prepare("SELECT cover FROM books WHERE id = ? LIMIT 1");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $res = $stmt_get->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['cover']) && file_exists('uploads/' . $row['cover'])) {
            unlink('uploads/' . $row['cover']);
        }
    }
    $stmt_get->close();

    // Hapus data dari tabel books
    $stmt = $koneksi->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data buku beserta cover berhasil dihapus!'];
    } else {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Buku tidak ditemukan atau telah dihapus.'];
    }
    $stmt->close();
} catch (Exception $e) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Gagal menghapus data: ' . $e->getMessage()];
}

header("Location: index.php");
exit();
?>