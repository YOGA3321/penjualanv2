<?php
session_start();
require_once '../auth/koneksi.php';

$page_title = "Dashboard Laporan";
$active_menu = "laporan";

// --- LOGIKA ISOLASI CABANG ---
$cabang_id = $_SESSION['cabang_id']; 
if ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id']; // Override ID jika Admin pilih cabang
}

// Query Dasar
$where_clause = "";
// Jika bukan admin, ATAU (Admin TAPI sedang memilih spesifik cabang)
if ($_SESSION['level'] != 'admin' || isset($_SESSION['view_cabang_id'])) {
    $where_clause = " WHERE cabang_id = '$cabang_id'"; 
}

// Hitung Ringkasan (Contoh Query Sederhana ke tabel terkait)
// Karena tabel transaksi blm ada isinya, kita count data master dulu sebagai indikator dashboard
$total_menu = $koneksi->query("SELECT COUNT(*) as total FROM menu" . $where_clause)->fetch_assoc()['total'];
$total_meja = $koneksi->query("SELECT COUNT(*) as total FROM meja" . $where_clause)->fetch_assoc()['total'];

include '../layouts/admin/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <h3>Halo, <?= $_SESSION['nama'] ?>!</h3>
                <p class="mb-0">Selamat datang di panel admin <strong><?= $_SESSION['cabang_name'] ?></strong>.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card summary-card h-100 border-start border-4 border-primary">
            <div class="card-body d-flex align-items-center">
                <div class="summary-icon bg-light text-primary"><i class="fas fa-utensils"></i></div>
                <div class="ms-3">
                    <h6 class="card-title text-muted mb-1">Total Menu Aktif</h6>
                    <h4 class="card-text"><?= $total_menu ?> Item</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card summary-card h-100 border-start border-4 border-success">
            <div class="card-body d-flex align-items-center">
                <div class="summary-icon bg-light text-success"><i class="fas fa-chair"></i></div>
                <div class="ms-3">
                    <h6 class="card-title text-muted mb-1">Meja Terdaftar</h6>
                    <h4 class="card-text"><?= $total_meja ?> Meja</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card summary-card h-100 border-start border-4 border-warning">
            <div class="card-body d-flex align-items-center">
                <div class="summary-icon bg-light text-warning"><i class="fas fa-clock"></i></div>
                <div class="ms-3">
                    <h6 class="card-title text-muted mb-1">Status Sistem</h6>
                    <h4 class="card-text text-success">Online</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">Statistik Penjualan (Segera Hadir)</h6>
    </div>
    <div class="card-body text-center py-5 text-muted">
        <i class="fas fa-chart-area fa-3x mb-3 text-gray-300"></i>
        <p>Data transaksi belum tersedia. Silakan lakukan pemesanan via QR Code.</p>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>