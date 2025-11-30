<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';
$page_title = "Monitor Dapur";
$active_menu = "dapur";
include '../layouts/admin/header.php';
?>

<div class="row" id="kitchen-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Menghubungkan ke Dapur...</p>
    </div>
</div>

<script>
    let kitchenSource = null;

    function startKitchenUpdates() {
        if (kitchenSource) kitchenSource.close();
        
        // Deteksi cabang
        <?php 
            $cabang_id = $_SESSION['cabang_id'] ?? 0;
            if ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) {
                $cabang_id = $_SESSION['view_cabang_id'];
            }
            $target = ($_SESSION['level']=='admin' && !isset($_SESSION['view_cabang_id'])) ? 'pusat' : $cabang_id;
        ?>
        const currentBranch = '<?= $target ?>';

        kitchenSource = new EventSource(`api/sse_dapur.php?view_cabang=${currentBranch}`);

        kitchenSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('kitchen-container');
            
            if(result.data.length === 0) {
                container.innerHTML = `<div class="col-12 text-center py-5 text-muted"><i class="fas fa-mug-hot fa-3x mb-3"></i><br>Belum ada pesanan masuk.</div>`;
                return;
            }

            let html = '';
            result.data.forEach(order => {
                let itemsHtml = '';
                order.items.forEach(item => {
                    itemsHtml += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${item.qty}x</strong> ${item.nama_menu}
                                ${item.catatan ? `<br><small class="text-danger">Note: ${item.catatan}</small>` : ''}
                            </div>
                        </li>`;
                });

                html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow-sm border-start border-5 border-warning h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Meja ${order.nomor_meja}</h5>
                            <span class="badge bg-light text-dark">${order.created_at.substring(11, 16)}</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="p-3 bg-light small">
                                <i class="fas fa-user me-1"></i> ${order.nama_pelanggan} 
                                <span class="float-end text-muted">${order.nama_cabang}</span>
                            </div>
                            <ul class="list-group list-group-flush">
                                ${itemsHtml}
                            </ul>
                        </div>
                        <div class="card-footer bg-white p-3">
                            <button class="btn btn-success w-100 fw-bold" onclick="selesaiMasak('${order.id}')">
                                <i class="fas fa-check me-2"></i> SELESAI SAJIKAN
                            </button>
                        </div>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        };
    }

    function selesaiMasak(id) {
        Swal.fire({
            title: 'Pesanan Selesai?', text: "Makanan akan diantar ke meja.", icon: 'question',
            showCancelButton: true, confirmButtonColor: '#198754', confirmButtonText: 'Ya, Sajikan!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('api/transaksi_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=selesai_masak&id=${id}`
                }).then(res => res.json()).then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success');
                    }
                });
            }
        })
    }

    document.addEventListener('DOMContentLoaded', startKitchenUpdates);
</script>
<?php include '../layouts/admin/footer.php'; ?>