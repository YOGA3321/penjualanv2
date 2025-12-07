<?php
session_start();
// Pastikan sesi kasir aktif
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

// Ambil Data Sesi
$cabang_id = $_SESSION['kasir_cabang_id'];
$nama_pelanggan = $_SESSION['kasir_nama_pelanggan'];
$nomor_meja = $_SESSION['kasir_no_meja'];

// Ambil Menu Sesuai Cabang
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND is_active = 1 AND stok > 0 ORDER BY kategori_id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - Meja <?= $nomor_meja ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    
    <style>
        body { height: 100vh; overflow: hidden; background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .layout-container { display: flex; height: 100vh; width: 100%; }
        
        /* Area Menu (Kiri) */
        .menu-area { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .menu-header { padding: 15px 25px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 10; display: flex; justify-content: space-between; align-items: center; }
        .menu-scroll { flex: 1; overflow-y: auto; padding: 25px; }
        
        /* Area Keranjang (Kanan) */
        .cart-area { width: 420px; background: white; display: flex; flex-direction: column; border-left: 1px solid #e0e0e0; box-shadow: -5px 0 15px rgba(0,0,0,0.03); z-index: 20; }
        .cart-header { padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; }
        .cart-items { flex: 1; overflow-y: auto; padding: 15px; }
        .cart-footer { padding: 20px; background: #fff; border-top: 1px solid #e0e0e0; }
        
        /* Card Menu */
        .menu-card { cursor: pointer; transition: 0.2s; border: none; border-radius: 12px; overflow: hidden; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.06); height: 100%; }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); border: 1px solid #0d6efd; }
        .menu-img { height: 140px; object-fit: cover; width: 100%; }
        .badge-promo { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div class="layout-container">
    <div class="menu-area">
        <div class="menu-header">
            <div class="d-flex align-items-center">
                <a href="order_manual.php" class="btn btn-outline-secondary rounded-circle me-3" title="Kembali"><i class="fas fa-arrow-left"></i></a>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">Daftar Menu</h5>
                    <small class="text-muted">Pilih menu untuk ditambahkan</small>
                </div>
            </div>
            <div class="input-group" style="width: 300px;">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="searchMenu" class="form-control bg-light border-start-0" placeholder="Cari nama menu...">
            </div>
        </div>

        <div class="menu-scroll">
            <div class="row g-3" id="menuGrid">
                <?php while($m = $menus->fetch_assoc()): 
                    $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                    $harga_tampil = $is_promo ? $m['harga_promo'] : $m['harga'];
                    // Siapkan data JSON untuk JS
                    $data_js = htmlspecialchars(json_encode([
                        'id' => $m['id'], 'nama' => $m['nama_menu'], 
                        'harga' => (float)$harga_tampil, 'stok' => (int)$m['stok']
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-6 col-md-4 col-lg-3 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                    <div class="menu-card position-relative" onclick='addToCart(<?= $data_js ?>)'>
                        <?php if($is_promo): ?><div class="badge-promo">PROMO</div><?php endif; ?>
                        
                        <?php $img = $m['gambar'] ? '../'.$m['gambar'] : '../assets/images/menu1.jpg'; ?>
                        <img src="<?= $img ?>" class="menu-img" onerror="this.src='../assets/images/menu1.jpg'">
                        
                        <div class="p-3">
                            <h6 class="fw-bold mb-1 text-truncate" title="<?= $m['nama_menu'] ?>"><?= $m['nama_menu'] ?></h6>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div>
                                    <?php if($is_promo): ?>
                                        <div class="text-decoration-line-through text-muted small" style="font-size:0.7rem">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                        <div class="text-danger fw-bold">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                                    <?php else: ?>
                                        <div class="text-primary fw-bold">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                    <?php endif; ?>
                                </div>
                                <small class="badge bg-light text-dark border">Stok: <?= $m['stok'] ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="cart-area">
        <div class="cart-header">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h5 class="mb-0 fw-bold"><i class="fas fa-receipt me-2"></i>Pesanan Baru</h5>
                <span class="badge bg-primary">Meja <?= $nomor_meja ?></span>
            </div>
            <div class="text-muted small text-truncate"><i class="fas fa-user me-1"></i> <?= htmlspecialchars($nama_pelanggan) ?></div>
        </div>
        
        <div class="cart-items" id="cartList">
            <div class="text-center py-5 mt-5">
                <i class="fas fa-basket-shopping fa-3x mb-3 text-muted opacity-25"></i>
                <p class="text-muted">Keranjang masih kosong</p>
            </div>
        </div>

        <div class="cart-footer">
            <div class="input-group input-group-sm mb-2">
                <input type="text" id="voucherCode" class="form-control text-uppercase" placeholder="Kode Voucher">
                <button class="btn btn-outline-secondary" onclick="cekVoucher()">Gunakan</button>
            </div>
            <small id="voucherMsg" class="d-block mb-2 fw-bold" style="display:none"></small>

            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>Subtotal</span> <span id="subTotalDisplay">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-2 small text-success fw-bold" id="diskonRow" style="display:none">
                <span>Diskon</span> <span id="diskonDisplay">-Rp 0</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between mb-4">
                <span class="fw-bold h5 mb-0">Total Bayar</span>
                <span class="fw-bold h4 text-primary mb-0" id="totalDisplay">Rp 0</span>
            </div>
            
            <div class="row g-2">
                <div class="col-6">
                    <button class="btn btn-success w-100 py-2 fw-bold" onclick="prosesBayar('tunai')">
                        <i class="fas fa-money-bill-wave me-2"></i> TUNAI
                    </button>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary w-100 py-2 fw-bold" onclick="prosesBayar('midtrans')">
                        <i class="fas fa-qrcode me-2"></i> QRIS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // --- STATE MANAGEMENT ---
    let cart = [];
    let activeVoucher = null;

    // --- SEARCH FILTER ---
    document.getElementById('searchMenu').addEventListener('keyup', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.menu-item').forEach(el => {
            el.style.display = el.dataset.name.includes(val) ? 'block' : 'none';
        });
    });

    // --- CART LOGIC ---
    function addToCart(item) {
        item.harga = parseFloat(item.harga);
        let exist = cart.find(c => c.id === item.id);
        
        if(exist) {
            if(exist.qty < item.stok) {
                exist.qty++;
            } else {
                const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 1500});
                Toast.fire({icon: 'warning', title: 'Stok habis!'});
                return;
            }
        } else {
            cart.push({...item, qty: 1});
        }
        updateUI();
    }

    function updateQty(idx, delta) {
        if(delta === -1 && cart[idx].qty === 1) {
            cart.splice(idx, 1);
        } else {
            let n = cart[idx].qty + delta;
            if(n <= cart[idx].stok) cart[idx].qty = n;
            else Swal.fire('Stok Terbatas', 'Jumlah melebihi stok tersedia', 'warning');
        }
        updateUI();
    }

    function updateUI() {
        let html = '', sub = 0;
        
        if(cart.length === 0) {
            document.getElementById('cartList').innerHTML = `<div class="text-center py-5 mt-5"><i class="fas fa-basket-shopping fa-3x mb-3 text-muted opacity-25"></i><p class="text-muted">Keranjang kosong</p></div>`;
            document.getElementById('totalDisplay').innerText = 'Rp 0';
            document.getElementById('subTotalDisplay').innerText = 'Rp 0';
            return;
        }

        cart.forEach((c, i) => {
            sub += parseFloat(c.harga) * parseInt(c.qty);
            html += `
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2 animate-fade">
                <div style="flex:1">
                    <div class="fw-bold text-dark">${c.nama}</div>
                    <small class="text-muted">Rp ${parseInt(c.harga).toLocaleString()}</small>
                </div>
                <div class="d-flex align-items-center bg-light rounded px-2 py-1">
                    <button class="btn btn-sm btn-link text-danger p-0" onclick="updateQty(${i}, -1)"><i class="fas fa-minus-circle"></i></button>
                    <span class="fw-bold mx-2" style="width:20px; text-align:center">${c.qty}</span>
                    <button class="btn btn-sm btn-link text-success p-0" onclick="updateQty(${i}, 1)"><i class="fas fa-plus-circle"></i></button>
                </div>
                <div class="fw-bold ms-3">Rp ${(c.harga * c.qty).toLocaleString()}</div>
            </div>`;
        });

        // Hitung Diskon
        let diskon = 0;
        if(activeVoucher) {
            let val = parseFloat(activeVoucher.val) || 0;
            if(activeVoucher.type === 'fixed') diskon = val;
            else diskon = sub * (val / 100);
            
            // Validasi Min Belanja
            if(sub < (activeVoucher.min || 0)) {
                activeVoucher = null; diskon = 0;
                document.getElementById('voucherMsg').innerText = "Voucher otomatis dilepas (Min. Belanja)";
                document.getElementById('voucherMsg').className = "d-block mb-2 text-warning";
                document.getElementById('voucherCode').value = '';
            }
        }
        if(diskon > sub) diskon = sub;
        let total = sub - diskon;

        // Render Angka
        document.getElementById('cartList').innerHTML = html;
        document.getElementById('subTotalDisplay').innerText = 'Rp ' + sub.toLocaleString('id-ID');
        document.getElementById('diskonDisplay').innerText = '-Rp ' + diskon.toLocaleString('id-ID');
        document.getElementById('totalDisplay').innerText = 'Rp ' + total.toLocaleString('id-ID');
        
        let rowD = document.getElementById('diskonRow');
        rowD.style.display = (diskon > 0) ? 'flex' : 'none';
    }

    // --- VOUCHER LOGIC ---
    function cekVoucher() {
        let kode = document.getElementById('voucherCode').value;
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        
        if(!kode) return Swal.fire('Kode Kosong', 'Masukkan kode voucher', 'info');
        
        // Panggil API Cek Voucher (Pastikan path file ini benar)
        fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${sub}`)
        .then(r=>r.json())
        .then(d => {
            let msg = document.getElementById('voucherMsg');
            msg.style.display = 'block';
            if(d.valid) {
                activeVoucher = { 
                    code: d.kode, 
                    type: 'fixed', // Asumsi API mengembalikan nilai fix atau logic bisa disesuaikan
                    val: parseFloat(d.potongan) || 0, 
                    min: 0 
                };
                msg.className = "d-block mb-2 text-success";
                msg.innerHTML = `<i class="fas fa-check-circle"></i> Potongan Rp ${parseInt(d.potongan).toLocaleString()}`;
            } else {
                msg.className = "d-block mb-2 text-danger";
                msg.innerHTML = `<i class="fas fa-times-circle"></i> ${d.msg}`;
                activeVoucher = null;
            }
            updateUI();
        })
        .catch(err => Swal.fire('Error', 'Gagal cek voucher', 'error'));
    }

    // --- PEMBAYARAN LOGIC ---
    function prosesBayar(metode) {
        if(cart.length === 0) return Swal.fire('Keranjang Kosong', 'Silakan pilih menu terlebih dahulu!', 'warning');
        
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        let diskon = activeVoucher ? (parseFloat(activeVoucher.val) || 0) : 0;
        let total = sub - diskon;

        if(metode === 'tunai') {
            // Popup Input Tunai
            Swal.fire({
                title: 'Pembayaran Tunai',
                html: `
                    <div class="text-center mb-3">
                        <small class="text-muted">Total Tagihan</small>
                        <h2 class="text-primary fw-bold">Rp ${total.toLocaleString('id-ID')}</h2>
                    </div>
                    <input type="number" id="cashInput" class="form-control text-center fs-4 mb-2" placeholder="Input Uang" autofocus>
                    <div class="alert alert-secondary p-2 mb-0" id="infoKembalian">Kembalian: Rp 0</div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print"></i> Bayar & Cetak',
                confirmButtonColor: '#198754',
                didOpen: () => {
                    const input = document.getElementById('cashInput');
                    const info = document.getElementById('infoKembalian');
                    input.focus();
                    
                    input.addEventListener('input', () => {
                        let bayar = parseInt(input.value) || 0;
                        let kembali = bayar - total;
                        if(kembali >= 0) {
                            info.innerHTML = `Kembalian: <b>Rp ${kembali.toLocaleString('id-ID')}</b>`;
                            info.className = "alert alert-success p-2 mb-0";
                        } else {
                            info.innerHTML = `Kurang: <b>Rp ${Math.abs(kembali).toLocaleString('id-ID')}</b>`;
                            info.className = "alert alert-danger p-2 mb-0";
                        }
                    });
                },
                preConfirm: () => {
                    let val = parseInt(document.getElementById('cashInput').value);
                    if(!val || val < total) return Swal.showValidationMessage('Uang pembayaran kurang!');
                    return { value: val };
                }
            }).then((res) => {
                if(res.isConfirmed) {
                    let uang = res.value.value;
                    let kembali = uang - total;
                    kirimData(metode, total, uang, kembali, diskon);
                }
            });
        } else {
            // QRIS (Midtrans)
            kirimData(metode, total, 0, 0, diskon);
        }
    }

    function kirimData(metode, total, uang, kembali, diskon) {
        // Tampilkan Loading
        Swal.fire({
            title: 'Memproses Transaksi...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        let payload = {
            meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
            nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
            total_harga: total,
            diskon: diskon,
            kode_voucher: activeVoucher ? activeVoucher.code : null,
            metode: metode,
            uang_bayar: uang,
            kembalian: kembali,
            items: cart
        };

        fetch('api/proses_transaksi_kasir.php', {
            method: 'POST', 
            headers: {'Content-Type':'application/json'}, 
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(d => {
            if(d.status === 'success') {
                if(metode === 'midtrans' && d.snap_token) {
                    // Buka Popup Midtrans
                    window.snap.pay(d.snap_token, {
                        onSuccess: function(result){ 
                            // Jika sukses bayar QRIS, anggap lunas
                            selesaiTransaksi(d.uuid, 0, true); 
                        },
                        onPending: function(result){ 
                            Swal.fire('Menunggu Pembayaran', 'Silakan selesaikan pembayaran di QRIS.', 'info'); 
                        },
                        onError: function(result){ 
                            Swal.fire('Error', 'Pembayaran gagal/error', 'error'); 
                        },
                        onClose: function(){ 
                            Swal.fire('Batal', 'Popup ditutup sebelum pembayaran selesai.', 'warning'); 
                        }
                    });
                } else {
                    // Tunai Langsung Selesai
                    selesaiTransaksi(d.uuid, kembali, false);
                }
            } else {
                Swal.fire('Gagal', d.message, 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Server Error', 'Terjadi kesalahan koneksi', 'error');
        });
    }

    function selesaiTransaksi(uuid, kembali, is_qris) {
        let textKembali = is_qris ? "Lunas via QRIS" : `Kembalian: Rp ${kembali.toLocaleString('id-ID')}`;
        
        Swal.fire({
            icon: 'success',
            title: 'Transaksi Berhasil!',
            html: `<h3 class="text-primary mt-2">${textKembali}</h3><p class="text-muted small">Data telah tersimpan di sistem</p>`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Cetak Struk',
            cancelButtonText: 'Menu Utama',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d'
        }).then((res) => {
            // Buka Tab Struk
            if (res.isConfirmed) {
                window.open(`../penjualan/cetak_struk_pdf.php?uuid=${uuid}`, '_blank');
            }
            // Reset & Kembali
            resetKasir();
            window.location.href = 'order_manual.php';
        });
    }

    function resetKasir() {
        cart = []; activeVoucher = null; updateUI();
        document.getElementById('voucherCode').value = '';
    }
</script>

<style>
    /* Animasi kecil untuk item masuk */
    @keyframes fadeIn { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }
    .animate-fade { animation: fadeIn 0.3s ease-out; }
</style>

</body>
</html>