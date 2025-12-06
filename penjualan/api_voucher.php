<?php
require_once '../auth/koneksi.php';
header('Content-Type: application/json');

$kode = $_GET['kode'] ?? '';
$total = (int)($_GET['total'] ?? 0); // Pastikan integer
$today = date('Y-m-d');

if(empty($kode)) { echo json_encode(['valid'=>false, 'msg'=>'Kode kosong']); exit; }

$q = $koneksi->query("SELECT * FROM vouchers WHERE kode = '$kode' AND stok > 0 AND berlaku_sampai >= '$today'");
$v = $q->fetch_assoc();

if(!$v) {
    echo json_encode(['valid'=>false, 'msg'=>'Voucher tidak ditemukan']);
} else {
    if($total < $v['min_belanja']) {
        echo json_encode(['valid'=>false, 'msg'=>'Min. belanja Rp '.number_format($v['min_belanja'])]);
    } else {
        $potongan = 0;
        if($v['tipe'] == 'fixed') {
            $potongan = $v['nilai'];
        } else {
            $potongan = ($total * $v['nilai']) / 100;
        }
        
        // [FIX] Pastikan diskon tidak minus dan tidak melebihi total
        if($potongan > $total) $potongan = $total;
        $potongan = floor($potongan); // Bulatkan ke bawah

        echo json_encode([
            'valid' => true,
            'msg' => 'Voucher digunakan!',
            'potongan' => (int)$potongan, // Kirim sebagai integer
            'kode' => $v['kode']
        ]);
    }
}
?>