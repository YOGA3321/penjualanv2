<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }

require_once '../auth/koneksi.php';

$page_title = "Manajemen Meja";
$active_menu = "meja";
$level = $_SESSION['level'] ?? '';
$user_cabang = $_SESSION['cabang_id'] ?? 0;

$view_cabang_id = "";
if ($level == 'admin') {
    $view_cabang_id = $_SESSION['view_cabang_id'] ?? 'pusat'; 
    if ($view_cabang_id == 'pusat') $view_cabang_id = "";
} else {
    $view_cabang_id = $user_cabang;
}

// --- LOGIKA TAMBAH/HAPUS (Tetap Sama, dipersingkat) ---
if (isset($_POST['tambah_meja'])) {
    $jumlah = (int)$_POST['jumlah_meja'];
    $target = $_POST['cabang_id']; 
    if (empty($target)) {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Cabang belum dipilih!'];
    } elseif ($jumlah < 1) {
        $_SESSION['swal'] = ['icon'=>'warning', 'title'=>'Gagal', 'text'=>'Jumlah minimal 1'];
    } else {
        $q = $koneksi->query("SELECT MAX(CAST(nomor_meja AS UNSIGNED)) as max FROM meja WHERE cabang_id = '$target'");
        $row = $q->fetch_assoc();
        $start = ($row['max']) ? $row['max'] + 1 : 1;
        $stmt = $koneksi->prepare("INSERT INTO meja (cabang_id, nomor_meja, qr_token, status) VALUES (?, ?, ?, 'kosong')");
        for ($i = 0; $i < $jumlah; $i++) {
            $nomor = $start + $i;
            $token = md5(uniqid(rand(), true));
            $stmt->bind_param("iss", $target, $nomor, $token);
            $stmt->execute();
        }
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Berhasil', 'text'=>"$jumlah Meja ditambahkan"];
    }
    header("Location: meja"); exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $cek = $koneksi->query("SELECT status FROM meja WHERE id = '$id'")->fetch_assoc();
    if ($cek['status'] == 'terisi') {
        $_SESSION['swal'] = ['icon'=>'error', 'title'=>'Gagal', 'text'=>'Meja sedang terisi!'];
    } else {
        $koneksi->query("DELETE FROM meja WHERE id = '$id'");
        $_SESSION['swal'] = ['icon'=>'success', 'title'=>'Terhapus', 'text'=>'Meja dihapus'];
    }
    header("Location: meja"); exit;
}

$cabangs = $koneksi->query("SELECT * FROM cabang");

