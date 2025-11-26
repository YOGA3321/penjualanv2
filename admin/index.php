<?php
// admin/index.php
session_start();

// Cek apakah user sudah login
if (isset($_SESSION['user_id']) && isset($_SESSION['level'])) {
    // Jika levelnya admin atau karyawan, masuk ke laporan
    if ($_SESSION['level'] == 'admin' || $_SESSION['level'] == 'karyawan') {
        header("Location: laporan"); 
        exit;
    } else {
        // Jika pelanggan nyasar ke admin, kembalikan ke home
        header("Location: ../index.html");
        exit;
    }
} else {
    // Jika belum login, lempar ke halaman login
    header("Location: ../login");
    exit;
}
?>