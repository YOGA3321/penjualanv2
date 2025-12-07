<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Monitor Dapur";
$active_menu = "dapur";

// Deteksi Cabang
$cabang_id = $_SESSION['cabang_id'] ?? 0;
if ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
$target_cabang = ($_SESSION['level']=='admin' && !isset($_SESSION['view_cabang_id'])) ? 'pusat' : $cabang_id;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold text-danger"><i class="fas fa-fire me-2"></i>LIVE ORDER MONITOR</h5>
        <small class="text-muted">Menampilkan pesanan yang sedang diproses (dimasak)</small>
    </div>
    <div class="badge bg-light text-danger border">
        <i class="fas fa-sync fa-spin me-1"></i> Realtime
    </div>
</div>

<div class="row" id="dapur-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="mt-2 text-muted">Menghubungkan ke dapur...</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let dapurSource = null;

    function startDapurStream() {
        if(dapurSource) dapurSource.close();
        
        // Sesuaikan path ke file sse_dapur.php
        dapurSource = new EventSource(`api/sse_dapur.php?view_cabang=<?= $target_cabang ?>`);
        
        dapurSource.onmessage = function(event) {
            const orders = JSON.parse(event.data);
            const container = document.getElementById('dapur-container');
            
            if(orders.length === 0) {
                container.innerHTML = `<div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-utensils fa-3x mb-3 opacity-25"></i>
                    <h4>Dapur Bersih</h4>
                    <p>Tidak ada pesanan antri.</p>
                </div>`;
            } else {
                let html = '';
                orders.forEach(o => {
                    let itemsHtml = '';
                    o.items.forEach(i => {
                        itemsHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">${i.nama_menu}</span>
                                ${i.catatan ? `<div class="text-danger small fst-italic"><i class="fas fa-comment-dots"></i> ${i.catatan}</div>` : ''}
                            </div>
                            <span class="badge bg-primary rounded-pill">${i.qty}</span>
                        </li>`;
                    });

                    // Tombol Selesai Masak
                    html += `
                    <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                <div class="fw-bold">Meja ${o.nomor_meja}</div>
                                <small>${o.created_at.substr(11, 5)}</small>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-3 border-bottom bg-light">
                                    <small class="text-muted d-block">Pelanggan</small>
                                    <div class="fw-bold text-truncate">${o.nama_pelanggan}</div>
                                </div>
                                <ul class="list-group list-group-flush">
                                    ${itemsHtml}
                                </ul>
                            </div>
                            <div class="card-footer bg-white p-3">
                                <button class="btn btn-success w-100 fw-bold py-2" onclick="selesaiMasak(${o.id})">
                                    <i class="fas fa-check-circle me-2"></i> SELESAI MASAK
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            }
        };
        
        dapurSource.onerror = function() {
            // Reconnect jika putus
            dapurSource.close();
            // console.log("SSE Error, reconnecting...");
            // setTimeout(startDapurStream, 5000); 
        };
    }

    function selesaiMasak(id) {
        Swal.fire({
            title: 'Selesai Masak?',
            text: "Pesanan akan ditandai SIAP SAJI dan hilang dari layar dapur.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Ya, Selesai!',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if(res.isConfirmed) {
                let fd = new FormData();
                // [FIX] Gunakan action 'update_status' dan status 'siap_saji'
                fd.append('action', 'update_status');
                fd.append('id', id);
                fd.append('status', 'siap_saji'); 
                
                fetch('api/transaksi_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if(d.status === 'success') {
                        const Toast = Swal.mixin({
                            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true
                        });
                        Toast.fire({icon: 'success', title: 'Pesanan Siap Saji!'});
                        // Data otomatis hilang via SSE, tidak perlu reload
                    } else {
                        Swal.fire('Gagal', d.message || 'Terjadi kesalahan', 'error');
                    }
                })
                .catch(err => Swal.fire('Error', 'Gagal koneksi ke server', 'error'));
            }
        });
    }

    document.addEventListener('DOMContentLoaded', startDapurStream);
</script>

<?php include '../layouts/admin/footer.php'; ?>