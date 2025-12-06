<?php
session_start();

// Aktifkan Error Reporting agar ketahuan salahnya dimana
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'koneksi.php';
require_once 'google_config.php';

// [FIX SSL LOCALHOST] Bypass sertifikat agar tidak error cURL 60 di Laragon
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168') !== false) {
    $client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
}

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Cek Error Token
        if (isset($token['error'])) {
            throw new Exception("Google Token Error: " . $token['error']);
        }

        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $info = $google_oauth->userinfo->get();
        
        $email = $info->email;
        $google_id = $info->id;
        $name = $info->name;
        
        // [FIX FOTO] Pastikan URL Foto aman (tidak error jika kosong)
        $picture = isset($info->picture) ? $info->picture : NULL;

        // 1. CEK DATABASE
        $stmt = $koneksi->prepare("SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Database Read Error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // --- USER LAMA (LOGIN) ---
            $user = $result->fetch_assoc();
            
            // UPDATE: Foto & Google ID
            $upd = $koneksi->prepare("UPDATE users SET google_id=?, foto=?, last_active=NOW() WHERE id=?");
            // Pastikan jumlah tipe data (ssi) sesuai parameter
            $upd->bind_param("ssi", $google_id, $picture, $user['id']);
            
            if (!$upd->execute()) {
                throw new Exception("Database Update Error: " . $upd->error);
            }
            
            // Update session foto
            $user['foto'] = $picture; 

        } else {
            // --- USER BARU (REGISTER PELANGGAN) ---
            $pass_dummy = password_hash(uniqid(), PASSWORD_DEFAULT);
            
            $ins = $koneksi->prepare("INSERT INTO users (nama, email, password, level, google_id, foto, poin) VALUES (?, ?, ?, 'pelanggan', ?, ?, 0)");
            // Pastikan jumlah tipe data (sssss) sesuai parameter (5 parameter)
            $ins->bind_param("sssss", $name, $email, $pass_dummy, $google_id, $picture);
            
            if (!$ins->execute()) {
                throw new Exception("Database Insert Error: " . $ins->error);
            }
            
            $user_id = $koneksi->insert_id;
            $user = [
                'id' => $user_id, 
                'nama' => $name, 
                'email' => $email, 
                'level' => 'pelanggan', 
                'foto' => $picture, 
                'cabang_id' => NULL, 
                'nama_cabang' => NULL
            ];
        }

        // SET SESSION
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['level'] = $user['level'];
        $_SESSION['foto'] = $user['foto'];
        $_SESSION['cabang_id'] = $user['cabang_id'];
        
        // Redirect Logic
        if ($user['level'] == 'admin' || $user['level'] == 'karyawan') {
            $_SESSION['cabang_name'] = ($user['level'] == 'admin') ? 'Cabang Pusat (Global)' : ($user['nama_cabang'] ?? 'Cabang Pusat');
            $_SESSION['view_cabang_id'] = 'pusat';
            header("Location: ../admin/");
        } else {
            header("Location: ../pelanggan/");
        }
        exit;

    } catch (Exception $e) {
        // Tampilkan Pesan Error Asli untuk Debugging
        $_SESSION['swal'] = [
            'icon' => 'error', 
            'title' => 'Login Gagal', 
            'text' => $e->getMessage() // Ini akan memberitahu kita apa salahnya
        ];
        header("Location: ../login");
        exit;
    }
}

header("Location: ../login");
exit;
?>