<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login"); exit; }
require_once '../auth/koneksi.php';

$page_title = "Pesanan Manual (Kasir)";
$active_menu = "order_manual";

// --- PROSES MULAI PESANAN ---
if(isset($_POST['mulai_pesanan'])) {
    $meja_id = $_POST['meja_id'];
    $nama_pelanggan = $_POST['nama_pelanggan'];
    
    // Ambil info meja & cabang
    $m = $koneksi->query("SELECT m.nomor_meja, c.id as id_cabang, c.nama_cabang, m.status 
                          FROM meja m 
                          JOIN cabang c ON m.cabang_id = c.id 
                          WHERE m.id = '$meja_id'")->fetch_assoc();

    // Set Session Kasir
    $_SESSION['kasir_meja_id'] = $meja_id;
    $_SESSION['kasir_no_meja'] = $m['nomor_meja'];
    $_SESSION['kasir_cabang_id'] = $m['id_cabang']; 
    $_SESSION['kasir_nama_cabang'] = $m['nama_cabang'];

    // Jika meja kosong, pakai nama baru. Jika terisi (nambah), pakai nama yang sudah ada di DB (opsional)
    // Disini kita timpa saja nama pelanggannya sesuai input kasir (bisa sama/beda)
    $_SESSION['kasir_nama_pelanggan'] = $nama_pelanggan;
    
    header("Location: kasir_transaksi.php"); exit;
}

// --- PERSIAPAN DATA ---
$level = $_SESSION['level'];
$view_cabang = ($level == 'admin') ? ($_SESSION['view_cabang_id'] ?? 'pusat') : $_SESSION['cabang_id'];
$is_global = ($view_cabang == 'pusat');

// Ambil Cabang (Jika Admin Pusat)
$list_cabang = [];
if($is_global) {
    $q_cab = $koneksi->query("SELECT * FROM cabang");
    while($c = $q_cab->fetch_assoc()) $list_cabang[] = $c;
}

include '../layouts/admin/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card border-0 shadow-lg mt-4">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-cash-register me-2"></i>Pilih Meja Pelanggan</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="formMulai">
                    
                    <?php if($is_global): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted">Lokasi Cabang</label>
                            <select id="pilih_cabang" class="form-select form-select-lg" onchange="loadMeja(this.value)" required>
                                <option value="" selected disabled>-- Pilih Lokasi --</option>
                                <?php foreach($list_cabang as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php 
                        $target = ($level == 'admin') ? $view_cabang : $_SESSION['cabang_id'];
                        
                        // Query: Ambil SEMUA meja (Kosong & Terisi) di cabang ini
                        // Kecuali meja yang sudah di-reservasi orang lain di jam dekat
                        $now = date('Y-m-d H:i:s');
                        $sql_meja = "
                            SELECT m.*, 
                            (SELECT nama_pelanggan FROM transaksi WHERE meja_id = m.id AND status_pesanan != 'selesai' AND status_pesanan != 'cancel' ORDER BY id DESC LIMIT 1) as pelanggan_aktif
                            FROM meja m 
                            WHERE m.cabang_id = '$target' 
                            AND m.id NOT IN (
                                SELECT meja_id FROM reservasi 
                                WHERE status IN ('pending', 'checkin') 
                                AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 30 MINUTE) 
                                               AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
                            )
                            ORDER BY CAST(m.nomor_meja AS UNSIGNED) ASC
                        ";
                        
                        // Jika global, query dijalankan via AJAX/JS nanti, tapi struktur HTML disiapkan
                        if(!$is_global) {
                            $q_meja = $koneksi->query($sql_meja);
                        }
                    ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted">Pilih Meja</label>
                        <select name="meja_id" id="pilih_meja" class="form-select form-select-lg" required onchange="cekStatusMeja()">
                            <option value="" selected disabled>-- Pilih Meja --</option>
                            <?php if(!$is_global && $q_meja): ?>
                                <?php while($m = $q_meja->fetch_assoc()): ?>
                                    <?php 
                                        $status = $m['status'];
                                        $label = "Meja " . $m['nomor_meja'];
                                        $style = "";
                                        $pelanggan = "";
                                        
                                        if($status == 'terisi') {
                                            $pelanggan = $m['pelanggan_aktif'] ? " (" . $m['pelanggan_aktif'] . ")" : " (Terisi)";
                                            $label .= $pelanggan . " - [TAMBAH PESANAN]";
                                            $style = "background-color: #ffcccc; color: #a00;"; // Merah muda
                                        } else {
                                            $label .= " (Kosong)";
                                        }
                                    ?>
                                    <option value="<?= $m['id'] ?>" data-status="<?= $status ?>" data-pelanggan="<?= $m['pelanggan_aktif'] ?>" style="<?= $style ?>">
                                        <?= $label ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted">Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control form-control-lg" placeholder="Contoh: Budi" required>
                        <div id="info_tambah" class="form-text text-primary fw-bold mt-2" style="display:none;">
                            <i class="fas fa-info-circle"></i> Mode Tambah Pesanan: Nama otomatis terisi.
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="mulai_pesanan" class="btn btn-primary btn-lg fw-bold shadow-sm">
                            Lanjut ke Menu <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi Otomatis Isi Nama jika Tambah Pesanan (Hanya Lokal/Non-Global dulu)
function cekStatusMeja() {
    let select = document.getElementById('pilih_meja');
    let option = select.options[select.selectedIndex];
    let status = option.getAttribute('data-status');
    let pelanggan = option.getAttribute('data-pelanggan');
    let inputNama = document.getElementById('nama_pelanggan');
    let info = document.getElementById('info_tambah');

    if(status === 'terisi' && pelanggan) {
        inputNama.value = pelanggan;
        // inputNama.readOnly = true; // Opsional: Kunci nama biar gak berubah
        info.style.display = 'block';
    } else {
        inputNama.value = '';
        // inputNama.readOnly = false;
        info.style.display = 'none';
    }
}

<?php if($is_global): ?>
// LOGIKA KHUSUS ADMIN PUSAT (Load Ajax)
function loadMeja(cabangId) {
    const mejaSelect = document.getElementById('pilih_meja');
    mejaSelect.innerHTML = '<option>Memuat...</option>';
    mejaSelect.disabled = true;

    // Kita embed data PHP ke JS array untuk simulasi API
    <?php 
        $all_meja = [];
        $now = date('Y-m-d H:i:s');
        $q_all = $koneksi->query("
            SELECT m.id, m.cabang_id, m.nomor_meja, m.status,
            (SELECT nama_pelanggan FROM transaksi WHERE meja_id = m.id AND status_pesanan != 'selesai' AND status_pesanan != 'cancel' ORDER BY id DESC LIMIT 1) as pelanggan_aktif
            FROM meja m
            WHERE m.id NOT IN (
                SELECT meja_id FROM reservasi 
                WHERE status IN ('pending', 'checkin') 
                AND '$now' BETWEEN DATE_SUB(waktu_reservasi, INTERVAL 30 MINUTE) AND DATE_ADD(waktu_reservasi, INTERVAL durasi_menit MINUTE)
            )
            ORDER BY CAST(nomor_meja AS UNSIGNED) ASC
        ");
        while($am = $q_all->fetch_assoc()) $all_meja[] = $am;
    ?>
    const dbMeja = <?= json_encode($all_meja) ?>;
    
    let html = '<option value="" selected disabled>-- Pilih Meja --</option>';
    const filtered = dbMeja.filter(m => m.cabang_id == cabangId);
    
    if(filtered.length === 0) {
        html = '<option value="" disabled>Tidak ada meja tersedia</option>';
    } else {
        filtered.forEach(m => {
            let label = `Meja ${m.nomor_meja}`;
            let style = "";
            let pNama = m.pelanggan_aktif || "";
            
            if(m.status === 'terisi') {
                label += ` (${pNama}) - [TAMBAH]`;
                style = "background-color: #ffcccc; color: #a00;";
            } else {
                label += " (Kosong)";
            }
            
            html += `<option value="${m.id}" data-status="${m.status}" data-pelanggan="${pNama}" style="${style}">${label}</option>`;
        });
    }
    
    mejaSelect.innerHTML = html;
    mejaSelect.disabled = false;
}
<?php endif; ?>
</script>

<?php include '../layouts/admin/footer.php'; ?>