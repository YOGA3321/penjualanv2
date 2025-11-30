<?php
session_start();
require_once '../auth/koneksi.php';

// Cek Sesi Meja (Wajib scan QR dulu)
if (!isset($_SESSION['plg_meja_id'])) {
    header("Location: index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Modern Bites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="bg-light">

    <div class="bg-white shadow-sm p-3 sticky-top">
        <div class="d-flex align-items-center">
            <a href="index.php" class="text-dark me-3"><i class="fas fa-arrow-left fa-lg"></i></a>
            <h5 class="mb-0 fw-bold flex-grow-1">Keranjang Pesanan</h5>
            <span class="badge bg-primary">Meja <?= $_SESSION['plg_no_meja'] ?></span>
        </div>
    </div>

    <div class="container py-3 mb-5" style="padding-bottom: 100px;">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
                <div id="cartList" class="p-3">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary spinner-border-sm" role="status"></div> 
                        Memuat data...
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="checkoutFormCard" style="display:none;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-user-edit me-2"></i>Informasi Pemesan</h6>
                <div class="mb-3">
                    <label class="form-label small text-muted">Nama Pemesan (Wajib)</label>
                    <input type="text" id="namaPelanggan" class="form-control" placeholder="Contoh: Budi" required>
                </div>

                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-wallet me-2"></i>Metode Pembayaran</h6>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="metode" id="bayarTunai" value="tunai" checked>
                    <label class="btn btn-outline-primary" for="bayarTunai">
                        <i class="fas fa-money-bill-wave d-block mb-1 fs-5"></i> Tunai (Kasir)
                    </label>

                    <input type="radio" class="btn-check" name="metode" id="bayarMidtrans" value="midtrans">
                    <label class="btn btn-outline-primary" for="bayarMidtrans">
                        <i class="fas fa-qrcode d-block mb-1 fs-5"></i> QRIS / E-Wallet
                    </label>
                </div>
                
                <div class="alert alert-warning mt-3 small mb-0 d-flex align-items-start" id="infoTunai">
                    <i class="fas fa-info-circle me-2 mt-1"></i> 
                    <div>Silakan menuju kasir untuk melakukan pembayaran setelah klik "Buat Pesanan".</div>
                </div>
                <div class="alert alert-info mt-3 small mb-0 d-flex align-items-start d-none" id="infoMidtrans">
                    <i class="fas fa-info-circle me-2 mt-1"></i> 
                    <div>Anda akan diarahkan ke halaman pembayaran online (Gopay/OVO/ShopeePay).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed-bottom bg-white p-3 border-top shadow-lg">
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Total Pembayaran</span>
            <strong class="text-primary fs-5" id="grandTotal">Rp 0</strong>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow" onclick="processCheckout()">
            Buat Pesanan <i class="fas fa-paper-plane ms-2"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // --- LOGIKA JAVASCRIPT PELANGGAN ---

        // Ambil data dari LocalStorage (Key: cart_v2)
        let cart = JSON.parse(localStorage.getItem('cart_v2')) || [];
        const container = document.getElementById('cartList');
        const formCard = document.getElementById('checkoutFormCard');

        function renderCart() {
            // Cek jika kosong
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-shopping-cart fa-3x mb-3 text-secondary opacity-50"></i><br>
                        Keranjangmu masih kosong.<br>
                        <a href="index.php" class="btn btn-sm btn-outline-primary mt-3 rounded-pill">Pesan Dulu Yuk!</a>
                    </div>`;
                formCard.style.display = 'none';
                document.getElementById('grandTotal').innerText = 'Rp 0';
                return;
            }

            formCard.style.display = 'block';
            let html = '';
            let total = 0;

            cart.forEach((item, index) => {
                // [PENTING] Menangani perbedaan nama variabel agar tidak NaN
                // Dashboard.js menyimpan: name, price, quantity, image
                let qty = item.quantity ? parseInt(item.quantity) : 1;
                let price = item.price ? parseInt(item.price) : 0;
                
                let subtotal = price * qty;
                total += subtotal;
                
                // Gunakan gambar default jika tidak ada
                let imgUrl = item.image ? item.image : '../assets/images/no-image.jpg';

                html += `
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="${imgUrl}" class="rounded shadow-sm" width="60" height="60" style="object-fit:cover;">
                        <div class="ms-3">
                            <h6 class="mb-1 fw-bold text-dark">${item.name}</h6>
                            <div class="text-muted small">${qty} x Rp ${price.toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold mb-2 text-primary">Rp ${subtotal.toLocaleString('id-ID')}</div>
                        <button class="btn btn-sm btn-outline-danger rounded-circle" style="width:30px;height:30px;padding:0;" onclick="removeItem(${index})">
                            <i class="fas fa-trash-alt small"></i>
                        </button>
                    </div>
                </div>`;
            });

            container.innerHTML = html;
            document.getElementById('grandTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function removeItem(index) {
            cart.splice(index, 1);
            localStorage.setItem('cart_v2', JSON.stringify(cart)); // Update storage
            renderCart();
        }

        // Event Listener Ganti Metode Bayar
        document.querySelectorAll('input[name="metode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                if(e.target.value === 'tunai') {
                    document.getElementById('infoTunai').classList.remove('d-none');
                    document.getElementById('infoMidtrans').classList.add('d-none');
                } else {
                    document.getElementById('infoTunai').classList.add('d-none');
                    document.getElementById('infoMidtrans').classList.remove('d-none');
                }
            });
        });

        // PROSES CHECKOUT
        function processCheckout() {
            const nama = document.getElementById('namaPelanggan').value.trim();
            const metode = document.querySelector('input[name="metode"]:checked').value;
            
            if (cart.length === 0) return Swal.fire('Oops', 'Keranjang kosong', 'error');
            if (nama === '') {
                // Highlight input nama
                document.getElementById('namaPelanggan').focus();
                return Swal.fire('Nama Wajib Diisi', 'Mohon isi nama pemesan agar pesanan tidak tertukar.', 'warning');
            }

            Swal.fire({
                title: 'Konfirmasi Pesanan',
                text: "Apakah pesanan sudah sesuai?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Pesan Sekarang',
                confirmButtonColor: '#0d6efd'
            }).then((result) => {
                if (result.isConfirmed) {
                    kirimData(nama, metode);
                }
            });
        }

        function kirimData(nama, metode) {
            // Loading
            Swal.fire({ title: 'Memproses Pesanan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            // Hitung Total Ulang
            let total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            // Data Payload
            let payload = {
                nama_pelanggan: nama,
                metode: metode,
                total_harga: total,
                items: cart // Kirim array cart apa adanya
            };

            fetch('proses_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Hapus Keranjang setelah sukses
                    localStorage.removeItem('cart_v2');
                    
                    if (metode === 'midtrans' && data.snap_token) {
                        // Redirect ke Midtrans Snap Page
                        // Gunakan URL Sandbox untuk testing
                        window.location.href = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' + data.snap_token;
                    } else {
                        // Tunai -> Struk
                        Swal.fire({
                            icon: 'success',
                            title: 'Pesanan Masuk',
                            text: 'Mohon selesaikan pembayaran di Kasir.',
                            showConfirmButton: true,
                            confirmButtonText: 'Lanjut'
                        })
                        .then(() => {
                            // [CHANGE] Arahkan ke status.php, bukan struk.php
                            window.location.href = 'status.php?uuid=' + data.uuid;
                        });
                    }
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Terjadi masalah koneksi internet.', 'error');
            });
        }

        // Init pertama kali
        renderCart();
    </script>
    <script type="text/javascript"
    src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST">
</script>
</body>
</html>