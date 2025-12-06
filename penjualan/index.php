<?php
session_start();
require_once '../auth/koneksi.php';

// --- LOGIKA PENANGANAN TOKEN (SCAN QR) ---
if (isset($_GET['token'])) {
    $token = $koneksi->real_escape_string($_GET['token']);
    
    // 1. Cek Token Valid
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang 
              FROM meja 
              JOIN cabang ON meja.cabang_id = cabang.id 
              WHERE meja.qr_token = '$token'";
    $result = $koneksi->query($query);
    
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        $meja_id = $info['id'];
        $user_id = $_SESSION['user_id'] ?? 0;

        // =========================================================
        // GATEKEEPER: CEK RESERVASI AKTIF
        // =========================================================
        $now = date('Y-m-d H:i:s');
        
        // Cek reservasi di meja ini yang waktunya beririsan dengan sekarang
        // (Mulai dari 15 menit sebelum jadwal, sampai durasi selesai)
        $cek_res = $koneksi->query("SELECT * FROM reservasi 
                                    WHERE meja_id = '$meja_id' 
                                    AND status IN ('pending', 'checkin')
                                    AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 15 MINUTE) 
                                                   AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
                                    LIMIT 1");

        if ($cek_res->num_rows > 0) {
            $res_data = $cek_res->fetch_assoc();
            
            // A. Jika RESERVASI MILIK ORANG LAIN
            if ($res_data['user_id'] != $user_id) {
                $jam_res = date('H:i', strtotime($res_data['waktu_reservasi']));
                $error_msg = "Maaf, Meja ini sudah di-RESERVASI oleh pelanggan lain untuk pukul $jam_res. Silakan pilih meja lain.";
            } 
            // B. Jika RESERVASI MILIK SENDIRI (CHECK-IN)
            else {
                // Update status jadi checkin (User datang)
                $koneksi->query("UPDATE reservasi SET status='checkin' WHERE id='".$res_data['id']."'");
                
                // Lanjut Masuk...
                $_SESSION['plg_meja_id'] = $info['id'];
                $_SESSION['plg_no_meja'] = $info['nomor_meja'];
                $_SESSION['plg_cabang_id'] = $info['id_cabang'];
                $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
                
                if(!isset($_SESSION['plg_meja_id']) || $_SESSION['plg_meja_id'] != $info['id']) {
                    $_SESSION['force_reset_cart'] = true;
                }
                
                header("Location: index.php"); exit;
            }
        } 
        // =========================================================
        // JIKA TIDAK ADA RESERVASI (WALK-IN OFFLINE)
        // =========================================================
        else {
            if ($info['status'] == 'terisi') {
                if (isset($_SESSION['plg_meja_id']) && $_SESSION['plg_meja_id'] == $info['id']) {
                    header("Location: index.php"); exit; 
                } else {
                    $error_msg = "Meja ini sedang digunakan pelanggan lain.";
                }
            } else {
                $_SESSION['plg_meja_id'] = $info['id'];
                $_SESSION['plg_no_meja'] = $info['nomor_meja'];
                $_SESSION['plg_cabang_id'] = $info['id_cabang'];
                $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
                $_SESSION['force_reset_cart'] = true;
                header("Location: index.php"); exit;
            }
        }

    } else {
        $error_msg = "QR Code tidak valid!";
    }
}

