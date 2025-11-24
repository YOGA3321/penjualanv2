<?php
include 'koneksi.php';

header('Content-Type: application/json');

$nama = $_POST['nama'] ?? '';
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$NoTlp = $_POST['NoTlp'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

// Cek duplikasi username
$stmt = $Koneksi->prepare("SELECT id FROM user WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username sudah digunakan.']);
    exit;
}

// Hashing password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$level = 5; // Level default untuk user baru

$stmt = $Koneksi->prepare("INSERT INTO user (nama, username, email, password, NoTlp, level) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssi', $nama, $username, $email, $hashed_password, $NoTlp, $level);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Registrasi berhasil! Silakan pindah ke halaman login.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar. Terjadi kesalahan server.']);
}

$stmt->close();
$Koneksi->close();
?>