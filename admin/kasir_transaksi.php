<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Kasir: " . $_SESSION['kasir_nama_pelanggan'];
$cabang_id = $_SESSION['kasir_cabang_id'];
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND stok > 0");

include '../layouts/admin/header.php';
?>

<div class="row h-100">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <input type="text" id="cariMenu" class="form-control" placeholder="Cari menu (Ketik nama menu)...">
            </div>
            <div class="card-body" style="overflow-y: auto; max-height: 70vh;">
                <div class="row g-3" id="menuContainerGrid">
                    <?php while($m = $menus->fetch_assoc()): ?>
                        <?php 
                            $habis = $m['stok'] <= 0;
                            $onclick = $habis ? '' : "onclick='addToCart(".json_encode($m).")'";
                        ?>
                        <div class="col-md-4 col-lg-3 menu-item-card" data-name="<?= strtolower($m['nama_menu']) ?>">
                            <div class="card h-100 border pointer position-relative <?= $habis?'stok-habis':'' ?>" <?= $onclick ?>>
                                <?php if($m['gambar']): ?>
                                    <img src="../<?= $m['gambar'] ?>" class="card-img-top" style="height: 100px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 100px;">
                                        <i class="fas fa-utensils text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="position-absolute top-0 end-0 badge <?= $habis?'bg-danger':'bg-warning text-dark' ?> m-1 shadow-sm" style="font-size: 0.6rem;">
                                    <?= $habis ? 'HABIS' : 'Stok: '.$m['stok'] ?>
                                </span>
                                <div class="card-body p-2 text-center">
                                    <h6 class="mb-1 small fw-bold text-truncate"><?= $m['nama_menu'] ?></h6>
                                    <span class="text-primary small fw-bold">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0 fs-6">
                    <i class="fas fa-chair me-2"></i>Meja <?= $_SESSION['kasir_no_meja'] ?> <br>
                    <small class="fw-normal"><?= htmlspecialchars($_SESSION['kasir_nama_pelanggan']) ?></small>
                </h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div id="cartList" class="flex-grow-1 overflow-auto mb-3" style="max-height: 35vh;">
                    <div class="text-center text-muted mt-5">
                        <i class="fas fa-shopping-basket fa-2x mb-2"></i><br>Belum ada item
                    </div>
                </div>

                <div class="input-group mb-2">
                    <input type="text" id="inputVoucher" class="form-control form-control-sm" placeholder="Kode Voucher">
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="cekVoucher()">Gunakan</button>
                </div>
                <div id="voucherInfo" class="mb-2 text-success small fw-bold" style="display:none;"></div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between small text-muted">
                        <span>Subtotal:</span>
                        <span id="subtotalDisplay">Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between small text-danger mb-2">
                        <span>Diskon:</span>
                        <span id="diskonDisplay">- Rp 0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Akhir:</span>
                        <strong class="fs-4 text-primary" id="totalDisplay">Rp 0</strong>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-success fw-bold py-2" onclick="prosesBayar('tunai')">
                            <i class="fas fa-money-bill-wave me-2"></i>Bayar Tunai
                        </button>
                        <button class="btn btn-primary fw-bold py-2" onclick="prosesBayar('midtrans')">
                            <i class="fas fa-qrcode me-2"></i>QRIS / E-Wallet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .stok-habis { filter: grayscale(100%); opacity: 0.6; cursor: not-allowed !important; }
    .pointer { cursor: pointer; transition: 0.2s; }
    .pointer:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
</style>

<script>
let cart = [];
let currentVoucher = null;
let currentDiskon = 0;

// Filter Menu
document.getElementById('cariMenu').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.menu-item-card').forEach(item => {
        item.style.display = item.getAttribute('data-name').includes(filter) ? 'block' : 'none';
    });
});

function addToCart(menu) {
    let item = cart.find(i => i.id == menu.id);
    if(item) item.qty++;
    else cart.push({ id: menu.id, nama_menu: menu.nama_menu, harga: parseInt(menu.harga), qty: 1 });
    hitungTotal();
}

function hapusItem(index) {
    if (cart[index].qty > 1) cart[index].qty--;
    else cart.splice(index, 1);
    hitungTotal();
}

