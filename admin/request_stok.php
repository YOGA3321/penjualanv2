<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Request Stok ke Gudang";
$active_menu = "request_stok";

// --- PROSES REQUEST ---
if (isset($_POST['submit_request'])) {
    $cabang_id = $_SESSION['cabang_id'];
    // Jika admin global (pusat) tapi tidak set view_cabang, assume cabang_id = 1 (pusat) or deny??
    // Asumsi: Admin request untuk cabang yang sedang dia view atau login.
    if (!isset($cabang_id) && isset($_SESSION['view_cabang_id'])) {
        $cabang_id = $_SESSION['view_cabang_id'];
    }
    // Jika 'pusat' yang dipilih di filter, kita anggap request untuk operasional pusat (jika pusat adalah cabang)
    if ($cabang_id == 'pusat') $cabang_id = 1; // Fallback hardcode OR handle error. Let's assume ID 1 is Pusat.

    $user_id = $_SESSION['user_id'];
    $catatan = $_POST['catatan'];
    $kode_req = "REQ-" . date("ymdHis") . "-" . $cabang_id;
    
    // 1. Insert Header
    $stmt = $koneksi->prepare("INSERT INTO request_stok (kode_request, cabang_id, user_id, status, catatan_cabang) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("siis", $kode_req, $cabang_id, $user_id, $catatan);
    $stmt->execute();
    $req_id = $stmt->insert_id;
    
    // 2. Insert Details
    $items = $_POST['items']; // array item_id
    $qtys = $_POST['qtys'];   // array qty
    
    for ($i = 0; $i < count($items); $i++) {
        $i_id = $items[$i];
        $i_qty = $qtys[$i];
        if ($i_qty > 0) {
            $koneksi->query("INSERT INTO request_detail (request_id, item_id, qty_minta) VALUES ('$req_id', '$i_id', '$i_qty')");
        }
    }
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terkirim', 'text'=>'Permintaan stok telah dikirim ke Gudang'];
    header("Location: request_stok.php"); exit;
}

// History Request Cabang Ini
$cabang_filter = $_SESSION['view_cabang_id'] ?? $_SESSION['cabang_id'];
if($cabang_filter == 'pusat') $cabang_filter = 1;

$history = $koneksi->query("SELECT * FROM request_stok WHERE cabang_id = '$cabang_filter' ORDER BY CASE WHEN status IN ('pending', 'dikirim') THEN 0 ELSE 1 END ASC, created_at DESC LIMIT 20");
$gudang_items = $koneksi->query("SELECT * FROM gudang_items ORDER BY nama_item ASC");

include '../layouts/admin/header.php';
?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white fw-bold"><i class="fas fa-plus-circle me-2"></i>Buat Permintaan Baru</div>
            <div class="card-body">
                <form method="POST" id="formRequest">
                    <div class="mb-3">
                        <label>Catatan Tambahan</label>
                        <textarea name="catatan" class="form-control" rows="2" placeholder="Contoh: Sangat mendesak, tolong dikirim pagi."></textarea>
                    </div>
                    
                    <label class="fw-bold mb-2">Pilih Barang:</label>
                    <div id="items-container">
                        <div class="row g-2 mb-2 item-row">
                            <div class="col-7">
                                <select name="items[]" class="form-select" required>
                                    <option value="" disabled selected>-- Pilih Item --</option>
                                    <?php foreach($gudang_items as $gi): ?>
                                    <option value="<?= $gi['id'] ?>"><?= $gi['nama_item'] ?> (<?= $gi['satuan'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <input type="number" name="qtys[]" class="form-control" placeholder="Qty" required min="1">
                            </div>
                            <div class="col-2">
                                <button type="button" class="btn btn-danger w-100" onclick="removeItem(this)"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-3 w-100 dashed-border" onclick="addItem()">
                        <i class="fas fa-plus me-1"></i> Tambah Item Lain
                    </button>
                    
                    <button type="submit" name="submit_request" class="btn btn-primary w-100 fw-bold py-2">
                        <i class="fas fa-paper-plane me-2"></i> Kirim Permintaan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <h5 class="fw-bold text-secondary mb-3"><i class="fas fa-history me-2"></i>Riwayat Permintaan</h5>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Kode Request</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <?php foreach($history as $h): 
                                $badge = 'bg-secondary';
                                if($h['status']=='pending') $badge='bg-warning';
                                elseif($h['status']=='dikirim') $badge='bg-info';
                                elseif($h['status']=='selesai') $badge='bg-success';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-primary"><?= $h['kode_request'] ?></td>
                                <td class="small text-muted"><?= date('d M Y H:i', strtotime($h['created_at'])) ?></td>
                                <td><span class="badge <?= $badge ?> text-uppercase"><?= $h['status'] ?></span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border" onclick="showDetail(<?= $h['id'] ?>)">Detail</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Row Script & Realtime Info -->
<script>
    // --- REALTIME LISTENER ---
    // Menggunakan existing global connection dari footer jika ada, atau buat baru?
    // Footer header.php membuat 'globalEventSource'. Kita bisa tap in.
    
    document.addEventListener('DOMContentLoaded', function() {
        // Cek apakah globalEventSource tersedia (dari footer)
        // Karena ini script inline, mungkin perlu delay sedikit atau attach ke window
        
        // Kita buat listener spesifik saja agar aman
        const sseUrl = 'api/sse_channel.php?cabang_id=<?= $cabang_filter ?>'; 
        const statusSource = new EventSource(sseUrl);
        
        statusSource.onmessage = function(event) {
            const data = JSON.parse(event.data);
            
            if(data.request_history) {
                renderHistory(data.request_history);
            }
        };
    });

    function renderHistory(data) {
        const tbody = document.getElementById('historyTableBody');
        let html = '';
        
        if (data.length === 0) {
             html = '<tr><td colspan="4" class="text-center py-3 text-muted">Belum ada riwayat.</td></tr>';
        } else {
            data.forEach(h => {
                let badge = 'bg-secondary';
                if(h.status === 'pending') badge = 'bg-warning text-dark';
                else if(h.status === 'dikirim') badge = 'bg-info text-white';
                else if(h.status === 'selesai') badge = 'bg-success';
                
                // Format Date (Simple)
                // Assuming created_at is YYYY-MM-DD HH:MM:SS
                let dateStr = h.created_at; 
                
                html += `<tr>
                    <td class="ps-4 fw-bold text-primary">${h.kode_request}</td>
                    <td class="small text-muted">${dateStr}</td>
                    <td><span class="badge ${badge} text-uppercase">${h.status}</span></td>
                    <td class="text-end pe-4">
                        <button class="btn btn-sm btn-light border" onclick="showDetail(${h.id})">Detail</button>
                    </td>
                </tr>`;
            });
        }
        tbody.innerHTML = html;
    }

    // Clone first option list logic needed? simplenya clone innerhTML
    const itemOptions = `<?php foreach($gudang_items as $gi): ?><option value="<?= $gi['id'] ?>"><?= $gi['nama_item'] ?> (<?= $gi['satuan'] ?>)</option><?php endforeach; ?>`;

    function addItem() {
        const div = document.createElement('div');
        div.className = 'row g-2 mb-2 item-row';
        div.innerHTML = `
            <div class="col-7">
                <select name="items[]" class="form-select" required>
                    <option value="" disabled selected>-- Pilih Item --</option>
                    ${itemOptions}
                </select>
            </div>
            <div class="col-3">
                <input type="number" name="qtys[]" class="form-control" placeholder="Qty" required min="1">
            </div>
            <div class="col-2">
                <button type="button" class="btn btn-danger w-100" onclick="removeItem(this)"><i class="fas fa-times"></i></button>
            </div>
        `;
        document.getElementById('items-container').appendChild(div);
    }

    function removeItem(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length > 1) {
            btn.closest('.item-row').remove();
        } else {
            alert("Minimal satu item harus ada.");
        }
    }

    function showDetail(id) {
        // Simple alert or modal for detail - optional for now
        Swal.fire({ text: 'Detail request #' + id + ' (Fitur detail lengkap menyusul)', icon: 'info' });
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>
