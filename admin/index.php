<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Dashboard";
$active_menu = "dashboard";

// --- LOGIKA FILTER CABANG (GLOBAL/LOKAL) ---
$level = $_SESSION['level'];
$view_cabang = 'pusat';
$cabang_label = "Semua Cabang";

if ($level == 'admin') {
    $view_cabang = $_SESSION['view_cabang_id'] ?? 'pusat';
} else {
    $view_cabang = $_SESSION['cabang_id'];
}

// --- 1. SET QUERY FILTER DEFAULT ---
// Default: Filter kondisi dasar dulu
$where_trx = "WHERE t.status_pembayaran = 'settlement'";
$where_menu = "WHERE m.is_active = 1";
$where_meja = "WHERE status = 'terisi'"; // [FIX] Default query meja

// --- 2. JIKA MEMILIH CABANG SPESIFIK ---
if ($view_cabang != 'pusat') {
    // Tambahkan filter cabang menggunakan 'AND'
    $where_trx .= " AND t.meja_id IN (SELECT id FROM meja WHERE cabang_id = '$view_cabang')";
    
    // [FIX] Tambahkan AND untuk meja
    $where_meja .= " AND cabang_id = '$view_cabang'";
    
    $where_menu .= " AND (m.cabang_id = '$view_cabang' OR m.cabang_id IS NULL)";
    
    // Ambil nama cabang untuk label
    $c = $koneksi->query("SELECT nama_cabang FROM cabang WHERE id = '$view_cabang'")->fetch_assoc();
    $cabang_label = $c['nama_cabang'] ?? 'Cabang Terpilih';
}

// --- 3. EKSEKUSI QUERY ---

// A. Total Omset (Hari Ini)
$today = date('Y-m-d');
$q_omset = $koneksi->query("SELECT SUM(total_harga) as total FROM transaksi t $where_trx AND DATE(t.created_at) = '$today'");
$omset_hari_ini = $q_omset->fetch_assoc()['total'] ?? 0;

// B. Total Pesanan (Hari Ini)
$q_trx = $koneksi->query("SELECT COUNT(*) as total FROM transaksi t $where_trx AND DATE(t.created_at) = '$today'");
$trx_hari_ini = $q_trx->fetch_assoc()['total'] ?? 0;

// C. Total Menu Aktif
$q_menu = $koneksi->query("SELECT COUNT(*) as total FROM menu m $where_menu");
$total_menu = $q_menu->fetch_assoc()['total'] ?? 0;

// D. Meja Terisi [FIXED QUERY]
// Variabel $where_meja sekarang sudah berisi "WHERE status='terisi' [AND cabang_id='...']"
$q_meja = $koneksi->query("SELECT COUNT(*) as total FROM meja $where_meja");
$meja_terisi = $q_meja->fetch_assoc()['total'] ?? 0;

include '../layouts/admin/header.php';
?>

<div class="alert alert-primary shadow-sm border-0 mb-4">
    <h4 class="alert-heading fw-bold"><i class="fas fa-smile-wink me-2"></i>Selamat Datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>
    <p class="mb-0">Anda sedang memantau data: <strong><?= $cabang_label ?></strong></p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Omset Hari Ini</p>
                        <h4 class="fw-bold text-primary mb-0">Rp <?= number_format($omset_hari_ini, 0, ',', '.') ?></h4>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Pesanan Hari Ini</p>
                        <h4 class="fw-bold text-success mb-0"><?= $trx_hari_ini ?></h4>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                        <i class="fas fa-shopping-bag fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Menu Aktif</p>
                        <h4 class="fw-bold text-warning mb-0"><?= $total_menu ?></h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-utensils fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Meja Terisi</p>
                        <h4 class="fw-bold text-danger mb-0"><?= $meja_terisi ?></h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="fas fa-chair fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <a href="transaksi_masuk" class="btn btn-primary w-100 py-3 fw-bold shadow-sm">
            <i class="fas fa-bell fa-lg me-2"></i> Cek Pesanan Masuk
        </a>
    </div>
    <div class="col-md-4">
        <a href="dapur" class="btn btn-dark w-100 py-3 fw-bold shadow-sm">
            <i class="fas fa-fire fa-lg me-2"></i> Monitor Dapur
        </a>
    </div>
    <div class="col-md-4">
        <a href="laporan" class="btn btn-info text-white w-100 py-3 fw-bold shadow-sm">
            <i class="fas fa-chart-bar fa-lg me-2"></i> Laporan Lengkap
        </a>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>