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

// Ambil list request pending / diproses
$sql = "SELECT r.*, c.nama_cabang, u.nama as nama_user 
        FROM request_stok r 
        JOIN cabang c ON r.cabang_id = c.id
        JOIN users u ON r.user_id = u.id
        WHERE r.status IN ('pending', 'diproses')
        ORDER BY r.created_at ASC";
$requests = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<h4 class="fw-bold text-primary mb-4"><i class="fas fa-inbox me-2"></i>Permintaan Masuk (Pending)</h4>

<div class="row">
    <?php if ($requests->num_rows > 0): ?>
        <?php foreach($requests as $req): ?>
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm border-top border-primary border-4 h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h6 class="fw-bold mb-0 text-primary"><?= $req['kode_request'] ?></h6>
                        <small class="text-muted"><i class="fas fa-clock me-1"></i> <?= $req['created_at'] ?></small>
                    </div>
                    <span class="badge bg-warning text-dark text-uppercase"><?= $req['status'] ?></span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block uppercase fw-bold" style="font-size:0.7em">PEMOHON</small>
                        <span class="fw-bold fs-5"><?= $req['nama_cabang'] ?></span><br>
                        <small class="text-muted">User: <?= $req['nama_user'] ?></small>
                    </div>
                    <?php if($req['catatan_cabang']): ?>
                    <div class="alert alert-light border small mb-3">
                        <strong>Catatan:</strong> <?= $req['catatan_cabang'] ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
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
                                    <?php 
                                    $req_id = $req['id'];
                                    $details = $koneksi->query("SELECT rd.*, g.nama_item, g.stok as stok_gudang, g.satuan 
                                                                FROM request_detail rd 
                                                                JOIN gudang_items g ON rd.item_id = g.id 
                                                                WHERE rd.request_id='$req_id'");
                                    foreach($details as $d):
                                        // Default kirim = minta, tapi tidak boleh lebih dari stok gudang
                                        $default_kirim = min($d['qty_minta'], $d['stok_gudang']);
                                    ?>
                                    <tr>
                                        <td>
                                            <?= $d['nama_item'] ?> <br>
                                            <small class="text-muted">Stok Gudang: <?= $d['stok_gudang'] ?> <?= $d['satuan'] ?></small>
                                        </td>
                                        <td class="text-center fw-bold align-middle"><?= $d['qty_minta'] ?></td>
                                        <td>
                                            <input type="number" name="details[<?= $d['id'] ?>]" class="form-control form-control-sm border-primary fw-bold" value="<?= $default_kirim ?>" min="0" max="<?= $d['stok_gudang'] ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mb-3">
                           <label class="small fw-bold">Catatan Pengiriman / No. Resi</label>
                           <textarea name="catatan_gudang" class="form-control" rows="2" placeholder="Tulis pesan untuk cabang..."></textarea>
                        </div>

                        <button type="submit" name="kirim_barang" class="btn btn-primary w-100 fw-bold" onclick="return confirm('Sudah yakin dengan jumlah yang dikirim? Stok gudang akan berkurang.')">
                            <i class="fas fa-paper-plane me-2"></i> Proses & Kirim Barang
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <div class="text-muted mb-3"><i class="fas fa-check-circle fa-4x"></i></div>
            <h5 class="text-muted">Tidak ada permintaan pending saat ini.</h5>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/admin/footer.php'; ?>
