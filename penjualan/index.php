<?php
session_start();
require_once '../auth/koneksi.php';

// 1. TANGKAP ID MEJA DARI URL (CONTOH: ?meja=1) ATAU DARI SESI
// Jika scan QR, biasanya ada parameter GET. Jika refresh, ambil dari SESSION.
if (isset($_GET['meja'])) {
    $meja_id = $_GET['meja'];
    // Validasi meja ada di DB
    $cek = $koneksi->query("SELECT * FROM meja WHERE id = '$meja_id'")->fetch_assoc();
    if (!$cek) die("Meja tidak valid!");
    
    // LOGIKA RE-ORDER / MEJA PENUH
    // Jika meja terisi, TAPI sesinya sama dengan sesi pengguna saat ini, BOLEH MASUK (Re-order)
    // Jika meja terisi DAN sesinya beda, TOLAK.
    if ($cek['status'] == 'terisi' && (!isset($_SESSION['plg_meja_id']) || $_SESSION['plg_meja_id'] != $meja_id)) {
        die("
            <div style='text-align:center; padding:50px; font-family:sans-serif;'>
                <h1>🚫 Meja Sedang Digunakan</h1>
                <p>Meja ini sedang ditempati pelanggan lain. Silakan hubungi pelayan jika ini kesalahan.</p>
                <a href='index.php?meja=$meja_id&force_reset=1' style='color:red; font-size:0.8rem;'>Debug: Reset Paksa (Dev Only)</a>
            </div>
        ");
    }

    // Simpan meja ke sesi
    $_SESSION['plg_meja_id'] = $meja_id;
    $_SESSION['plg_no_meja'] = $cek['nomor_meja'];
    $_SESSION['plg_cabang_id'] = $cek['cabang_id'];
} 

// Jika tidak ada data meja sama sekali
if (!isset($_SESSION['plg_meja_id'])) {
    die("Silakan Scan QR Code pada Meja.");
}

$meja_id = $_SESSION['plg_meja_id'];
$cabang_id = $_SESSION['plg_cabang_id'];
$is_logged_in = isset($_SESSION['user_id']);

// Simpan URL saat ini untuk redirect balik setelah login (Fitur Ide Kamu)
$_SESSION['redirect_after_login'] = "penjualan/index.php?meja=$meja_id";

// 2. AMBIL MENU (Sesuai Cabang & Aktif)
$q_menu = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND stok > 0 AND is_active = 1");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Menu Pemesanan - Meja <?= $_SESSION['plg_no_meja'] ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; padding-bottom: 80px; }
        
        /* Header Meja */
        .hero-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 20px 15px;
            border-radius: 0 0 25px 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        /* Card Menu */
        .menu-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .menu-card:active { transform: scale(0.98); }
        .menu-img-wrapper {
            position: relative;
            height: 140px;
            overflow: hidden;
        }
        .menu-img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Badges */
        .badge-promo {
            position: absolute; bottom: 0; left: 0; 
            background: #e11d48; color: white; 
            padding: 3px 10px; font-size: 0.7rem; font-weight: bold;
            border-top-right-radius: 10px;
        }

        /* Tombol Tambah (Bulat Floating) */
        .btn-add {
            width: 35px; height: 35px;
            border-radius: 50%;
            padding: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem;
            position: absolute; bottom: 10px; right: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        /* Floating Cart Bar */
        .floating-cart {
            position: fixed; bottom: 20px; left: 20px; right: 20px;
            background: #1e293b; color: white;
            padding: 15px; border-radius: 50px;
            box-shadow: 0 10px 25px rgba(30, 41, 59, 0.3);
            display: none; /* Hidden by default */
            z-index: 1000;
            align-items: center; justify-content: space-between;
            cursor: pointer;
            animation: slideUp 0.3s ease-out;
        }
        @keyframes slideUp { from { transform: translateY(100px); } to { transform: translateY(0); } }

        /* Modal Cart Custom */
        .cart-item-row { border-bottom: 1px solid #eee; padding: 10px 0; }
        .qty-control { background: #f1f5f9; border-radius: 20px; padding: 2px; display: inline-flex; align-items: center; }
        .qty-btn { width: 28px; height: 28px; border-radius: 50%; border: none; background: white; color: #333; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <div class="hero-header d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 opacity-75">Selamat Datang di</h6>
            <h4 class="fw-bold mb-0">Meja <?= $_SESSION['plg_no_meja'] ?></h4>
        </div>
        <div class="text-end">
            <?php if($is_logged_in): ?>
                <div class="badge bg-white text-primary rounded-pill px-3 py-2 shadow-sm">
                    <i class="fas fa-user-check me-1"></i> Member
                </div>
            <?php else: ?>
                <a href="../login.php" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    <i class="fas fa-sign-in-alt me-1"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <div class="position-relative mb-4">
            <input type="text" id="searchMenu" class="form-control form-control-lg border-0 shadow-sm ps-5 rounded-pill" placeholder="Cari makanan...">
            <i class="fas fa-search text-muted position-absolute top-50 start-0 translate-middle-y ms-3 fs-5"></i>
        </div>

        <h6 class="fw-bold text-secondary mb-3 ps-2">Daftar Menu</h6>
        <div class="row g-3 pb-5" id="menuContainer">
            <?php if($q_menu->num_rows > 0): ?>
                <?php while($m = $q_menu->fetch_assoc()): ?>
                    <?php 
                        // LOGIKA HARGA PROMO (Backend)
                        $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                        $harga_tampil = $is_promo ? $m['harga_promo'] : $m['harga'];
                        
                        // JSON Data untuk JS
                        $data_js = htmlspecialchars(json_encode([
                            'id' => $m['id'],
                            'nama' => $m['nama_menu'],
                            'harga' => $harga_tampil,
                            'gambar' => $m['gambar']
                        ]), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="col-6 col-md-4 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                        <div class="menu-card h-100 position-relative">
                            <div class="menu-img-wrapper" onclick='addToCart(<?= $data_js ?>)'>
                                <img src="../<?= $m['gambar'] ? $m['gambar'] : 'assets/images/no-image.png' ?>" class="menu-img" loading="lazy">
                                <?php if($is_promo): ?>
                                    <div class="badge-promo">PROMO</div>
                                <?php endif; ?>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1 text-truncate"><?= $m['nama_menu'] ?></h6>
                                
                                <div class="mt-auto d-flex justify-content-between align-items-end">
                                    <div class="lh-1">
                                        <?php if($is_promo): ?>
                                            <small class="text-decoration-line-through text-muted" style="font-size:0.7rem">Rp <?= number_format($m['harga'],0,',','.') ?></small>
                                            <div class="text-danger fw-bold">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                                        <?php else: ?>
                                            <div class="text-primary fw-bold">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn btn-primary btn-add" onclick='addToCart(<?= $data_js ?>)'>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center text-muted py-5">Belum ada menu tersedia.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="floating-cart" id="floatingCart" onclick="openCartModal()">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px;" id="totalQtyBadge">0</div>
            <div class="lh-1">
                <small class="opacity-75">Total Estimasi</small>
                <div class="fw-bold fs-5" id="totalPriceBadge">Rp 0</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="fw-bold">Lihat</span>
            <i class="fas fa-chevron-right"></i>
        </div>
    </div>

    <div class="modal fade" id="cartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Pesanan Anda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItemsContainer"></div>
                    
                    <div class="mt-4">
                        <label class="small fw-bold text-muted mb-1">Punya Voucher?</label>
                        <div class="input-group">
                            <input type="text" id="inputVoucher" class="form-control" placeholder="Kode...">
                            <button class="btn btn-outline-primary" onclick="cekVoucher()">Pakai</button>
                        </div>
                        <small id="voucherMsg" class="text-success fw-bold mt-1 d-block"></small>
                    </div>

                    <?php if(!$is_logged_in): ?>
                    <div class="mt-3">
                        <label class="small fw-bold text-muted mb-1">Nama Pemesan</label>
                        <input type="text" id="namaPelanggan" class="form-control" placeholder="Contoh: Budi">
                    </div>
                    <?php else: ?>
                        <input type="hidden" id="namaPelanggan" value="<?= $_SESSION['nama'] ?? 'Member' ?>">
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer border-0 pt-0 d-block">
                    <div class="d-flex justify-content-between mb-2 small text-muted">
                        <span>Subtotal</span>
                        <span id="modalSubtotal">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3 small text-danger" id="rowDiskon" style="display:none;">
                        <span>Diskon Voucher</span>
                        <span id="modalDiskon">- Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold fs-5">Total Bayar</span>
                        <span class="fw-bold fs-4 text-primary" id="modalTotal">Rp 0</span>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <button class="btn btn-success w-100 fw-bold py-2 rounded-pill" onclick="checkout('tunai')">
                                <i class="fas fa-money-bill me-1"></i> Tunai
                            </button>
                        </div>
                        <div class="col-6">
                            <button class="btn btn-primary w-100 fw-bold py-2 rounded-pill" onclick="checkout('midtrans')">
                                <i class="fas fa-qrcode me-1"></i> QRIS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    <script>
        let cart = [];
        let activeVoucher = null;
        const modal = new bootstrap.Modal(document.getElementById('cartModal'));

        // --- 1. PROMPT LOGIN UNTUK TAMU (Ide Kamu) ---
        <?php if(!$is_logged_in): ?>
        window.addEventListener('load', () => {
            // Cek apakah user pernah menutup prompt ini (sessionStorage) agar tidak annoying
            if(!sessionStorage.getItem('prompt_login_seen')) {
                Swal.fire({
                    title: 'Selamat Datang!',
                    text: 'Punya akun member? Login sekarang untuk dapat poin & simpan riwayat pesanan!',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '🚀 Login / Daftar',
                    cancelButtonText: 'Lanjut sebagai Tamu',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '../login.php'; // Redirect ke login
                    }
                    sessionStorage.setItem('prompt_login_seen', 'true');
                });
            }
        });
        <?php endif; ?>

        // Search
        document.getElementById('searchMenu').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('.menu-item').forEach(el => {
                el.style.display = el.dataset.name.includes(val) ? 'block' : 'none';
            });
        });

        // Add to Cart
        function addToCart(item) {
            let exist = cart.find(c => c.id === item.id);
            if(exist) exist.qty++;
            else cart.push({...item, qty: 1});
            
            updateUI();
            
            // Animasi kecil feedback
            const toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 1000,
                didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
            });
            toast.fire({icon: 'success', title: item.nama + ' ditambahkan'});
        }

        function changeQty(index, delta) {
            if(delta === -1 && cart[index].qty === 1) cart.splice(index, 1);
            else cart[index].qty += delta;
            updateUI();
        }

        function updateUI() {
            let totalQty = 0;
            let subtotal = 0;

            let html = '';
            if(cart.length === 0) {
                html = '<div class="text-center py-4 text-muted"><i class="fas fa-shopping-cart mb-2"></i><br>Keranjang kosong</div>';
                document.getElementById('floatingCart').style.display = 'none';
            } else {
                document.getElementById('floatingCart').style.display = 'flex';
                cart.forEach((item, i) => {
                    totalQty += item.qty;
                    subtotal += item.harga * item.qty;
                    html += `
                    <div class="cart-item-row d-flex justify-content-between align-items-center">
                        <div style="flex:1">
                            <div class="fw-bold text-dark text-truncate">${item.nama}</div>
                            <small class="text-muted">Rp ${item.harga.toLocaleString('id-ID')}</small>
                        </div>
                        <div class="qty-control">
                            <button class="qty-btn" onclick="changeQty(${i}, -1)">-</button>
                            <span class="mx-3 small fw-bold">${item.qty}</span>
                            <button class="qty-btn" onclick="changeQty(${i}, 1)">+</button>
                        </div>
                    </div>`;
                });
            }

            // Hitung Voucher
            let diskon = 0;
            if(activeVoucher) {
                if(subtotal < activeVoucher.min_belanja) {
                    activeVoucher = null;
                    document.getElementById('voucherMsg').innerText = 'Voucher dilepas (Min. belanja kurang)';
                    document.getElementById('inputVoucher').value = '';
                } else {
                    diskon = (activeVoucher.tipe === 'fixed') 
                        ? parseFloat(activeVoucher.nilai) 
                        : subtotal * (parseFloat(activeVoucher.nilai)/100);
                }
            }
            if(diskon > subtotal) diskon = subtotal;
            let totalBayar = subtotal - diskon;

            // Render
            document.getElementById('cartItemsContainer').innerHTML = html;
            document.getElementById('totalQtyBadge').innerText = totalQty;
            document.getElementById('totalPriceBadge').innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');
            
            document.getElementById('modalSubtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('modalTotal').innerText = 'Rp ' + totalBayar.toLocaleString('id-ID');
            
            if(diskon > 0) {
                document.getElementById('rowDiskon').style.display = 'flex';
                document.getElementById('modalDiskon').innerText = '- Rp ' + diskon.toLocaleString('id-ID');
            } else {
                document.getElementById('rowDiskon').style.display = 'none';
            }
        }

        function openCartModal() {
            modal.show();
        }

        function cekVoucher() {
            let kode = document.getElementById('inputVoucher').value;
            let subtotal = cart.reduce((a, b) => a + (b.harga * b.qty), 0);
            
            if(!kode) return;
            
            fetch(`api_voucher.php?kode=${kode}&total=${subtotal}`)
            .then(r => r.json())
            .then(d => {
                if(d.valid) {
                    activeVoucher = { kode:d.kode, tipe:d.tipe, nilai:d.nilai_voucher, min_belanja:d.min_belanja };
                    document.getElementById('voucherMsg').innerText = "✅ Voucher Aktif";
                    updateUI();
                } else {
                    activeVoucher = null;
                    document.getElementById('voucherMsg').innerText = "❌ " + d.msg;
                    updateUI();
                }
            });
        }

        function checkout(metode) {
            let nama = document.getElementById('namaPelanggan').value;
            if(!nama && !<?= $is_logged_in ? 'true' : 'false' ?>) return Swal.fire('Wajib', 'Masukkan nama Anda', 'warning');
            if(cart.length === 0) return;

            let subtotal = cart.reduce((a, b) => a + (b.harga * b.qty), 0);
            let diskon = 0;
            if(activeVoucher) diskon = (activeVoucher.tipe === 'fixed') ? parseFloat(activeVoucher.nilai) : subtotal * (parseFloat(activeVoucher.nilai)/100);
            
            let payload = {
                items: cart,
                nama_pelanggan: nama,
                total_harga: subtotal - diskon, // Total setelah diskon
                diskon: diskon,
                kode_voucher: activeVoucher ? activeVoucher.kode : null,
                metode: metode
            };

            Swal.fire({title:'Memproses...', didOpen:()=>Swal.showLoading()});

            fetch('proses_checkout.php', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(d => {
                if(d.status === 'success') {
                    if(metode === 'midtrans' && d.snap_token) {
                        window.snap.pay(d.snap_token, {
                            onSuccess: () => finishOrder(true, d.is_logged_in),
                            onPending: () => finishOrder(true, d.is_logged_in),
                            onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error')
                        });
                    } else {
                        finishOrder(false, d.is_logged_in);
                    }
                } else {
                    Swal.fire('Error', d.message, 'error');
                }
            });
        }

        function finishOrder(isMidtrans, isLoggedIn) {
            Swal.fire({
                icon: 'success', 
                title: 'Pesanan Diterima!', 
                text: 'Mohon tunggu konfirmasi pelayan.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                if(isLoggedIn) {
                    // Redirect ke Riwayat jika login
                    window.location.href = '../pelanggan/riwayat.php';
                } else {
                    // Redirect ke Halaman Struk / Sukses jika tamu
                    window.location.href = 'sukses.php'; 
                }
            });
        }
    </script>
</body>
</html>