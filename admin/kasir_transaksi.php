<?php
session_start();
// Cek sesi login & meja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Kasir"; 
$cabang_id = $_SESSION['kasir_cabang_id'];

// Ambil menu aktif & stok tersedia
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND stok > 0 AND is_active = 1");

include '../layouts/admin/header.php';
?>

<style>
    /* --- 1. LAYOUT FIXED (PAS LAYAR) --- */
    html, body {
        height: 100vh;
        overflow: hidden; /* Matikan scroll browser utama */
    }
    
    /* Container Utama mengisi sisa tinggi setelah Header Admin */
    .kasir-wrapper {
        height: calc(100vh - 80px); /* Sesuaikan 80px dengan tinggi navbar Anda */
        padding: 10px;
        overflow: hidden;
    }

    .col-h-100 {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* --- 2. CARD & SCROLL AREA --- */
    .card-kasir {
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        background: #fff;
    }

    .card-header-sm {
        padding: 10px 15px;
        background: #fff;
        border-bottom: 1px solid #e3e6f0;
        flex-shrink: 0;
    }

    .card-body-scroll {
        flex: 1; /* Isi sisa ruang */
        overflow-y: auto; /* Scroll terjadi di sini */
        overflow-x: hidden;
        padding: 10px;
        background: #f8f9fc;
    }

    .card-footer-sm {
        padding: 10px 15px;
        background: #fff;
        border-top: 1px solid #e3e6f0;
        flex-shrink: 0;
    }

    /* Custom Scrollbar Tipis */
    .card-body-scroll::-webkit-scrollbar { width: 5px; }
    .card-body-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
    .card-body-scroll::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .card-body-scroll::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

    /* --- 3. ITEM MENU COMPACT --- */
    .menu-item {
        background: #fff;
        border: 1px solid #e3e6f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        overflow: hidden;
        height: 100%;
        position: relative;
    }
    .menu-item:hover { transform: translateY(-3px); border-color: var(--bs-primary); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    
    .menu-img {
        height: 100px; /* Tinggi gambar dikecilkan */
        width: 100%;
        object-fit: cover;
    }
    .menu-details { padding: 8px; text-align: center; }
    .menu-name { font-size: 0.85rem; font-weight: 700; color: #444; line-height: 1.2; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .menu-price { font-size: 0.9rem; color: var(--bs-primary); font-weight: 700; }
    
    /* Promo Style */
    .price-coret { text-decoration: line-through; color: #aaa; font-size: 0.7rem; }
    .price-promo { color: #e74a3b; font-weight: 700; font-size: 0.9rem; }
    .badge-promo { position: absolute; top: 0; left: 0; background: #e74a3b; color: white; font-size: 0.6rem; padding: 2px 8px; border-bottom-right-radius: 8px; z-index: 2; }
    .badge-stok { position: absolute; top: 5px; right: 5px; background: rgba(255,255,255,0.9); color: #333; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; border: 1px solid #ddd; z-index: 2; font-weight: bold;}

    /* --- 4. CART ITEM COMPACT --- */
    .cart-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fff;
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #e3e6f0;
        margin-bottom: 6px;
    }
    .btn-xs { padding: 1px 6px; font-size: 0.8rem; border-radius: 4px; }
    .fs-7 { font-size: 0.85rem; }
</style>

<div class="row g-2 kasir-wrapper">
    
    <div class="col-lg-8 col-md-7 col-h-100">
        <div class="card-kasir">
            <div class="card-header-sm d-flex align-items-center gap-2">
                <i class="fas fa-search text-muted"></i>
                <input type="text" id="cariMenu" class="form-control form-control-sm border-0 bg-light" placeholder="Ketik nama menu..." autocomplete="off">
            </div>

            <div class="card-body-scroll">
                <div class="row g-2" id="menuContainerGrid">
                    <?php while($m = $menus->fetch_assoc()): ?>
                        <?php 
                            $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                            $harga_final = $is_promo ? $m['harga_promo'] : $m['harga'];
                            
                            $data_js = htmlspecialchars(json_encode([
                                'id' => $m['id'],
                                'nama_menu' => $m['nama_menu'],
                                'harga' => $harga_final,
                                'harga_asli' => $m['harga'],
                                'stok' => $m['stok'],
                                'is_promo' => $is_promo
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="col-6 col-md-4 col-xl-3 menu-item-wrapper" data-name="<?= strtolower($m['nama_menu']) ?>">
                            <div class="menu-item" onclick='addToCart(<?= $data_js ?>)'>
                                <div class="position-relative">
                                    <?php if($m['gambar']): ?>
                                        <img src="../<?= $m['gambar'] ?>" class="menu-img" loading="lazy">
                                    <?php else: ?>
                                        <div class="menu-img d-flex align-items-center justify-content-center bg-light text-muted"><i class="fas fa-utensils"></i></div>
                                    <?php endif; ?>
                                    
                                    <span class="badge-stok"><?= $m['stok'] ?></span>
                                    <?php if($is_promo): ?><span class="badge-promo">PROMO</span><?php endif; ?>
                                </div>
                                <div class="menu-details">
                                    <div class="menu-name"><?= $m['nama_menu'] ?></div>
                                    <?php if($is_promo): ?>
                                        <div class="price-coret">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                        <div class="price-promo">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                                    <?php else: ?>
                                        <div class="menu-price">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-5 col-h-100">
        <div class="card-kasir border-top-primary">
            <div class="card-header-sm bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-chair me-1"></i> <span class="fw-bold">Meja <?= $_SESSION['kasir_no_meja'] ?></span>
                </div>
                <div class="text-truncate fs-7" style="max-width: 150px;">
                    <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['kasir_nama_pelanggan']) ?>
                </div>
            </div>

            <div class="card-body-scroll bg-white">
                <div id="cartList">
                    <div class="text-center text-muted mt-5">
                        <i class="fas fa-basket-shopping fa-3x opacity-25 mb-2"></i>
                        <p class="small">Belum ada pesanan</p>
                    </div>
                </div>
            </div>

            <div class="card-footer-sm">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-white"><i class="fas fa-ticket-alt text-secondary"></i></span>
                    <input type="text" id="inputVoucher" class="form-control" placeholder="Kode Voucher...">
                    <button class="btn btn-outline-secondary" onclick="cekVoucher()">Cek</button>
                </div>
                <div id="voucherInfo" class="mb-2" style="display:none"></div>

                <div class="d-flex justify-content-between fs-7 text-muted mb-1">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay">Rp 0</span>
                </div>
                <div class="d-flex justify-content-between fs-7 text-danger mb-2" id="rowDiskon" style="display:none;">
                    <span>Potongan</span>
                    <span id="diskonDisplay">- Rp 0</span>
                </div>
                <div class="d-flex justify-content-between align-items-center border-top pt-2 mb-3">
                    <span class="fw-bold text-dark">TOTAL</span>
                    <span class="fw-bold fs-4 text-primary" id="totalDisplay">Rp 0</span>
                </div>

                <div class="row g-1">
                    <div class="col-6">
                        <button class="btn btn-success btn-sm w-100 fw-bold py-2" onclick="prosesBayar('tunai')">
                            <i class="fas fa-money-bill-wave"></i> TUNAI
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-primary btn-sm w-100 fw-bold py-2" onclick="prosesBayar('midtrans')">
                            <i class="fas fa-qrcode"></i> QRIS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];
let activeVoucher = null; 
const formatRp = (num) => 'Rp ' + num.toLocaleString('id-ID');

document.getElementById('cariMenu').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.menu-item-wrapper').forEach(item => {
        item.style.display = item.getAttribute('data-name').includes(filter) ? 'block' : 'none';
    });
});

function addToCart(menu) {
    let item = cart.find(i => i.id == menu.id);
    if(item) {
        if(item.qty < menu.stok) item.qty++;
        else return Swal.fire({toast:true, position:'top', icon:'warning', title:'Stok Habis', showConfirmButton:false, timer:1000});
    } else {
        cart.push({
            id: menu.id,
            nama_menu: menu.nama_menu,
            harga: parseInt(menu.harga), // Harga promo sudah dari PHP
            qty: 1,
            stok: menu.stok
        });
    }
    renderCart();
}

function updateQty(index, change) {
    if(change === -1 && cart[index].qty === 1) cart.splice(index, 1);
    else {
        let newQty = cart[index].qty + change;
        if(newQty <= cart[index].stok) cart[index].qty = newQty;
        else Swal.fire({toast:true, position:'top', icon:'warning', title:'Stok Maksimal', showConfirmButton:false, timer:1000});
    }
    renderCart();
}

function renderCart() {
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    // Hitung Voucher
    let discVoucher = 0;
    if (activeVoucher) {
        if (subtotal < activeVoucher.min_belanja) {
            activeVoucher = null;
            document.getElementById('inputVoucher').value = '';
            Swal.fire({toast:true, position:'top', icon:'info', title:'Voucher dilepas (Min. belanja kurang)'});
        } else {
            discVoucher = (activeVoucher.tipe === 'fixed') 
                ? parseFloat(activeVoucher.nilai) 
                : subtotal * (parseFloat(activeVoucher.nilai) / 100);
        }
    }
    if (discVoucher > subtotal) discVoucher = subtotal;
    let totalAkhir = subtotal - discVoucher;

    // HTML List
    let html = '';
    if (cart.length === 0) html = `<div class="text-center text-muted mt-5"><i class="fas fa-basket-shopping fa-2x opacity-25 mb-2"></i><p class="small">Kosong</p></div>`;
    else {
        cart.forEach((item, index) => {
            html += `
            <div class="cart-row">
                <div style="flex:1; overflow:hidden;">
                    <div class="fw-bold text-truncate fs-7">${item.nama_menu}</div>
                    <div class="fs-7 text-muted">${formatRp(item.harga)} x ${item.qty}</div>
                </div>
                <div class="d-flex align-items-center gap-1 mx-2">
                    <button class="btn btn-outline-danger btn-xs" onclick="updateQty(${index}, -1)"><i class="fas fa-minus"></i></button>
                    <span class="fw-bold fs-7 text-center" style="width:20px;">${item.qty}</span>
                    <button class="btn btn-outline-success btn-xs" onclick="updateQty(${index}, 1)"><i class="fas fa-plus"></i></button>
                </div>
                <div class="fw-bold fs-7 text-end" style="min-width:60px;">${formatRp(item.harga * item.qty)}</div>
            </div>`;
        });
    }
    document.getElementById('cartList').innerHTML = html;

    // Angka Footer
    document.getElementById('subtotalDisplay').innerText = formatRp(subtotal);
    document.getElementById('totalDisplay').innerText = formatRp(totalAkhir);
    
    let elDiskon = document.getElementById('rowDiskon');
    let elVoucherInfo = document.getElementById('voucherInfo');
    
    if (discVoucher > 0) {
        elDiskon.style.display = 'flex';
        document.getElementById('diskonDisplay').innerText = '- ' + formatRp(discVoucher);
        elVoucherInfo.style.display = 'block';
        elVoucherInfo.innerHTML = `<span class="badge bg-success-subtle text-success w-100 d-flex justify-content-between"><span><i class="fas fa-ticket-alt"></i> ${activeVoucher.kode}</span> <i class="fas fa-check"></i></span>`;
    } else {
        elDiskon.style.display = 'none';
        elVoucherInfo.style.display = 'none';
    }
}

function cekVoucher() {
    let kode = document.getElementById('inputVoucher').value;
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    if(!kode || subtotal === 0) return;

    Swal.showLoading();
    fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${subtotal}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.valid) {
            activeVoucher = { kode: data.kode, tipe: data.tipe, nilai: data.nilai_voucher, min_belanja: data.min_belanja };
            renderCart();
            Swal.fire({toast:true, position:'top', icon:'success', title:'Voucher OK', showConfirmButton:false, timer:1500});
        } else {
            activeVoucher = null;
            renderCart();
            Swal.fire({toast:true, position:'top', icon:'error', title:data.msg});
        }
    });
}

