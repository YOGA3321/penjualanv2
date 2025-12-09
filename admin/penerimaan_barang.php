<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['level'] != 'admin') { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Penerimaan Barang";
$active_menu = "penerimaan_barang";

// --- PROSES TERIMA BARANG ---
if (isset($_POST['terima_barang'])) {
    $req_id = $_POST['request_id'];
    $details = $_POST['terima']; // Array id => qty_terima
    $selesai_flag = true;
    
    foreach ($details as $det_id => $qty_terima) {
        $qty_terima = (int)$qty_terima;
        
        // Cek qty_kirim
        $q_cek = $koneksi->query("SELECT qty_kirim, item_id FROM request_detail WHERE id='$det_id'")->fetch_assoc();
        $qty_kirim = $q_cek['qty_kirim'];
        
        $status_item = 'sesuai';
        if ($qty_terima < $qty_kirim) $status_item = 'kurang';
        elseif ($qty_terima > $qty_kirim) $status_item = 'lebih';
        
        // Update Detail
        $koneksi->query("UPDATE request_detail SET qty_terima='$qty_terima', status_item='$status_item' WHERE id='$det_id'");
        
        // Optional: Update Stok Lokal Cabang here?
        // Note: Currently we don't have separate `cabang_stok` table. 
        // If items map directly to `menu` (product), we could update `menu.stok`.
        // But `gudang_items` might be raw materials (bahan baku) which are not in `menu`.
        // So for now, we just record receipt.
    }
    
    // Update Header
    $tanggal_terima = date('Y-m-d H:i:s');
    $koneksi->query("UPDATE request_stok SET status='selesai', tanggal_terima='$tanggal_terima' WHERE id='$req_id'");
    
    $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Selesai', 'text'=>'Barang telah diterima dan diverifikasi'];
    header("Location: penerimaan_barang.php"); exit;
}

// List Request 'Dikirim' untuk Cabang Ini
$cabang_id = $_SESSION['view_cabang_id'] ?? $_SESSION['cabang_id'];
if($cabang_id == 'pusat') $cabang_id = 1;

$requests = $koneksi->query("SELECT r.*, u.nama as pengirim FROM request_stok r LEFT JOIN users u ON r.user_id = u.id WHERE r.cabang_id='$cabang_id' AND r.status='dikirim'");

include '../layouts/admin/header.php';
?>

<div class="row">
    <div class="col-12 mb-4">
        <h4 class="fw-bold text-primary"><i class="fas fa-check-double me-2"></i>Verifikasi Penerimaan Barang</h4>
        <p class="text-muted">Pastikan menghitung fisik barang dengan teliti sebelum konfirmasi.</p>
    </div>

    <?php if ($requests->num_rows > 0): ?>
        <?php foreach($requests as $req): ?>
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><?= $req['kode_request'] ?></h5>
                            <span class="text-muted small">Dikirim: <?= $req['tanggal_kirim'] ?></span>
                        </div>
                        <span class="badge bg-info align-self-center">SEDANG DIKIRIM</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if($req['catatan_gudang']): ?>
                        <div class="alert alert-info small mb-3"><i class="fas fa-sticky-note me-2"></i><strong>Pesan Gudang:</strong> <?= $req['catatan_gudang'] ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                        <div class="table-responsive mb-3 border rounded">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th class="text-center">Qty Minta</th>
                                        <th class="text-center text-primary">Qty Dikirim (Surat Jalan)</th>
                                        <th width="150" class="text-center bg-warning bg-opacity-10">Qty Diterima (Fisik)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $req_id = $req['id'];
                                    $details = $koneksi->query("SELECT rd.*, g.nama_item, g.satuan FROM request_detail rd JOIN gudang_items g ON rd.item_id = g.id WHERE rd.request_id='$req_id'");
                                    foreach($details as $d):
                                    ?>
                                    <tr>
                                        <td><?= $d['nama_item'] ?> <span class="text-muted small">(<?= $d['satuan'] ?>)</span></td>
                                        <td class="text-center text-muted"><?= $d['qty_minta'] ?></td>
                                        <td class="text-center fw-bold text-primary fs-5"><?= $d['qty_kirim'] ?></td>
                                        <td class="bg-warning bg-opacity-10">
                                            <input type="number" name="terima[<?= $d['id'] ?>]" class="form-control fw-bold text-center border-warning" value="<?= $d['qty_kirim'] ?>" required min="0">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end">
                            <button type="submit" name="terima_barang" class="btn btn-success fw-bold px-5" onclick="return confirm('Apakah jumlah fisik sudah sesuai? Data tidak bisa diubah setelah disimpan.')">
                                <i class="fas fa-save me-2"></i> Konfirmasi Penerimaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 py-5 text-center text-muted">
            <i class="fas fa-truck-loading fa-3x mb-3 opacity-50"></i>
            <h5>Tidak ada pengiriman yang perlu diverifikasi.</h5>
        </div>
    <?php endif; ?>
</div>

<?php include '../layouts/admin/header.php'; ?>
