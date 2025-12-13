<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['level'] != 'gudang' && $_SESSION['level'] != 'admin')) { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Permintaan Masuk";
$active_menu = "permintaan";

// --- PROSES PENGIRIMAN ---
if (isset($_POST['kirim_barang'])) {
    $req_id = $_POST['request_id'];
    $catatan_gudang = $_POST['catatan_gudang'];
    $tanggal_kirim = date('Y-m-d H:i:s');
    
    // Update Header Request
    $koneksi->query("UPDATE request_stok SET status='dikirim', catatan_gudang='$catatan_gudang', tanggal_kirim='$tanggal_kirim' WHERE id='$req_id'");
    
    // Loop detail untuk update qty_kirim & kurangi stok gudang
    $details = $_POST['details']; // Array id => qty_kirim
    foreach ($details as $det_id => $qty_kirim) {
        $qty_kirim = (int)$qty_kirim;
        
        // 1. Ambil info item untuk kurangi stok
        $q_det = $koneksi->query("SELECT item_id FROM request_detail WHERE id='$det_id'")->fetch_assoc();
        $item_id = $q_det['item_id'];
        
        // 2. Update Detail Request
        $koneksi->query("UPDATE request_detail SET qty_kirim='$qty_kirim' WHERE id='$det_id'");
        
        // 3. Kurangi stok real di Gudang
        if ($qty_kirim > 0) {
            $koneksi->query("UPDATE gudang_items SET stok = stok - $qty_kirim WHERE id='$item_id'");
            $koneksi->query("INSERT INTO mutasi_gudang (item_id, jenis_mutasi, qty, keterangan) VALUES ('$item_id', 'keluar_cabang', '$qty_kirim', 'Dikirim untuk Request #$req_id')");
        }
    }
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terkirim', 'text'=>'Barang status dikirim ke cabang'];
    header("Location: permintaan_masuk.php"); exit;
}

include '../layouts/admin/header.php';
?>

<h4 class="fw-bold text-primary mb-4"><i class="fas fa-inbox me-2"></i>Permintaan Masuk (Pending)</h4>

<!-- LOAD INDICATOR -->
<div id="loading-spinner" class="col-12 text-center py-5">
    <div class="spinner-border text-primary" role="status"></div>
    <p class="mt-2 text-muted fw-bold">Menghubungkan ke warehouse stream...</p>
</div>

<!-- CONTAINER DATA REALTIME -->
<div class="row" id="requests-container" style="display:none;"></div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let requestSource = null;

    function startRequestStream() {
        if(requestSource) requestSource.close();
        
        // Connect ke SSE Channel Gudang
        requestSource = new EventSource('api/sse_channel.php');
        
        requestSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            const container = document.getElementById('requests-container');
            const spinner = document.getElementById('loading-spinner');
            
            // Handle List Data
            if(data.request_list) {
                spinner.style.display = 'none';
                container.style.display = 'flex'; // row is flex
                
                if(data.request_list.length === 0) {
                    container.innerHTML = `
                        <div class="col-12 text-center py-5">
                            <div class="text-muted mb-3"><i class="fas fa-check-circle fa-4x opacity-25"></i></div>
                            <h5 class="text-muted">Tidak ada permintaan pending saat ini.</h5>
                        </div>`;
                } else {
                    let html = '';
                    data.request_list.forEach(req => {
                        let itemsHtml = '';
                        req.items.forEach(item => {
                            itemsHtml += `
                            <tr>
                                <td>
                                    ${item.nama_item} <br>
                                    <small class="text-muted">Stok Gudang: ${item.stok_gudang} ${item.satuan}</small>
                                </td>
                                <td class="text-center fw-bold align-middle">${item.qty_minta}</td>
                                <td>
                                    <input type="number" name="details[${item.id}]" class="form-control form-control-sm border-primary fw-bold" 
                                           value="${item.default_kirim}" min="0" max="${item.stok_gudang}">
                                </td>
                            </tr>`;
                        });
                        
                        // Badge Status Color
                        let badgeClass = req.status === 'pending' ? 'bg-warning text-dark' : 'bg-info text-white';

                        html += `
                        <div class="col-md-6 mb-4">
                            <div class="card border-0 shadow-sm border-top border-primary border-4 h-100">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-primary">${req.kode_request}</h6>
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i> ${req.created_at}</small>
                                    </div>
                                    <span class="badge ${badgeClass} text-uppercase">${req.status}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted d-block uppercase fw-bold" style="font-size:0.7em">PEMOHON</small>
                                        <span class="fw-bold fs-5">${req.nama_cabang}</span><br>
                                        <small class="text-muted">User: ${req.nama_user}</small>
                                    </div>
                                    
                                    ${req.catatan_cabang ? `<div class="alert alert-light border small mb-3"><strong>Catatan:</strong> ${req.catatan_cabang}</div>` : ''}

                                    <form method="POST" onsubmit="confirmPirim(event)">
                                        <input type="hidden" name="request_id" value="${req.id}">
                                        <!-- ADDED: Hidden field required for PHP isset check -->
                                        <input type="hidden" name="kirim_barang" value="1">
                                        
                                        <div class="table-responsive mb-3 border rounded">
                                            <table class="table table-sm table-striped mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Item</th>
                                                        <th class="text-center" width="80">Minta</th>
                                                        <th width="100">Kirim</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${itemsHtml}
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mb-3">
                                           <label class="small fw-bold">Catatan Pengiriman / No. Resi</label>
                                           <textarea name="catatan_gudang" class="form-control" rows="2" placeholder="Tulis pesan untuk cabang..."></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                                            <i class="fas fa-paper-plane me-2"></i> Proses & Kirim Barang
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>`;
                    });
                    container.innerHTML = html;
                }
            }
            
            // Handle Alerts Toast (DIPERBAIKI: Persistent / Manual Close)
            if(data.new_request_alert) {
                 Swal.fire({
                    title: data.new_request_alert.title,
                    text: data.new_request_alert.message,
                    icon: 'info',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: true, // User harus klik OK
                    confirmButtonText: 'Lihat',
                    timer: null, // Tidak hilang otomatis
                    timerProgressBar: false,
                    didOpen: (toast) => {
                        toast.addEventListener('click', () => {
                             // Optional: Highlight or scroll to request
                        });
                    }
                });
            }
            
            // Update online counter global if exists
             const elOnline = document.getElementById('onlineCount');
             if(elOnline && data.online_users !== undefined) elOnline.innerText = data.online_users;
        };
        
        requestSource.onerror = function() {
            // Reconnect auto
            requestSource.close();
            // console.log("SSE Reconnecting...");
        };
    }
    
    function confirmPirim(e) {
        e.preventDefault();
        const form = e.target;
        
        Swal.fire({
            title: 'Kirim Barang?',
            text: "Stok gudang akan dikurangi sesuai jumlah yang dikirim.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kirim!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        })
    }

    document.addEventListener('DOMContentLoaded', startRequestStream);
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?= $_SESSION['swal']['icon'] ?>',
            title: '<?= $_SESSION['swal']['title'] ?>',
            text: '<?= $_SESSION['swal']['text'] ?>',
            timer: 3000,
            showConfirmButton: false
        });
    });
</script>
<?php unset($_SESSION['swal']); endif; ?>

<?php include '../layouts/admin/footer.php'; ?>
