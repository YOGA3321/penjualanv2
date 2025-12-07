<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../login"); exit; }
require_once '../auth/koneksi.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

$page_title = "Pesanan Masuk";
$active_menu = "transaksi_masuk";

// --- 1. LOGIKA CABANG ---
$target_cabang = ($_SESSION['level'] == 'admin' && isset($_SESSION['view_cabang_id'])) ? $_SESSION['view_cabang_id'] : ($_SESSION['cabang_id'] ?? 0);
if ($target_cabang == 'pusat' && $_SESSION['level'] == 'admin') $target_cabang = 'pusat';

$search_kw = $_GET['search'] ?? '';

include '../layouts/admin/header.php';
?>

<audio id="notifSound" src="../assets/audio/bell.mp3" preload="auto"></audio>

<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-primary"><i class="fas fa-bell me-2"></i>Pesanan Masuk</h4>
            <div class="d-flex align-items-center gap-2">
                <small class="text-muted">Antrian Aktif</small>
                <div class="spinner-grow text-success spinner-grow-sm" role="status" style="width: 0.7rem; height: 0.7rem;"></div>
                <span class="text-success small fw-bold">Live Stream</span>
            </div>
        </div>
        
        <div class="input-group shadow-sm" style="width: 250px;">
            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
            <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari..." value="<?= htmlspecialchars($search_kw) ?>" autocomplete="off">
        </div>
    </div>

    <div class="row g-4" id="incoming-container">
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted">Menghubungkan ke server...</p>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Detail Pesanan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">Loading...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a href="#" id="btnCetak" target="_blank" class="btn btn-primary"><i class="fas fa-print"></i> Struk</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-m2n6kBqd8rsKrRST"></script>

