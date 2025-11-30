<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Monitor Dapur";
$active_menu = "dapur";

// Deteksi Cabang untuk JS
$cabang_id = $_SESSION['cabang_id'] ?? 0;
if ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) {
    $cabang_id = $_SESSION['view_cabang_id'];
}
$target_cabang = ($_SESSION['level']=='admin' && !isset($_SESSION['view_cabang_id'])) ? 'pusat' : $cabang_id;

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
        <div class="spinner-grow text-danger me-2" role="status" style="width: 1rem; height: 1rem;"></div>
        <span class="fw-bold text-danger">LIVE ORDER MONITOR</span>
    </div>
    <div class="text-muted small">
        Pesanan masuk otomatis tanpa refresh
    </div>
</div>

<div class="row" id="kitchen-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Menghubungkan ke Dapur...</p>
    </div>
</div>

<audio id="notifSound" src="../assets/audio/bell.mp3" preload="auto"></audio>

<script>
    let kitchenSource = null;
    let lastOrderCount = 0;

    function startKitchenUpdates() {
        if (kitchenSource) kitchenSource.close();
        
        const currentBranch = '<?= $target_cabang ?>';
        console.log("Connecting to Kitchen Stream: " + currentBranch);

        kitchenSource = new EventSource(`api/sse_dapur.php?view_cabang=${currentBranch}`);

        kitchenSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('kitchen-container');
            
            if(result.status === 'success') {
                // Bunyikan notifikasi jika jumlah order bertambah
                if (result.data.length > lastOrderCount) {
                    playNotification();
                }
                lastOrderCount = result.data.length;

                if(result.data.length === 0) {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5 text-muted">
                            <div class="mb-3 opacity-50">
                                <i class="fas fa-check-circle fa-4x text-success"></i>
                            </div>
                            <h5>Semua Pesanan Selesai!</h5>
                            <p>Belum ada pesanan baru yang masuk.</p>
                        </div>`;
                    return;
                }

                let html = '';
                result.data.forEach(order => {
                    let itemsHtml = '';
                    order.items.forEach(item => {
                        // Highlight jika ada catatan
                        let noteHtml = item.catatan ? 
                            `<div class="text-danger small fst-italic mt-1"><i class="fas fa-exclamation-circle"></i> ${item.catatan}</div>` : '';
                        
                        itemsHtml += `
                            <li class="list-group-item border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="fw-bold" style="font-size: 1.1rem;">${item.qty}x</div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold text-dark">${item.nama_menu}</div>
                                        ${noteHtml}
                                    </div>
                                </div>
                            </li>`;
                    });

                    // Hitung durasi (Opsional, bisa dikembangkan)
                    // let orderTime = new Date(order.created_at);
                    
                    html += `
                    <div class="col-md-6 col-xl-4 mb-4 fade-in-anim">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header bg-warning bg-gradient text-dark d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <h5 class="mb-0 fw-bold"><i class="fas fa-chair me-2"></i>Meja ${order.nomor_meja}</h5>
                                    <small class="text-dark opacity-75">#${order.id} • ${order.nama_pelanggan}</small>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-white text-dark shadow-sm">
                                        <i class="far fa-clock me-1"></i> ${order.created_at.substring(11, 16)}
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    ${itemsHtml}
                                </ul>
                            </div>
                            <div class="card-footer bg-white p-3 border-top-0">
                                <button class="btn btn-success w-100 py-2 fw-bold shadow-sm" onclick="selesaiMasak('${order.id}', '${order.nomor_meja}')">
                                    <i class="fas fa-check-double me-2"></i> SELESAI & SAJIKAN
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                container.innerHTML = html;
            }
        };
    }

    function playNotification() {
        // Fitur suara perlu interaksi user dulu di browser modern, 
        // tapi kita siapkan kodenya.
        try {
            document.getElementById('notifSound').play();
        } catch(e) {
            console.log("Audio play blocked");
        }
    }

    function selesaiMasak(id, meja) {
        Swal.fire({
            title: 'Pesanan Selesai?', 
            text: `Pastikan semua menu untuk Meja ${meja} sudah siap disajikan.`, 
            icon: 'question',
            showCancelButton: true, 
            confirmButtonColor: '#198754', 
            confirmButtonText: 'Ya, Panggil Pelayan!',
            cancelButtonText: 'Belum'
        }).then((result) => {
            if (result.isConfirmed) {
                // Panggil API Action
                const formData = new FormData();
                formData.append('action', 'selesai_masak');
                formData.append('id', id);

                fetch('api/transaksi_action.php', {
                    method: 'POST',
                    body: formData
                }).then(res => res.json()).then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            icon: 'success', 
                            title: 'Siap Disajikan!',
                            text: 'Status pesanan telah diperbarui.',
                            timer: 1500, 
                            showConfirmButton: false
                        });
                        // Tidak perlu reload, SSE akan otomatis menghapus kartu
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
            }
        })
    }

    document.addEventListener('DOMContentLoaded', startKitchenUpdates);
</script>

<style>
    .fade-in-anim { animation: fadeIn 0.5s; }
    @keyframes fadeIn { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
</style>

<?php include '../layouts/admin/footer.php'; ?>