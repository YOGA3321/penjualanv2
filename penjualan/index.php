<?php
session_start();
require_once '../auth/koneksi.php';

// --- LOGIKA SCAN QR ---
if (isset($_GET['token'])) {
    $token = $koneksi->real_escape_string($_GET['token']);
    $query = "SELECT meja.*, cabang.nama_cabang, cabang.id as id_cabang FROM meja JOIN cabang ON meja.cabang_id = cabang.id WHERE meja.qr_token = '$token'";
    $result = $koneksi->query($query);
    
    if ($result->num_rows > 0) {
        $info = $result->fetch_assoc();
        $meja_id = $info['id'];
        $user_id = $_SESSION['user_id'] ?? 0;

        // Gatekeeper Reservasi
        $now = date('Y-m-d H:i:s');
        $cek_res = $koneksi->query("SELECT * FROM reservasi WHERE meja_id = '$meja_id' AND status IN ('pending', 'checkin') AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 15 MINUTE) AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE) LIMIT 1");

        if ($cek_res->num_rows > 0) {
            $res_data = $cek_res->fetch_assoc();
            if ($res_data['user_id'] != $user_id) {
                $error_msg = "Maaf, Meja ini sudah direservasi pelanggan lain.";
            } else {
                $koneksi->query("UPDATE reservasi SET status='checkin' WHERE id='".$res_data['id']."'");
                // Set Session Meja
                $_SESSION['plg_meja_id'] = $info['id'];
                $_SESSION['plg_no_meja'] = $info['nomor_meja'];
                $_SESSION['plg_cabang_id'] = $info['id_cabang'];
                $_SESSION['plg_nama_cabang'] = $info['nama_cabang'];
                $_SESSION['force_reset_cart'] = true;
                header("Location: index.php"); exit;
            }
        } else {
            if ($info['status'] == 'terisi') {
                if (isset($_SESSION['plg_meja_id']) && $_SESSION['plg_meja_id'] == $info['id']) {
                    header("Location: index.php"); exit;
                } else {
                    $error_msg = "Meja sedang digunakan pelanggan lain.";
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

// BLOCKER JIKA BELUM SCAN
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
        <style>body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8f9fa; }</style>
    </head>
    <body>
        <div class="text-center bg-white p-5 rounded shadow">
            <div class="mb-4 text-primary"><i class="fas fa-qrcode fa-5x"></i></div>
            <?php if(isset($error_msg)): ?>
                <h4 class="text-danger fw-bold">Akses Ditolak</h4>
                <p class="text-muted"><?= $error_msg ?></p>
                <a href="../pelanggan/index.php" class="btn btn-outline-secondary rounded-pill mt-3">Kembali ke Dashboard</a>
            <?php else: ?>
                <h3>Selamat Datang!</h3>
                <p class="text-muted">Silakan scan QR Code di meja Anda.</p>
                <a href="../pelanggan/index.php" class="btn btn-primary rounded-pill mt-2">Buka Pemindai</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php exit;
}

// --- LOAD MENU ---
$cabang_id = $_SESSION['plg_cabang_id'];
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['nama'] ?? 'Tamu';

$menus = $koneksi->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori_menu k ON m.kategori_id = k.id WHERE (m.cabang_id = '$cabang_id' OR m.cabang_id IS NULL) AND m.stok > 0 AND m.is_active = 1 ORDER BY k.id ASC");
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
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; padding-bottom: 100px; }
        .hero-header { background: #fff; padding: 15px 20px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; }
        .menu-card { border: none; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); height: 100%; display: flex; flex-direction: column; transition: transform 0.2s; cursor: pointer; }
        .menu-card:active { transform: scale(0.98); }
        .menu-img-wrap { position: relative; height: 140px; background: #eee; }
        .menu-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .badge-promo { position: absolute; top: 10px; left: 10px; background: #e11d48; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; box-shadow: 0 2px 5px rgba(225, 29, 72, 0.4); }
        
        .floating-cart { position: fixed; bottom: 20px; left: 20px; right: 20px; background: #1e293b; color: white; padding: 15px 20px; border-radius: 50px; box-shadow: 0 10px 25px rgba(30, 41, 59, 0.4); display: none; z-index: 1000; align-items: center; justify-content: space-between; cursor: pointer; animation: slideUp 0.3s ease-out; }
        @keyframes slideUp { from { transform: translateY(100px); } to { transform: translateY(0); } }
        
        .search-box { background: #f1f5f9; border-radius: 50px; padding: 10px 20px; display: flex; align-items: center; margin: 20px 0; }
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
                <a href="../pelanggan/profil.php" class="text-dark fw-bold text-decoration-none d-flex align-items-center gap-2">
                    <small class="text-muted d-none d-sm-block">Hi, <?= explode(' ', $user_name)[0] ?></small>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=random" class="rounded-circle border" width="35" height="35">
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
            <?php while($m = $menus->fetch_assoc()): ?>
                <?php 
                    $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                    $harga_tampil = $is_promo ? $m['harga_promo'] : $m['harga'];
                    
                    // JSON Safe Encode
                    $data_js = htmlspecialchars(json_encode([
                        'id' => $m['id'],
                        'nama' => $m['nama_menu'],
                        'harga' => (float)$harga_tampil,
                        'stok' => (int)$m['stok']
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-6 col-md-4 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                    <div class="menu-card" onclick='addToCart(<?= $data_js ?>)'>
                        <div class="menu-img-wrap">
                            <img src="<?= !empty($m['gambar']) ? '../'.$m['gambar'] : '../assets/images/no-image.png' ?>" loading="lazy">
                            <?php if($is_promo): ?><span class="badge-promo">PROMO</span><?php endif; ?>
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
                    <input type="text" id="inputVoucher" class="form-control text-uppercase" placeholder="Masukan Kode">
                    <button class="btn btn-dark" onclick="cekVoucher()">Pakai</button>
                </div>
                <div id="voucherMsg" class="mt-2 small fw-bold" style="display:none"></div>
            </div>

            <div class="mt-3">
                <label class="small fw-bold text-muted mb-1">Nama Pemesan</label>
                <input type="text" id="namaPelanggan" class="form-control form-control-lg" 
                       value="<?= $is_logged_in ? $user_name : '' ?>" 
                       placeholder="Contoh: Budi" <?= $is_logged_in ? '' : '' ?>>
            </div>
        </div>
        
        <div class="offcanvas-footer p-3 border-top bg-white">
            <div class="d-flex justify-content-between mb-2 small text-muted">
                <span>Subtotal</span> <span id="subtotalDisplay">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-3 small text-danger" id="diskonRow" style="display:none">
                <span>Diskon Voucher</span> <span id="diskonDisplay">- Rp 0</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold">Total Bayar</span>
                <span class="fw-bold fs-4 text-primary" id="totalDisplay">Rp 0</span>
            </div>
            <div class="row g-2">
                <div class="col-6"><button class="btn btn-success w-100 py-3 rounded-4 fw-bold" onclick="checkout('tunai')">Tunai</button></div>
                <div class="col-6"><button class="btn btn-primary w-100 py-3 rounded-4 fw-bold" onclick="checkout('midtrans')">QRIS</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    
    <script>
    let cart = [];
    let activeVoucher = null;
    const bsOffcanvas = new bootstrap.Offcanvas('#cartModal');

    // Auto Prompt Login
    <?php if(!$is_logged_in): ?>
    window.onload = () => {
        if(!sessionStorage.getItem('seen_prompt')) {
            Swal.fire({
                title: 'Halo!', text: 'Login biar dapet poin & promo member?', icon: 'question',
                showCancelButton: true, confirmButtonText: 'Ya, Login', cancelButtonText: 'Nanti Saja'
            }).then((res) => {
                sessionStorage.setItem('seen_prompt', '1');
                if(res.isConfirmed) window.location.href='../login.php';
            });
        }
    };
    <?php endif; ?>

    // Search Logic
    document.getElementById('searchMenu').addEventListener('keyup', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.menu-item').forEach(el => {
            el.style.display = el.dataset.name.includes(val) ? 'block' : 'none';
        });
    });

    function addToCart(item) {
        // Force number type
        item.harga = parseFloat(item.harga); 
        
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

        // Voucher Logic
        let diskon = 0;
        if(activeVoucher) {
            let nilai = parseFloat(activeVoucher.val);
            let min = parseFloat(activeVoucher.min);
            
            if(sub < min) {
                activeVoucher = null;
                document.getElementById('voucherMsg').style.display = 'none';
                document.getElementById('inputVoucher').value = '';
                Swal.fire({toast:true, icon:'info', title:'Voucher dilepas (Min. Belanja)'});
            } else {
                if(activeVoucher.type === 'fixed') diskon = nilai;
                else diskon = sub * (nilai / 100);
            }
        }
        
        if(diskon > sub) diskon = sub;
        let totalBayar = sub - diskon;
        
        document.getElementById('cartListContainer').innerHTML = html;
        document.getElementById('totalQty').innerText = qty;
        document.getElementById('totalPrice').innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');
        
        document.getElementById('subtotalDisplay').innerText = 'Rp ' + sub.toLocaleString('id-ID');
        document.getElementById('totalDisplay').innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');
        
        let rowDiskon = document.getElementById('diskonRow');
        if(diskon > 0) {
            rowDiskon.style.display = 'flex';
            document.getElementById('diskonDisplay').innerText = '- Rp ' + diskon.toLocaleString('id-ID');
        } else {
            rowDiskon.style.display = 'none';
        }
    }

    function openCart() { bsOffcanvas.show(); }

    function cekVoucher() {
        let kode = document.getElementById('inputVoucher').value;
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        
        if(!kode) return Swal.fire({toast:true, icon:'warning', title:'Isi kode voucher'});
        if(sub === 0) return Swal.fire({toast:true, icon:'warning', title:'Belanja dulu'});
        
        fetch(`api_voucher.php?kode=${kode}&total=${sub}`)
        .then(r=>r.json())
        .then(d => {
            let msg = document.getElementById('voucherMsg');
            msg.style.display = 'block';
            if(d.valid) {
                activeVoucher = { code: d.kode, type: d.tipe, val: parseFloat(d.potongan), min: 0 }; // Potongan sudah dihitung backend atau ambil raw value dari DB (disini ambil hasil hitung fixed sementara)
                // Idealnya backend kirim raw value, tapi untuk simplifikasi kita percaya backend
                // Revisi: Kita ambil raw type & value dari backend agar kalkulasi JS reaktif
                // Tapi api_voucher.php kita tadi mengembalikan 'potongan' hasil hitung.
                // Agar aman, kita simpan potongan nominal saja (Fixed).
                
                msg.className = 'mt-2 small fw-bold text-success';
                msg.innerText = "✅ Voucher Aktif: Hemat Rp " + parseInt(d.potongan).toLocaleString();
                
                // Override activeVoucher agar statis (karena backend sudah hitung)
                // Atau panggil ulang API saat qty berubah?
                // Untuk mudahnya: Kita pakai mode FIXED DISCOUNT dari hasil backend.
                activeVoucher = { type: 'fixed', val: parseFloat(d.potongan), min: 0 };
                
                updateUI();
            } else {
                msg.className = 'mt-2 small fw-bold text-danger';
                msg.innerText = "❌ " + d.msg;
                activeVoucher = null;
                updateUI();
            }
        });
    }

    function checkout(metode) {
        let nama = document.getElementById('namaPelanggan').value;
        if(!nama) return Swal.fire('Info', 'Nama pemesan wajib diisi', 'warning');
        
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        let diskon = 0;
        if(activeVoucher) diskon = activeVoucher.val; // Sudah fixed dari backend
        
        let payload = {
            items: cart,
            nama_pelanggan: nama,
            total_harga: sub - diskon,
            diskon: diskon,
            kode_voucher: document.getElementById('inputVoucher').value,
            metode: metode
        };

        Swal.fire({title:'Memproses...', allowOutsideClick:false, didOpen:()=>Swal.showLoading()});

        fetch('proses_checkout.php', {
            method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload)
        })
        .then(r=>r.json())
        .then(d => {
            if(d.status === 'success') {
                if(metode === 'midtrans' && d.snap_token) {
                    window.snap.pay(d.snap_token, {
                        onSuccess: () => finish(d.uuid),
                        onPending: () => finish(d.uuid),
                        onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error')
                    });
                } else {
                    finish(d.uuid);
                }
            } else {
                Swal.fire('Gagal', d.message, 'error');
            }
        });
    }

    function finish(uuid) {
        Swal.fire({icon:'success', title:'Pesanan Berhasil!', timer:2000, showConfirmButton:false})
        .then(() => {
            // [FIX URL REDIRECT] Pastikan parameter UUID benar
            <?php if($is_logged_in): ?>
                window.location.href = '../pelanggan/riwayat.php';
            <?php else: ?>
                window.location.href = 'sukses.php?uuid=' + uuid; 
            <?php endif; ?>
        });
    }

    <?php if (isset($_SESSION['force_reset_cart'])): ?>
    document.addEventListener('DOMContentLoaded', function() { cart = []; updateUI(); });
    <?php unset($_SESSION['force_reset_cart']); endif; ?>
    </script>

</body>
</html>