<script>
    let eventSource = null;
    let currentSearch = "<?= $search_kw ?>";
    const viewCabang = "<?= $target_cabang ?>";

    // --- 1. START SSE STREAM ---
    function startStream() {
        if(eventSource) eventSource.close();
        
        const url = `api/sse_order.php?view_cabang=${viewCabang}&search=${encodeURIComponent(currentSearch)}`;
        eventSource = new EventSource(url);

        eventSource.onmessage = function(e) {
            if(!e.data || e.data.includes("keepalive")) return;
            const data = JSON.parse(e.data);
            renderData(data);
        };
    }

    // --- 2. RENDER DATA ---
    function renderData(data) {
        const container = document.getElementById('incoming-container');
        
        if(data.length === 0) {
            container.innerHTML = `<div class="col-12 text-center py-5"><div class="text-muted opacity-50 mb-3"><i class="fas fa-mug-hot fa-3x"></i></div><h5>Tidak ada pesanan aktif</h5><p class="text-muted">Pesanan baru akan muncul otomatis.</p></div>`;
            return;
        }

        let html = '';
        data.forEach(row => {
            let borderClass = 'primary';
            let status = row.status_pesanan;
            let pay_status = row.status_pembayaran;
            let buttonsHtml = `<button class="btn btn-sm btn-light border flex-grow-1" onclick="showDetail(${row.id})" title="Lihat Detail"><i class="fas fa-eye"></i></button>`;

            // LOGIKA TOMBOL BERDASARKAN STATUS
            if(status == 'siap_saji') { 
                borderClass = 'success'; 
                buttonsHtml += `<button class="btn btn-sm btn-success flex-grow-1 fw-bold" onclick="selesaiPesanan(${row.id})"><i class="fas fa-check"></i> Selesai</button>`;
            
            } else if (status == 'menunggu_konfirmasi') { 
                borderClass = 'warning'; 
                buttonsHtml += `<button class="btn btn-sm btn-warning flex-grow-1 text-dark fw-bold" onclick="konfirmasiBayar(${row.id}, ${row.total_harga})"><i class="fas fa-cash-register"></i> Terima Tunai</button>`;
                buttonsHtml += `<button class="btn btn-sm btn-outline-danger" onclick="batalkanPesanan(${row.id})" title="Batalkan"><i class="fas fa-times"></i></button>`;
            
            } else if (pay_status == 'pending') { 
                borderClass = 'warning'; 
                
                // [FIX] TOMBOL KHUSUS MIDTRANS PENDING
                if(row.metode_pembayaran == 'midtrans') {
                    // Tombol 1: Paksa Cek Status (Sync)
                    buttonsHtml += `<button class="btn btn-sm btn-info text-white flex-grow-1" onclick="syncManual(${row.id})" title="Paksa Cek Status"><i class="fas fa-sync-alt"></i> Cek</button>`;
                    
                    // Tombol 2: Opsi Bayar (Popup/Tunai)
                    buttonsHtml += `<button class="btn btn-sm btn-primary flex-grow-1" onclick="opsiBayar(${row.id}, '${row.uuid}')" title="Buka Popup / Bayar Tunai"><i class="fas fa-wallet"></i> Bayar</button>`;
                }
                
                buttonsHtml += `<button class="btn btn-sm btn-outline-danger" onclick="batalkanPesanan(${row.id})" title="Batalkan"><i class="fas fa-times"></i></button>`;
            } else {
                buttonsHtml += `<button class="btn btn-sm btn-light border flex-grow-1 disabled">${status.replace('_',' ').toUpperCase()}</button>`;
            }

            // Items Preview
            let itemsHtml = '';
            row.items.forEach(i => {
                itemsHtml += `<div class="d-flex justify-content-between"><span>${i.qty}x ${i.nama_menu}</span></div>${i.catatan ? `<small class="text-danger d-block lh-1 mb-1" style="font-size:0.7rem">${i.catatan}</small>` : ''}`;
            });
            if(row.more_items > 0) itemsHtml += `<small class="text-muted fst-italic">...dan ${row.more_items} lainnya</small>`;

            html += `
            <div class="col-md-6 col-xl-4 animate-fade">
                <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 bottom-0 bg-${borderClass}" style="width: 5px;"></div>
                    <div class="card-body ps-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div><h5 class="fw-bold mb-0">${row.nama_pelanggan}</h5><span class="badge bg-dark rounded-pill">Meja ${row.nomor_meja}</span><small class="text-muted ms-2">${row.created_at.substr(11,5)}</small></div>
                            <div class="text-end"><h5 class="fw-bold text-primary mb-0">Rp ${parseInt(row.total_harga).toLocaleString('id-ID')}</h5><span class="badge bg-light text-dark border">${row.metode_pembayaran.toUpperCase()}</span></div>
                        </div>
                        <div class="bg-light p-2 rounded mb-3" style="font-size: 0.9rem;">${itemsHtml}</div>
                        <div class="d-flex gap-2">${buttonsHtml}</div>
                    </div>
                </div>
            </div>`;
        });
        container.innerHTML = html;
    }

    let timeout = null;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => { currentSearch = this.value; startStream(); }, 600);
    });

    // --- FUNGSI AKSI BARU ---

    // 1. SYNC MANUAL (Tembak ke Midtrans)
    function syncManual(id) {
        Swal.fire({
            title: 'Sinkronisasi...',
            text: 'Memeriksa status terbaru ke Server Midtrans',
            didOpen: () => Swal.showLoading()
        });

        const fd = new FormData();
        fd.append('action', 'sync_midtrans');
        fd.append('id', id);

        fetch('api/transaksi_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.status === 'success') {
                Swal.fire('LUNAS!', d.message, 'success');
                // Tidak perlu reload, SSE akan update tampilan otomatis
            } else if(d.status === 'warning') {
                Swal.fire('Expired/Batal', d.message, 'warning');
            } else {
                Swal.fire('Masih Pending', d.message, 'info');
            }
        })
        .catch(e => Swal.fire('Error', 'Gagal koneksi server', 'error'));
    }

    // 2. OPSI BAYAR (Popup / Switch Tunai)
    function opsiBayar(id, uuid) {
        Swal.fire({
            title: 'Metode Pembayaran',
            text: 'Pelanggan ingin membayar sekarang?',
            icon: 'question',
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-qrcode"></i> Tampilkan QRIS',
            denyButtonText: '<i class="fas fa-money-bill"></i> Bayar Tunai',
            cancelButtonText: 'Tutup',
            confirmButtonColor: '#0d6efd',
            denyButtonColor: '#198754'
        }).then((res) => {
            if (res.isConfirmed) {
                // BUKA POPUP
                fetch('api/get_detail_transaksi.php?id=' + id).then(r=>r.json()).then(dt => {
                    if(dt.header.snap_token) {
                        window.snap.pay(dt.header.snap_token, {
                            onSuccess: function(result){ syncManual(id); }, // Cek lagi setelah sukses
                            onPending: function(result){ Swal.fire('Pending', 'Menunggu pembayaran...', 'info'); },
                            onError: function(result){ Swal.fire('Error', 'Gagal', 'error'); },
                            onClose: function(){ syncManual(id); } // Cek saat ditutup, siapa tau sudah bayar
                        });
                    } else {
                        Swal.fire('Error', 'Token expired/invalid', 'error');
                    }
                });
            } else if (res.isDenied) {
                // SWITCH KE TUNAI
                fetch('api/get_detail_transaksi.php?id=' + id).then(r=>r.json()).then(dt => {
                    konfirmasiBayar(id, parseInt(dt.header.total_harga));
                });
            }
        });
    }

    // 3. KONFIRMASI TUNAI
    function konfirmasiBayar(id, total) {
        Swal.fire({
            title: 'Terima Tunai',
            html: `<h3 class="text-primary mb-3">Tagihan: Rp ${total.toLocaleString('id-ID')}</h3>
                   <input type="number" id="uangBayar" class="form-control text-center mb-3" placeholder="Masukkan Uang Diterima">
                   <div class="alert alert-light border" id="infoKembalian">Kembalian: Rp 0</div>`,
            showCancelButton: true, confirmButtonText: 'Bayar & Proses',
            didOpen: () => {
                const input = document.getElementById('uangBayar');
                const info = document.getElementById('infoKembalian');
                input.focus();
                input.addEventListener('input', () => {
                    let bayar = parseInt(input.value) || 0;
                    let kembali = bayar - total;
                    info.innerHTML = kembali >= 0 
                        ? `Kembalian: <strong class="text-success">Rp ${kembali.toLocaleString('id-ID')}</strong>`
                        : `Kurang: <strong class="text-danger">Rp ${Math.abs(kembali).toLocaleString('id-ID')}</strong>`;
                    info.className = kembali >= 0 ? "alert alert-success border" : "alert alert-danger border";
                });
            },
            preConfirm: () => {
                const bayar = parseInt(document.getElementById('uangBayar').value);
                if (!bayar || bayar < total) return Swal.showValidationMessage('Uang kurang!');
                return { bayar: bayar, kembali: bayar - total };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'konfirmasi_tunai'); 
                fd.append('id', id);
                fd.append('uang_bayar', result.value.bayar);
                fd.append('kembalian', result.value.kembali);
                
                fetch('api/transaksi_action.php', { method: 'POST', body: fd })
                .then(res => res.json()).then(data => {
                    if(data.status === 'success') {
                        Swal.fire({
                            icon: 'success', title: 'LUNAS!',
                            html: `Kembalian: <b>Rp ${result.value.kembali.toLocaleString('id-ID')}</b>`,
                            showConfirmButton: true, confirmButtonText: 'Cetak Struk'
                        }).then((resCetak) => {
                            if(resCetak.isConfirmed) window.open('../penjualan/cetak_struk_pdf.php?uuid='+data.uuid, '_blank');
                        });
                    } else {
                        Swal.fire('Gagal', data.msg, 'error');
                    }
                });
            }
        });
    }

    function showDetail(id) {
        fetch('api/get_detail_transaksi.php?id=' + id).then(r => r.json()).then(data => {
            if(data.status === 'success') {
                let h = data.header;
                let html = `<div class="d-flex justify-content-between mb-3"><div><strong>${h.nama_pelanggan}</strong><br>Meja ${h.nomor_meja}</div><div class="text-end text-primary fw-bold">Rp ${parseInt(h.total_harga).toLocaleString('id-ID')}</div></div><ul class="list-group list-group-flush mb-3">`;
                data.items.forEach(i => { html += `<li class="list-group-item d-flex justify-content-between align-items-center px-0"><div>${i.nama_menu} <small class="text-muted">x${i.qty}</small></div><span>Rp ${parseInt(i.subtotal).toLocaleString('id-ID')}</span></li>`; });
                if(parseInt(h.diskon) > 0) html += `<li class="list-group-item d-flex justify-content-between text-success"><span>Diskon</span><span>- Rp ${parseInt(h.diskon).toLocaleString('id-ID')}</span></li>`;
                html += `</ul>`;
                document.getElementById('modalContent').innerHTML = html;
                document.getElementById('btnCetak').href = '../penjualan/cetak_struk_pdf.php?uuid=' + h.uuid;
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        });
    }

    function selesaiPesanan(id) {
        const fd = new FormData(); fd.append('action', 'update_status'); fd.append('id', id); fd.append('status', 'selesai');
        fetch('api/transaksi_action.php', { method: 'POST', body: fd });
    }

    function batalkanPesanan(id) {
        Swal.fire({title: 'Tolak?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya'}).then((res) => {
            if(res.isConfirmed) {
                const fd = new FormData(); fd.append('action', 'tolak_pesanan'); fd.append('id', id);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd });
            }
        });
    }

    startStream();
</script>

<style>.animate-fade { animation: fadeIn 0.5s; } @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }</style>
<?php include '../layouts/admin/footer.php'; ?>