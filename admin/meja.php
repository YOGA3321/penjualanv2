<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }

require_once '../auth/koneksi.php';

$page_title = "Manajemen Meja";
$active_menu = "meja";
$level = $_SESSION['level'] ?? '';
$user_cabang = $_SESSION['cabang_id'] ?? 0;

// --- LOGIKA GENERATE MEJA ---
if (isset($_POST['tambah_meja'])) {
    $jumlah_generate = (int)$_POST['jumlah_meja']; 
    $cabang_target = ($level == 'admin') ? $_POST['cabang_id'] : $user_cabang;
    
    if ($jumlah_generate < 1) {
         $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Gagal', 'text' => 'Jumlah minimal 1!'];
    } else {
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
    
    // Validasi: Cek apakah meja sedang terisi
    $cek = $koneksi->query("SELECT status FROM meja WHERE id = '$id_hapus'")->fetch_assoc();
    
    if ($cek['status'] == 'terisi') {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Ditolak', 'text' => 'Meja sedang digunakan! Kosongkan dulu.'];
    } else {
        $koneksi->query("DELETE FROM meja WHERE id = '$id_hapus'");
        $_SESSION['swal'] = ['icon' => 'success', 'title' => 'Terhapus', 'text' => 'Data meja dihapus.'];
    }
    header("Location: meja"); exit;
}

// Data Cabang
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
    </div>
</div>

<div class="row" id="meja-container">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Menghubungkan ke server...</p>
    </div>
</div>

<div class="modal fade" id="mejaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Generate Meja</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary">Jumlah Meja</label>
                        <div class="input-group">
                             <input type="number" name="jumlah_meja" class="form-control text-center" value="1" min="1" max="50" required>
                             <span class="input-group-text">Unit</span>
                        </div>
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

<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center pt-0">
                <h5 id="qrMejaTitle" class="mb-1 fw-bold"></h5>
                <p id="qrCabangTitle" class="text-muted small mb-3"></p>
                <div id="qrcode" class="d-flex justify-content-center mb-3"></div>
                <button class="btn btn-primary btn-sm w-100" onclick="window.print()"><i class="fas fa-print me-2"></i> Cetak</button>
            </div>
        </div>
    </div>
</div>

<script>
    let eventSource = null;

    function startRealtimeUpdates() {
        const cabangId = document.getElementById('filterCabang').value;
        if (eventSource) eventSource.close();

        eventSource = new EventSource(`api/sse_meja.php?cabang_id=${cabangId}`);
        
        eventSource.onmessage = function(event) {
            const result = JSON.parse(event.data);
            if(result.status === 'success') {
                const container = document.getElementById('meja-container');
                let html = '';
                if(result.data.length === 0) {
                    html = `<div class="col-12 text-center py-5"><p class="text-muted">Belum ada data meja.</p></div>`;
                } else {
                    result.data.forEach(row => {
                        const isTerisi = row.status === 'terisi';
                        const bgBadge = isTerisi ? 'bg-danger' : 'bg-success';
                        const iconColor = isTerisi ? 'text-danger' : 'text-success';
                        const statusText = row.status.toUpperCase();

                        // LOGIKA TOMBOL
                        let buttons = '';
                        if (isTerisi) {
                            // Jika Terisi: Tombol Kosongkan (Aktif), Hapus (Mati)
                            buttons = `
                                <button class="btn btn-sm btn-warning w-100 fw-bold mb-2" onclick="kosongkanMeja('${row.id}', '${row.nomor_meja}')">
                                    <i class="fas fa-sync-alt me-1"></i> Kosongkan
                                </button>
                            `;
                        } else {
                            // Jika Kosong: Tombol QR (Aktif), Hapus (Aktif)
                            buttons = `
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-dark flex-grow-1" onclick="showQR('${row.nomor_meja}', '${row.nama_cabang}', '${row.qr_url}')">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDelete('${row.id}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }

                        html += `
                        <div class="col-6 col-md-4 col-lg-3 mb-4">
                            <div class="card table-card text-center h-100 border-${isTerisi ? 'danger' : 'success'}">
                                <div class="card-body">
                                    <i class="fas fa-chair table-icon ${iconColor} mb-2"></i>
                                    <h5 class="card-title mb-0">Meja ${row.nomor_meja}</h5>
                                    <small class="text-muted d-block mb-2">${row.nama_cabang}</small>
                                    <span class="badge ${bgBadge} mb-3">${statusText}</span>
                                    
                                    <div class="mt-2">
                                        ${buttons}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    });
                }
                container.innerHTML = html;
            }
        };
    }

    document.addEventListener('DOMContentLoaded', startRealtimeUpdates);

    function showQR(nomor, cabang, url) {
        document.getElementById('qrMejaTitle').innerText = "Meja " + nomor;
        document.getElementById('qrCabangTitle').innerText = cabang;
        document.getElementById('qrcode').innerHTML = "";
        new QRCode(document.getElementById("qrcode"), { text: url, width: 150, height: 150 });
        new bootstrap.Modal(document.getElementById('qrModal')).show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Meja?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Hapus'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = "?hapus=" + id;
        })
    }

    function kosongkanMeja(id, nomor) {
        Swal.fire({
            title: `Kosongkan Meja ${nomor}?`,
            text: "Pastikan pelanggan sudah pergi.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Ya, Bersihkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Panggil API Action
                const formData = new FormData();
                formData.append('action', 'kosongkan_meja');
                formData.append('id', id);
                
                fetch('api/transaksi_action.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        Swal.fire('Berhasil', data.message, 'success');
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                });
            }
        })
    }
</script>

<?php include '../layouts/admin/footer.php'; ?>