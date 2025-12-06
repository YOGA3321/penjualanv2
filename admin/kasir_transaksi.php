<?php
session_start();
// Cek sesi login & meja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Kasir: " . $_SESSION['kasir_nama_pelanggan'];
$cabang_id = $_SESSION['kasir_cabang_id'];

// Ambil data menu aktif
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND stok > 0 AND is_active = 1");

include '../layouts/admin/header.php';
?>

<style>
    /* DESKTOP: Layout Aplikasi (Pas Layar) */
    @media (min-width: 992px) {
        body { overflow: hidden; } /* Hilangkan scroll window utama */
        .kasir-container {
            height: calc(100vh - 80px); /* Full height minus navbar */
            padding-bottom: 0 !important;
        }
        .col-height-full { height: 100%; }
    }

    /* MOBILE: Scroll Biasa */
    @media (max-width: 991px) {
        .kasir-container { height: auto; margin-bottom: 80px; }
        .col-height-full { height: 600px; }
    }

    /* AREA SCROLL (Tengah) */
    .scroll-area {
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1; 
        scrollbar-width: thin;
        min-height: 0;
    }

    /* Custom Scrollbar */
    .scroll-area::-webkit-scrollbar { width: 6px; }
    .scroll-area::-webkit-scrollbar-track { background: #f1f1f1; }
    .scroll-area::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .scroll-area::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

    /* CARD STYLE */
    .card-kasir {
        border: none;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        height: 100%;
        display: flex;
        flex-direction: column;
        border-radius: 1rem;
        overflow: hidden;
    }

    /* ITEM MENU */
    .menu-item-card { transition: all 0.2s; cursor: pointer; }
    .menu-item-card:hover .card { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important; border-color: var(--bs-primary) !important; }
    .stok-habis { filter: grayscale(100%); opacity: 0.6; cursor: not-allowed; }
    
    .animate-pulse { animation: pulse 2s infinite; }
    @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.7; transform: scale(1.05); } 100% { opacity: 1; transform: scale(1); } }
</style>

