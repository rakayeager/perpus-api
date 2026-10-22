<?php
/**
 * File: export_pdf.php
 * Deskripsi: Terintegrasi dengan Pusat Laporan Terpadu (export.php)
 */
session_start();
$query = $_GET;
if (!isset($query['action'])) {
    $query['action'] = 'print';
}
header("Location: export.php?" . http_build_query($query));
exit();
?>
