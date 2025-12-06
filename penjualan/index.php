<?php
session_start();
require_once '../auth/koneksi.php';

// --- 1. PROSES TOKEN (JIKA SCAN QR) ---
if (isset($_GET['token'])) {
    $token = $koneksi->real_escape_string($_GET['token']);
    
    // Cek Token di Database
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang 
              FROM meja 
              JOIN cabang ON meja.cabang_id = cabang.id 
              WHERE meja.qr_token = '$token'";
    $result = $koneksi->query($query);
    
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        $meja_id = $info['id'];
        $user_id = $_SESSION['user_id'] ?? 0;

        // --- GATEKEEPER: CEK RESERVASI ---
        $now = date('Y-m-d H:i:s');
        $cek_res = $koneksi->query("SELECT * FROM reservasi 
                                    WHERE meja_id = '$meja_id' 
                                    AND status IN ('pending', 'checkin')
                                    AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 15 MINUTE) 
                                                   AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
                                    LIMIT 1");

        $allow_entry = true;
        $error_msg = null;

        if ($cek_res->num_rows > 0) {
            $res_data = $cek_res->fetch_assoc();
            // Jika reservasi punya orang lain
            if ($res_data['user_id'] != $user_id) {
                $allow_entry = false;
                $jam_res = date('H:i', strtotime($res_data['waktu_reservasi']));
                $error_msg = "Meja ini dipesan untuk pukul $jam_res.";
            } else {
                // Check-in reservasi sendiri
                $koneksi->query("UPDATE reservasi SET status='checkin' WHERE id='".$res_data['id']."'");
            }
        } 
        // Jika tidak ada reservasi, cek status meja fisik (Walk-in)
        else {
            if ($info['status'] == 'terisi') {
                // Cek apakah ini sesi saya sendiri (Re-order)?
                if (!isset($_SESSION['plg_meja_id']) || $_SESSION['plg_meja_id'] != $meja_id) {
                    $allow_entry = false;
                    $error_msg = "Meja sedang digunakan pelanggan lain.";
                }
            }
        }

        // --- FINALISASI SESI ---
        if ($allow_entry) {
            // Set Session
            $_SESSION['plg_meja_id'] = $info['id'];
            $_SESSION['plg_no_meja'] = $info['nomor_meja'];
            $_SESSION['plg_cabang_id'] = $info['id_cabang'];
            $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
            
            // Reset keranjang jika meja berbeda dari sebelumnya
            if(!isset($_SESSION['plg_meja_id']) || $_SESSION['plg_meja_id'] != $info['id']) {
                $_SESSION['force_reset_cart'] = true;
            }

            // [FIX BUG] Paksa simpan sesi sebelum redirect agar tidak hilang
            session_write_close(); 
            
            // Redirect bersih (hapus token dari URL)
            header("Location: index.php"); 
            exit;
        }

    } else {
        $error_msg = "QR Code tidak dikenali / kadaluarsa.";
    }
}

