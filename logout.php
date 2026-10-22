<?php
/**
 * File: logout.php
 * Deskripsi: Menghapus seluruh session autentikasi dan mengembalikan admin ke halaman login
 */

// Mulai sesi
session_start();

// Kosongkan seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi jika ada di browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan sesi di server
session_destroy();

// Alihkan pengguna kembali ke login.php
header("Location: login.php");
exit();
?>