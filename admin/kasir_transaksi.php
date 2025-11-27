<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Kasir: " . $_SESSION['kasir_nama_pelanggan'];
// Ambil Menu Sesuai Cabang Meja Terpilih
$cabang_id = $_SESSION['kasir_cabang_id'];
$menus = $koneksi->query("SELECT * FROM menu WHERE (cabang_id = '$cabang_id' OR cabang_id IS NULL) AND stok > 0");

include '../layouts/admin/header.php';
?>

<div class="row h-100">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white">
                <input type="text" id="cariMenu" class="form-control" placeholder="Cari menu...">
            </div>
            <div class="card-body" style="overflow-y: auto; max-height: 70vh;">
                <div class="row g-3">
                    <?php while($m = $menus->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3 menu-item" data-name="<?= strtolower($m['nama_menu']) ?>">
                        <div class="card h-100 border pointer" onclick="addToCart(<?= htmlspecialchars(json_encode($m)) ?>)">
                            <?php if($m['gambar']): ?><img src="../<?= $m['gambar'] ?>" class="card-img-top" style="height: 100px; object-fit: cover;"><?php endif; ?>
                            <div class="card-body p-2 text-center">
                                <h6 class="mb-1 small fw-bold"><?= $m['nama_menu'] ?></h6>
                                <span class="text-primary small">Rp <?= number_format($m['harga']) ?></span>
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
                <h5 class="mb-0">Meja <?= $_SESSION['kasir_no_meja'] ?> - <?= $_SESSION['kasir_nama_pelanggan'] ?></h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div id="cartList" class="flex-grow-1 overflow-auto mb-3" style="max-height: 40vh;">
                    <div class="text-center text-muted mt-5">Belum ada item</div>
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total:</span>
                        <strong class="fs-4" id="totalDisplay">Rp 0</strong>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" onclick="prosesBayar('tunai')">Bayar Tunai</button>
                        <button class="btn btn-primary" onclick="prosesBayar('midtrans')">QRIS / Midtrans</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToCart(menu) {
    let item = cart.find(i => i.id === menu.id);
    if(item) {
        item.qty++;
    } else {
        cart.push({...menu, qty: 1});
    }
    renderCart();
}

function renderCart() {
    let html = '';
    let total = 0;
    cart.forEach((item, index) => {
        let subtotal = item.harga * item.qty;
        total += subtotal;
        html += `
        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
            <div>
                <div class="fw-bold">${item.nama_menu}</div>
                <small class="text-muted">${item.qty} x ${item.harga}</small>
            </div>
            <div class="text-end">
                <div>${subtotal}</div>
                <button class="btn btn-xs btn-danger" onclick="hapusItem(${index})">x</button>
            </div>
        </div>`;
    });
    document.getElementById('cartList').innerHTML = html;
    document.getElementById('totalDisplay').innerText = 'Rp ' + total.toLocaleString();
}

function hapusItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function prosesBayar(metode) {
    if(cart.length === 0) { 
        Swal.fire('Error', 'Pesanan masih kosong!', 'error'); 
        return; 
    }
    
    // Siapkan Data
    let total = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    
    let payload = {
        meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
        nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
        total_harga: total,
        metode: metode,
        items: cart
    };

    // Loading State
    Swal.fire({ title: 'Memproses...', didOpen: () => { Swal.showLoading() } });

    // Kirim ke API
    fetch('api/proses_transaksi_kasir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi telah disimpan.',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Redirect ke struk atau kembali ke order manual
                window.location.href = 'riwayat.php'; 
            });
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error');
    });
}
</script>

<?php include '../layouts/admin/footer.php'; ?>