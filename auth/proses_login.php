<?php
session_start();
require_once 'koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $koneksi->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // [PERBAIKAN QUERY] Ambil juga nama_cabang
    $sql = "SELECT u.*, c.nama_cabang 
            FROM users u 
            LEFT JOIN cabang c ON u.cabang_id = c.id 
            WHERE u.email = '$email'";
            
    $result = $koneksi->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['cabang_id'] = $user['cabang_id'];
            
            // [BARU] Simpan Nama Cabang ke Session
            if ($user['level'] == 'admin') {
                $_SESSION['cabang_name'] = 'Cabang Pusat (Global)';
                $_SESSION['view_cabang_id'] = 'pusat'; // Default view admin
            } else {
                // Jika Karyawan, gunakan nama cabang dari DB
                // Jika NULL (misal user global tanpa admin), set Pusat
                $_SESSION['cabang_name'] = $user['nama_cabang'] ?? 'Cabang Pusat';
            }

            // Update Last Active
            $koneksi->query("UPDATE users SET last_active = NOW() WHERE id = '".$user['id']."'");

            $redirect = ($user['level'] == 'pelanggan') ? '../penjualan/index.php' : '../admin/index.php';
            echo json_encode(['status' => 'success', 'message' => 'Login berhasil!', 'redirect' => $redirect]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>