<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$page_title = isset($page_title) ? $page_title : 'Admin Dashboard';
$active_menu = isset($active_menu) ? $active_menu : '';

// --- LOGIKA NAMA CABANG ---
$display_cabang = "Cabang Pusat";
if(isset($_SESSION['level'])) {
    if ($_SESSION['level'] == 'admin') {
        $view_id = $_SESSION['view_cabang_id'] ?? 'pusat';
        if ($view_id == 'pusat') {
            $display_cabang = "Semua Cabang (Global)";
        } else {
            $q_view = $koneksi->query("SELECT nama_cabang FROM cabang WHERE id = '$view_id'");
            if($r_view = $q_view->fetch_assoc()) {
                $display_cabang = "Cabang " . $r_view['nama_cabang'];
            }
        }
    } else {
        $display_cabang = $_SESSION['cabang_name'] ?? 'Cabang Anda';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Waroeng Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/dashboard.css">

    <link rel="stylesheet" href="../assets/css/dashboard-modern.css"> 
    
    <link rel="shortcut icon" href="../assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
    </head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar d-none d-lg-flex">
        <div class="sidebar-header">
             <a href="index" class="sidebar-logo text-decoration-none text-dark fw-bold fs-5">
                <img src="../assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo" height="35">
                <span>Modern Bites</span>
            </a>
        </div>
        
        <nav class="sidebar-nav">
            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-2 mb-2">Menu Utama</p>
            <ul class="list-unstyled">
                <li class="<?= $active_menu == 'dashboard' ? 'active' : '' ?>">
                    <a href="index" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard
                    </a>
                </li>

                <li class="<?= $active_menu == 'laporan' ? 'active' : '' ?>">
                    <a href="laporan" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-chart-line fa-fw me-2"></i> Laporan
                    </a>
                </li>
                <li class="<?= $active_menu == 'riwayat' ? 'active' : '' ?>">
                    <a href="riwayat" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-history fa-fw me-2"></i> Riwayat Transaksi
                    </a>
                </li>
                <li class="<?= $active_menu == 'menu' ? 'active' : '' ?>">
                    <a href="menu" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-utensils fa-fw me-2"></i> Manajemen Menu
                    </a>
                </li>
                <li class="<?= $active_menu == 'kategori' ? 'active' : '' ?>">
                    <a href="kategori" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-tags fa-fw me-2"></i> Kategori Menu
                    </a>
                </li>

                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <li class="<?= $active_menu == 'users' ? 'active' : '' ?>">
                        <a href="users" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                            <i class="fas fa-users-cog fa-fw me-2"></i> Manajemen User
                        </a>
                    </li>
                    <li class="<?= $active_menu == 'cabang' ? 'active' : '' ?>">
                        <a href="cabang" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                            <i class="fas fa-store-alt fa-fw me-2"></i> Manajemen Cabang
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Operasional</p>
            <ul class="list-unstyled">
                <li class="<?= $active_menu == 'transaksi_masuk' ? 'active' : '' ?>">
                    <a href="transaksi_masuk" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-bell fa-fw me-2"></i> Pesanan Masuk
                    </a>
                </li>
                <li class="<?= $active_menu == 'order_manual' ? 'active' : '' ?>">
                    <a href="order_manual" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-tablet-alt fa-fw me-2"></i> Pesanan Manual
                    </a>
                </li>
                <li class="<?= $active_menu == 'dapur' ? 'active' : '' ?>">
                    <a href="dapur" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-fire fa-fw me-2"></i> Monitor Dapur
                    </a>
                </li>
                <li class="<?= $active_menu == 'meja' ? 'active' : '' ?>">
                    <a href="meja" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium">
                        <i class="fas fa-chair fa-fw me-2"></i> Manajemen Meja
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../logout" class="text-decoration-none text-danger fw-bold">
                <i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header d-flex justify-content-between align-items-center mb-4">
             <div class="header-left d-flex align-items-center">
                 <button class="btn d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h3 class="mb-0 fw-bold text-dark"><?= $page_title ?></h3>
                    <p class="mb-0 text-muted small">
                        <i class="fas fa-map-marker-alt me-1 text-danger"></i> 
                        <?= $display_cabang ?>
                    </p>
                </div>
            </div>
            
            <div class="header-right d-flex align-items-center gap-3">
                <div class="d-none d-md-flex align-items-center bg-white px-3 py-2 rounded shadow-sm border">
                    <div id="statusIndicator" class="rounded-circle bg-secondary me-2" style="width: 10px; height: 10px;"></div>
                    <div class="d-flex flex-column" style="line-height: 1.1;">
                        <small class="fw-bold text-success" style="font-size: 0.7rem;">SYSTEM LIVE</small>
                        <small class="text-muted" style="font-size: 0.7rem;"><span id="onlineCount">0</span> Online</small>
                    </div>
                </div>
                
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <?php 
                        $list_cabang = $koneksi->query("SELECT * FROM cabang");
                        $current_view = $_SESSION['view_cabang_id'] ?? 'pusat';
                    ?>
                    <form action="setter_cabang.php" method="POST">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-filter text-primary"></i></span>
                            <select name="cabang_tujuan" class="form-select border-start-0 fw-bold text-primary" onchange="this.form.submit()" style="cursor:pointer;">
                                <option value="pusat" <?= $current_view == 'pusat' ? 'selected' : '' ?>>Semua Cabang</option>
                                <?php foreach($list_cabang as $lc): ?>
                                    <option value="<?= $lc['id'] ?>" <?= $current_view == $lc['id'] ? 'selected' : '' ?>><?= $lc['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fw-bold" style="width: 35px; height: 35px;">
                        <?= substr($_SESSION['nama'] ?? 'A', 0, 1) ?>
                    </div>
                </div>
            </div>
        </header>