function hitungTotal() {
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    // Reset voucher jika keranjang kosong
    if(subtotal === 0) { currentDiskon = 0; currentVoucher = null; }
    
    // Pastikan diskon tidak lebih besar dari subtotal
    if(currentDiskon > subtotal) currentDiskon = subtotal;
    
    let totalAkhir = subtotal - currentDiskon;

    // Render HTML Keranjang
    let html = '';
    cart.forEach((item, index) => {
        html += `
        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
            <div>
                <div class="fw-bold text-dark">${item.nama_menu}</div>
                <small class="text-muted">${item.qty} x Rp ${item.harga.toLocaleString('id-ID')}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold">Rp ${(item.harga * item.qty).toLocaleString('id-ID')}</div>
                <button class="btn btn-xs btn-outline-danger rounded-circle px-2 py-0 mt-1" onclick="hapusItem(${index})"><i class="fas fa-minus"></i></button>
            </div>
        </div>`;
    });
    
    document.getElementById('cartList').innerHTML = cart.length ? html : '<div class="text-center text-muted mt-5"><i class="fas fa-shopping-basket fa-2x mb-2"></i><br>Belum ada item</div>';
    
    // Update Angka
    document.getElementById('subtotalDisplay').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('diskonDisplay').innerText = '- Rp ' + currentDiskon.toLocaleString('id-ID');
    document.getElementById('totalDisplay').innerText = 'Rp ' + totalAkhir.toLocaleString('id-ID');
    
    // Update Info Voucher UI
    let voucherDiv = document.getElementById('voucherInfo');
    if(currentVoucher) {
        voucherDiv.style.display = 'block';
        voucherDiv.innerHTML = `<i class="fas fa-tag"></i> ${currentVoucher} terpasang (-Rp ${currentDiskon.toLocaleString('id-ID')})`;
    } else {
        voucherDiv.style.display = 'none';
    }
}

function cekVoucher() {
    let kode = document.getElementById('inputVoucher').value;
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    if(!kode) return Swal.fire('Error', 'Masukkan kode voucher', 'warning');
    if(subtotal === 0) return Swal.fire('Error', 'Belanja dulu sebelum pakai voucher', 'warning');

    Swal.showLoading();
    // Panggil API Voucher yang ada di folder penjualan
    fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${subtotal}`)
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if(data.valid) {
            currentVoucher = data.kode;
            currentDiskon = data.potongan;
            hitungTotal();
            Swal.fire('Berhasil', data.msg, 'success');
        } else {
            currentVoucher = null;
            currentDiskon = 0;
            hitungTotal();
            Swal.fire('Gagal', data.msg, 'error');
        }
    })
    .catch(err => Swal.fire('Error', 'Gagal mengecek voucher', 'error'));
}

function prosesBayar(metode) {
    let subtotal = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    let totalAkhir = subtotal - currentDiskon;

    if(cart.length === 0) return Swal.fire('Error', 'Pesanan kosong!', 'error');

    if (metode === 'tunai') {
        Swal.fire({
            title: 'Pembayaran Tunai',
            html: `
                <div class="text-start mb-3 p-3 bg-light rounded">
                    <small>Total Tagihan</small>
                    <h3 class="text-primary fw-bold m-0">Rp ${totalAkhir.toLocaleString('id-ID')}</h3>
                    ${currentDiskon > 0 ? '<small class="text-success">Termasuk Potongan Voucher</small>' : ''}
                </div>
                <div class="form-group text-start">
                    <label class="fw-bold mb-1">Uang Diterima</label>
                    <input type="number" id="cashInput" class="form-control form-control-lg fw-bold" placeholder="0">
                    <div class="mt-2 fw-bold" id="changeDisplay">Kembalian: Rp 0</div>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Bayar',
            didOpen: () => {
                const input = document.getElementById('cashInput');
                const display = document.getElementById('changeDisplay');
                const btn = Swal.getConfirmButton();
                btn.disabled = true;
                
                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - totalAkhir;
                    if(kembali >= 0) {
                        display.innerHTML = 'Kembalian: <span class="text-success fw-bold">Rp ' + kembali.toLocaleString('id-ID') + '</span>';
                        btn.disabled = false;
                    } else {
                        display.innerHTML = 'Kurang: <span class="text-danger fw-bold">Rp ' + Math.abs(kembali).toLocaleString('id-ID') + '</span>';
                        btn.disabled = true;
                    }
                });
            },
            preConfirm: () => {
                let bayar = parseInt(document.getElementById('cashInput').value) || 0;
                return { uang: bayar, kembali: bayar - totalAkhir };
            }
        }).then((res) => {
            if(res.isConfirmed) kirimData(metode, totalAkhir, res.value.uang, res.value.kembali);
        });
    } else {
        kirimData(metode, totalAkhir, 0, 0);
    }
}

function kirimData(metode, total, uang, kembalian) {
    let payload = {
        meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
        nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
        items: cart,
        total_harga: total,
        metode: metode,
        uang_bayar: uang,
        kembalian: kembalian,
        // Data Voucher
        kode_voucher: currentVoucher,
        diskon: currentDiskon
    };

    Swal.fire({ title: 'Memproses...', didOpen: () => Swal.showLoading() });

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
                    onSuccess: () => resetForm("Pembayaran Berhasil!"),
                    onPending: () => resetForm("Menunggu Pembayaran..."),
                    onError: () => Swal.fire('Gagal', 'Pembayaran gagal', 'error')
                });
            } else {
                resetForm("Transaksi Selesai!");
            }
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    })
    .catch(err => Swal.fire('Error', 'Koneksi error', 'error'));
}

function resetForm(msg) {
    Swal.fire('Sukses', msg, 'success').then(() => {
        cart = [];
        currentVoucher = null;
        currentDiskon = 0;
        document.getElementById('inputVoucher').value = '';
        hitungTotal();
    });
}
</script>
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
<?php include '../layouts/admin/footer.php'; ?>