<div class="row g-3 kasir-container">
    
    <div class="col-lg-8 col-height-full">
        <div class="card card-kasir bg-light">
            <div class="card-header bg-white py-3 border-bottom shadow-sm z-1">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-transparent border-end-0 text-muted ps-3"><i class="fas fa-search"></i></span>
                    <input type="text" id="cariMenu" class="form-control border-start-0 fs-6" placeholder="Cari menu makanan atau minuman...">
                </div>
            </div>
            
            <div class="card-body p-3 scroll-area bg-light">
                <div class="row g-3" id="menuContainerGrid">
                    <?php while($m = $menus->fetch_assoc()): ?>
                        <?php 
                            // LOGIKA DISKON MAKANAN (ITEM PROMO)
                            // Jika is_promo = 1, maka harga otomatis berubah jadi harga_promo
                            $is_promo = ($m['is_promo'] == 1 && $m['harga_promo'] > 0);
                            $harga_final = $is_promo ? $m['harga_promo'] : $m['harga'];
                            
                            $data_js = [
                                'id' => $m['id'],
                                'nama_menu' => $m['nama_menu'],
                                'harga' => $harga_final, // Masuk keranjang sudah harga diskon
                                'harga_asli' => $m['harga'],
                                'stok' => $m['stok'],
                                'is_promo' => $is_promo
                            ];
                            $json_menu = htmlspecialchars(json_encode($data_js), ENT_QUOTES, 'UTF-8');
                            $habis = $m['stok'] <= 0;
                            $onclick = $habis ? '' : "onclick='addToCart($json_menu)'";
                        ?>
                        <div class="col-6 col-md-4 col-xl-3 menu-item-card" data-name="<?= strtolower($m['nama_menu']) ?>">
                            <div class="card h-100 border-0 shadow-sm position-relative <?= $habis?'stok-habis':'' ?>" <?= $onclick ?>>
                                <div class="ratio ratio-4x3 bg-light">
                                    <?php if($m['gambar']): ?>
                                        <img src="../<?= $m['gambar'] ?>" class="w-100 h-100 object-fit-cover" alt="Menu">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center text-muted"><i class="fas fa-utensils fa-2x"></i></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="position-absolute top-0 end-0 p-2 d-flex flex-column align-items-end gap-1">
                                    <?php if($habis): ?>
                                        <span class="badge bg-danger shadow-sm">HABIS</span>
                                    <?php else: ?>
                                        <span class="badge bg-white text-dark shadow-sm border" style="font-size:0.6rem;">Stok: <?= $m['stok'] ?></span>
                                        <?php if($is_promo): ?>
                                            <span class="badge bg-danger shadow-sm animate-pulse">PROMO</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body p-2 text-center d-flex flex-column justify-content-center">
                                    <h6 class="card-title text-dark fw-bold text-truncate small mb-1"><?= $m['nama_menu'] ?></h6>
                                    
                                    <?php if($is_promo): ?>
                                        <div class="lh-1">
                                            <small class="text-decoration-line-through text-muted" style="font-size: 0.65rem;">Rp <?= number_format($m['harga'],0,',','.') ?></small>
                                            <div class="text-danger fw-bold small">Rp <?= number_format($m['harga_promo'],0,',','.') ?></div>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-primary fw-bold small">Rp <?= number_format($m['harga'],0,',','.') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-height-full">
        <div class="card card-kasir border-0 shadow-lg">
            
            <div class="card-header bg-dark text-white py-3 shadow-sm z-1">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width:38px; height:38px; font-size:1.1rem;">
                            <?= $_SESSION['kasir_no_meja'] ?>
                        </div>
                        <div class="lh-sm overflow-hidden">
                            <div class="small text-white-50" style="font-size:0.7rem;">PELANGGAN</div>
                            <div class="fw-bold text-truncate" style="max-width: 140px;"><?= htmlspecialchars($_SESSION['kasir_nama_pelanggan']) ?></div>
                        </div>
                    </div>
                    <div class="text-end lh-sm">
                        <div class="fw-bold text-warning small"><i class="fas fa-utensils me-1"></i>DINE IN</div>
                        <small class="text-white-50" style="font-size:0.7rem;"><?= date('d M Y') ?></small>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 scroll-area bg-white position-relative">
                <div id="cartList" class="p-3">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted mt-5 py-5">
                        <div class="bg-light rounded-circle p-4 mb-3">
                            <i class="fas fa-basket-shopping fa-3x text-secondary opacity-50"></i>
                        </div>
                        <h6 class="fw-bold text-secondary">Keranjang Kosong</h6>
                        <p class="small text-center mb-0 px-4">Pilih menu di sebelah kiri untuk menambahkan pesanan.</p>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-top p-3 shadow-lg z-2 position-relative">
                
                <div class="mb-3">
                    <label class="small fw-bold text-muted ps-1 mb-1">Punya Kode Voucher?</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-primary"><i class="fas fa-ticket-alt"></i></span>
                        <input type="text" id="inputVoucher" class="form-control" placeholder="Masukan kode disini...">
                        <button class="btn btn-outline-primary fw-bold" type="button" onclick="cekVoucher()">PAKAI</button>
                    </div>
                    <div id="voucherInfo" class="mt-1" style="display:none; font-size: 0.75rem;"></div>
                </div>

                <div class="bg-light rounded p-2 mb-3 border border-light-subtle">
                    <div class="d-flex justify-content-between small mb-1 text-secondary">
                        <span>Subtotal</span>
                        <span class="fw-bold text-dark" id="subtotalDisplay">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between small mb-1 text-danger" id="rowDiskon" style="display:none;">
                        <span><i class="fas fa-tag me-1"></i>Potongan Voucher</span>
                        <span id="diskonDisplay">- Rp 0</span>
                    </div>
                    <div class="border-top my-1 border-secondary-subtle"></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Total Tagihan</span>
                        <span class="fw-bold fs-4 text-primary" id="totalDisplay">Rp 0</span>
                    </div>
                </div>

                <div class="d-grid gap-2 d-flex">
                    <button class="btn btn-success fw-bold flex-fill py-2 shadow-sm" onclick="prosesBayar('tunai')">
                        <div class="d-flex flex-column align-items-center lh-1">
                            <span class="small mb-1"><i class="fas fa-money-bill-wave"></i></span>
                            <span>TUNAI</span>
                        </div>
                    </button>
                    <button class="btn btn-primary fw-bold flex-fill py-2 shadow-sm" onclick="prosesBayar('midtrans')">
                        <div class="d-flex flex-column align-items-center lh-1">
                            <span class="small mb-1"><i class="fas fa-qrcode"></i></span>
                            <span>QRIS / E-WALLET</span>
                        </div>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
<script>
let cart = [];
let activeVoucher = null; 
const formatRp = (num) => 'Rp ' + num.toLocaleString('id-ID');

// Filter Menu
document.getElementById('cariMenu').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.menu-item-card').forEach(item => {
        item.style.display = item.getAttribute('data-name').includes(filter) ? 'block' : 'none';
    });
});

function addToCart(menu) {
    let item = cart.find(i => i.id == menu.id);
    if(item) {
        if(item.qty < menu.stok) item.qty++;
        else Swal.fire({toast:true, position:'top-end', icon:'warning', title:'Stok maksimal', showConfirmButton:false, timer:1500});
    } else {
        cart.push({
            id: menu.id,
            nama_menu: menu.nama_menu,
            harga: parseInt(menu.harga), // Ini sudah harga PROMO jika ada
            harga_asli: parseInt(menu.harga_asli),
            is_promo: menu.is_promo,
            qty: 1,
            stok: menu.stok
        });
    }
    hitungTotal();
}

