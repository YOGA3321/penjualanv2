<?php
session_start();
require_once 'koneksi.php';
require_once 'google_config.php';

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    if(!isset($token['error'])){
        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $google_id = $google_account_info->id;
        $name = $google_account_info->name;

        // 1. CEK APAKAH EMAIL ADA DI DATABASE?
        $stmt = $koneksi->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Update Google ID & Last Active
            $koneksi->query("UPDATE users SET google_id = '$google_id', last_active = NOW() WHERE id = '".$user['id']."'");

            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['cabang_id'] = $user['cabang_id'];

            // Redirect sesuai level
            if ($user['level'] == 'admin' || $user['level'] == 'karyawan') {
                header("Location: ../admin/laporan");
            } else {
                // Jika pelanggan tidak sengaja login lewat sini
                header("Location: ../penjualan/");
            }
            exit;
        } else {
            // EMAIL TIDAK TERDAFTAR -> TOLAK
            echo "<script>
                alert('Email Google Anda tidak terdaftar sebagai Admin/Karyawan. Silakan hubungi Pemilik Resto.');
                window.location.href = '../login.php';
            </script>";
            exit;
        }
    }
}
header("Location: ../login.php");
exit;
?>