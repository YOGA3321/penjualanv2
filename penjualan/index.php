<?php
session_start();
require_once '../auth/koneksi.php';

// --- 1. LOGIKA VALIDASI TOKEN QR ---
// Cek apakah ada token di URL (Scan baru) atau sudah ada di sesi (Refresh halaman)
if (isset($_GET['token'])) {
    $token = $koneksi->real_escape_string($_GET['token']);
    
    // Cek token di database
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang 
              FROM meja 
              JOIN cabang ON meja.cabang_id = cabang.id 
              WHERE meja.qr_token = '$token'";
    
    $result = $koneksi->query($query);
    
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        // Simpan ke Session agar tidak hilang saat refresh
        $_SESSION['plg_meja_id'] = $info['id'];
        $_SESSION['plg_no_meja'] = $info['nomor_meja'];
        $_SESSION['plg_cabang_id'] = $info['id_cabang'];
        $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
    } else {
        die("<div class='alert alert-danger text-center m-5'>QR Code tidak valid atau kadaluarsa!</div>");
    }
}

// Cek apakah sesi pelanggan sudah ada
if (!isset($_SESSION['plg_meja_id'])) {
    die("<div class='alert alert-warning text-center m-5'>Silakan scan QR Code pada meja terlebih dahulu.</div>");
}

// --- 2. AMBIL DATA MENU SESUAI CABANG ---
$cabang_id = $_SESSION['plg_cabang_id'];

// Logika: Menu Cabang Ini + Menu Global (NULL)
$sql_menu = "SELECT m.*, k.nama_kategori 
             FROM menu m 
             JOIN kategori_menu k ON m.kategori_id = k.id 
             WHERE (m.cabang_id = '$cabang_id' OR m.cabang_id IS NULL) 
             AND m.stok > 0 
             ORDER BY k.id ASC, m.id DESC";

$menus = $koneksi->query($sql_menu);

// Kelompokkan menu by Kategori untuk filter (opsional, disini kita tampilkan grid dulu)
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan - <?= $_SESSION['plg_nama_cabang'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="shortcut icon" href="../assets/images/pngkey.com-food-network-logo-png-430444.png" type="image/x-icon">
</head>
<body>

    <div class="dashboard-wrapper">
        <aside class="sidebar d-none d-lg-flex">
            <div class="sidebar-header">
                <a href="#" class="sidebar-logo">
                    <img src="../assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo">
                    <span>Modern Bites</span>
                </a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="index.php"><i class="fas fa-utensils fa-fw"></i> <span>Menu Pesan</span></a></li>
                    <li><a href="#" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas"><i class="fas fa-shopping-cart fa-fw"></i> <span>Keranjang</span></a></li>
                    <li><a href="#"><i class="fas fa-history fa-fw"></i> <span>Riwayat</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <div class="px-4 py-3">
                    <small class="text-muted d-block">Lokasi Anda:</small>
                    <strong><?= $_SESSION['plg_nama_cabang'] ?></strong>
                    <div class="badge bg-primary">Meja <?= $_SESSION['plg_no_meja'] ?></div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="btn d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvasMobile">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1>Mau makan apa?</h1>
                        <p class="d-none d-md-block text-muted">
                            <i class="fas fa-map-marker-alt text-danger"></i> <?= $_SESSION['plg_nama_cabang'] ?> &bull; Meja <?= $_SESSION['plg_no_meja'] ?>
                        </p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="search-box d-none d-md-block">
                        <input type="text" id="searchMenu" placeholder="Cari menu lapar..." onkeyup="filterMenu()">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </header>

            <div class="category-filter mb-4">
                <button class="filter-btn active" onclick="filterKategori('all')">Semua</button>
                </div>

            <div class="menu-grid" id="menuContainer">
                <?php if($menus->num_rows > 0): ?>
                    <?php while($m = $menus->fetch_assoc()): ?>
                        <div class="menu-card" 
                             data-id="<?= $m['id'] ?>" 
                             data-name="<?= htmlspecialchars($m['nama_menu']) ?>" 
                             data-price="<?= $m['harga'] ?>" 
                             data-image="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.jpg' ?>">
                            
                            <div class="position-relative">
                                <img src="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.jpg' ?>" 
                                     alt="<?= $m['nama_menu'] ?>" class="menu-card-img">
                                <?php if($m['stok'] < 5): ?>
                                    <span class="position-absolute top-0 end-0 badge bg-warning text-dark m-2">Sisa <?= $m['stok'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="menu-card-body">
                                <small class="text-muted"><?= $m['nama_kategori'] ?></small>
                                <h5 class="text-truncate" title="<?= $m['nama_menu'] ?>"><?= $m['nama_menu'] ?></h5>
                                <p class="price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                            </div>
                            <button class="btn-add-to-cart ripple-effect">+</button>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Belum ada menu tersedia untuk cabang ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div class="cart-fab" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-badge">0</span>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Keranjang Saya</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items-container">
                <p class="text-center text-muted empty-cart-message my-5">
                    <i class="fas fa-shopping-basket fa-3x mb-3 text-light-gray"></i><br>
                    Keranjangmu masih kosong!
                </p>
            </div>
        </div>
        <div class="cart-footer bg-white border-top p-3">
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold">Total:</span>
                <strong id="cart-total-price" class="text-primary fs-5">Rp 0</strong>
            </div>
            <a href="cart.php" class="btn btn-primary w-100 py-2 rounded-pill fw-bold btn-checkout-action disabled">
                Lanjutkan Pembayaran
            </a>
        </div>
    </div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvasMobile">
        <div class="offcanvas-header">
             <a href="#" class="sidebar-logo">
                <img src="../assets/images/pngkey.com-food-network-logo-png-430444.png" alt="Logo">
                <span>Modern Bites</span>
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
             <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="index.php"><i class="fas fa-utensils fa-fw"></i> <span>Menu Pesan</span></a></li>
                    <li><a href="#" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas"><i class="fas fa-shopping-cart fa-fw"></i> <span>Keranjang</span></a></li>
                    <li><a href="#"><i class="fas fa-history fa-fw"></i> <span>Riwayat</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer mt-auto border-top p-3">
                <small>Login sebagai:</small><br>
                <strong>Pelanggan Meja <?= $_SESSION['plg_no_meja'] ?></strong>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    
    <script>
        function filterMenu() {
            let input = document.getElementById('searchMenu').value.toLowerCase();
            let cards = document.querySelectorAll('.menu-card');
            
            cards.forEach(card => {
                let name = card.getAttribute('data-name').toLowerCase();
                if (name.includes(input)) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>