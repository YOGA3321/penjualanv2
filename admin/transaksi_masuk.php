<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

$page_title = "Pesanan Masuk";
$active_menu = "transaksi_masuk";

// --- 1. AUTO SYNC MIDTRANS ---
$cek_pending = $koneksi->query("SELECT * FROM transaksi WHERE metode_pembayaran = 'midtrans' AND status_pembayaran = 'pending' AND midtrans_id IS NOT NULL");
if ($cek_pending->num_rows > 0) {
    \Midtrans\Config::$serverKey = 'SB-Mid-server-p0J5Kw0tX_JHY_HoYJOQzYXQ'; 
    \Midtrans\Config::$isProduction = false;
    \Midtrans\Config::$isSanitized = true;
    while($trx = $cek_pending->fetch_assoc()) {
        try {
            $status = \Midtrans\Transaction::status($trx['midtrans_id']);
            $transaction = $status->transaction_status;
            $new_status = 'pending';
            if ($transaction == 'capture' || $transaction == 'settlement') $new_status = 'settlement';
            else if (in_array($transaction, ['deny', 'expire', 'cancel'])) $new_status = 'cancel';

            if ($new_status != 'pending') {
                $uuid = $trx['uuid'];
                $koneksi->query("UPDATE transaksi SET status_pembayaran = '$new_status' WHERE uuid = '$uuid'");
                if ($new_status == 'settlement') {
                    $koneksi->query("UPDATE transaksi SET status_pesanan = 'diproses' WHERE uuid = '$uuid'");
                    if ($trx['user_id'] && $trx['poin_didapat'] > 0) $koneksi->query("UPDATE users SET poin = poin + ".$trx['poin_didapat']." WHERE id = '".$trx['user_id']."'");
                } elseif ($new_status == 'cancel') {
                    $koneksi->query("UPDATE transaksi SET status_pesanan = 'cancel' WHERE uuid = '$uuid'");
                    $koneksi->query("UPDATE meja SET status = 'kosong' WHERE id = '".$trx['meja_id']."'");
                }
            }
        } catch (Exception $e) {}
    }
}

// --- 2. QUERY UTAMA ---
$target_cabang = ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) ? $_SESSION['view_cabang_id'] : ($_SESSION['cabang_id'] ?? 0);
if ($target_cabang == 'pusat' && $_SESSION['level'] == 'admin') $target_cabang = 'pusat';

$where_cabang = ($target_cabang != 'pusat') ? "AND m.cabang_id = '$target_cabang'" : "";
$search_kw = $_GET['search'] ?? '';
$where_search = "";
if($search_kw) $where_search = " AND (t.nama_pelanggan LIKE '%$search_kw%' OR t.uuid LIKE '%$search_kw%') ";

$sql = "SELECT t.*, m.nomor_meja, c.nama_cabang 
        FROM transaksi t
        JOIN meja m ON t.meja_id = m.id
        JOIN cabang c ON m.cabang_id = c.id
        WHERE t.status_pesanan IN ('menunggu_konfirmasi', 'menunggu_bayar', 'diproses', 'siap_saji')
        AND t.status_pembayaran NOT IN ('cancel', 'expire', 'failure')
        $where_cabang $where_search
        ORDER BY CASE t.status_pesanan WHEN 'siap_saji' THEN 1 WHEN 'menunggu_konfirmasi' THEN 2 WHEN 'diproses' THEN 3 ELSE 4 END ASC, t.created_at ASC";
$data = $koneksi->query($sql);

include '../layouts/admin/header.php';
?>

