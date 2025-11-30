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

<script>
    let orderSource = null;
    let lastCount = 0;

    function startOrderStream() {
        if (orderSource) orderSource.close();
        
        // Connect SSE
        orderSource = new EventSource(`api/sse_order.php?view_cabang=<?= $target_cabang ?>`);

        orderSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('incoming-container');

            if(result.status === 'success') {
                // Bunyikan Bell jika ada order baru bertambah
                if (result.data.length > lastCount) {
                    try { document.getElementById('notifSound').play(); } catch(e){}
                }
                lastCount = result.data.length;

                if(result.data.length === 0) {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-clipboard-check fa-4x text-gray-300 mb-3"></i>
                            <h5 class="text-muted">Tidak ada pesanan baru</h5>
                            <small>Pesanan pelanggan via QR Code akan muncul di sini.</small>
                        </div>`;
                    return;
                }

                let html = '';
                result.data.forEach(trx => {
                    // Render Item List
                    let itemList = '';
                    trx.items.forEach(item => {
                        itemList += `<li class="d-flex justify-content-between small"><span>${item.nama_menu}</span> <span class="fw-bold">x${item.qty}</span></li>`;
                    });

                    // Tombol Aksi Berdasarkan Status & Metode
                    let actionButtons = '';
                    
                    if(trx.metode_pembayaran === 'tunai' && trx.status_pesanan === 'menunggu_konfirmasi') {
                        // Tombol Terima Pembayaran (Tunai)
                        actionButtons = `
                            <button class="btn btn-success w-100 fw-bold" onclick="terimaPembayaran('${trx.id}', ${trx.total_harga}, '${trx.nama_pelanggan}')">
                                <i class="fas fa-money-bill-wave me-1"></i> Terima Pembayaran
                            </button>
                            <button class="btn btn-outline-danger w-100 mt-2 btn-sm" onclick="batalkanPesanan('${trx.id}')">Tolak</button>
                        `;
                    } else if (trx.status_pesanan === 'menunggu_bayar') {
                        // Online Payment (Hanya pantau)
                        actionButtons = `
                            <div class="alert alert-info small mb-0 p-2 text-center">
                                <i class="fas fa-spinner fa-spin me-1"></i> Menunggu Pelanggan Bayar via QRIS/VA
                            </div>
                        `;
                    }

                    // Card HTML
                    html += `
                    <div class="col-md-4 mb-4 fade-in">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary">Meja ${trx.nomor_meja}</span>
                                <small class="text-muted text-end">${trx.created_at}</small>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold">${trx.nama_pelanggan}</h5>
                                <div class="badge bg-light text-dark border mb-3">${trx.metode_pembayaran.toUpperCase()}</div>
                                
                                <ul class="list-unstyled bg-light p-2 rounded mb-3" style="max-height:100px; overflow-y:auto;">
                                    ${itemList}
                                </ul>

                                <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                                    <span>Total</span>
                                    <span class="text-primary">Rp ${parseInt(trx.total_harga).toLocaleString('id-ID')}</span>
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

    // Fungsi PopUp Terima Pembayaran Tunai (Mirip Kasir Manual)
    function terimaPembayaran(id, total, nama) {
        Swal.fire({
            title: 'Konfirmasi Tunai',
            html: `
                <div class="text-start mb-3 bg-light p-2 rounded">
                    <small>Pelanggan: <b>${nama}</b></small><br>
                    <small>Tagihan:</small>
                    <h3 class="text-primary fw-bold m-0">Rp ${parseInt(total).toLocaleString('id-ID')}</h3>
                </div>
                <div class="form-group text-start">
                    <label class="fw-bold mb-1">Uang Diterima</label>
                    <input type="number" id="cashInputAdmin" class="form-control form-control-lg fw-bold" placeholder="0" min="0">
                    <div class="mt-2 fw-bold" id="changeDisplayAdmin">Kembalian: Rp 0</div>
                </div>
            `,
            confirmButtonText: 'Proses & Masuk Dapur',
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
                        display.innerHTML = 'Kembalian: <span class="text-success">Rp ' + kembali.toLocaleString('id-ID') + '</span>';
                        btn.disabled = false;
                    } else {
                        display.innerHTML = 'Kurang: <span class="text-danger">Rp ' + Math.abs(kembali).toLocaleString('id-ID') + '</span>';
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
                // Kirim ke API Action
                const formData = new FormData();
                formData.append('action', 'konfirmasi_bayar');
                formData.append('id', id);
                formData.append('uang_bayar', result.value.uang);
                formData.append('kembalian', result.value.kembali);

                fetch('api/transaksi_action.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Berhasil', 'Pembayaran diterima. Pesanan diteruskan ke dapur.', 'success');
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                });
            }
        });
    }

    function batalkanPesanan(id) {
        Swal.fire({
            title: 'Tolak Pesanan?', text: "Pesanan akan dibatalkan.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Tolak'
        }).then((res) => {
            if(res.isConfirmed) {
                // Implementasi logika batal (bisa update status ke 'cancel' di transaksi_action.php)
                // Sementara kita biarkan dulu atau Anda bisa tambahkan action 'cancel' di API
                Swal.fire('Info', 'Fitur tolak belum diaktifkan di backend.', 'info');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', startOrderStream);
</script>

<style>
    .fade-in { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }
</style>

<?php include '../layouts/admin/footer.php'; ?>