function prosesBayar(metode) {
    if(cart.length === 0) return Swal.fire({toast:true, position:'top', icon:'warning', title:'Keranjang Kosong'});

    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    let discVoucher = 0;
    if (activeVoucher) discVoucher = (activeVoucher.tipe === 'fixed') ? parseFloat(activeVoucher.nilai) : subtotal * (parseFloat(activeVoucher.nilai)/100);
    if(discVoucher > subtotal) discVoucher = subtotal;
    let totalAkhir = subtotal - discVoucher;

    if (metode === 'tunai') {
        // --- PERBAIKAN: GUNAKAN STANDARD INPUT GROUP AGAR TIDAK TERTUTUP LABEL ---
        Swal.fire({
            title: 'Pembayaran Tunai',
            html: `
                <div class="text-center mb-3">
                    <small class="text-muted">Total Tagihan</small>
                    <h2 class="text-primary fw-bold mb-0">${formatRp(totalAkhir)}</h2>
                </div>
                <div class="mb-3 text-start">
                    <label class="form-label fw-bold small">Uang Diterima</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="cashInput" class="form-control form-control-lg fw-bold" placeholder="0">
                    </div>
                </div>
                <div class="d-flex justify-content-between p-2 bg-light rounded border">
                    <span class="fw-bold small text-muted">KEMBALIAN</span>
                    <span class="fw-bold fs-5" id="changeDisplay">Rp 0</span>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'BAYAR',
            confirmButtonColor: '#198754',
            didOpen: () => {
                const input = document.getElementById('cashInput');
                const display = document.getElementById('changeDisplay');
                const btn = Swal.getConfirmButton();
                btn.disabled = true;
                setTimeout(() => input.focus(), 300);

                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - totalAkhir;
                    if(kembali >= 0) {
                        display.innerText = formatRp(kembali);
                        display.classList.add('text-success');
                        display.classList.remove('text-danger');
                        btn.disabled = false;
                    } else {
                        display.innerText = 'Kurang ' + formatRp(Math.abs(kembali));
                        display.classList.add('text-danger');
                        display.classList.remove('text-success');
                        btn.disabled = true;
                    }
                });
            },
            preConfirm: () => {
                return { uang: parseInt(document.getElementById('cashInput').value), kembali: parseInt(document.getElementById('cashInput').value) - totalAkhir };
            }
        }).then((res) => {
            if(res.isConfirmed) kirimData(metode, totalAkhir, discVoucher, res.value.uang, res.value.kembali);
        });
    } else {
        kirimData(metode, totalAkhir, discVoucher, 0, 0);
    }
}

function kirimData(metode, total, diskon, uang, kembalian) {
    Swal.fire({title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading()});
    
    let payload = {
        meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
        nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
        items: cart,
        total_harga: total,
        metode: metode,
        uang_bayar: uang,
        kembalian: kembalian,
        kode_voucher: activeVoucher ? activeVoucher.kode : null,
        diskon: diskon
    };

    fetch('api/proses_transaksi_kasir.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(metode === 'midtrans' && data.snap_token) {
                window.snap.pay(data.snap_token, {
                    onSuccess: () => resetAll("Pembayaran Sukses!"),
                    onPending: () => resetAll("Menunggu Pembayaran..."),
                    onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error')
                });
            } else {
                resetAll("Transaksi Selesai!");
            }
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    });
}

function resetAll(msg) {
    Swal.fire({
        icon: 'success', 
        title: 'Sukses', 
        text: msg, 
        timer: 1500, 
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'order_manual.php'; 
    });
}
</script>

<?php include '../layouts/admin/footer.php'; ?>