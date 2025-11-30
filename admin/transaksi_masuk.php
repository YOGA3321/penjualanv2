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

    function startOrderStream() {
        if (orderSource) orderSource.close();
        
        orderSource = new EventSource(`api/sse_order.php?view_cabang=<?= $target_cabang ?>`);

        orderSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('incoming-container');

            if(result.status === 'success') {
                if (result.data.length > lastCount) {
                    try { document.getElementById('notifSound').play(); } catch(e){}
                }
                lastCount = result.data.length;

                if(result.data.length === 0) {
                    container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="fas fa-check-circle fa-3x mb-3"></i><h5>Tidak ada pesanan baru</h5></div>`;
                    return;
                }

                let html = '';
                result.data.forEach(trx => {
                    let itemList = '';
                    trx.items.forEach(item => {
                        itemList += `<li class="d-flex justify-content-between small border-bottom py-1"><span>${item.nama_menu}</span> <span class="fw-bold">x${item.qty}</span></li>`;
                    });

                    let actionButtons = '';
                    let badgeMetode = '';

                    // === LOGIKA TAMPILAN ===
                    if(trx.metode_pembayaran === 'tunai') {
                        badgeMetode = '<span class="badge bg-success mb-2"><i class="fas fa-money-bill-wave me-1"></i> TUNAI</span>';
                        actionButtons = `
                            <button class="btn btn-success w-100 fw-bold shadow-sm mb-2" onclick="terimaPembayaran('${trx.id}', ${trx.total_harga}, '${trx.nama_pelanggan}')">
                                <i class="fas fa-hand-holding-usd me-1"></i> Terima Uang
                            </button>`;
                    } else if (trx.metode_pembayaran === 'midtrans') {
                        badgeMetode = '<span class="badge bg-info text-dark mb-2"><i class="fas fa-qrcode me-1"></i> MIDTRANS</span>';
                        actionButtons = `
                            <div class="alert alert-primary small mb-2 p-2 text-center">Status: <b>Pending</b></div>
                            <button class="btn btn-primary w-100 fw-bold shadow-sm mb-2" onclick="resumePembayaran('${trx.snap_token}', '${trx.uuid}')">
                                <i class="fas fa-qrcode me-1"></i> Buka QRIS / Bayar
                            </button>
                            <button class="btn btn-light btn-sm w-100 text-muted" onclick="cekStatusManual('${trx.uuid}')">
                                <i class="fas fa-sync-alt me-1"></i> Cek Status
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
                                ${badgeMetode}
                                <ul class="list-unstyled bg-light p-3 rounded mb-3" style="max-height:150px; overflow-y:auto;">${itemList}</ul>
                                <div class="d-flex justify-content-between fw-bold fs-5 mb-3 border-top pt-2">
                                    <span>Total</span> <span class="text-primary">Rp ${parseInt(trx.total_harga).toLocaleString('id-ID')}</span>
                                </div>
                                ${actionButtons}
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            }
        };
    }

    // --- FUNGSI RESUME & AUTO CHECK ---
    function resumePembayaran(token, uuid) {
        if(!token || token === 'null') { return Swal.fire('Error', 'Token tidak valid.', 'error'); }

        window.snap.pay(token, {
            onSuccess: function(result){ cekStatusManual(uuid, true); },
            onPending: function(result){ cekStatusManual(uuid, true); },
            onError: function(result){ Swal.fire('Gagal', 'Pembayaran gagal.', 'error'); },
            onClose: function(){ 
                // SAAT DI-CLOSE: Cek otomatis ke server (Active Inquiry)
                console.log('Popup closed, checking status...');
                cekStatusManual(uuid, true); 
            }
        });
    }

    function cekStatusManual(uuid, silent = false) {
        if(!silent) Swal.fire({title: 'Sinkronisasi...', didOpen: () => Swal.showLoading()});
        
        // Panggil API Check Status (yang punya fitur cek ke Midtrans Server)
        fetch(`../penjualan/check_status_api.php?uuid=${uuid}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                if(data.data.status_pembayaran === 'settlement') {
                    if(!silent) Swal.close();
                    Swal.fire({icon: 'success', title: 'LUNAS!', text: 'Pesanan masuk dapur.', timer: 1500, showConfirmButton: false});
                } else if (data.data.status_pembayaran === 'cancel') {
                    if(!silent) Swal.fire('Batal', 'Transaksi kadaluarsa.', 'error');
                } else {
                    if(!silent) Swal.fire('Pending', 'Menunggu pembayaran.', 'info');
                }
            }
        });
    }

    function terimaPembayaran(id, total, nama) {
        // ... (Kode terima pembayaran tunai sama seperti sebelumnya)
        // Silakan copy dari kode sebelumnya jika perlu, atau gunakan placeholder ini
        Swal.fire('Info', 'Gunakan logika terima tunai yang sudah ada.', 'info');
    }

    document.addEventListener('DOMContentLoaded', startOrderStream);
</script>
<style>.fade-in { animation: fadeIn 0.5s ease-in-out; } @keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }</style>
<?php include '../layouts/admin/footer.php'; ?>