include '../layouts/admin/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-chair me-2"></i>Manajemen Meja</h4>
        
        <?php if($level == 'admin'): ?>
            <select id="filterCabang" class="form-select form-select-sm" style="width:auto;" onchange="updateView(this.value)">
                <option value="" <?= $view_cabang_id == "" ? 'selected' : '' ?>>Semua Cabang</option>
                <?php foreach($cabangs as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $view_cabang_id == $c['id'] ? 'selected' : '' ?>><?= $c['nama_cabang'] ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <span class="badge bg-secondary"><?= $_SESSION['cabang_name'] ?></span>
            <input type="hidden" id="filterCabang" value="<?= $user_cabang ?>">
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fas fa-plus-circle me-2"></i> Tambah Meja
        </button>
        <a href="cetak_qr_all.php" target="_blank" class="btn btn-dark fw-bold shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak QR
        </a>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3 bg-white p-2 rounded shadow-sm">
    <button class="btn btn-sm btn-outline-primary" id="btnPrev" onclick="changePage(-1)" disabled><i class="fas fa-chevron-left"></i> Sebelumnya</button>
    <span class="small fw-bold text-muted" id="pageInfo">Halaman 1</span>
    <button class="btn btn-sm btn-outline-primary" id="btnNext" onclick="changePage(1)" disabled>Selanjutnya <i class="fas fa-chevron-right"></i></button>
</div>

<div class="row g-3" id="meja-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat data meja...</p>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Tambah Meja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Lokasi Cabang</label>
                    <?php if($level == 'admin' && $view_cabang_id == ""): ?>
                        <select name="cabang_id" class="form-select" required>
                            <option value="" disabled selected>-- Pilih Cabang --</option>
                            <?php foreach($cabangs as $c): echo "<option value='".$c['id']."'>".$c['nama_cabang']."</option>"; endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php 
                            $nama_cabang_aktif = $_SESSION['cabang_name'] ?? 'Cabang Terpilih';
                            foreach($cabangs as $c) { if($c['id'] == $view_cabang_id) $nama_cabang_aktif = $c['nama_cabang']; }
                        ?>
                        <input type="text" class="form-control bg-light" value="<?= $nama_cabang_aktif ?>" readonly>
                        <input type="hidden" name="cabang_id" value="<?= $view_cabang_id ?>">
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Jumlah Penambahan</label>
                    <input type="number" name="jumlah_meja" class="form-control text-center fw-bold" value="1" min="1" max="50" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="tambah_meja" class="btn btn-primary fw-bold w-100">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0">
                <h5 id="qrTitle" class="fw-bold mb-0"></h5>
                <p id="qrSub" class="text-muted small mb-3"></p>
                <div id="qrBig" class="d-flex justify-content-center mb-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
    let eventSource = null;
    let currentPage = 1;
    let totalPages = 1;

    function updateView(id) {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = 'setter_cabang.php';
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'cabang_tujuan'; input.value = id || 'pusat';
        form.appendChild(input); document.body.appendChild(form); form.submit();
    }

    // Fungsi Ganti Halaman
    function changePage(step) {
        currentPage += step;
        startRealtimeUpdates(); // Restart SSE dengan halaman baru
    }

    function startRealtimeUpdates() {
        const filterEl = document.getElementById('filterCabang');
        const cabangId = filterEl ? filterEl.value : '';

        if (eventSource) eventSource.close();

        // Kirim parameter PAGE ke server
        eventSource = new EventSource(`api/sse_meja.php?cabang_id=${cabangId}&page=${currentPage}`);
        
        eventSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            const container = document.getElementById('meja-container');
            
            if(result.status === 'success') {
                // Update Pagination Info
                totalPages = result.pagination.total_pages;
                currentPage = result.pagination.current_page; // Sinkronkan
                
                document.getElementById('pageInfo').innerText = `Halaman ${currentPage} dari ${totalPages || 1}`;
                document.getElementById('btnPrev').disabled = (currentPage <= 1);
                document.getElementById('btnNext').disabled = (currentPage >= totalPages);

                if(result.data.length === 0) {
                    container.innerHTML = `<div class="col-12 text-center py-5 text-muted">Belum ada meja.</div>`;
                    return;
                }

                let html = '';
                result.data.forEach(row => {
                    const isTerisi = row.status === 'terisi';
                    const color = isTerisi ? 'danger' : 'success';
                    const statusText = isTerisi ? 'BUSY' : 'FREE';
                    
                    // [LOGIKA NAMA CABANG]
                    // Tampilkan hanya jika filter "Semua Cabang" dipilih (cabangId kosong)
                    let showCabang = (cabangId === "") ? `<small class="text-muted d-block text-truncate mb-2">${row.nama_cabang}</small>` : '';

                    let btnAction = '';
                    if(isTerisi) {
                        btnAction = `<button class="btn btn-warning btn-sm w-100 text-white fw-bold" onclick="kosongkan('${row.id}', '${row.nomor_meja}')"><i class="fas fa-eraser"></i> Reset</button>`;
                    } else {
                        btnAction = `<button class="btn btn-outline-danger btn-sm w-100" onclick="hapus('${row.id}')"><i class="fas fa-trash"></i> Hapus</button>`;
                    }

                    html += `
                    <div class="col-6 col-md-4 col-lg-3 col-xl-2 fade-in">
                        <div class="card border-0 shadow-sm h-100 position-relative">
                            <div class="position-absolute top-0 end-0 p-2">
                                <span class="badge bg-${color}">${statusText}</span>
                            </div>
                            <div class="card-body text-center pt-4 pb-2 px-2">
                                <i class="fas fa-chair fa-3x text-${color} mb-2 mt-2"></i>
                                <h3 class="fw-bold mb-0">${row.nomor_meja}</h3>
                                ${showCabang}
                                
                                <div class="bg-white border rounded p-1 mb-2 d-inline-block" 
                                     style="cursor:zoom-in" 
                                     onclick="showQR('${row.qr_token}', 'Meja ${row.nomor_meja}', '${row.nama_cabang}')">
                                    <div id="qr-mini-${row.id}"></div>
                                </div>
                                
                                ${btnAction}
                            </div>
                        </div>
                    </div>`;
                    
                    setTimeout(() => {
                        const el = document.getElementById(`qr-mini-${row.id}`);
                        if(el && el.innerHTML === '') {
                            new QRCode(el, {
                                text: row.qr_url, // Pakai URL dari backend
                                width: 50, height: 50, correctLevel: QRCode.CorrectLevel.L
                            });
                        }
                    }, 100);
                });
                container.innerHTML = html;
            }
        };
    }

    // Fungsi Zoom QR
    function showQR(token, title, sub) {
        document.getElementById('qrTitle').innerText = title;
        document.getElementById('qrSub').innerText = sub;
        document.getElementById('qrBig').innerHTML = '';
        new QRCode(document.getElementById('qrBig'), {
            text: "<?= BASE_URL ?>/penjualan/index.php?token=" + token,
            width: 180, height: 180
        });
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }

    function kosongkan(id, nama) {
        Swal.fire({
            title: `Reset Meja ${nama}?`, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya'
        }).then((res) => {
            if(res.isConfirmed) {
                let fd = new FormData(); fd.append('action', 'kosongkan_meja'); fd.append('id', id);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                    if(d.status==='success') Swal.fire('Berhasil', '', 'success');
                });
            }
        });
    }

    function hapus(id) {
        Swal.fire({
            title: 'Hapus Meja?', icon: 'error', showCancelButton: true, confirmButtonText: 'Hapus', confirmButtonColor: '#d33'
        }).then((res) => {
            if(res.isConfirmed) window.location.href = "?hapus=" + id;
        });
    }

    document.addEventListener('DOMContentLoaded', startRealtimeUpdates);
</script>

<style>.fade-in { animation: fadeIn 0.5s ease-in-out; }</style>
<?php include '../layouts/admin/footer.php'; ?>