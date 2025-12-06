<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $koneksi->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Ambil data user beserta nama cabangnya
    $sql = "SELECT u.*, c.nama_cabang 
            FROM users u 
            LEFT JOIN cabang c ON u.cabang_id = c.id 
            WHERE u.email = '$email'";
            
    $result = $koneksi->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // 1. SET SESSION UTAMA
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['cabang_id'] = $user['cabang_id'];
            $_SESSION['foto'] = $user['foto']; // Tambahan jika ada foto
            
            // 2. SET SESSION CABANG (Untuk Admin/Karyawan)
            if ($user['level'] == 'admin') {
                $_SESSION['cabang_name'] = 'Cabang Pusat (Global)';
                $_SESSION['view_cabang_id'] = 'pusat'; 
            } else {
                $_SESSION['cabang_name'] = $user['nama_cabang'] ?? 'Cabang Pusat';
            }

            // 3. UPDATE STATUS AKTIF
            $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '".$user['id']."'");

            // ============================================================
            // 4. LOGIKA SMART REDIRECT (INITU YANG PENTING)
            // ============================================================
            $redirect = '';

            // Cek apakah ada permintaan redirect khusus (misal: dari Scan QR Meja)
            if (isset($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login']; // Isinya: penjualan/index.php?meja=...
                unset($_SESSION['redirect_after_login']); // Hapus agar tidak nyangkut
                $redirect = '../' . $target; 
            } 
            else {
                // Redirect Normal sesuai Level
                if ($user['level'] == 'admin') {
                    $redirect = '../admin/index.php';
                } 
                elseif ($user['level'] == 'karyawan') {
                    $redirect = '../admin/transaksi_masuk.php'; 
                } 
                else {
                    $redirect = '../pelanggan/index.php';
                }
            }

            echo json_encode([
                'status' => 'success', 
                'message' => 'Login berhasil!', 
                'redirect' => $redirect
            ]);

        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email tidak terdaftar!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>