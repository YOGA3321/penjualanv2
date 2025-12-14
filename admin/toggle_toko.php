<?php
session_start();
require_once '../auth/koneksi.php';

// Cek Login & Admin
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') {
    header("Location: ../login.php"); exit;
}

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    // 1. Ambil status saat ini
    $cek = $koneksi->query("SELECT is_open FROM cabang WHERE id='$id'")->fetch_assoc();
    
    // 2. Balik Statusnya (Toggle)
    $status_baru = ($cek['is_open'] == 1) ? 0 : 1;
    
    // 3. Update Database
    $stmt = $koneksi->prepare("UPDATE cabang SET is_open = ? WHERE id = ?");
    $stmt->bind_param("ii", $status_baru, $id);
    
    if ($stmt->execute()) {
        $pesan = $status_baru ? "Toko BERHASIL DIBUKA! Pelanggan bisa reservasi." : "Toko DITUTUP SEMENTARA. Reservasi dimatikan.";
        $icon = $status_baru ? "success" : "warning";
        
        $_SESSION['swal'] = ['icon'=> $icon, 'title'=>'Status Diubah', 'text'=> $pesan];
    } else {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Database error'];
    }
}

// Kembali ke halaman sebelumnya
if(isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: ./");
}
exit;
?>