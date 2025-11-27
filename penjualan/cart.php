<?php
session_start();
require_once '../auth/koneksi.php';

// Cek Sesi Meja
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

    <div class="container py-3 mb-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
                <div id="cartList" class="p-3">
                    <div class="text-center py-5 text-muted">Memuat keranjang...</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="checkoutFormCard" style="display:none;">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="fas fa-user-edit me-2"></i>Informasi Pemesan</h6>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Nama Pemesan</label>
                    <input type="text" id="namaPelanggan" class="form-control" placeholder="Contoh: Budi" required>
                </div>

                <h6 class="fw-bold mb-3 mt-4"><i class="fas fa-wallet me-2"></i>Metode Pembayaran</h6>
                
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="metode" id="bayarTunai" value="tunai" checked>
                    <label class="btn btn-outline-primary" for="bayarTunai">
                        <i class="fas fa-money-bill-wave d-block mb-1"></i> Tunai (Kasir)
                    </label>

                    <input type="radio" class="btn-check" name="metode" id="bayarMidtrans" value="midtrans">
                    <label class="btn btn-outline-primary" for="bayarMidtrans">
                        <i class="fas fa-qrcode d-block mb-1"></i> QRIS / E-Wallet
                    </label>
                </div>
                
                <div class="alert alert-info mt-3 small mb-0" id="infoTunai">
                    <i class="fas fa-info-circle me-1"></i> Mohon lakukan pembayaran di kasir setelah checkout.
                </div>
                <div class="alert alert-info mt-3 small mb-0 d-none" id="infoMidtrans">
                    <i class="fas fa-info-circle me-1"></i> Anda akan diarahkan ke halaman pembayaran online.
                </div>
            </div>
        </div>
    </div>

    <div class="fixed-bottom bg-white p-3 border-top shadow-lg">
        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Total Pembayaran</span>
            <strong class="text-primary fs-5" id="grandTotal">Rp 0</strong>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold" onclick="processCheckout()">
            Buat Pesanan
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Logika Tampilan Cart
        let cart = JSON.parse(localStorage.getItem('cart_v2')) || [];
        const container = document.getElementById('cartList');
        const formCard = document.getElementById('checkoutFormCard');

        function renderCart() {
            if (cart.length === 0) {
                container.innerHTML = '<div class="text-center py-5 text-muted">Keranjang kosong.<br><a href="index.php" class="btn btn-sm btn-outline-primary mt-2">Pesan Dulu</a></div>';
                formCard.style.display = 'none';
                document.getElementById('grandTotal').innerText = 'Rp 0';
                return;
            }

            formCard.style.display = 'block';
            let html = '';
            let total = 0;

            cart.forEach((item, index) => {
                let subtotal = item.price * item.qty;
                total += subtotal;
                html += `
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <img src="${item.image}" class="rounded" width="50" height="50" style="object-fit:cover;">
                        <div class="ms-3">
                            <h6 class="mb-0 fw-bold">${item.name}</h6>
                            <small class="text-muted">${item.qty} x Rp ${item.price.toLocaleString()}</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold mb-1">Rp ${subtotal.toLocaleString()}</div>
                        <button class="btn btn-xs btn-outline-danger rounded-circle" onclick="removeItem(${index})"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            });

            container.innerHTML = html;
            document.getElementById('grandTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function removeItem(index) {
            cart.splice(index, 1);
            localStorage.setItem('cart_v2', JSON.stringify(cart));
            renderCart();
        }

        // Logika Ganti Metode Bayar
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
            
            if (cart.length === 0) return Swal.fire('Error', 'Keranjang kosong', 'error');
            if (nama === '') return Swal.fire('Error', 'Mohon isi nama pemesan', 'warning');

            Swal.fire({
                title: 'Konfirmasi Pesanan',
                text: "Pastikan pesanan sudah sesuai.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Pesan'
            }).then((result) => {
                if (result.isConfirmed) {
                    kirimData(nama, metode);
                }
            });
        }

        function kirimData(nama, metode) {
            // Loading
            Swal.fire({ title: 'Memproses...', didOpen: () => Swal.showLoading() });

            // Hitung Total
            let total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

            // Data Payload
            let payload = {
                nama_pelanggan: nama,
                metode: metode,
                total_harga: total,
                items: cart
            };

            fetch('proses_checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Hapus Keranjang
                    localStorage.removeItem('cart_v2');
                    
                    if (metode === 'midtrans') {
                        // Redirect ke Midtrans Snap / Payment Page
                        window.location.href = 'payment.php?uuid=' + data.uuid;
                    } else {
                        // Tunai -> Struk (Menunggu Konfirmasi)
                        Swal.fire('Berhasil', 'Silakan menuju kasir untuk pembayaran.', 'success')
                        .then(() => {
                            window.location.href = 'struk.php?uuid=' + data.uuid;
                        });
                    }
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(err => Swal.fire('Error', 'Koneksi bermasalah', 'error'));
        }

        renderCart();
    </script>
</body>
</html>