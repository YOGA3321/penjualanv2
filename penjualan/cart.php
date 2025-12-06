<?php
session_start();
require_once '../auth/koneksi.php';
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
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="bg-light">

    <div class="bg-white shadow-sm p-3 sticky-top">
        <div class="d-flex align-items-center">
            <a href="index.php" class="text-dark me-3"><i class="fas fa-arrow-left fa-lg"></i></a>
            <h5 class="mb-0 fw-bold flex-grow-1">Keranjang</h5>
            <span class="badge bg-primary">Meja <?= $_SESSION['plg_no_meja'] ?></span>
        </div>
    </div>

    <div class="container py-3 mb-5" style="padding-bottom: 150px;">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-0">
                <div id="cartList" class="p-3"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3" id="formCard" style="display:none;">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted">Nama Pemesan</label>
                    <input type="text" id="namaPelanggan" class="form-control" value="<?= $_SESSION['nama'] ?? '' ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small text-muted">Kode Voucher / Diskon</label>
                    <div class="input-group">
                        <input type="text" id="voucherCode" class="form-control text-uppercase" placeholder="Masukan Kode">
                        <button class="btn btn-outline-primary" type="button" onclick="cekVoucher()">Gunakan</button>
                    </div>
                    <small id="voucherMsg" class="text-muted"></small>
                    <input type="hidden" id="diskonVal" value="0">
                    <input type="hidden" id="kodeVal" value="">
                </div>

                <h6 class="fw-bold mb-2 mt-4">Pembayaran</h6>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="metode" id="tunai" value="tunai" checked>
                    <label class="btn btn-outline-primary" for="tunai"><i class="fas fa-money-bill"></i> Tunai</label>

                    <input type="radio" class="btn-check" name="metode" id="midtrans" value="midtrans">
                    <label class="btn btn-outline-primary" for="midtrans"><i class="fas fa-qrcode"></i> QRIS</label>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed-bottom bg-white p-3 border-top shadow-lg">
        <div class="d-flex justify-content-between mb-1 text-muted small">
            <span>Subtotal</span> <span id="subTotal">Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-2 text-success small">
            <span>Diskon</span> <span id="diskonDisplay">-Rp 0</span>
        </div>
        <div class="d-flex justify-content-between mb-3">
            <span class="fw-bold">Total Bayar</span>
            <strong class="text-primary fs-5" id="grandTotal">Rp 0</strong>
        </div>
        <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow" onclick="processCheckout()">
            Buat Pesanan
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>
    
    <script>
        let cart = JSON.parse(localStorage.getItem('cart_v2')) || [];
        let totalBelanja = 0;

        function renderCart() {
            if (cart.length === 0) {
                document.getElementById('cartList').innerHTML = '<div class="text-center py-5">Keranjang kosong.</div>';
                document.getElementById('formCard').style.display = 'none';
                return;
            }
            document.getElementById('formCard').style.display = 'block';
            let html = '';
            totalBelanja = 0;

            cart.forEach((item, index) => {
                let qty = item.quantity ? item.quantity : 1;
                let price = item.price ? parseInt(item.price) : 0;
                let sub = price * qty;
                totalBelanja += sub;
                html += `<div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                    <div><b>${item.name}</b><br><small>${qty} x ${price.toLocaleString()}</small></div>
                    <div class="text-end"><div>${sub.toLocaleString()}</div><a href="#" onclick="rem(${index})" class="text-danger small">Hapus</a></div>
                </div>`;
            });
            document.getElementById('cartList').innerHTML = html;
            hitungTotal();
        }

        function rem(i) { cart.splice(i, 1); localStorage.setItem('cart_v2', JSON.stringify(cart)); renderCart(); }

        function cekVoucher() {
            const kode = document.getElementById('voucherCode').value;
            if(!kode) return;
            
            fetch(`../penjualan/api_voucher.php?kode=${kode}&total=${totalBelanja}`)
            .then(r => r.json()).then(d => {
                const msg = document.getElementById('voucherMsg');
                if(d.valid) {
                    msg.className = "text-success fw-bold";
                    msg.innerText = d.msg;
                    document.getElementById('diskonVal').value = d.potongan;
                    document.getElementById('kodeVal').value = d.kode;
                    hitungTotal();
                } else {
                    msg.className = "text-danger";
                    msg.innerText = d.msg;
                    document.getElementById('diskonVal').value = 0;
                    document.getElementById('kodeVal').value = "";
                    hitungTotal();
                }
            });
        }

        function hitungTotal() {
            let diskon = parseInt(document.getElementById('diskonVal').value) || 0;
            let final = totalBelanja - diskon;
            if(final < 0) final = 0;

            document.getElementById('subTotal').innerText = 'Rp ' + totalBelanja.toLocaleString();
            document.getElementById('diskonDisplay').innerText = '-Rp ' + diskon.toLocaleString();
            document.getElementById('grandTotal').innerText = 'Rp ' + final.toLocaleString();
        }

        function processCheckout() {
            const nama = document.getElementById('namaPelanggan').value;
            const metode = document.querySelector('input[name="metode"]:checked').value;
            const diskon = document.getElementById('diskonVal').value;
            const kodeV = document.getElementById('kodeVal').value;
            
            // Hitung ulang di JS sebelum kirim
            let totalAkhir = totalBelanja - diskon;

            let payload = {
                nama_pelanggan: nama,
                metode: metode,
                total_harga: totalAkhir, // Total yang harus dibayar (sudah potong diskon)
                items: cart,
                // Data Tambahan
                diskon: diskon,
                kode_voucher: kodeV
            };
            
            Swal.fire({title:'Memproses...', didOpen:()=>Swal.showLoading()});

            fetch('proses_checkout.php', {
                method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
            }).then(r=>r.json()).then(d=>{
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
            });
        }

        renderCart();
    </script>
</body>
</html>