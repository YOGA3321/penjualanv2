<?php
session_start();
// 1. CEK LOGIN (KEAMANAN)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit;
}

require_once '../auth/koneksi.php';

$page_title = "Manajemen Meja";
$active_menu = "meja";
$level = $_SESSION['level'] ?? '';
$user_cabang = $_SESSION['cabang_id'] ?? 0;

// --- LOGIKA GENERATE MEJA (BULK INSERT) ---
// Kita tetap pakai POST biasa untuk aksi tambah/hapus karena hanya sesekali
if (isset($_POST['tambah_meja'])) {
    $jumlah_generate = (int)$_POST['jumlah_meja']; 
    $cabang_target = ($level == 'admin') ? $_POST['cabang_id'] : $user_cabang;
    
    if ($jumlah_generate < 1) {
         $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Jumlah minimal 1!'];
    } else {
        // Cari nomor terakhir
        $query_max = $koneksi->query("SELECT MAX(CAST(nomor_meja AS UNSIGNED)) as max_no FROM meja WHERE cabang_id = '$cabang_target'");
        $row_max = $query_max->fetch_assoc();
        $last_number = ($row_max['max_no'] == null) ? 0 : (int)$row_max['max_no'];

        $berhasil = 0;
        $stmt = $koneksi->prepare("INSERT INTO meja (cabang_id, nomor_meja, qr_token, status) VALUES (?, ?, ?, 'kosong')");
        
        for ($i = 1; $i <= $jumlah_generate; $i++) {
            $next_number = $last_number + $i;
            $token = bin2hex(random_bytes(16));
            $stmt->bind_param("iss", $cabang_target, $next_number, $token);
            if ($stmt->execute()) { $berhasil++; }
        }

        if ($berhasil > 0) {
             $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Selesai!', 'text' => "Berhasil generate $berhasil meja baru."];
        } else {
             $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Gagal membuat meja.'];
        }
    }
    header("Location: meja"); exit;
}

// --- DELETE ---
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $koneksi->query("DELETE FROM meja WHERE id = '$id_hapus'");
    $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Terhapus', 'text' => 'Data meja dihapus.'];
    header("Location: meja"); exit;
}

// Ambil Data Cabang untuk Dropdown Filter Admin
$cabangs = $koneksi->query("SELECT * FROM cabang");

$header_action_btn = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mejaModal"><i class="fas fa-plus me-2"></i>Generate Meja</button>';

include '../layouts/admin/header.php';
?>