<audio id="notifSound" src="../assets/audio/bell.mp3" preload="auto"></audio>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-bell me-2"></i>Pesanan Masuk</h4>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted">Antrian Aktif</small>
                <span class="badge bg-light text-success border"><i class="fas fa-sync fa-spin me-1"></i> Live</span>
            </div>
        </div>
        <form class="d-flex" method="GET" id="searchForm">
            <div class="input-group shadow-sm" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="search" id="searchInput" class="form-control border-start-0" placeholder="Cari..." value="<?= htmlspecialchars($search_kw) ?>" autocomplete="off">
            </div>
        </form>
    </div>

    <div class="row g-4" id="incoming-container">
        <?php if($data->num_rows > 0): ?>
            <?php while($row = $data->fetch_assoc()): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                        <?php 
                            $status = $row['status_pesanan'];
                            $pay_status = $row['status_pembayaran'];
                            $border = ($status=='siap_saji') ? 'success' : (($status=='menunggu_konfirmasi' || $pay_status=='pending') ? 'warning' : 'primary');
                        ?>
                        <div class="position-absolute top-0 start-0 bottom-0 bg-<?= $border ?>" style="width: 5px;"></div>
                        
                        <div class="card-body ps-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($row['nama_pelanggan']) ?></h5>
                                    <span class="badge bg-dark rounded-pill">Meja <?= $row['nomor_meja'] ?></span>
                                    <small class="text-muted ms-2"><?= date('H:i', strtotime($row['created_at'])) ?></small>
                                </div>
                                <div class="text-end">
                                    <h5 class="fw-bold text-primary mb-0">Rp <?= number_format($row['total_harga']) ?></h5>
                                    <span class="badge bg-light text-dark border"><?= strtoupper($row['metode_pembayaran']) ?></span>
                                </div>
                            </div>

                            <div class="bg-light p-2 rounded mb-3" style="font-size: 0.9rem;">
                                <?php
                                    $trx_id = $row['id'];
                                    $items = $koneksi->query("SELECT d.qty, m.nama_menu, d.catatan FROM transaksi_detail d JOIN menu m ON d.menu_id = m.id WHERE d.transaksi_id = '$trx_id' LIMIT 3");
                                    while($item = $items->fetch_assoc()):
                                ?>
                                    <div class="d-flex justify-content-between">
                                        <span><?= $item['qty'] ?>x <?= $item['nama_menu'] ?></span>
                                    </div>
                                    <?php if($item['catatan']): ?><small class="text-danger d-block lh-1 mb-1" style="font-size:0.7rem"><?= $item['catatan'] ?></small><?php endif; ?>
                                <?php endwhile; ?>
                                <?php if($items->num_rows >= 3): ?><small class="text-muted fst-italic">...dan lainnya</small><?php endif; ?>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-info text-white flex-grow-1" onclick="showDetail(<?= $row['id'] ?>)"><i class="fas fa-eye"></i> Detail</button>

                                <?php if($status == 'menunggu_konfirmasi'): ?>
                                    <button class="btn btn-sm btn-warning flex-grow-1 text-dark fw-bold" onclick="konfirmasiBayar(<?= $row['id'] ?>, <?= $row['total_harga'] ?>)">
                                        <i class="fas fa-cash-register"></i> Terima Tunai
                                    </button>
                                <?php elseif($pay_status == 'pending' && $row['metode_pembayaran'] == 'midtrans'): ?>
                                    <button class="btn btn-sm btn-secondary flex-grow-1" onclick="location.reload()"><i class="fas fa-sync"></i> Cek Midtrans</button>
                                <?php elseif($status == 'siap_saji'): ?>
                                    <button class="btn btn-sm btn-success flex-grow-1 fw-bold" onclick="selesaiPesanan(<?= $row['id'] ?>)"><i class="fas fa-check"></i> Selesai</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light border flex-grow-1 disabled"><?= strtoupper(str_replace('_', ' ', $status)) ?></button>
                                <?php endif; ?>
                                
                                <?php if($status == 'menunggu_konfirmasi' || $pay_status == 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-danger" onclick="batalkanPesanan(<?= $row['id'] ?>)" title="Batalkan"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted opacity-50 mb-3"><i class="fas fa-check-circle fa-3x"></i></div>
                <h5>Tidak ada pesanan aktif</h5>
                <p class="text-muted">Pesanan selesai atau belum masuk.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Detail Pesanan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="modalContent">Loading...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnCetak" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Struk</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let timeout = null;
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');

    if(searchInput.value) {
        searchInput.focus();
        let val = searchInput.value; searchInput.value = ''; searchInput.value = val;
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => { searchForm.submit(); }, 600);
    });

    function showDetail(id) {
        fetch('api/get_detail_transaksi.php?id=' + id).then(r => r.json()).then(data => {
            if(data.status === 'success') {
                let h = data.header;
                let html = `
                    <div class="d-flex justify-content-between mb-3">
                        <div><strong>${h.nama_pelanggan}</strong><br>Meja ${h.nomor_meja}</div>
                        <div class="text-end text-primary fw-bold">Rp ${parseInt(h.total_harga).toLocaleString('id-ID')}</div>
                    </div>
                    <ul class="list-group list-group-flush mb-3">`;
                
                data.items.forEach(i => {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <div>${i.nama_menu} <small class="text-muted">x${i.qty}</small>
                        ${i.catatan ? '<br><small class="text-danger fst-italic">'+i.catatan+'</small>' : ''}
                        </div>
                        <span>Rp ${parseInt(i.subtotal).toLocaleString('id-ID')}</span>
                    </li>`;
                });
                
                let diskon = parseInt(h.diskon);
                if(diskon > 0) {
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center px-0 text-success">
                        <span>Diskon Voucher</span>
                        <span>- Rp ${diskon.toLocaleString('id-ID')}</span>
                    </li>`;
                }
                html += `</ul>`;
                
                document.getElementById('modalContent').innerHTML = html;
                document.getElementById('btnCetak').href = '../penjualan/cetak_struk_pdf.php?uuid=' + h.uuid;
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        });
    }

    function konfirmasiBayar(id, total) {
        Swal.fire({
            title: 'Terima Pembayaran Tunai',
            html: `
                <div class="mb-3">Tagihan: <strong class="text-primary">Rp ${total.toLocaleString('id-ID')}</strong></div>
                <input type="number" id="uangBayar" class="form-control text-center mb-3" placeholder="Masukkan Uang Diterima">
                <div class="alert alert-light border" id="infoKembalian">Kembalian: Rp 0</div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Bayar & Proses',
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            didOpen: () => {
                const input = document.getElementById('uangBayar');
                const info = document.getElementById('infoKembalian');
                input.focus();
                
                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - total;
                    if(kembali >= 0) {
                        info.innerHTML = `Kembalian: <strong class="text-success">Rp ${kembali.toLocaleString('id-ID')}</strong>`;
                        info.className = "alert alert-success border";
                    } else {
                        info.innerHTML = `Kurang: <strong class="text-danger">Rp ${Math.abs(kembali).toLocaleString('id-ID')}</strong>`;
                        info.className = "alert alert-danger border";
                    }
                });
            },
            preConfirm: () => {
                const bayar = parseInt(document.getElementById('uangBayar').value);
                if (!bayar || bayar < total) return Swal.showValidationMessage('Uang pembayaran kurang!');
                return { bayar: bayar, kembali: bayar - total };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'konfirmasi_tunai'); 
                fd.append('id', id);
                // Kirim uang agar bisa disimpan kalau perlu (meski backend update_status tidak butuh)
                fd.append('uang_bayar', result.value.bayar);
                
                fetch('api/transaksi_action.php', { method: 'POST', body: fd })
                .then(res => res.json()).then(data => {
                    if(data.status === 'success') {
                        // [FIX] POPUP KEMBALIAN
                        Swal.fire({
                            icon: 'success',
                            title: 'Pembayaran Berhasil!',
                            html: `<h3>Kembalian: Rp ${result.value.kembali.toLocaleString('id-ID')}</h3><br>Pesanan masuk ke dapur.`,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-print"></i> Cetak Struk',
                            cancelButtonText: 'Tutup',
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#198754'
                        }).then((resAlert) => {
                            if (resAlert.isConfirmed) {
                                window.open(`../penjualan/cetak_struk_pdf.php?uuid=${data.uuid}`, '_blank');
                                location.reload();
                            } else {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire('Gagal', data.msg, 'error');
                    }
                });
            }
        });
    }

    function batalkanPesanan(id) {
        Swal.fire({
            title: 'Tolak Pesanan?', text: "Stok akan dikembalikan dan meja dikosongkan.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Tolak'
        }).then((res) => {
            if(res.isConfirmed) {
                const fd = new FormData(); fd.append('action', 'tolak_pesanan'); fd.append('id', id);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
                    if(d.status === 'success') Swal.fire('Ditolak', 'Pesanan dibatalkan', 'success').then(() => location.reload());
                    else Swal.fire('Gagal', 'Terjadi kesalahan', 'error');
                });
            }
        });
    }
    
    function selesaiPesanan(id) {
        const fd = new FormData(); fd.append('action', 'update_status'); fd.append('id', id); fd.append('status', 'selesai');
        fetch('api/transaksi_action.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if(d.status === 'success') location.reload(); });
    }

    <?php if(empty($search_kw)): ?>
    setInterval(() => {
        if(document.activeElement !== searchInput && !document.querySelector('.modal.show')) { location.reload(); }
    }, 15000); 
    <?php endif; ?>
</script>

<?php include '../layouts/admin/footer.php'; ?>