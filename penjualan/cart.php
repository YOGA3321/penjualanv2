<?php
session_start();
require_once '../auth/koneksi.php';
// Cek sesi meja wajib ada
if (!isset($_SESSION['plg_meja_id'])) { header("Location: index.php"); exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
</head>
<body class="bg-light" style="padding-bottom: 160px;">

    <div class="bg-white shadow-sm p-3 sticky-top d-flex align-items-center">
        <a href="index.php" class="text-dark me-3"><i class="fas fa-arrow-left fa-lg"></i></a>
        <h5 class="mb-0 fw-bold flex-grow-1">Keranjang</h5>
        <span class="badge bg-primary">Meja <?= $_SESSION['plg_no_meja'] ?></span>
    </div>

    <div class="container py-3">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0" id="cartList"></div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="formCard" style="display:none;">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">Nama Pemesan</label>
                    <input type="text" id="namaPelanggan" class="form-control" value="<?= $_SESSION['nama'] ?? '' ?>" required>
                </div>
                
                <label class="form-label small text-muted fw-bold">Punya Kode Voucher?</label>
                <div class="input-group mb-2">
                    <input type="text" id="voucherCode" class="form-control text-uppercase" placeholder="Masukan Kode">
                    <button class="btn btn-dark" type="button" onclick="cekVoucher()">Pakai</button>
                </div>
                <small id="voucherMsg" class="d-block mb-2"></small>
                
                <input type="hidden" id="diskonVal" value="0">
                <input type="hidden" id="kodeVal" value="">

                <h6 class="fw-bold mb-2 mt-4">Metode Pembayaran</h6>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="metode" id="tunai" value="tunai" checked>
                    <label class="btn btn-outline-primary" for="tunai"><i class="fas fa-money-bill me-1"></i> Tunai</label>

                    <input type="radio" class="btn-check" name="metode" id="midtrans" value="midtrans">
                    <label class="btn btn-outline-primary" for="midtrans"><i class="fas fa-qrcode me-1"></i> QRIS</label>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed-bottom bg-white p-3 border-top shadow-lg">
        <div class="d-flex justify-content-between mb-1 text-muted small">
            <span>Subtotal</span> <span id="subTotal">Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-2 text-success small fw-bold">
            <span>Diskon</span> <span id="diskonDisplay">-Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-3 align-items-center">
            <span class="fw-bold">Total Bayar</span>
            <strong class="text-primary fs-4" id="grandTotal">Rp 0</strong>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow" onclick="processCheckout()">
            Buat Pesanan <i class="fas fa-paper-plane ms-2"></i>
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('cart_v2')) || [];
        let totalBelanja = 0;

        function renderCart() {
            if (cart.length === 0) {
                document.getElementById('cartList').innerHTML = '<div class="text-center py-5 text-muted">Keranjang kosong.</div>';
                document.getElementById('formCard').style.display = 'none';
                // Reset nilai jika kosong
                totalBelanja = 0;
                hitungTotal();
                return;
            }
            document.getElementById('formCard').style.display = 'block';
            
            let html = '';
            totalBelanja = 0;

            cart.forEach((item, index) => {
                // [FIX] Normalisasi nama variabel agar tidak NaN
                let qty = parseInt(item.quantity || item.qty || 1);
                let price = parseInt(item.price || item.harga || 0);
                
                let sub = price * qty;
                totalBelanja += sub;
                
                html += `
                <div class="d-flex justify-content-between align-items-center border-bottom p-3">
                    <div>
                        <div class="fw-bold text-dark">${item.name || item.nama_menu}</div>
                        <small class="text-muted">${qty} x Rp ${price.toLocaleString('id-ID')}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">Rp ${sub.toLocaleString('id-ID')}</div>
                        <a href="javascript:void(0)" onclick="removeItem(${index})" class="text-danger small text-decoration-none">Hapus</a>
                    </div>
                </div>`;
            });
            document.getElementById('cartList').innerHTML = html;
            hitungTotal();
        }

        function removeItem(index) {
            cart.splice(index, 1);
            localStorage.setItem('cart_v2', JSON.stringify(cart));
            renderCart();
        }

        function cekVoucher() {
            const kode = document.getElementById('voucherCode').value;
            const msg = document.getElementById('voucherMsg');
            
            // Validasi sederhana sebelum fetch
            if(!kode) {
                msg.className = "d-block mb-2 text-danger";
                msg.innerText = "Masukkan kode dulu";
                return;
            }
            if(totalBelanja <= 0) {
                msg.className = "d-block mb-2 text-danger";
                msg.innerText = "Belanja dulu sebelum pakai voucher";
                return;
            }

            msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengecek...';
            
            // Panggil API Voucher
            fetch(`api_voucher.php?kode=${kode}&total=${totalBelanja}`)
            .then(r => r.json())
            .then(d => {
                if(d.valid) {
                    msg.className = "d-block mb-2 text-success fw-bold";
                    msg.innerHTML = `<i class="fas fa-check-circle"></i> ${d.msg}`;
                    document.getElementById('diskonVal').value = parseInt(d.potongan) || 0; // Pastikan integer
                    document.getElementById('kodeVal').value = d.kode;
                } else {
                    msg.className = "d-block mb-2 text-danger";
                    msg.innerText = d.msg;
                    document.getElementById('diskonVal').value = 0;
                    document.getElementById('kodeVal').value = "";
                }
                hitungTotal();
            })
            .catch(err => {
                console.error(err);
                msg.className = "d-block mb-2 text-danger";
                msg.innerText = "Gagal mengecek voucher";
            });
        }

        function hitungTotal() {
            let diskon = parseInt(document.getElementById('diskonVal').value) || 0;
            
            // [FIX] Cegah Diskon lebih besar dari total (Minus)
            if(diskon > totalBelanja) diskon = totalBelanja;
            
            let final = totalBelanja - diskon;
            
            // [FIX] Cegah NaN
            if(isNaN(final)) final = 0;

            document.getElementById('subTotal').innerText = 'Rp ' + totalBelanja.toLocaleString('id-ID');
            document.getElementById('diskonDisplay').innerText = '-Rp ' + diskon.toLocaleString('id-ID');
            document.getElementById('grandTotal').innerText = 'Rp ' + final.toLocaleString('id-ID');
        }

        function processCheckout() {
            const nama = document.getElementById('namaPelanggan').value;
            const metode = document.querySelector('input[name="metode"]:checked').value;
            const diskon = parseInt(document.getElementById('diskonVal').value) || 0;
            const kodeV = document.getElementById('kodeVal').value;
            
            if(!nama) return Swal.fire('Nama Wajib', 'Harap isi nama pemesan', 'warning');
            
            // [FIX] Validasi Total 0 (Kecuali memang gratis karena voucher)
            // Tapi biasanya minimal pembayaran Rp 100 perak untuk Midtrans/System
            let totalAkhir = totalBelanja - diskon;
            if(totalAkhir <= 0 && metode === 'midtrans') {
                 return Swal.fire('Error', 'Pembayaran QRIS minimal Rp 1. Gunakan Tunai jika gratis.', 'warning');
            }
            if(cart.length === 0) return Swal.fire('Error', 'Keranjang kosong', 'warning');

            let payload = {
                nama_pelanggan: nama,
                metode: metode,
                total_harga: totalAkhir, 
                items: cart,
                diskon: diskon,
                kode_voucher: kodeV
            };

            Swal.fire({title:'Memproses...', didOpen:()=>Swal.showLoading()});

            fetch('proses_checkout.php', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
            }).then(r=>r.json()).then(d => {
                if(d.status === 'success') {
                    localStorage.removeItem('cart_v2'); 
                    
                    if(metode === 'midtrans' && d.snap_token) {
                        window.snap.pay(d.snap_token, {
                            onSuccess: function(){ window.location.href = 'sukses.php?uuid='+d.uuid; },
                            onPending: function(){ window.location.href = 'status.php?uuid='+d.uuid; },
                            onClose: function(){ window.location.href = 'status.php?uuid='+d.uuid; }
                        });
                    } else {
                        window.location.href = 'sukses.php?uuid='+d.uuid;
                    }
                } else {
                    Swal.fire('Gagal', d.message, 'error');
                }
            }).catch(err => Swal.fire('Error', 'Koneksi terputus', 'error'));
        }

        // Init pertama kali
        renderCart();
    </script>
</body>
</html>