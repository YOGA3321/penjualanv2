<?php
// Cek sesi login (Wajib ditaruh di sini agar aman dlm 1 file)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login & levelnya admin (Sesuai kebutuhan keamanan)
// if (!isset($_SESSION['user'])) { header("Location: ../login"); exit; }

// Variabel default untuk title dan menu active
$page_title = isset($page_title) ? $page_title : 'Admin Dashboard';
$active_menu = isset($active_menu) ? $active_menu : '';
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
    <link rel="shortcut icon" href="../assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <aside class="sidebar d-none d-lg-flex">
        <div class="sidebar-header">
             <a href="index" class="sidebar-logo">
                <img src="../assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo">
                <span>Modern Bites</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <p class="sidebar-heading">Menu Utama</p>
            <ul>
                <li class="<?= $active_menu == 'laporan' ? 'active' : '' ?>">
                    <a href="laporan"><i class="fas fa-chart-line fa-fw"></i> <span>Laporan</span></a>
                </li>
                <li class="<?= $active_menu == 'riwayat' ? 'active' : '' ?>">
                    <a href="riwayat"><i class="fas fa-history fa-fw"></i> <span>Riwayat Transaksi</span></a>
                </li>
                
                <li class="<?= $active_menu == 'menu' ? 'active' : '' ?>">
                    <a href="menu"><i class="fas fa-utensils fa-fw"></i> <span>Manajemen Menu</span></a>
                </li>
                <li class="<?= $active_menu == 'kategori' ? 'active' : '' ?>">
                    <a href="kategori"><i class="fas fa-tags fa-fw"></i> <span>Kategori Menu</span></a>
                </li>
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <li><a href="users">Manajemen User</a></li>
                    <li><a href="cabang">Manajemen Cabang</a></li>
                <?php endif; ?>
            </ul>

            <p class="sidebar-heading">Operasional</p>
            <ul>
                <li class="<?= $active_menu == 'transaksi_masuk' ? 'active' : '' ?>">
                    <a href="transaksi_masuk"><i class="fas fa-bell fa-fw"></i> <span>Pesanan Masuk</span></a>
                </li>
                <li class="<?= $active_menu == 'order_manual' ? 'active' : '' ?>">
                    <a href="order_manual"><i class="fas fa-tablet-alt fa-fw"></i> <span>Pesanan Manual</span></a>
                </li>
                <li class="<?= $active_menu == 'dapur' ? 'active' : '' ?>">
                    <a href="dapur"><i class="fas fa-fire fa-fw"></i> <span>Monitor Dapur</span></a>
                </li>
                <li class="<?= $active_menu == 'meja' ? 'active' : '' ?>">
                    <a href="meja"><i class="fas fa-chair fa-fw"></i> <span>Manajemen Meja</span></a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout"><i class="fas fa-sign-out-alt fa-fw"></i> <span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                 <button class="btn d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h1><?= $page_title ?></h1>
                    <p class="mb-0 text-muted">
                        <i class="fas fa-map-marker-alt me-1 text-primary"></i> 
                        <?= isset($_SESSION['cabang_name']) ? $_SESSION['cabang_name'] : 'Cabang Pusat' ?>
                    </p>
                </div>
            </div>
            <div class="header-right d-flex align-items-center gap-3">
                
                <div class="d-flex align-items-center bg-white px-3 py-1 rounded shadow-sm border">
                    <div id="statusIndicator" class="rounded-circle bg-secondary me-2" 
                         style="width: 10px; height: 10px;" title="Offline"></div>
                    
                    <div class="d-flex flex-column" style="line-height: 1.2;">
                        <small class="fw-bold text-dark" style="font-size: 0.7rem;">SYSTEM LIVE</small>
                        <small class="text-muted" style="font-size: 0.7rem;">
                            <span id="onlineCount">0</span> User Online
                        </small>
                    </div>
                </div>

                <div class="vr h-50"></div>

                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <?php 
                        $list_cabang = $koneksi->query("SELECT * FROM cabang");
                        $current_view = $_SESSION['view_cabang_id'] ?? 'pusat';
                    ?>
                    <form action="setter_cabang.php" method="POST" class="d-flex align-items-center">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-exchange-alt text-primary"></i></span>
                            <select name="cabang_tujuan" class="form-select form-select-sm border-start-0" onchange="this.form.submit()" style="max-width: 150px;">
                                <option value="pusat" <?= $current_view == 'pusat' ? 'selected' : '' ?>>Lihat Semua (Global)</option>
                                <?php foreach($list_cabang as $lc): ?>
                                    <option value="<?= $lc['id'] ?>" <?= $current_view == $lc['id'] ? 'selected' : '' ?>>
                                        <?= $lc['nama_cabang'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <div class="vr h-50"></div>
                <?php endif; ?>

                <?php if(isset($header_action_btn)) echo $header_action_btn; ?>
            </div>
        </header>