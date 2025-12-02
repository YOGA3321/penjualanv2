<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pesanan Masuk";
$active_menu = "transaksi_masuk";

// Deteksi Cabang
$cabang_id = $_SESSION['cabang_id'] ?? 0;
if ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
$target_cabang = ($_SESSION['level']=='admin' && !isset($_SESSION['view_cabang_id'])) ? 'pusat' : $cabang_id;

include '../layouts/admin/header.php';
?>

<audio id="notifSound" src="../assets/audio/bell.mp3" preload="auto"></audio>

<div class="row" id="incoming-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Menunggu pesanan masuk...</p>
    </div>
</div>

<script type="text/javascript"
    src="https://app.sandbox.midtrans.com/snap/snap.js"
    data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST">
</script>

<script>
    let orderSource = null;
    let lastCount = 0;
    let lastDataJson = ""; // [BARU] Untuk menyimpan data terakhir

    // --- SSE (REALTIME) ---
    function startOrderStream() {
        if (orderSource) orderSource.close();
        
        // Tambahkan timestamp agar browser tidak cache request SSE
        orderSource = new EventSource(`api/sse_order.php?view_cabang=<?= $target_cabang ?>&t=${new Date().getTime()}`);

        orderSource.onmessage = function(event) {
            // [LOGIKA BARU] Cek apakah datanya sama dengan yang tampil sekarang?
            if (event.data === lastDataJson) {
                return; // Jika sama, diam saja (jangan refresh layar) -> Animasi tidak akan muncul ulang
            }
            lastDataJson = event.data; // Simpan data baru

            const result = JSON.parse(event.data);
            const container = document.getElementById('incoming-container');

            if(result.status === 'success') {
                if (result.data.length > lastCount) {
                    try { document.getElementById('notifSound').play(); } catch(e){}
                }
                lastCount = result.data.length;

                if(result.data.length === 0) {
                    container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="fas fa-clipboard-check fa-3x mb-3"></i><h5>Tidak ada pesanan baru</h5></div>`;
                    return;
                }

                let html = '';
                result.data.forEach(trx => {
                    let itemList = '';
                    trx.items.forEach(item => {
                        itemList += `<li class="d-flex justify-content-between small border-bottom py-1"><span>${item.nama_menu}</span> <span class="fw-bold">x${item.qty}</span></li>`;
                    });

                    let badge = '';
                    let buttons = '';

                    // LOGIKA TAMPILAN
                    if (trx.metode_pembayaran === 'midtrans') {
                        badge = '<span class="badge bg-info text-dark mb-2"><i class="fas fa-qrcode me-1"></i> MIDTRANS</span>';
                        buttons = `
                            <div class="alert alert-primary small mb-2 p-2 text-center">Status: <b>Pending</b></div>
                            <button class="btn btn-primary w-100 fw-bold shadow-sm mb-2" onclick="resumePembayaran('${trx.snap_token}', '${trx.uuid}')">
                                <i class="fas fa-qrcode me-1"></i> Buka QRIS / Bayar
                            </button>
                            <button class="btn btn-light btn-sm w-100 text-muted" onclick="cekStatusManual('${trx.uuid}')">
                                <i class="fas fa-sync-alt me-1"></i> Cek Status (Sync)
                            </button>`;
                    } else {
                        badge = '<span class="badge bg-success mb-2"><i class="fas fa-money-bill-wave me-1"></i> TUNAI</span>';
                        buttons = `
                            <div class="alert alert-warning small p-2 mb-2 text-center"><i class="fas fa-hand-holding-usd"></i> Terima pembayaran</div>
                            <button class="btn btn-success w-100 fw-bold shadow-sm mb-2" onclick="terimaPembayaran('${trx.id}', ${trx.total_harga}, '${trx.nama_pelanggan}')">
                                <i class="fas fa-check me-1"></i> Terima Uang
                            </button>
                             <button class="btn btn-outline-danger w-100 btn-sm" onclick="batalkanPesanan('${trx.id}')">
                                <i class="fas fa-times me-1"></i> Tolak
                            </button>`;
                    }

                    html += `
                    <div class="col-md-6 col-lg-4 mb-4 fade-in">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary rounded-pill px-3">Meja ${trx.nomor_meja}</span>
                                <small class="text-muted">${trx.created_at}</small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-1">${trx.nama_pelanggan}</h5>
                                <div>${badge}</div>
                                <ul class="list-unstyled bg-light p-3 rounded mb-3 flex-grow-1" style="max-height:150px; overflow-y:auto;">${itemList}</ul>
                                <div class="d-flex justify-content-between fw-bold fs-5 mb-3 border-top pt-2">
                                    <span>Total</span> <span class="text-primary">Rp ${parseInt(trx.total_harga).toLocaleString('id-ID')}</span>
                                </div>
                                <div class="mt-auto">${buttons}</div>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            }
        };
        
        // Auto reconnect jika putus
        orderSource.onerror = function() {
            orderSource.close();
            setTimeout(startOrderStream, 5000);
        };
    }

    // --- FUNGSI RESUME PEMBAYARAN ---
    function resumePembayaran(token, uuid) {
        if(!token || token === 'null') { 
            cekStatusManual(uuid);
            return; 
        }

        window.snap.pay(token, {
            onSuccess: function(result){ cekStatusManual(uuid, true); },
            onPending: function(result){ cekStatusManual(uuid, true); },
            onError: function(result){ 
                console.log("Midtrans Error/Expired", result);
                cekStatusManual(uuid, true); 
            },
            onClose: function(){ 
                console.log('Popup closed, checking status...');
                cekStatusManual(uuid, true); 
            }
        });
    }

    // --- FUNGSI SINKRONISASI STATUS ---
    function cekStatusManual(uuid, silent = false) {
        if(!silent) Swal.fire({title: 'Sinkronisasi Data...', text: 'Menghubungi Server Midtrans...', didOpen: () => Swal.showLoading()});
        
        fetch(`../penjualan/check_status_api.php?uuid=${uuid}&t=${new Date().getTime()}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.data.status_pembayaran === 'settlement') {
                    if(!silent) Swal.close();
                    Swal.fire({
                        icon: 'success', 
                        title: 'LUNAS!', 
                        text: 'Pembayaran terkonfirmasi. Masuk dapur.', 
                        timer: 1500, 
                        showConfirmButton: false
                    }).then(() => { location.reload(); });
                } 
                else if (data.data.status_pembayaran === 'cancel' || data.data.status_pembayaran === 'expire') {
                    if(!silent) Swal.close();
                    Swal.fire({
                        icon: 'error', 
                        title: 'KADALUARSA', 
                        text: 'Token pembayaran sudah expired. Transaksi dibatalkan.',
                        timer: 2000
                    }).then(() => { location.reload(); });
                } 
                else {
                    if(!silent) Swal.fire('Info', 'Status masih Menunggu Pembayaran (Pending).', 'info');
                }
            } else {
                if(!silent) Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => {
            if(!silent) Swal.fire('Error', 'Gagal koneksi ke server.', 'error');
        });
    }

    // Fungsi Terima Tunai (DESAIN DIPERBAIKI AGAR KONSISTEN)
    function terimaPembayaran(id, total, nama) {
        Swal.fire({
            title: 'Terima Pembayaran',
            html: `
                <div class="mb-3 text-start bg-light p-3 rounded">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Pelanggan:</small> 
                        <strong>${nama}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small>Total Tagihan:</small>
                        <h3 class="text-primary fw-bold mb-0">Rp ${parseInt(total).toLocaleString('id-ID')}</h3>
                    </div>
                </div>
                
                <label class="form-label fw-bold small text-muted">Uang Diterima (Tunai)</label>
                <input type="number" id="cashInputAdmin" class="form-control form-control-lg text-center fw-bold fs-3" placeholder="0">
                
                <div class="mt-3 p-2 border rounded bg-light">
                    <small class="text-muted d-block">Kembalian</small>
                    <div class="fw-bold fs-4 text-success" id="changeDisplayAdmin">Rp 0</div>
                </div>
            `,
            confirmButtonText: '<i class="fas fa-print me-2"></i> BAYAR & LUNAS',
            confirmButtonColor: '#198754',
            showCancelButton: true,
            didOpen: () => {
                const input = document.getElementById('cashInputAdmin');
                const display = document.getElementById('changeDisplayAdmin');
                const btn = Swal.getConfirmButton();
                
                btn.disabled = true;
                input.focus();
                
                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - total;
                    if(kembali >= 0) {
                        display.innerHTML = 'Rp ' + kembali.toLocaleString('id-ID');
                        display.className = 'fw-bold fs-4 text-success';
                        btn.disabled = false;
                    } else {
                        display.innerHTML = 'Kurang: Rp ' + Math.abs(kembali).toLocaleString('id-ID');
                        display.className = 'fw-bold fs-4 text-danger';
                        btn.disabled = true;
                    }
                });
            },
            preConfirm: () => {
                let bayar = parseInt(document.getElementById('cashInputAdmin').value);
                return { uang: bayar, kembali: bayar - total };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'konfirmasi_bayar');
                formData.append('id', id);
                formData.append('uang_bayar', result.value.uang);
                formData.append('kembalian', result.value.kembali);

                Swal.fire({title: 'Memproses...', didOpen: () => Swal.showLoading()});

                fetch('api/transaksi_action.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            icon: 'success', 
                            title: 'LUNAS!', 
                            text: 'Kembalian: Rp ' + result.value.kembali.toLocaleString('id-ID'), 
                            timer: 2000, 
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal koneksi', 'error'));
            }
        });
    }

    function batalkanPesanan(id) {
        Swal.fire({
            title: 'Tolak Pesanan?', 
            text: "Stok akan dikembalikan dan meja dikosongkan.", 
            icon: 'warning',
            showCancelButton: true, 
            confirmButtonColor: '#d33', 
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if(res.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'tolak_pesanan');
                formData.append('id', id);

                fetch('api/transaksi_action.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(d => {
                    if(d.status === 'success') {
                        Swal.fire('Ditolak', d.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Gagal', d.message, 'error');
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', startOrderStream);
</script>
<style>.fade-in { animation: fadeIn 0.5s ease-in-out; } @keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }</style>
<?php include '../layouts/admin/footer.php'; ?>