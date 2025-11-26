<?php
session_start();
require_once '../auth/koneksi.php';

// Hanya Admin yang boleh akses file ini
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header("Location: laporan");
    exit;
}

if (isset($_POST['cabang_tujuan'])) {
    $target = $_POST['cabang_tujuan'];

    if ($target == 'pusat') {
        // Jika pilih Pusat, hapus mode view cabang (Reset ke Global)
        unset($_SESSION['view_cabang_id']);
        $_SESSION['cabang_name'] = "Cabang Pusat (Global)";
    } else {
        // Jika pilih Cabang Tertentu, set session view
        $_SESSION['view_cabang_id'] = $target;
        
        // Update nama cabang di header agar Admin sadar sedang melihat cabang mana
        $stmt = $koneksi->prepare("SELECT nama_cabang FROM cabang WHERE id = ?");
        $stmt->bind_param("i", $target);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res) {
            $_SESSION['cabang_name'] = $res['nama_cabang'] . " [Mode Lihat]";
        }
    }
}

// Kembalikan ke halaman asal
header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
?>