<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'gudang') {
    header("Location: ../login.php");
    exit;
}
require_once '../auth/koneksi.php';

$page_title = "Dashboard Gudang";
$active_menu = "dashboard";

// --- WIDGET STATISTICS ---
// 1. Stok Menipis (< 10)
$q_stok = $koneksi->query("SELECT COUNT(*) as total FROM gudang_items WHERE stok < 10");
$stok_menipis = $q_stok->fetch_assoc()['total'];

// 2. Request Pending
$q_pending = $koneksi->query("SELECT COUNT(*) as total FROM request_stok WHERE status='pending'");
$req_pending = $q_pending->fetch_assoc()['total'];

// 3. Request Dikirim (Butuh konfirmasi cabang)
$q_dikirim = $koneksi->query("SELECT COUNT(*) as total FROM request_stok WHERE status='dikirim'");
$req_dikirim = $q_dikirim->fetch_assoc()['total'];

include '../layouts/admin/header.php';
?>

<div class="alert alert-primary shadow-sm border-0 mb-4">
    <h4 class="alert-heading fw-bold"><i class="fas fa-warehouse me-2"></i>Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>
    <p class="mb-0">Selamat datang di <strong>Central Warehouse Management System</strong>.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Stok Menipis</p>
                        <h4 class="fw-bold text-warning mb-0"><?= $stok_menipis ?> Item</h4>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Permintaan Baru</p>
                        <h4 class="fw-bold text-danger mb-0"><?= $req_pending ?> Request</h4>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger">
                        <i class="fas fa-inbox fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100 border-start border-info border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small fw-bold text-uppercase mb-1">Sedang Dikirim</p>
                        <h4 class="fw-bold text-info mb-0"><?= $req_dikirim ?> Pengiriman</h4>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <a href="inventory.php" class="btn btn-primary w-100 py-4 fw-bold shadow-sm">
            <i class="fas fa-boxes fa-2x mb-2 d-block"></i>
            Manajemen Stok & Produksi
        </a>
    </div>
    <div class="col-md-6">
        <a href="permintaan_masuk.php" class="btn btn-success w-100 py-4 fw-bold shadow-sm">
            <i class="fas fa-dolly-flatbed fa-2x mb-2 d-block"></i>
            Proses Permintaan Cabang
        </a>
    </div>
</div>

<?php include '../layouts/admin/footer.php'; ?>
