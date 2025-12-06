<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pesanan Manual (Kasir)";
$active_menu = "order_manual";

if(isset($_POST['mulai_pesanan'])) {
    $_SESSION['kasir_nama_pelanggan'] = $_POST['nama_pelanggan'];
    $_SESSION['kasir_meja_id'] = $_POST['meja_id'];
    
    $m = $koneksi->query("SELECT m.nomor_meja, c.id as id_cabang, c.nama_cabang 
                          FROM meja m 
                          JOIN cabang c ON m.cabang_id = c.id 
                          WHERE m.id = '".$_POST['meja_id']."'")->fetch_assoc();
                          
    $_SESSION['kasir_no_meja'] = $m['nomor_meja'];
    $_SESSION['kasir_cabang_id'] = $m['id_cabang']; 
    $_SESSION['kasir_nama_cabang'] = $m['nama_cabang'];
    
    header("Location: kasir_transaksi.php"); exit;
}

$level = $_SESSION['level'];
$view_cabang = ($level == 'admin') ? ($_SESSION['view_cabang_id'] ?? 'pusat') : $_SESSION['cabang_id'];
$is_global = ($view_cabang == 'pusat');

$list_cabang = [];
if($is_global) {
    $q_cab = $koneksi->query("SELECT * FROM cabang");
    while($c = $q_cab->fetch_assoc()) $list_cabang[] = $c;
}

include '../layouts/admin/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-lg mt-4">
            <div class="card-header bg-primary text-white text-center py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-cash-register me-2"></i>Mulai Pesanan Baru</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="formMulai">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" class="form-control form-control-lg" placeholder="Contoh: Budi" required>
                    </div>

                    <?php if($is_global): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Pilih Cabang</label>
                            <select id="pilih_cabang" class="form-select form-select-lg" onchange="loadMeja(this.value)" required>
                                <option value="" selected disabled>-- Pilih Lokasi --</option>
                                <?php foreach($list_cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Pilih Meja</label>
                            <select name="meja_id" id="pilih_meja" class="form-select form-select-lg" disabled required>
                                <option value="">-- Pilih Cabang Dulu --</option>
                            </select>
                        </div>
                    <?php else: ?>
                        <?php 
                            // [FIX FILTER MEJA AGESIF]
                            $target = ($level == 'admin') ? $view_cabang : $_SESSION['cabang_id'];
                            $now = date('Y-m-d H:i:s');
                            
                            // Cari Meja Kosong DAN Tidak ada Reservasi Aktif (H-15 s/d Durasi Selesai)
                            $q_meja = $koneksi->query("
                                SELECT * FROM meja m 
                                WHERE m.cabang_id = '$target' 
                                AND m.status = 'kosong'
                                AND m.id NOT IN (
                                    SELECT meja_id FROM reservasi 
                                    WHERE status IN ('pending', 'checkin') 
                                    AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 15 MINUTE) 
                                                   AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
                                )
                                ORDER BY CAST(m.nomor_meja AS UNSIGNED) ASC
                            ");
                        ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted">Pilih Meja Kosong</label>
                            <select name="meja_id" class="form-select form-select-lg" required>
                                <option value="" selected disabled>-- Pilih Meja --</option>
                                <?php if($q_meja->num_rows > 0): ?>
                                    <?php while($m = $q_meja->fetch_assoc()): ?>
                                        <option value="<?= $m['id'] ?>">Meja <?= $m['nomor_meja'] ?></option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>Semua meja penuh / direservasi!</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <button type="submit" name="mulai_pesanan" class="btn btn-primary btn-lg fw-bold shadow-sm">
                            Buka Kasir <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if($is_global): ?>
<script>
function loadMeja(cabangId) {
    const mejaSelect = document.getElementById('pilih_meja');
    mejaSelect.innerHTML = '<option>Memuat...</option>';
    mejaSelect.disabled = true;

    // [FIX JS FILTER]
    <?php 
        $all_meja = [];
        $now = date('Y-m-d H:i:s');
        $q_all = $koneksi->query("
            SELECT id, cabang_id, nomor_meja FROM meja m
            WHERE status = 'kosong'
            AND m.id NOT IN (
                SELECT meja_id FROM reservasi 
                WHERE status IN ('pending', 'checkin') 
                AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 15 MINUTE) AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
            )
            ORDER BY CAST(nomor_meja AS UNSIGNED) ASC
        ");
        while($am = $q_all->fetch_assoc()) $all_meja[] = $am;
    ?>
    const dbMeja = <?= json_encode($all_meja) ?>;
    
    let html = '<option value="" selected disabled>-- Pilih Meja --</option>';
    const filtered = dbMeja.filter(m => m.cabang_id == cabangId);
    
    if(filtered.length === 0) html = '<option value="" disabled>Semua meja penuh/reservasi</option>';
    else filtered.forEach(m => { html += `<option value="${m.id}">Meja ${m.nomor_meja}</option>`; });
    
    mejaSelect.innerHTML = html;
    mejaSelect.disabled = false;
}
</script>
<?php endif; ?>

<?php include '../layouts/admin/footer.php'; ?>