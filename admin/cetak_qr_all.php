<?php
session_start();
require_once '../auth/koneksi.php';

if (!isset($_SESSION['user_id'])) { die("Akses ditolak"); }

// Filter Cabang (Sesuai user yang login)
$cabang_id = $_SESSION['cabang_id'] ?? 0;
$where = "";
if ($_SESSION['level'] != 'admin' || (isset($_SESSION['view_cabang_id']) && $_SESSION['view_cabang_id'] != 'pusat')) {
    $target = $_SESSION['view_cabang_id'] ?? $cabang_id;
    $where = "WHERE m.cabang_id = '$target'";
}

// [FIX] GUNAKAN 'CAST' AGAR URUTAN ANGKA BENAR (1, 2, 3... 10)
$sql = "SELECT m.*, c.nama_cabang 
        FROM meja m 
        LEFT JOIN cabang c ON m.cabang_id = c.id 
        $where 
        ORDER BY c.id ASC, CAST(m.nomor_meja AS UNSIGNED) ASC, m.nomor_meja ASC";

$data = $koneksi->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak QR Code Meja</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; background: #eee; -webkit-print-color-adjust: exact; }
        .page { 
            background: white; 
            width: 210mm; /* Lebar A4 */
            min-height: 297mm; 
            margin: 20px auto; 
            padding: 20px; 
            display: flex; 
            flex-wrap: wrap; 
            align-content: flex-start;
            gap: 15px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .qr-card {
            width: 140px;
            height: 180px;
            border: 2px dashed #ccc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 10px;
            page-break-inside: avoid; /* PENTING: Agar tidak terpotong saat ganti halaman kertas */
            text-align: center;
        }
        .table-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        .branch-name { font-size: 10px; margin-bottom: 10px; color: #666; }
        
        @media print {
            body { background: none; margin: 0; }
            .page { margin: 0; box-shadow: none; border: none; width: 100%; }
            .no-print { display: none; }
            .qr-card { border: 1px solid #ddd; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align:center; padding: 20px; background: #333; color: white; position: sticky; top: 0; z-index: 999;">
        <h3>Preview Cetak QR Code</h3>
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #0d6efd; color: white; border: none; border-radius: 5px; font-weight: bold;">
            🖨️ Cetak Sekarang (Ctrl + P)
        </button>
    </div>

    <div class="page">
        <?php if($data->num_rows > 0): ?>
            <?php while($row = $data->fetch_assoc()): ?>
                <div class="qr-card">
                    <div class="table-name"><?= $row['nomor_meja'] ?></div>
                    <div class="branch-name"><?= $row['nama_cabang'] ?></div>
                    <div id="qr-<?= $row['id'] ?>"></div>
                    
                    <script>
                        new QRCode(document.getElementById("qr-<?= $row['id'] ?>"), {
                            text: "<?= BASE_URL ?>/penjualan/index.php?token=<?= $row['qr_token'] ?>",
                            width: 100,
                            height: 100,
                            correctLevel : QRCode.CorrectLevel.H
                        });
                    </script>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="width:100%; text-align:center; padding:50px; color: #999;">
                Belum ada data meja. Silakan tambah meja terlebih dahulu.
            </p>
        <?php endif; ?>
    </div>

</body>
</html>