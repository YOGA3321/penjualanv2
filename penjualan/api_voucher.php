<?php
require_once '../auth/koneksi.php';
header('Content-Type: application/json');

$kode = $_GET['kode'] ?? '';
$total = $_GET['total'] ?? 0;
$today = date('Y-m-d');

if(empty($kode)) { echo json_encode(['valid'=>false, 'msg'=>'Kode kosong']); exit; }

// Cek Database
$q = $koneksi->query("SELECT * FROM vouchers WHERE kode = '$kode' AND stok > 0 AND berlaku_sampai >= '$today'");
$v = $q->fetch_assoc();

if(!$v) {
    echo json_encode(['valid'=>false, 'msg'=>'Voucher tidak valid / habis / kadaluarsa']);
} else {
    // Cek Minimal Belanja
    if($total < $v['min_belanja']) {
        echo json_encode(['valid'=>false, 'msg'=>'Min. belanja Rp '.number_format($v['min_belanja'])]);
    } else {
        // Hitung Diskon
        $potongan = 0;
        if($v['tipe'] == 'fixed') {
            $potongan = $v['nilai'];
        } else {
            $potongan = ($total * $v['nilai']) / 100;
        }
        
        // Jangan sampai diskon lebih besar dari total
        if($potongan > $total) $potongan = $total;

        echo json_encode([
            'valid' => true,
            'msg' => 'Voucher digunakan!',
            'potongan' => $potongan,
            'kode' => $v['kode']
        ]);
    }
}
?>