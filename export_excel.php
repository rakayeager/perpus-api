<?php
/**
 * File: export_excel.php
 * Deskripsi: Terintegrasi dengan Pusat Laporan Terpadu (export.php)
 */
session_start();
$query = $_GET;
$query['action'] = 'excel';
header("Location: export.php?" . http_build_query($query));
exit();
?>
