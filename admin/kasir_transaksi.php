<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { header("Location: order_manual.php"); exit; }
require_once '../auth/koneksi.php';

// Ambil Data Cabang/Meja
$cabang_id = $_SESSION['kasir_cabang_id'];
$nama_pelanggan = $_SESSION['kasir_nama_pelanggan'];
$nomor_meja = $_SESSION['kasir_no_meja'];

// Query Menu (Sesuai Cabang)
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND is_active = 1 AND stok > 0");
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
        body { height: 100vh; overflow: hidden; background-color: #f0f2f5; }
        .layout-container { display: flex; height: 100vh; width: 100%; }
        .menu-area { flex: 1; display: flex; flex-direction: column; height: 100%; overflow: hidden; }
        .menu-header { padding: 15px; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.05); z-index: 10; flex-shrink: 0; }
        .menu-scroll { flex: 1; overflow-y: auto; padding: 20px; }
        .cart-area { width: 400px; background: white; display: flex; flex-direction: column; border-left: 1px solid #e0e0e0; box-shadow: -5px 0 15px rgba(0,0,0,0.05); flex-shrink: 0; }
        .cart-header { padding: 20px; background: #f8f9fa; border-bottom: 1px solid #e0e0e0; flex-shrink: 0; }
        .cart-items { flex: 1; overflow-y: auto; padding: 15px; }
        .cart-footer { padding: 20px; background: #fff; border-top: 1px solid #e0e0e0; flex-shrink: 0; }
        
        /* Menu Card Style */
        .menu-card { cursor: pointer; transition: 0.2s; border: none; border-radius: 15px; overflow: hidden; background: white; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 1px solid #0d6efd; }
        .menu-img { height: 120px; object-fit: cover; width: 100%; }
        .badge-promo { position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: bold; }
    </style>
</head>
<body>

<div class="layout-container">
    
    <div class="menu-area">
        <div class="menu-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <a href="order_manual.php" class="btn btn-light rounded-circle me-3 border"><i class="fas fa-arrow-left"></i></a>
                <h5 class="mb-0 fw-bold">Menu Pesanan</h5>
            </div>
            <div class="input-group w-50">
                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="searchMenu" class="form-control bg-light border-start-0" placeholder="Cari menu...">
            </div>
        </div>

        <div class="menu-scroll">
            <div class="row g-3" id="menuGrid">
                <?php while($m = $menus->fetch_assoc()): 
                    $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                    $harga_tampil = $is_promo ? $m['harga_promo'] : $m['harga'];
                    
                    $data_js = htmlspecialchars(json_encode([
                        'id' => $m['id'], 'nama' => $m['nama_menu'], 
                        'harga' => (float)$harga_tampil, 'stok' => (int)$m['stok']
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="col-6 col-md-4 col-lg-3 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                    <div class="menu-card h-100 position-relative" onclick='addToCart(<?= $data_js ?>)'>
                        <?php if($is_promo): ?><div class="badge-promo">PROMO</div><?php endif; ?>
                        <?php $img = $m['gambar'] ? '../'.$m['gambar'] : '../assets/images/menu1.jpg'; ?>
                        <img src="<?= $img ?>" class="menu-img">
                        <div class="p-3 text-center">
                            <h6 class="fw-bold mb-1 text-truncate"><?= $m['nama_menu'] ?></h6>
                            
                            <?php if($is_promo): ?>
                                <div class="text-decoration-line-through text-muted small">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                <div class="text-danger fw-bold">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                            <?php else: ?>
                                <div class="text-primary fw-bold">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                            <?php endif; ?>
                            
                            <small class="text-muted">Stok: <?= $m['stok'] ?></small>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="cart-area">
        <div class="cart-header">
            <h5 class="mb-0 fw-bold"><i class="fas fa-shopping-cart me-2"></i>Meja <?= $nomor_meja ?></h5>
            <small class="text-muted"><?= htmlspecialchars($nama_pelanggan) ?></small>
        </div>
        
        <div class="cart-items" id="cartList">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-basket-shopping fa-3x mb-3 opacity-25"></i>
                <p>Belum ada pesanan</p>
            </div>
        </div>

        <div class="cart-footer">
            <div class="input-group input-group-sm mb-3">
                <input type="text" id="voucherCode" class="form-control text-uppercase" placeholder="Kode Voucher">
                <button class="btn btn-outline-secondary" onclick="cekVoucher()">Cek</button>
            </div>
            <small id="voucherMsg" class="d-block mb-2 fw-bold" style="display:none"></small>

            <div class="d-flex justify-content-between mb-1 small text-muted">
                <span>Subtotal</span> <span id="subTotalDisplay">Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-3 small text-success fw-bold" id="diskonRow" style="display:none">
                <span>Diskon</span> <span id="diskonDisplay">-Rp 0</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold h5">Total</span>
                <span class="fw-bold h5 text-primary" id="totalDisplay">Rp 0</span>
            </div>
            <div class="d-grid gap-2">
                <button class="btn btn-success py-2 fw-bold" onclick="prosesBayar('tunai')">
                    <i class="fas fa-money-bill me-2"></i> Bayar Tunai
                </button>
                <button class="btn btn-primary py-2 fw-bold" onclick="prosesBayar('midtrans')">
                    <i class="fas fa-qrcode me-2"></i> QRIS
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let cart = [];
    let activeVoucher = null;

    document.getElementById('searchMenu').addEventListener('keyup', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('.menu-item').forEach(el => {
            el.style.display = el.dataset.name.includes(val) ? 'block' : 'none';
        });
    });

    function addToCart(item) {
        item.harga = parseFloat(item.harga);
        let exist = cart.find(c => c.id === item.id);
        if(exist) {
            if(exist.qty < item.stok) exist.qty++;
            else Swal.fire('Stok Habis', 'Maksimal stok tercapai', 'warning');
        } else {
            cart.push({...item, qty: 1});
        }
        updateUI();
    }

    function updateQty(idx, delta) {
        if(delta === -1 && cart[idx].qty === 1) cart.splice(idx, 1);
        else {
            let n = cart[idx].qty + delta;
            if(n <= cart[idx].stok) cart[idx].qty = n;
            else return Swal.fire('Stok Habis', 'Maksimal stok', 'warning');
        }
        updateUI();
    }

    function updateUI() {
        let html = '', sub = 0;
        if(cart.length === 0) {
            document.getElementById('cartList').innerHTML = `<div class="text-center py-5 text-muted"><p>Keranjang kosong</p></div>`;
            document.getElementById('totalDisplay').innerText = 'Rp 0';
            return;
        }

        cart.forEach((c, i) => {
            sub += parseFloat(c.harga) * parseInt(c.qty);
            html += `
            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                <div><div class="fw-bold">${c.nama}</div><small>${c.qty} x ${parseInt(c.harga).toLocaleString()}</small></div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-light text-danger" onclick="updateQty(${i}, -1)">-</button>
                    <span class="fw-bold">${c.qty}</span>
                    <button class="btn btn-sm btn-light text-success" onclick="updateQty(${i}, 1)">+</button>
                </div>
            </div>`;
        });

        // Hitung Diskon
        let diskon = 0;
        if(activeVoucher) {
            let val = parseFloat(activeVoucher.val) || 0;
            if(activeVoucher.type === 'fixed') diskon = val;
            else diskon = sub * (val / 100);
            
            if(sub < (activeVoucher.min || 0)) {
                activeVoucher = null; diskon = 0;
                document.getElementById('voucherMsg').innerText = "Voucher dilepas (Min. Belanja)";
                document.getElementById('voucherMsg').className = "d-block mb-2 text-warning";
                document.getElementById('voucherCode').value = '';
            }
        }
        if(diskon > sub) diskon = sub;
        let total = sub - diskon;

        // Anti NaN
        if(isNaN(sub)) sub = 0;
        if(isNaN(diskon)) diskon = 0;
        if(isNaN(total)) total = 0;

        document.getElementById('cartList').innerHTML = html;
        document.getElementById('subTotalDisplay').innerText = 'Rp ' + sub.toLocaleString('id-ID');
        document.getElementById('diskonDisplay').innerText = '-Rp ' + diskon.toLocaleString('id-ID');
        document.getElementById('totalDisplay').innerText = 'Rp ' + total.toLocaleString('id-ID');
        
        let rowD = document.getElementById('diskonRow');
        if(diskon > 0) rowD.style.display='flex'; else rowD.style.display='none';
    }

    function cekVoucher() {
        let kode = document.getElementById('voucherCode').value;
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        
        if(!kode) return Swal.fire('Kode Kosong', 'Masukkan kode dulu', 'warning');
        
        fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${sub}`)
        .then(r=>r.json())
        .then(d => {
            let msg = document.getElementById('voucherMsg');
            msg.style.display = 'block';
            if(d.valid) {
                activeVoucher = { 
                    code: d.kode, 
                    type: 'fixed', 
                    val: parseFloat(d.potongan) || 0, 
                    min: 0 
                };
                msg.className = "d-block mb-2 text-success";
                msg.innerText = "✅ Hemat Rp " + parseInt(d.potongan).toLocaleString();
            } else {
                msg.className = "d-block mb-2 text-danger";
                msg.innerText = "❌ " + d.msg;
                activeVoucher = null;
            }
            updateUI();
        })
        .catch(err => Swal.fire('Error', 'Gagal cek voucher (Path Error?)', 'error'));
    }

    function prosesBayar(metode) {
        if(cart.length === 0) return Swal.fire('Kosong', 'Pilih menu dulu!', 'warning');
        
        let sub = cart.reduce((a,b) => a + (b.harga * b.qty), 0);
        let diskon = activeVoucher ? activeVoucher.val : 0;
        let total = sub - diskon;

        if(metode === 'tunai') {
            Swal.fire({
                title: 'Bayar Tunai',
                html: `
                    <div class="mb-3">Tagihan: <strong class="text-primary">Rp ${total.toLocaleString('id-ID')}</strong></div>
                    <input type="number" id="cashInput" class="form-control text-center mb-3 fs-4" placeholder="Uang Diterima">
                    <div class="alert alert-light border" id="infoKembalian">Kembalian: Rp 0</div>
                `,
                confirmButtonText: 'Proses Bayar',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                didOpen: () => {
                    const input = document.getElementById('cashInput');
                    const info = document.getElementById('infoKembalian');
                    input.focus();
                    
                    input.addEventListener('input', () => {
                        let bayar = parseInt(input.value) || 0;
                        let kembali = bayar - total;
                        if(kembali >= 0) {
                            info.innerHTML = `Kembalian: <strong class="text-success">Rp ${kembali.toLocaleString('id-ID')}</strong>`;
                            info.className = "alert alert-success border";
                        } else {
                            info.innerHTML = `Kurang: <strong class="text-danger">Rp ${Math.abs(kembali).toLocaleString('id-ID')}</strong>`;
                            info.className = "alert alert-danger border";
                        }
                    });
                },
                preConfirm: () => {
                    let val = parseInt(document.getElementById('cashInput').value);
                    if(!val || val < total) return Swal.showValidationMessage('Uang kurang!');
                    return { value: val };
                }
            }).then((res) => {
                // Kirim kembalian (res.value - total) ke fungsi kirimData
                if(res.isConfirmed) kirimData(metode, total, res.value.value, res.value.value - total, diskon);
            });
        } else {
            kirimData(metode, total, 0, 0, diskon);
        }
    }

    function kirimData(metode, total, uang, kembali, diskon) {
        Swal.fire({title:'Memproses...', didOpen:()=>Swal.showLoading()});
        
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
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
        }).then(r=>r.json()).then(d=>{
            if(d.status === 'success') {
                if(metode === 'midtrans' && d.snap_token) {
                    window.snap.pay(d.snap_token, {
                        onSuccess: function(){ selesaiTransaksi(d.uuid, kembali); },
                        onPending: function(){ Swal.fire('Pending', 'Menunggu Customer Bayar', 'info'); },
                        onClose: function(){ Swal.fire('Tutup', 'Belum lunas', 'warning'); }
                    });
                } else {
                    selesaiTransaksi(d.uuid, kembali);
                }
            } else {
                Swal.fire('Gagal', d.message, 'error');
            }
        });
    }

    function selesaiTransaksi(uuid, kembali) {
        Swal.fire({
            icon: 'success',
            title: 'Transaksi Selesai!',
            // [FIX] Tampilkan Kembalian di Sini
            html: `<h3>Kembalian: Rp ${kembali.toLocaleString('id-ID')}</h3>`,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-print"></i> Cetak Struk',
            cancelButtonText: 'Transaksi Baru',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#198754'
        }).then((res) => {
            if (res.isConfirmed) {
                window.open(`../penjualan/cetak_struk_pdf.php?uuid=${uuid}`, '_blank');
                resetKasir();
                window.location.href = 'order_manual.php';
            } else {
                resetKasir();
                window.location.href = 'order_manual.php';
            }
        });
    }

    function resetKasir() {
        cart = []; activeVoucher = null; updateUI();
        document.getElementById('voucherCode').value = '';
    }
</script>

</body>
</html>