<div class="card mb-4">
    <div class="card-body d-flex align-items-center">
         <?php if($level == 'admin'): ?>
             <label for="filterCabang" class="form-label me-3 mb-0">Filter Cabang:</label>
             <select class="form-select w-auto" id="filterCabang" onchange="startRealtimeUpdates()">
                <option value="">Semua Cabang</option>
                <?php foreach($cabangs as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                <?php endforeach; ?>
            </select>
         <?php else: ?>
             <span class="text-muted"><i class="fas fa-info-circle me-2"></i>Menampilkan meja untuk <strong><?= $_SESSION['cabang_name'] ?></strong></span>
             <input type="hidden" id="filterCabang" value="<?= $user_cabang ?>">
         <?php endif; ?>
         
         <div class="ms-auto small">
             Status: <span id="connectionStatus" class="badge bg-secondary">Connecting...</span>
         </div>
    </div>
</div>

<div class="row" id="meja-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Menghubungkan ke server...</p>
    </div>
</div>

<div class="modal fade" id="mejaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Generate Meja Otomatis</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Generate Otomatis</label>
                        <div class="input-group">
                             <span class="input-group-text">Tambahkan</span>
                             <input type="number" name="jumlah_meja" class="form-control text-center" value="1" min="1" max="50" required>
                             <span class="input-group-text">Meja Baru</span>
                        </div>
                        <small class="text-muted">Sistem akan melanjutkan nomor meja terakhir.</small>
                    </div>
                     <div class="mb-3">
                        <label class="form-label">Lokasi Cabang</label>
                        <?php if($level == 'admin'): ?>
                            <select class="form-select" name="cabang_id" required>
                                <?php $cabangs->data_seek(0); while($c = $cabangs->fetch_assoc()): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['nama_cabang'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control bg-light" value="<?= $_SESSION['cabang_name'] ?>" readonly>
                            <input type="hidden" name="cabang_id" value="<?= $user_cabang ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_meja" class="btn btn-primary">Proses</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0">
                <h5 id="qrMejaTitle" class="mb-1 fw-bold">Meja X</h5>
                <p id="qrCabangTitle" class="text-muted small mb-3">Cabang X</p>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <p class="small text-muted" style="font-size: 10px; word-break: break-all;" id="urlText"></p>
                <button class="btn btn-primary btn-sm w-100" onclick="window.print()"><i class="fas fa-print me-2"></i> Cetak Label</button>
            </div>
        </div>
    </div>
</div>

<script>
    let eventSource = null; // Variabel global untuk koneksi

    function startRealtimeUpdates() {
        // 1. Ambil ID Cabang yang dipilih/aktif
        const cabangId = document.getElementById('filterCabang').value;
        const statusBadge = document.getElementById('connectionStatus');

        // 2. Jika ada koneksi lama, putus dulu agar tidak numpuk
        if (eventSource) {
            eventSource.close();
            console.log("Koneksi lama ditutup.");
        }

        // 3. Buka Koneksi Baru ke API SSE
        // Pastikan path 'api/sse_meja.php' sudah benar sesuai struktur folder
        eventSource = new EventSource(`api/sse_meja.php?cabang_id=${cabangId}`);

        // Saat koneksi terbuka
        eventSource.onopen = function() {
            statusBadge.className = 'badge bg-success';
            statusBadge.innerText = 'Live Connected';
        };

        // SAAT SERVER MENGIRIM DATA
        eventSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            
            if(result.status === 'success') {
                const container = document.getElementById('meja-container');
                let html = '';

                if(result.data.length === 0) {
                    html = `<div class="col-12 text-center py-5"><p class="text-muted">Belum ada data meja.</p></div>`;
                } else {
                    result.data.forEach(row => {
                        // Logic Warna Status
                        const bgBadge = row.status === 'kosong' ? 'bg-success' : 'bg-danger';
                        const iconColor = row.status === 'kosong' ? 'text-success' : 'text-danger';
                        const statusText = row.status.charAt(0).toUpperCase() + row.status.slice(1);

                        html += `
                        <div class="col-sm-6 col-md-4 col-lg-3 mb-4">
                            <div class="card table-card text-center h-100">
                                <div class="card-body">
                                    <i class="fas fa-chair table-icon ${iconColor}"></i>
                                    <h5 class="card-title mt-3">Meja ${row.nomor_meja}</h5>
                                    <small class="text-muted d-block mb-2">${row.nama_cabang}</small>
                                    <span class="badge ${bgBadge}">${statusText}</span>
                                </div>
                                <div class="card-footer bg-white border-0">
                                     <button class="btn btn-sm btn-outline-dark" 
                                             onclick="showQR('${row.nomor_meja}', '${row.nama_cabang}', '${row.qr_url}')">
                                         <i class="fas fa-qrcode"></i> QR
                                     </button>
                                     <a href="javascript:void(0);" onclick="confirmDelete('${row.id}')" class="btn btn-sm btn-outline-danger">
                                         <i class="fas fa-trash"></i>
                                     </a>
                                </div>
                            </div>
                        </div>
                        `;
                    });
                }
                // Update DOM
                container.innerHTML = html;
            }
        };

        // Saat terjadi error (biasanya server restart atau koneksi putus)
        eventSource.onerror = function() {
            statusBadge.className = 'badge bg-warning text-dark';
            statusBadge.innerText = 'Reconnecting...';
            // SSE otomatis mencoba reconnect, jadi kita biarkan saja
        };
    }

    // Jalankan saat halaman selesai dimuat
    document.addEventListener('DOMContentLoaded', () => {
        startRealtimeUpdates();
    });

    // Fungsi Helper Lainnya
    function showQR(nomor, cabang, url) {
        document.getElementById('qrMejaTitle').innerText = "Meja " + nomor;
        document.getElementById('qrCabangTitle').innerText = cabang;
        document.getElementById('urlText').innerText = url;
        document.getElementById('qrcode').innerHTML = "";
        new QRCode(document.getElementById("qrcode"), { text: url, width: 180, height: 180, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H });
        var myModal = new bootstrap.Modal(document.getElementById('qrModal'));
        myModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Meja?', text: "Pastikan meja sudah tidak digunakan!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "?hapus=" + id; }
        })
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>