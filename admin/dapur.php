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
    <h5 class="fw-bold text-danger"><i class="fas fa-fire me-2"></i>LIVE ORDER MONITOR</h5>
    <small class="text-muted">Pesanan masuk otomatis tanpa refresh</small>
</div>

<div class="row" id="dapur-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="mt-2 text-muted">Menghubungkan ke dapur...</p>
    </div>
</div>

<script>
    let dapurSource = null;

    function startDapurStream() {
        if (dapurSource) dapurSource.close();
        
        // Panggil API SSE Dapur
        dapurSource = new EventSource(`api/sse_dapur.php?view_cabang=<?= $target_cabang ?>&t=${new Date().getTime()}`);

        dapurSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('dapur-container');

            if(result.status === 'success') {
                if(result.data.length === 0) {
                    container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="fas fa-utensils fa-3x mb-3 opacity-50"></i><h5>Tidak ada pesanan aktif</h5><small>Semua pesanan sudah disajikan.</small></div>`;
                    return;
                }

                let html = '';
                result.data.forEach(order => {
                    // Hitung Durasi
                    let waktuPesan = new Date(order.created_at).getTime();
                    let sekarang = new Date().getTime();
                    let selisih = Math.floor((sekarang - waktuPesan) / 1000 / 60); // Menit
                    
                    let badgeWaktu = selisih > 15 ? 'bg-danger' : (selisih > 10 ? 'bg-warning text-dark' : 'bg-white text-dark border');
                    
                    let itemsHtml = '';
                    order.items.forEach(item => {
                        itemsHtml += `
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div class="fw-bold fs-5">${item.qty}x</div>
                            <div class="flex-grow-1 ms-3 fw-bold text-dark">${item.nama_menu}</div>
                        </div>`;
                    });

                    html += `
                    <div class="col-md-6 col-lg-4 mb-4 fade-in">
                        <div class="card border-0 shadow h-100">
                            <div class="card-header bg-warning border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-chair me-1"></i> <strong>Meja ${order.nomor_meja}</strong>
                                    <div class="small opacity-75">#${order.id} &bull; ${order.nama_pelanggan}</div>
                                </div>
                                <span class="badge ${badgeWaktu}"><i class="far fa-clock me-1"></i> ${selisih} mnt</span>
                            </div>
                            
                            <div class="bg-light px-3 py-1 small text-muted border-bottom text-end">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i> ${order.nama_cabang}
                            </div>

                            <div class="card-body">
                                ${itemsHtml}
                            </div>
                            <div class="card-footer bg-white border-0">
                                <button class="btn btn-success w-100 fw-bold py-2" onclick="selesaiMasak('${order.id}')">
                                    <i class="fas fa-check-double me-2"></i> SELESAI & SAJIKAN
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            }
        };
        
        dapurSource.onerror = function() {
            dapurSource.close();
            setTimeout(startDapurStream, 5000);
        };
    }

    function selesaiMasak(id) {
        Swal.fire({
            title: 'Selesai Masak?',
            text: "Pesanan akan ditandai siap saji.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Ya, Selesai'
        }).then((res) => {
            if(res.isConfirmed) {
                let fd = new FormData();
                fd.append('action', 'selesai_masak');
                fd.append('id', id);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if(d.status === 'success') {
                        const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
                        Toast.fire({icon: 'success', title: 'Pesanan Selesai!'});
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', startDapurStream);
</script>
<style>.fade-in { animation: fadeIn 0.5s; }</style>
<?php include '../layouts/admin/footer.php'; ?>