// --- 2. TAMPILAN JIKA BELUM SCAN / SCAN GAGAL (LANDING PAGE) ---
if (!isset($_SESSION['plg_meja_id']) || isset($error_msg)) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Scan Meja - Modern Bites</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body { 
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                font-family: 'Poppins', sans-serif; 
                height: 100vh; display: flex; align-items: center; justify-content: center; 
            }
            .scan-container {
                background: white; padding: 40px 30px; border-radius: 25px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                text-align: center; max-width: 400px; width: 90%;
                position: relative; overflow: hidden;
            }
            .scan-icon {
                width: 100px; height: 100px; background: #e0e7ff; 
                color: #6366f1; border-radius: 50%; 
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto 25px auto; font-size: 2.5rem;
                animation: pulse 2s infinite;
            }
            @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(99, 102, 241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); } }
            .btn-scan {
                background: linear-gradient(to right, #6366f1, #8b5cf6);
                border: none; padding: 12px 30px; font-weight: 600;
                transition: transform 0.2s;
            }
            .btn-scan:active { transform: scale(0.95); }
        </style>
    </head>
    <body>
        <div class="scan-container">
            <div class="scan-icon">
                <i class="fas fa-qrcode"></i>
            </div>
            
            <?php if(isset($error_msg)): ?>
                <h4 class="fw-bold text-danger mb-2">Oops!</h4>
                <p class="text-muted mb-4"><?= $error_msg ?></p>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">Coba Lagi</a>
            <?php else: ?>
                <h4 class="fw-bold mb-2">Selamat Datang</h4>
                <p class="text-muted mb-4">Silakan scan QR Code yang tertera di atas meja untuk mulai memesan menu.</p>
                
                <button class="btn btn-primary btn-scan rounded-pill w-100 shadow-lg">
                    <i class="fas fa-camera me-2"></i> Buka Kamera
                </button>
                <p class="small text-muted mt-3 mb-0 fst-italic">*Gunakan kamera bawaan HP Anda</p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- 3. JIKA SUDAH ADA SESI (LOAD MENU) ---
$cabang_id = $_SESSION['plg_cabang_id'];
$is_logged_in = isset($_SESSION['user_id']);

// Simpan URL untuk redirect balik setelah login
$_SESSION['redirect_after_login'] = "penjualan/index.php";

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Menu - Meja <?= $_SESSION['plg_no_meja'] ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; padding-bottom: 90px; }
        
        /* Header Modern */
        .hero-header {
            background: #ffffff;
            padding: 15px 20px;
            position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        /* Card Menu Responsive */
        .menu-card {
            border: none; background: white; border-radius: 15px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            height: 100%; display: flex; flex-direction: column;
            transition: transform 0.2s;
        }
        .menu-card:active { transform: scale(0.98); }
        
        .menu-img-wrap { position: relative; height: 140px; background: #eee; }
        .menu-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        
        .badge-promo {
            position: absolute; top: 10px; left: 10px;
            background: #e11d48; color: white; padding: 4px 10px;
            border-radius: 20px; font-size: 0.7rem; font-weight: bold;
            box-shadow: 0 2px 5px rgba(225, 29, 72, 0.4);
        }
        .stok-habis { filter: grayscale(1); opacity: 0.7; pointer-events: none; }

        /* Floating Cart */
        .floating-cart {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #1e293b; color: white;
            padding: 15px 20px; border-radius: 50px;
            box-shadow: 0 10px 25px rgba(30, 41, 59, 0.4);
            display: none; z-index: 1000;
            align-items: center; justify-content: space-between;
            cursor: pointer; animation: slideUp 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes slideUp { from { transform: translateY(100px); } to { transform: translateY(0); } }

        /* Input Search */
        .search-box {
            background: #f1f5f9; border-radius: 50px; padding: 10px 20px;
            display: flex; align-items: center; margin: 20px 0;
        }
        .search-box input { border: none; background: transparent; width: 100%; outline: none; }
    </style>
</head>
<body>

    <div class="hero-header">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 40px; height: 40px;">
                <?= $_SESSION['plg_no_meja'] ?>
            </div>
            <div class="lh-1">
                <small class="text-muted" style="font-size:0.75rem;">Lokasi</small>
                <div class="fw-bold text-dark text-truncate" style="max-width:120px;"><?= $_SESSION['plg_nama_cabang'] ?></div>
            </div>
        </div>
        <div>
            <?php if($is_logged_in): ?>
                <a href="../pelanggan/profil.php" class="text-dark text-decoration-none fw-bold">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['nama']) ?>&background=random" class="rounded-circle" width="35">
                </a>
            <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="search-box">
            <i class="fas fa-search text-muted me-2"></i>
            <input type="text" id="searchMenu" placeholder="Cari makanan kesukaanmu...">
        </div>

        <h6 class="fw-bold mb-3">Menu Tersedia</h6>
        <div class="row g-3" id="menuContainer">
            <?php if($menus->num_rows > 0): ?>
                <?php while($m = $menus->fetch_assoc()): ?>
                    <?php 
                        $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                        $harga_tampil = $is_promo ? $m['harga_promo'] : $m['harga'];
                        $stok_habis = $m['stok'] <= 0;
                        
                        $data_js = htmlspecialchars(json_encode([
                            'id' => $m['id'],
                            'nama' => $m['nama_menu'],
                            'harga' => $harga_tampil,
                            'stok' => $m['stok']
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="col-6 col-md-4 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                        <div class="menu-card <?= $stok_habis ? 'stok-habis' : '' ?>" onclick='addToCart(<?= $data_js ?>)'>
                            <div class="menu-img-wrap">
                                <img src="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.png' ?>" loading="lazy">
                                <?php if($is_promo): ?><span class="badge-promo">PROMO</span><?php endif; ?>
                                <?php if($stok_habis): ?>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 text-white fw-bold">HABIS</div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="fw-bold text-dark mb-1 text-truncate"><?= $m['nama_menu'] ?></div>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <div class="lh-1">
                                        <?php if($is_promo): ?>
                                            <small class="text-decoration-line-through text-muted" style="font-size:0.7rem">Rp <?= number_format($m['harga'],0,',','.') ?></small>
                                            <div class="text-danger fw-bold">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                                        <?php else: ?>
                                            <div class="text-primary fw-bold">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn btn-sm btn-light rounded-circle shadow-sm" style="width:30px; height:30px;"><i class="fas fa-plus small"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">Menu belum tersedia.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="floating-cart" id="floatingCart" onclick="openCart()">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:35px; height:35px;" id="totalQty">0</div>
            <div class="lh-1">
                <small class="opacity-75" style="font-size:0.7rem">Total</small>
                <div class="fw-bold" id="totalPrice">Rp 0</div>
            </div>
        </div>
        <div class="fw-bold small">Lihat Keranjang <i class="fas fa-chevron-right ms-1"></i></div>
    </div>

    <div class="offcanvas offcanvas-bottom rounded-top-4" tabindex="-1" id="cartModal" style="height: 85vh;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold">Pesanan Anda</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body pb-0">
            <div id="cartListContainer"></div>
            
            <div class="bg-light p-3 rounded-3 mt-3">
                <label class="small fw-bold text-muted mb-2">Kode Voucher</label>
                <div class="input-group">
                    <input type="text" id="inputVoucher" class="form-control" placeholder="Punya kode?">
                    <button class="btn btn-dark" onclick="cekVoucher()">Pakai</button>
                </div>
                <div id="voucherMsg" class="mt-2 small fw-bold text-success" style="display:none"></div>
            </div>

            <?php if(!$is_logged_in): ?>
            <div class="mt-3">
                <label class="small fw-bold text-muted mb-1">Nama Pemesan</label>
                <input type="text" id="namaPelanggan" class="form-control form-control-lg" placeholder="Contoh: Budi">
            </div>
            <?php else: ?>
                <input type="hidden" id="namaPelanggan" value="<?= $_SESSION['nama'] ?>">
            <?php endif; ?>
        </div>
        
        <div class="offcanvas-footer p-3 border-top bg-white">
            <div class="d-flex justify-content-between mb-2 small text-muted">
                <span>Subtotal</span>
                <span id="subtotalDisplay">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-3 small text-danger" id="diskonRow" style="display:none">
                <span>Diskon</span>
                <span id="diskonDisplay">- Rp 0</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold">Total Bayar</span>
                <span class="fw-bold fs-4 text-primary" id="totalDisplay">Rp 0</span>
            </div>
            <div class="row g-2">
                <div class="col-6">
                    <button class="btn btn-success w-100 py-3 rounded-4 fw-bold" onclick="checkout('tunai')">Tunai</button>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary w-100 py-3 rounded-4 fw-bold" onclick="checkout('midtrans')">QRIS</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    
    <script>
    let cart = [];
    let activeVoucher = null;
    const bsOffcanvas = new bootstrap.Offcanvas('#cartModal');

    // Auto Login Prompt
    <?php if(!$is_logged_in): ?>
    window.onload = () => {
        if(!sessionStorage.getItem('seen_prompt')) {
            Swal.fire({
                title: 'Halo!',
                text: 'Login biar dapet poin & promo member?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Login',
                cancelButtonText: 'Nanti Saja',
                reverseButtons: true
            }).then((res) => {
                sessionStorage.setItem('seen_prompt', '1');
                if(res.isConfirmed) window.location.href='../login.php';
            });
        }
    };
    <?php endif; ?>

    document.getElementById('searchMenu').addEventListener('keyup', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.menu-item').forEach(el => {
            el.style.display = el.dataset.name.includes(val) ? 'block' : 'none';
        });
    });

    function addToCart(item) {
        let exist = cart.find(c => c.id === item.id);
        if(exist) {
            if(exist.qty < item.stok) exist.qty++;
            else return Swal.fire({toast:true, position:'top', icon:'warning', title:'Stok habis'});
        } else {
            cart.push({...item, qty: 1});
        }
        updateUI();
        Swal.fire({toast:true, position:'bottom', icon:'success', title: item.nama + ' +1', showConfirmButton:false, timer:1000});
    }

    function updateQty(idx, delta) {
        if(delta === -1 && cart[idx].qty === 1) cart.splice(idx, 1);
        else {
            let n = cart[idx].qty + delta;
            if(n <= cart[idx].stok) cart[idx].qty = n;
            else return Swal.fire({toast:true, icon:'warning', title:'Stok mentok'});
        }
        updateUI();
    }

    function updateUI() {
        let qty = 0, sub = 0;
        let html = '';
        
        if(cart.length === 0) {
            document.getElementById('floatingCart').style.display = 'none';
            html = '<div class="text-center py-5 text-muted"><i class="fas fa-shopping-basket fa-3x mb-3 opacity-25"></i><br>Keranjang kosong</div>';
        } else {
            document.getElementById('floatingCart').style.display = 'flex';
            cart.forEach((c, i) => {
                qty += c.qty;
                sub += c.harga * c.qty;
                html += `
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <div>
                        <div class="fw-bold">${c.nama}</div>
                        <small class="text-muted">Rp ${c.harga.toLocaleString('id-ID')}</small>
                    </div>
                    <div class="d-flex align-items-center bg-light rounded-pill px-2 py-1">
                        <button class="btn btn-sm btn-link text-danger p-0" onclick="updateQty(${i}, -1)"><i class="fas fa-minus-circle"></i></button>
                        <span class="mx-2 fw-bold small">${c.qty}</span>
                        <button class="btn btn-sm btn-link text-success p-0" onclick="updateQty(${i}, 1)"><i class="fas fa-plus-circle"></i></button>
                    </div>
                </div>`;
            });
        }

        // Logic Voucher
        let diskon = 0;
        if(activeVoucher) {
            if(sub < activeVoucher.min) {
                activeVoucher = null;
                document.getElementById('voucherMsg').style.display = 'none';
                document.getElementById('inputVoucher').value = '';
            } else {
                diskon = (activeVoucher.type === 'fixed') ? activeVoucher.val : sub * (activeVoucher.val/100);
            }
        }
        if(diskon > sub) diskon = sub;
        
        document.getElementById('cartListContainer').innerHTML = html;
        document.getElementById('totalQty').innerText = qty;
        document.getElementById('totalPrice').innerText = 'Rp ' + (sub - diskon).toLocaleString('id-ID');
        
        document.getElementById('subtotalDisplay').innerText = 'Rp ' + sub.toLocaleString('id-ID');
        document.getElementById('totalDisplay').innerText = 'Rp ' + (sub - diskon).toLocaleString('id-ID');
        
        if(diskon > 0) {
            document.getElementById('diskonRow').style.display = 'flex';
            document.getElementById('diskonDisplay').innerText = '- Rp ' + diskon.toLocaleString('id-ID');
        } else {
            document.getElementById('diskonRow').style.display = 'none';
        }
    }

    function openCart() { bsOffcanvas.show(); }

    function cekVoucher() {
        let kode = document.getElementById('inputVoucher').value;
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        if(!kode) return;
        
        fetch(`api_voucher.php?kode=${kode}&total=${sub}`)
        .then(r=>r.json())
        .then(d => {
            if(d.valid) {
                activeVoucher = {code:d.kode, type:d.tipe, val:d.nilai_voucher, min:d.min_belanja};
                document.getElementById('voucherMsg').style.display = 'block';
                document.getElementById('voucherMsg').innerText = "✅ Voucher Aktif";
                updateUI();
            } else {
                Swal.fire({toast:true, icon:'error', title:d.msg});
                activeVoucher = null;
                updateUI();
            }
        });
    }

    function checkout(metode) {
        let nama = document.getElementById('namaPelanggan').value;
        if(!nama && !<?= $is_logged_in ? 'true' : 'false' ?>) return Swal.fire('Info', 'Nama pemesan wajib diisi', 'warning');
        
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        let diskon = activeVoucher ? ((activeVoucher.type === 'fixed') ? activeVoucher.val : sub * (activeVoucher.val/100)) : 0;
        
        let payload = {
            items: cart,
            nama_pelanggan: nama,
            total_harga: sub - diskon,
            diskon: diskon,
            kode_voucher: activeVoucher ? activeVoucher.code : null,
            metode: metode
        };

        Swal.fire({title:'Memproses...', didOpen:()=>Swal.showLoading()});

        fetch('proses_checkout.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
        })
        .then(r=>r.json())
        .then(d => {
            if(d.status === 'success') {
                if(metode === 'midtrans' && d.snap_token) {
                    window.snap.pay(d.snap_token, {
                        onSuccess: () => finish(true),
                        onPending: () => finish(true),
                        onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error')
                    });
                } else {
                    finish(false);
                }
            } else {
                Swal.fire('Gagal', d.message, 'error');
            }
        });
    }

    function finish(isMidtrans) {
        Swal.fire({icon:'success', title:'Pesanan Masuk!', text:'Mohon tunggu sebentar...', timer:2000, showConfirmButton:false})
        .then(() => {
            // Redirect sesuai status login
            <?php if($is_logged_in): ?>
                window.location.href = '../pelanggan/riwayat.php';
            <?php else: ?>
                window.location.href = 'sukses.php';
            <?php endif; ?>
        });
    }
    </script>
</body>
</html>