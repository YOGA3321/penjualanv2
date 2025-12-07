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
    let orderEventSource = null;
    let globalEventSource = null;
    let currentSearch = "<?= $search_kw ?>";
    const viewCabang = "<?= $target_cabang ?>";
    const notifAudio = document.getElementById('notifSound');

    // --- 1. STREAM PESANAN (DAFTAR TRANSAKSI) ---
    function startOrderStream() {
        if(orderEventSource) orderEventSource.close();
        
        const url = `api/sse_order.php?view_cabang=${viewCabang}&search=${encodeURIComponent(currentSearch)}`;
        orderEventSource = new EventSource(url);

        orderEventSource.onmessage = function(e) {
            if(!e.data || e.data.includes("keepalive")) return;
            const data = JSON.parse(e.data);
            renderData(data);
        };
    }

    // --- 2. STREAM GLOBAL (NOTIFIKASI PEMBAYARAN WEBHOOK) ---
    function startGlobalSSE() {
        if(globalEventSource) globalEventSource.close();

        // Connect ke SSE Channel yang baru (Sudah berisi listener file trigger)
        globalEventSource = new EventSource('api/sse_channel.php?cabang_id=' + viewCabang);
        
        globalEventSource.onmessage = function(e) {
            if(!e.data) return;
            const data = JSON.parse(e.data);

            // A. Handle Notifikasi Pembayaran dari Webhook
            if (data.payment_event) {
                console.log("Pembayaran Masuk:", data.payment_event);
                
                // Bunyikan Suara
                notifAudio.play().catch(e => console.log("Audio play blocked by browser"));

                // Tampilkan Alert Pojok Kanan Atas (Toast)
                Swal.fire({
                    icon: 'success',
                    title: 'Pembayaran Diterima!',
                    text: `Order ID: ${data.payment_event.order_id}`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true
                });

                // RELOAD TABLE TRANSAKSI OTOMATIS
                // Kita restart stream order untuk memaksa refresh data terbaru
                startOrderStream();
            }

            // B. Handle User Online & Reservasi (Fitur Lama Tetap Jalan)
            // (Opsional: Anda bisa update UI counter user online disini jika ada elemen HTML-nya)
        };
    }

    // --- 3. RENDER DATA ---
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

            // LOGIKA TOMBOL
            if(status == 'siap_saji') { 
                borderClass = 'success'; 
                buttonsHtml += `<button class="btn btn-sm btn-success flex-grow-1 fw-bold" onclick="selesaiPesanan(${row.id})"><i class="fas fa-check"></i> Selesai</button>`;
            
            } else if (status == 'menunggu_konfirmasi') { 
                borderClass = 'warning'; 
                buttonsHtml += `<button class="btn btn-sm btn-warning flex-grow-1 text-dark fw-bold" onclick="konfirmasiBayar(${row.id}, ${row.total_harga})"><i class="fas fa-cash-register"></i> Terima Tunai</button>`;
                buttonsHtml += `<button class="btn btn-sm btn-outline-danger" onclick="batalkanPesanan(${row.id})" title="Batalkan"><i class="fas fa-times"></i></button>`;
            
            } else if (pay_status == 'pending') { 
                borderClass = 'warning'; 
                
                if(row.metode_pembayaran == 'midtrans') {
                    // Tombol Paksa Sync
                    buttonsHtml += `<button class="btn btn-sm btn-info text-white flex-grow-1" onclick="syncManual(${row.id})" title="Paksa Cek Status"><i class="fas fa-sync-alt"></i> Cek</button>`;
                    // Tombol Bayar Manual
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
        timeout = setTimeout(() => { currentSearch = this.value; startOrderStream(); }, 600);
    });

    // --- FUNGSI AKSI (TETAP SAMA) ---
    function syncManual(id) {
        Swal.fire({ title: 'Sinkronisasi...', didOpen: () => Swal.showLoading() });
        const fd = new FormData(); fd.append('action', 'sync_midtrans'); fd.append('id', id);
        fetch('api/transaksi_action.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => {
            if(d.status === 'success') Swal.fire('LUNAS!', d.message, 'success');
            else Swal.fire('Info', d.message, 'info');
        });
    }

    function opsiBayar(id, uuid) {
        Swal.fire({
            title: 'Metode Pembayaran', showDenyButton: true, showCancelButton: true,
            confirmButtonText: 'QRIS', denyButtonText: 'Tunai'
        }).then((res) => {
            if (res.isConfirmed) {
                fetch('api/get_detail_transaksi.php?id=' + id).then(r=>r.json()).then(dt => {
                    if(dt.header.snap_token) window.snap.pay(dt.header.snap_token, { onClose: function(){ syncManual(id); } });
                });
            } else if (res.isDenied) {
                fetch('api/get_detail_transaksi.php?id=' + id).then(r=>r.json()).then(dt => {
                    konfirmasiBayar(id, parseInt(dt.header.total_harga));
                });
            }
        });
    }

    function konfirmasiBayar(id, total) {
        Swal.fire({
            title: 'Terima Tunai',
            html: `Tagihan: Rp ${total.toLocaleString('id-ID')}<br><input id="uangBayar" class="form-control mt-2" placeholder="Uang Diterima">`,
            showCancelButton: true,
            preConfirm: () => {
                const bayar = parseInt(document.getElementById('uangBayar').value);
                if (!bayar || bayar < total) return Swal.showValidationMessage('Uang kurang!');
                return { bayar: bayar };
            }
        }).then((res) => {
            if(res.isConfirmed) {
                const fd = new FormData(); fd.append('action', 'konfirmasi_tunai'); fd.append('id', id); fd.append('uang_bayar', res.value.bayar);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                    if(d.status==='success') window.open('../penjualan/cetak_struk_pdf.php?uuid='+d.uuid);
                });
            }
        });
    }

    function showDetail(id) {
        fetch('api/get_detail_transaksi.php?id=' + id).then(r => r.json()).then(data => {
            let h = data.header;
            let html = `<b>${h.nama_pelanggan}</b> - Meja ${h.nomor_meja}<hr>`;
            data.items.forEach(i => { html += `${i.nama_menu} x${i.qty} = ${parseInt(i.subtotal).toLocaleString('id-ID')}<br>`; });
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('btnCetak').href = '../penjualan/cetak_struk_pdf.php?uuid=' + h.uuid;
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        });
    }

    function selesaiPesanan(id) {
        const fd = new FormData(); fd.append('action', 'update_status'); fd.append('id', id); fd.append('status', 'selesai');
        fetch('api/transaksi_action.php', { method: 'POST', body: fd });
    }

    function batalkanPesanan(id) {
        Swal.fire({title: 'Batalkan?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya'}).then(r=>{
            if(r.isConfirmed) {
                const fd = new FormData(); fd.append('action', 'tolak_pesanan'); fd.append('id', id);
                fetch('api/transaksi_action.php', { method: 'POST', body: fd });
            }
        });
    }

    // Jalankan Dua Stream Sekaligus
    startOrderStream(); // Untuk list pesanan
    startGlobalSSE();   // Untuk notifikasi notifikasi webhook
</script>

<style>.animate-fade { animation: fadeIn 0.5s; } @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }</style>
<?php include '../layouts/admin/footer.php'; ?>