function updateQty(index, change) {
    if(change === -1 && cart[index].qty === 1) {
        cart.splice(index, 1);
    } else {
        let newQty = cart[index].qty + change;
        if(newQty <= cart[index].stok) cart[index].qty = newQty;
        else Swal.fire({toast:true, position:'top-end', icon:'warning', title:'Stok tidak cukup', showConfirmButton:false, timer:1500});
    }
    hitungTotal();
}

function hitungTotal() {
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    // LOGIKA VOUCHER (Diskon Total)
    let discVoucher = 0;
    if (activeVoucher) {
        if (subtotal < activeVoucher.min_belanja) {
            activeVoucher = null;
            document.getElementById('inputVoucher').value = '';
            Swal.fire({toast:true, icon:'info', title:'Voucher dilepas (Min. belanja kurang)'});
        } else {
            discVoucher = (activeVoucher.tipe === 'fixed') 
                ? parseFloat(activeVoucher.nilai) 
                : subtotal * (parseFloat(activeVoucher.nilai) / 100);
        }
    }

    // Pastikan diskon tidak minus
    if (discVoucher > subtotal) discVoucher = subtotal;
    let totalAkhir = subtotal - discVoucher;

    // RENDER CART LIST
    let html = '';
    if (cart.length === 0) {
        html = `
        <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted mt-5 py-5">
            <div class="bg-light rounded-circle p-4 mb-3">
                <i class="fas fa-basket-shopping fa-3x text-secondary opacity-50"></i>
            </div>
            <h6 class="fw-bold text-secondary">Keranjang Kosong</h6>
            <p class="small text-center mb-0 px-4">Pilih menu di sebelah kiri.</p>
        </div>`;
    } else {
        cart.forEach((item, index) => {
            // Tampilan Harga di List
            let priceDisplay = item.is_promo 
                ? `<small class="text-decoration-line-through text-muted" style="font-size:0.6rem">${formatRp(item.harga_asli)}</small> <span class="text-danger fw-bold">${formatRp(item.harga)}</span>`
                : `<span class="fw-bold text-dark">${formatRp(item.harga)}</span>`;

            html += `
            <div class="d-flex align-items-center justify-content-between p-2 mb-2 bg-light rounded border-start border-4 border-primary shadow-sm animate__animated animate__fadeIn">
                <div class="overflow-hidden pe-2" style="flex:1;">
                    <div class="fw-bold text-dark text-truncate" style="font-size:0.9rem;">${item.nama_menu}</div>
                    <div class="small lh-1">${priceDisplay}</div>
                </div>
                
                <div class="d-flex align-items-center bg-white rounded border px-1 shadow-sm">
                    <button class="btn btn-sm text-danger p-1" onclick="updateQty(${index}, -1)"><i class="fas fa-minus"></i></button>
                    <span class="fw-bold mx-2 text-center" style="min-width:20px;">${item.qty}</span>
                    <button class="btn btn-sm text-success p-1" onclick="updateQty(${index}, 1)"><i class="fas fa-plus"></i></button>
                </div>
                
                <div class="text-end ms-2 fw-bold small" style="min-width:65px;">
                    ${formatRp(item.harga * item.qty)}
                </div>
            </div>`;
        });
    }
    document.getElementById('cartList').innerHTML = html;

    // UPDATE ANGKA RINGKASAN
    document.getElementById('subtotalDisplay').innerText = formatRp(subtotal);
    document.getElementById('totalDisplay').innerText = formatRp(totalAkhir);

    // TAMPILKAN DISKON JIKA ADA VOUCHER
    let elDiskon = document.getElementById('rowDiskon');
    if (discVoucher > 0) {
        elDiskon.style.display = 'flex';
        document.getElementById('diskonDisplay').innerText = '- ' + formatRp(discVoucher);
    } else {
        elDiskon.style.display = 'none';
    }

    // BADGE VOUCHER INFO
    let vDiv = document.getElementById('voucherInfo');
    if(activeVoucher) {
        vDiv.style.display = 'block';
        vDiv.innerHTML = `<span class="badge bg-success-subtle text-success border border-success w-100 d-flex justify-content-between"><span><i class="fas fa-ticket-alt me-1"></i> ${activeVoucher.kode}</span> <i class="fas fa-check"></i></span>`;
    } else {
        vDiv.style.display = 'none';
    }
}

