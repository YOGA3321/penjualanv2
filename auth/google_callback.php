<?php
session_start();
require_once 'koneksi.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (!isset($token['error'])) {
            $client->setAccessToken($token['access_token']);
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
            
            $email = $google_account_info->email;
            $google_id = $google_account_info->id;
            
            // 1. CEK DATABASE
            $stmt = $koneksi->prepare("SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.id WHERE u.email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // --- SUKSES ---
                $user = $result->fetch_assoc();
                
                // Update Google ID & Last Active
                $koneksi->query("UPDATE users SET google_id = '$google_id', last_active = NOW() WHERE id = '".$user['id']."'");

                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['cabang_id'] = $user['cabang_id'];
                
                if ($user['level'] == 'admin') {
                    $_SESSION['cabang_name'] = 'Cabang Pusat (Global)';
                    $_SESSION['view_cabang_id'] = 'pusat';
                } else {
                    $_SESSION['cabang_name'] = $user['nama_cabang'] ?? 'Cabang Pusat';
                }

                // Redirect
                if ($user['level'] == 'admin' || $user['level'] == 'karyawan') {
                    header("Location: ../admin/laporan");
                } else {
                    header("Location: ../penjualan/");
                }
                exit;

            } else {
                // --- GAGAL (EMAIL TIDAK ADA) ---
                // [FIX] Gunakan Session SweetAlert, bukan echo script
                $_SESSION['swal'] = [
                    'icon' => 'error',
                    'title' => 'Akses Ditolak!',
                    'text' => "Email Google Anda ($email) belum terdaftar sebagai Karyawan. Silakan hubungi Admin."
                ];
                header("Location: ../login.php");
                exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['swal'] = ['icon' => 'error', 'title' => 'Error', 'text' => 'Gagal login ke Google.'];
        header("Location: ../login.php");
        exit;
    }
}

header("Location: ../login.php");
exit;
?>