// JIKA ERROR / BELUM SCAN -> TAMPILAN SCANNER
if (!isset($_SESSION['plg_meja_id']) || isset($error_msg)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Scan Meja</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8f9fa; }
            .scan-card { max-width: 400px; width: 90%; text-align: center; padding: 40px 30px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        </style>
    </head>
    <body>
        <div class="scan-card">
            <div class="mb-4 text-primary"><i class="fas fa-qrcode fa-4x"></i></div>
            
            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger py-3 mb-4 fw-bold">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                </div>
                <a href="../pelanggan/index.php" class="btn btn-outline-secondary rounded-pill w-100">Kembali ke Dashboard</a>
            <?php else: ?>
                <h3 class="fw-bold mb-2">Selamat Datang!</h3>
                <p class="text-muted mb-4">Silakan scan QR Code di meja untuk memesan.</p>
                <a href="../pelanggan/index.php" class="btn btn-primary w-100 rounded-pill">Buka Pemindai</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- LOAD MENU (TAMPILAN UTAMA) ---
$cabang_id = $_SESSION['plg_cabang_id'];
$sql_menu = "SELECT m.*, k.nama_kategori 
             FROM menu m 
             JOIN kategori_menu k ON m.kategori_id = k.id 
             WHERE (m.cabang_id = '$cabang_id' OR m.cabang_id IS NULL) 
             AND m.is_active = 1
             ORDER BY k.id ASC, m.id DESC";
$menus = $koneksi->query($sql_menu);
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
                    <li class="active"><a href="#"><i class="fas fa-utensils fa-fw"></i> <span>Menu Pesan</span></a></li>
                    <li><a href="#" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas"><i class="fas fa-shopping-cart fa-fw"></i> <span>Keranjang</span></a></li>
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
            </header>

            <div class="menu-grid" id="menuContainer">
                <?php if($menus->num_rows > 0): ?>
                    <?php while($m = $menus->fetch_assoc()): ?>
                        <?php 
                            $habis = $m['stok'] <= 0;
                            $class_card = $habis ? 'menu-card stok-habis' : 'menu-card';
                        ?>
                        <div class="<?= $class_card ?>" 
                             data-id="<?= $m['id'] ?>" 
                             data-name="<?= htmlspecialchars($m['nama_menu']) ?>" 
                             data-price="<?= $m['harga'] ?>" 
                             data-image="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.jpg' ?>">
                            
                            <div class="position-relative">
                                <img src="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.jpg' ?>" 
                                     alt="<?= $m['nama_menu'] ?>" class="menu-card-img">
                                
                                <?php if($habis): ?>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5); z-index: 5;">
                                        <span class="badge bg-danger fs-5 px-3 py-2 shadow">HABIS</span>
                                    </div>
                                <?php else: ?>
                                    <span class="position-absolute top-0 end-0 badge bg-success text-white m-2 shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> Tersedia
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="menu-card-body">
                                <small class="text-muted"><?= $m['nama_kategori'] ?></small>
                                <h5 class="text-truncate" title="<?= $m['nama_menu'] ?>"><?= $m['nama_menu'] ?></h5>
                                <p class="price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                            </div>
                            
                            <?php if(!$habis): ?>
                                <button class="btn-add-to-cart ripple-effect">+</button>
                            <?php else: ?>
                                <button class="btn-add-to-cart bg-secondary" disabled>x</button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">Belum ada menu tersedia.</p>
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
                <p class="text-center text-muted empty-cart-message my-5">Keranjangmu masih kosong!</p>
            </div>
        </div>
        <div class="cart-footer bg-white border-top p-3">
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold">Total:</span>
                <strong id="cart-total-price" class="text-primary fs-5">Rp 0</strong>
            </div>
            <a href="cart.php" class="btn btn-primary w-100 py-2 rounded-pill fw-bold btn-checkout-action disabled">Lanjutkan Pembayaran</a>
        </div>
    </div>
    
    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvasMobile">
        <div class="offcanvas-header">
             <a href="#" class="sidebar-logo"><span>Modern Bites</span></a>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
             <nav class="sidebar-nav">
                <ul>
                    <li class="active"><a href="#"><i class="fas fa-utensils fa-fw"></i> <span>Menu Pesan</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer mt-auto border-top p-3">
                <small>Meja:</small><br><strong><?= $_SESSION['plg_no_meja'] ?></strong>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/dashboard.js"></script>

    <?php if (isset($_SESSION['force_reset_cart'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            localStorage.removeItem('cart_v2'); 
            Swal.fire({icon: 'success', title: 'Selamat Datang!', text: 'Silakan pesan menu.', timer: 1500, showConfirmButton: false})
            .then(() => { location.reload(); });
        });
    </script>
    <?php unset($_SESSION['force_reset_cart']); endif; ?>

</body>
</html>