function cekVoucher() {
    let kode = document.getElementById('inputVoucher').value;
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    if(!kode) return Swal.fire({toast:true, icon:'warning', title:'Kode kosong'});
    if(subtotal === 0) return Swal.fire({toast:true, icon:'warning', title:'Keranjang kosong'});

    Swal.showLoading();
    fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${subtotal}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.valid) {
            activeVoucher = {
                kode: data.kode,
                tipe: data.tipe,
                nilai: data.nilai_voucher, 
                min_belanja: data.min_belanja
            };
            hitungTotal();
            Swal.fire({toast:true, icon:'success', title:'Voucher Berhasil!'});
        } else {
            activeVoucher = null;
            hitungTotal();
            Swal.fire({icon:'error', title:'Gagal', text:data.msg});
        }
    })
    .catch(err => Swal.fire('Error', 'Koneksi API error', 'error'));
}

function prosesBayar(metode) {
    if(cart.length === 0) return Swal.fire('Perhatian', 'Keranjang pesanan masih kosong!', 'warning');

    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    // Kalkulasi HANYA Voucher (Diskon Manual Sudah Dibuang)
    let discVoucher = 0;
    if (activeVoucher) {
        discVoucher = (activeVoucher.tipe === 'fixed') 
            ? parseFloat(activeVoucher.nilai) 
            : subtotal * (parseFloat(activeVoucher.nilai)/100);
    }
    
    if(discVoucher > subtotal) discVoucher = subtotal;
    let totalAkhir = subtotal - discVoucher;

    if (metode === 'tunai') {
        Swal.fire({
            title: 'Pembayaran Tunai',
            html: `
                <div class="bg-light p-3 rounded mb-3 border">
                    <small class="text-uppercase fw-bold text-muted">Tagihan</small>
                    <h1 class="text-primary fw-bold my-0">${formatRp(totalAkhir)}</h1>
                    ${discVoucher > 0 ? '<div class="badge bg-success mt-1">Hemat Voucher '+formatRp(discVoucher)+'</div>' : ''}
                </div>
                <div class="form-floating mb-3">
                    <input type="number" id="cashInput" class="form-control fs-3 fw-bold" placeholder="0">
                    <label>Uang Diterima (Rp)</label>
                </div>
                <div class="d-flex justify-content-between align-items-center p-2 bg-white border rounded">
                    <span class="fw-bold">Kembalian:</span>
                    <span class="fw-bold fs-4 text-secondary" id="changeDisplay">Rp 0</span>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'BAYAR SEKARANG',
            confirmButtonColor: '#198754',
            cancelButtonText: 'Batal',
            didOpen: () => {
                const input = document.getElementById('cashInput');
                const display = document.getElementById('changeDisplay');
                const btn = Swal.getConfirmButton();
                btn.disabled = true;
                
                input.focus();
                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - totalAkhir;
                    if(kembali >= 0) {
                        display.innerText = formatRp(kembali);
                        display.className = 'fw-bold fs-4 text-success';
                        btn.disabled = false;
                    } else {
                        display.innerText = 'Kurang ' + formatRp(Math.abs(kembali));
                        display.className = 'fw-bold fs-5 text-danger';
                        btn.disabled = true;
                    }
                });
            },
            preConfirm: () => {
                let bayar = parseInt(document.getElementById('cashInput').value) || 0;
                return { uang: bayar, kembali: bayar - totalAkhir };
            }
        }).then((res) => {
            if(res.isConfirmed) {
                // Parameter diskon hanya ambil dari Voucher
                kirimData(metode, totalAkhir, discVoucher, res.value.uang, res.value.kembali);
            }
        });
    } else {
        kirimData(metode, totalAkhir, discVoucher, 0, 0);
    }
}

function kirimData(metode, total, diskon, uang, kembalian) {
    let payload = {
        meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
        nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
        items: cart,
        total_harga: total,
        metode: metode,
        uang_bayar: uang,
        kembalian: kembalian,
        kode_voucher: activeVoucher ? activeVoucher.kode : null,
        diskon: diskon // Diskon ini MURNI dari Voucher saja
    };

    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    fetch('api/proses_transaksi_kasir.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(metode === 'midtrans' && data.snap_token) {
                window.snap.pay(data.snap_token, {
                    onSuccess: () => resetForm("Pembayaran Sukses!"),
                    onPending: () => resetForm("Menunggu Pembayaran..."),
                    onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error'),
                    onClose: () => Swal.fire('Batal', 'Jendela pembayaran ditutup', 'info')
                });
            } else {
                resetForm("Transaksi Selesai!");
            }
        } else {
            Swal.fire('Gagal', data.message || 'Server Error', 'error');
        }
    })
    .catch(err => Swal.fire('Error', 'Koneksi terputus', 'error'));
}

function resetForm(msg) {
    Swal.fire({ icon: 'success', title: 'Berhasil', text: msg, timer: 2000, showConfirmButton: false }).then(() => {
        cart = [];
        activeVoucher = null;
        document.getElementById('inputVoucher').value = '';
        hitungTotal();
    });
}
</script>

<?php include '../layouts/admin/footer.php'; ?>