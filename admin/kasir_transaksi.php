<?php
session_start();
// Pastikan user adalah admin/karyawan dan sudah set sesi meja kasir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['kasir_meja_id'])) { 
    header("Location: order_manual.php"); exit; 
}
require_once '../auth/koneksi.php';

$page_title = "Kasir: " . $_SESSION['kasir_nama_pelanggan'];

// Ambil Menu Sesuai Cabang Meja Terpilih
$cabang_id = $_SESSION['kasir_cabang_id'];
// Query menampilkan menu cabang tersebut ATAU menu global (cabang_id NULL)
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
                    <div class="col-md-4 col-lg-3 menu-item-card" data-name="<?= strtolower($m['nama_menu']) ?>">
                        <div class="card h-100 border pointer" onclick='addToCart(<?= json_encode($m) ?>)'>
                            <?php if($m['gambar']): ?>
                                <img src="../<?= $m['gambar'] ?>" class="card-img-top" style="height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 100px;">
                                    <i class="fas fa-utensils text-muted"></i>
                                </div>
                            <?php endif; ?>
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
                <div id="cartList" class="flex-grow-1 overflow-auto mb-3" style="max-height: 40vh;">
                    <div class="text-center text-muted mt-5">
                        <i class="fas fa-shopping-basket fa-2x mb-2"></i><br>Belum ada item
                    </div>
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total:</span>
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

<script>
// --- LOGIKA JAVASCRIPT ---

let cart = [];

// Fitur Pencarian Menu
document.getElementById('cariMenu').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let items = document.querySelectorAll('.menu-item-card');
    
    items.forEach(item => {
        let name = item.getAttribute('data-name');
        if (name.includes(filter)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

function addToCart(menu) {
    // Cari apakah menu sudah ada di cart
    let item = cart.find(i => i.id == menu.id);
    if(item) {
        item.qty++;
    } else {
        // Masukkan menu baru dengan format yang konsisten
        cart.push({
            id: menu.id,
            nama_menu: menu.nama_menu,
            harga: parseInt(menu.harga),
            qty: 1
        });
    }
    renderCart();
}

function renderCart() {
    let html = '';
    let total = 0;
    
    if (cart.length === 0) {
        document.getElementById('cartList').innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-shopping-basket fa-2x mb-2"></i><br>Belum ada item</div>';
        document.getElementById('totalDisplay').innerText = 'Rp 0';
        return;
    }

    cart.forEach((item, index) => {
        let subtotal = item.harga * item.qty;
        total += subtotal;
        html += `
        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
            <div>
                <div class="fw-bold text-dark">${item.nama_menu}</div>
                <small class="text-muted">${item.qty} x Rp ${item.harga.toLocaleString('id-ID')}</small>
            </div>
            <div class="text-end">
                <div class="fw-bold">Rp ${subtotal.toLocaleString('id-ID')}</div>
                <button class="btn btn-xs btn-outline-danger rounded-circle px-2 py-0 mt-1" onclick="hapusItem(${index})"><i class="fas fa-minus"></i></button>
            </div>
        </div>`;
    });
    
    document.getElementById('cartList').innerHTML = html;
    document.getElementById('totalDisplay').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

function hapusItem(index) {
    if (cart[index].qty > 1) {
        cart[index].qty--;
    } else {
        cart.splice(index, 1);
    }
    renderCart();
}

function prosesBayar(metode) {
    if(cart.length === 0) { 
        Swal.fire('Error', 'Pesanan masih kosong!', 'error'); 
        return; 
    }
    
    let total = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);

    // --- POPUP PEMBAYARAN TUNAI ---
    if (metode === 'tunai') {
        Swal.fire({
            title: 'Pembayaran Tunai',
            html: `
                <div class="text-start mb-3 p-3 bg-light rounded">
                    <small>Total Tagihan</small>
                    <h3 class="text-primary fw-bold m-0">Rp ${total.toLocaleString('id-ID')}</h3>
                </div>
                <div class="form-group text-start">
                    <label class="fw-bold mb-1">Uang Diterima</label>
                    <input type="number" id="cashInput" class="form-control form-control-lg fw-bold" placeholder="0" min="0">
                    <div class="mt-2 fw-bold" id="changeDisplay">Kembalian: Rp 0</div>
                </div>
            `,
            confirmButtonText: '<i class="fas fa-print me-1"></i> Bayar & Cetak',
            confirmButtonColor: '#198754',
            showCancelButton: true,
            cancelButtonText: 'Batal',
            didOpen: () => {
                const input = document.getElementById('cashInput');
                const display = document.getElementById('changeDisplay');
                const confirmBtn = Swal.getConfirmButton();
                
                // Disable tombol confirm di awal
                confirmBtn.disabled = true;
                input.focus();
                
                input.addEventListener('input', () => {
                    // Cegah input minus manual
                    if(input.value < 0) input.value = 0;

                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - total;

                    if(kembali >= 0) {
                        display.innerHTML = 'Kembalian: <span class="text-success fs-5">Rp ' + kembali.toLocaleString('id-ID') + '</span>';
                        // Aktifkan tombol jika uang cukup
                        confirmBtn.disabled = false;
                    } else {
                        display.innerHTML = 'Kurang: <span class="text-danger fs-5">Rp ' + Math.abs(kembali).toLocaleString('id-ID') + '</span>';
                        // Matikan tombol jika uang kurang
                        confirmBtn.disabled = true;
                    }
                });
            },
            preConfirm: () => {
                let bayar = parseInt(document.getElementById('cashInput').value) || 0;
                return { uang_bayar: bayar, kembalian: bayar - total };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                kirimTransaksi(metode, total, result.value.uang_bayar, result.value.kembalian);
            }
        });
    } else {
        // --- PEMBAYARAN NON-TUNAI ---
        kirimTransaksi(metode, total, 0, 0);
    }
}

function kirimTransaksi(metode, total, uang_bayar, kembalian) {
    let payload = {
        meja_id: '<?= $_SESSION['kasir_meja_id'] ?>',
        nama_pelanggan: '<?= $_SESSION['kasir_nama_pelanggan'] ?>',
        total_harga: total,
        metode: metode,
        uang_bayar: uang_bayar,
        kembalian: kembalian,
        items: cart
    };

    Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });

    fetch('api/proses_transaksi_kasir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if(data.status === 'success') {
            
            // --- JIKA METODE MIDTRANS / QRIS ---
            if (metode === 'midtrans' && data.snap_token) {
                Swal.close();
                // Buka Popup Midtrans agar pelanggan bisa scan di layar kasir
                // Pastikan script Midtrans Snap sudah di-load di <head> (nanti kita tambahkan)
                window.snap.pay(data.snap_token, {
                    onSuccess: function(result){
                        // Jika sukses bayar -> Redirect ke Struk
                        window.location.href = '../penjualan/struk.php?uuid=' + data.uuid + '&print=true';
                    },
                    onPending: function(result){
                        Swal.fire('Pending', 'Menunggu pembayaran pelanggan...', 'info');
                    },
                    onError: function(result){
                        Swal.fire('Error', 'Pembayaran gagal!', 'error');
                    },
                    onClose: function(){
                        Swal.fire('Tutup', 'Jendela pembayaran ditutup. Cek status di Riwayat.', 'warning');
                    }
                });
            } 
            // --- JIKA METODE TUNAI ---
            else {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Kembalian: Rp ' + parseInt(kembalian).toLocaleString('id-ID'),
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = '../penjualan/struk.php?uuid=' + data.uuid + '&print=true'; 
                });
            }

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
<script type="text/javascript"
    src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST">
</script>
<?php include '../layouts/admin/footer.php'; ?>