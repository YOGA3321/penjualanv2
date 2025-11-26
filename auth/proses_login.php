<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

// Mengambil input. Pastikan 'name' di form login adalah 'email' dan 'password'
$email = $_POST['email'] ?? ''; 
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan Password wajib diisi.']);
    exit;
}

// Query menggunakan EMAIL
$query = "SELECT users.*, cabang.nama_cabang 
          FROM users 
          LEFT JOIN cabang ON users.cabang_id = cabang.id
          WHERE users.email = ?"; // Validasi berdasarkan email

$stmt = $koneksi->prepare($query);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        
        // Set Session
        $_SESSION['user_id']   = $row['id'];
        $_SESSION['nama']      = $row['nama'];
        $_SESSION['level']     = $row['level'];
        $_SESSION['cabang_id'] = $row['cabang_id'];
        
        // Logika Nama Cabang
        if ($row['level'] == 'admin') {
            $_SESSION['cabang_name'] = "Cabang Pusat";
        } else {
            $_SESSION['cabang_name'] = $row['nama_cabang'] ?? 'Cabang Tidak Diketahui';
        }

        // Cek Level untuk Redirect
        if ($row['level'] == 'admin' || $row['level'] == 'karyawan') {
            $redirect_url = 'admin/'; // Akan ditangkap index.php -> lari ke laporan
        } else {
            $redirect_url = 'penjualan/dashboard'; // Untuk pelanggan (nanti kita buat)
        }

        echo json_encode([
            'status' => 'success', 
            'message' => 'Login berhasil! Mengalihkan...', 
            'redirect' => $redirect_url 
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Password salah.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Email tidak terdaftar.']);
}

$stmt->close();
$koneksi->close();
?>