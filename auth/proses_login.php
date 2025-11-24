<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

$username = $_POST['user'] ?? '';
$password = $_POST['pass'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Username dan Password tidak boleh kosong.']);
    exit;
}

$query = "SELECT * FROM user WHERE username = ?";
$stmt = $Koneksi->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        // Login berhasil
        $_SESSION['user'] = $username;
        $_SESSION['id_lgn'] = $row['id'];
        $_SESSION['nama'] = $row['nama'];
        $_SESSION['user_id'] = $row['level'];

        $currtime = date("Y-m-d H:i:s");
        $Koneksi->query('UPDATE user SET last_login = "'.$currtime.'" WHERE id = "'.$row['id'].'"');

        echo json_encode([
            'status' => 'success',
            'message' => 'Login berhasil! Mengalihkan...',
            'redirect' => 'Pemesanan/Dashboard'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Username tidak ditemukan.']);
}

$stmt->close();
$Koneksi->close();
?>