<?php
if (session_status() == PHP_SESSION_NONE) { session_start(); }

// OTOMATIS LOGOUT JIKA 1 JAM (3600 detik) TIDAK ADA AKTIVITAS
$timeout_duration = 3600; 
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?msg=timeout");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$page_title = isset($page_title) ? $page_title : 'Admin Dashboard';
$active_menu = isset($active_menu) ? $active_menu : '';

// LOGIKA NAMA CABANG
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <!-- PWA & SPA (Turbo) -->
    <link rel="manifest" href="/penjualanv2/manifest.json">
    <script type="module">
        import hotwiredTurbo from 'https://cdn.skypack.dev/@hotwired/turbo';
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/penjualanv2/service-worker.js')
                .then(reg => console.log('SW Registered!', reg.scope))
                .catch(err => console.log('SW Fail:', err));
            });
        }
    </script>

    <style>
        .dashboard-wrapper { display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; height: 100vh; display: flex; flex-direction: column; background-color: #fff; border-right: 1px solid #e9ecef; z-index: 1040; flex-shrink: 0; }
        .sidebar-header { flex-shrink: 0; padding: 1.5rem; border-bottom: 1px solid #e9ecef; }
        .sidebar-nav { flex-grow: 1; overflow-y: auto; padding: 1rem 0; }
        .sidebar-footer { flex-shrink: 0; padding: 1.5rem; border-top: 1px solid #e9ecef; background: #fff; }
        .main-content { flex-grow: 1; overflow-y: auto; padding: 1.5rem; background-color: #f4f7fa; }
        
        /* Smooth Page Transition */
        body { opacity: 1; transition: opacity 0.2s ease-in-out; }
        body[data-turbo-preview] { opacity: 0.5; pointer-events: none; }
    </style>
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
        
        <?php 
            // DETEKSI LOGIKA MENU BERDASARKAN URL
            $in_gudang_module = strpos($_SERVER['REQUEST_URI'], '/gudang/') !== false;
            $user_level = $_SESSION['level'] ?? '';
            
            // JIKA USER GUDANG, ATAU ADMIN SEDANG DI URL /GUDANG/
            $show_gudang_menu = ($user_level == 'gudang' || ($user_level == 'admin' && $in_gudang_module));
        ?>

        <nav class="sidebar-nav">
            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-2 mb-2">
                <?= $show_gudang_menu ? 'Menu Gudang' : 'Menu Utama' ?>
            </p>
            <ul class="list-unstyled">
                <?php if($show_gudang_menu): ?>
                    <!-- === MENU GUDANG (Admin & Gudang) === -->
                    <li class="<?= $active_menu == 'dashboard' ? 'active' : '' ?>">
                        <a href="../gudang/index.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a>
                    </li>
                    <li class="<?= $active_menu == 'inventory' ? 'active' : '' ?>">
                        <a href="../gudang/inventory.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-boxes fa-fw me-2"></i> Manajemen Stok</a>
                    </li>
                    <li class="<?= $active_menu == 'barang_masuk' ? 'active' : '' ?>">
                        <a href="../gudang/barang_masuk.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-download fa-fw me-2"></i> Barang Masuk</a>
                    </li>
                    <li class="<?= $active_menu == 'permintaan' ? 'active' : '' ?>">
                        <a href="../gudang/permintaan_masuk.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-inbox fa-fw me-2"></i> Permintaan Masuk</a>
                    </li>
                    <li class="<?= $active_menu == 'pemasok' ? 'active' : '' ?>">
                        <a href="../gudang/pemasok.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-truck fa-fw me-2"></i> Data Pemasok</a>
                    </li>

                <?php else: ?>
                    <!-- === MENU UTAMA ADMIN / KARYAWAN === -->
                    <li class="<?= $active_menu == 'dashboard' ? 'active' : '' ?>">
                        <a href="../admin/index.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tachometer-alt fa-fw me-2"></i> Dashboard</a>
                    </li>
                    <li class="<?= $active_menu == 'laporan' ? 'active' : '' ?>">
                        <a href="../admin/laporan.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chart-line fa-fw me-2"></i> Laporan</a>
                    </li>
                    <li class="<?= $active_menu == 'riwayat' ? 'active' : '' ?>">
                        <a href="../admin/riwayat.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-history fa-fw me-2"></i> Riwayat Transaksi</a>
                    </li>
                    <li class="<?= $active_menu == 'menu' ? 'active' : '' ?>">
                        <a href="../admin/menu.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-utensils fa-fw me-2"></i> Manajemen Menu</a>
                    </li>
                    <li class="<?= $active_menu == 'reservasi' ? 'active' : '' ?>">
                        <a href="../admin/reservasi.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-calendar-alt fa-fw me-2"></i> Data Reservasi</a>
                    </li>

                    <?php if($user_level == 'admin'): ?>
                        <li class="<?= $active_menu == 'kategori' ? 'active' : '' ?>">
                            <a href="../admin/kategori.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tags fa-fw me-2"></i> Kategori Menu</a>
                        </li>
                        <li class="<?= $active_menu == 'voucher' ? 'active' : '' ?>">
                            <a href="../admin/voucher.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-ticket-alt fa-fw me-2"></i> Manajemen Voucher</a>
                        </li>
                        <li class="<?= $active_menu == 'users' ? 'active' : '' ?>">
                            <a href="../admin/users.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-users-cog fa-fw me-2"></i> Manajemen User</a>
                        </li>
                        <li class="<?= $active_menu == 'cabang' ? 'active' : '' ?>">
                            <a href="../admin/cabang.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-store-alt fa-fw me-2"></i> Manajemen Cabang</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <?php if(!$show_gudang_menu): ?>
            <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Operasional</p>
            <ul class="list-unstyled">
                <li class="<?= $active_menu == 'transaksi_masuk' ? 'active' : '' ?>">
                    <a href="../admin/transaksi_masuk.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-bell fa-fw me-2"></i> Pesanan Masuk</a>
                </li>
                <li class="<?= $active_menu == 'order_manual' ? 'active' : '' ?>">
                    <a href="../admin/order_manual.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-tablet-alt fa-fw me-2"></i> Pesanan Manual</a>
                </li>
                <li class="<?= $active_menu == 'dapur' ? 'active' : '' ?>">
                    <a href="../admin/dapur.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-fire fa-fw me-2"></i> Monitor Dapur</a>
                </li>
                <li class="<?= $active_menu == 'meja' ? 'active' : '' ?>">
                    <a href="../admin/meja.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-chair fa-fw me-2"></i> Manajemen Meja</a>
                </li>
                
                <?php if($user_level == 'admin'): ?>
                <p class="sidebar-heading px-4 text-muted small fw-bold text-uppercase mt-4 mb-2">Logistik / Gudang</p>
                <li class="<?= $active_menu == 'request_stok' ? 'active' : '' ?>">
                    <a href="../admin/request_stok.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-box-open fa-fw me-2"></i> Request Stok</a>
                </li>
                <li class="<?= $active_menu == 'penerimaan_barang' ? 'active' : '' ?>">
                    <a href="../admin/penerimaan_barang.php" class="text-decoration-none px-4 py-2 d-block text-secondary fw-medium"><i class="fas fa-clipboard-check fa-fw me-2"></i> Penerimaan Barang</a>
                </li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="text-decoration-none text-danger fw-bold"><i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout</a>
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
                    <p class="mb-0 text-muted small"><i class="fas fa-map-marker-alt me-1 text-danger"></i> <?= $display_cabang ?></p>
                </div>
            </div>
            
            <div class="header-right d-flex align-items-center gap-3">
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <?php 
                        // Deteksi URL saat ini untuk menentukan tombol switch
                        $current_url = $_SERVER['REQUEST_URI'];
                        $is_gudang = strpos($current_url, '/gudang/') !== false;
                        $target_url = $is_gudang ? '../admin/index.php' : '../gudang/index.php';
                        $btn_text = $is_gudang ? 'Switch ke Admin' : 'Switch ke Gudang';
                        $btn_class = $is_gudang ? 'btn-outline-primary' : 'btn-outline-secondary';
                    ?>
                    <a href="<?= $target_url ?>" class="btn <?= $btn_class ?> btn-sm fw-bold shadow-sm d-none d-lg-flex align-items-center" title="Ganti Workspace">
                        <i class="fas fa-exchange-alt me-1"></i> <span class="d-none d-xl-inline"><?= $btn_text ?></span>
                    </a>
                <?php endif; ?>

                <div class="d-none d-md-flex align-items-center bg-white px-3 py-2 rounded shadow-sm border">
                    <div id="statusIndicator" class="rounded-circle bg-secondary me-2" style="width: 10px; height: 10px;"></div>
                    <div class="d-flex flex-column" style="line-height: 1.1;">
                        <small class="fw-bold text-success" style="font-size: 0.7rem;">SYSTEM LIVE</small>
                        <small class="text-muted" style="font-size: 0.7rem;"><span id="onlineCount">0</span> Online</small>
                    </div>
                </div>
                
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                    <?php
                        $target = $_SESSION['view_cabang_id'] ?? 'pusat';
                        
                        if($target != 'pusat'):
                            $c_status = $koneksi->query("SELECT is_open FROM cabang WHERE id='$target'")->fetch_assoc()['is_open'];
                            $btn_cls = $c_status ? 'btn-success' : 'btn-danger';
                            $btn_txt = $c_status ? 'TOKO BUKA' : 'TOKO TUTUP';
                            $btn_icon = $c_status ? 'fa-door-open' : 'fa-door-closed';
                            $confirm_msg = $c_status ? "Tutup toko sementara?" : "Buka toko kembali?";
                    ?>
                        <button onclick="toggleToko('<?= $target ?>', '<?= $confirm_msg ?>')" class="btn <?= $btn_cls ?> btn-sm fw-bold rounded-pill shadow-sm">
                            <i class="fas <?= $btn_icon ?> me-1"></i> <?= $btn_txt ?>
                        </button>
                        <script>
                        function toggleToko(id, msg) {
                            Swal.fire({ title: 'Ubah Status?', text: msg, icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Ubah' })
                            .then((r) => { if (r.isConfirmed) window.location.href = 'toggle_toko.php?id=' + id; });
                        }
                        </script>
                    <?php else: ?>
                         <button class="btn btn-secondary btn-sm fw-bold rounded-pill shadow-sm" disabled title="Pilih cabang spesifik untuk mengatur status toko">
                            <i class="fas fa-store me-1"></i> PILIH CABANG
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin' && !$show_gudang_menu): ?>
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

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-decoration-none" data-bs-toggle="dropdown">
                            <?php if(isset($_SESSION['foto']) && $_SESSION['foto']): ?>
                                 <img src="<?= $_SESSION['foto'] ?>" class="rounded-circle border shadow-sm" width="40" height="40">
                            <?php else: ?>
                                <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center fw-bold shadow-sm" style="width: 40px; height: 40px;">
                                    <?= substr($_SESSION['nama'] ?? 'A', 0, 1) ?>
                                </div>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                            <li><h6 class="dropdown-header text-primary fw-bold">Halo, <?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></h6></li>
                            
                            <?php if(isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                                <?php 
                                    // Deteksi URL saat ini untuk menentukan tombol switch
                                    $current_url = $_SERVER['REQUEST_URI'];
                                    $is_gudang = strpos($current_url, '/gudang/') !== false;
                                ?>
                                <li>
                                    <a class="dropdown-item fw-bold text-success" href="<?= $is_gudang ? '../admin/index.php' : '../gudang/index.php' ?>">
                                        <i class="fas fa-exchange-alt me-2"></i> 
                                        <?= $is_gudang ? 'Ke Mode Admin' : 'Ke Mode Gudang' ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user-edit me-2 text-muted"></i> Edit Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger fw-bold" href="../logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
            </div>
        </header>