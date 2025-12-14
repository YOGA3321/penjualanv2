<?php
session_start();

// Debugging: Tampilkan semua error
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'koneksi.php';
require_once 'google_config.php';

// Bypass SSL Verify (Hanya untuk Localhost/Testing darurat di Hosting)
// Di production sebaiknya dihapus jika SSL server sudah benar (Full/Strict)
$client->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));

if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Cek Error Token Google
        if (isset($token['error'])) {
            throw new Exception("Google Token Error: " . json_encode($token));
        }

        $client->setAccessToken($token['access_token']);
        $google_oauth = new Google_Service_Oauth2($client);
        $info = $google_oauth->userinfo->get();
        
        $email = $info->email;
        $google_id = $info->id;
        $name = $info->name;
        $picture = isset($info->picture) ? $info->picture : NULL;

        // Cek Database
        $stmt = $koneksi->prepare("SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("SQL Error Read: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // USER LAMA
            $user = $result->fetch_assoc();
            
            // Update Data
            $upd = $koneksi->prepare("UPDATE users SET google_id=?, foto=?, last_active=NOW() WHERE id=?");
            $upd->bind_param("ssi", $google_id, $picture, $user['id']);
            if (!$upd->execute()) throw new Exception("SQL Error Update: " . $upd->error);
            
            // Refresh Data Session
            $user['foto'] = $picture; 

        } else {
            // USER BARU
            $pass_dummy = password_hash(uniqid().time(), PASSWORD_DEFAULT);
            
            $ins = $koneksi->prepare("INSERT INTO users (nama, email, password, level, google_id, foto, poin) VALUES (?, ?, ?, 'pelanggan', ?, ?, 0)");
            $ins->bind_param("sssss", $name, $email, $pass_dummy, $google_id, $picture);
            
            if (!$ins->execute()) throw new Exception("SQL Error Insert: " . $ins->error);
            
            $user_id = $koneksi->insert_id;
            
            // LOGIC AUTO-ADMIN (Jika User Pertama)
            $cek_first = $koneksi->query("SELECT COUNT(*) as total FROM users");
            $row_first = $cek_first->fetch_assoc();
            
            // Total 1 berarti user ini adalah yang pertama (karena baru saja di-insert, totalnya jadi 1)
            // Atau kita cek sebelum insert, tapi karena sudah insert, cek if total == 1
            if ($row_first['total'] == 1) {
                $upd_admin = $koneksi->query("UPDATE users SET level='admin' WHERE id='$user_id'");
                $level_user = 'admin'; // Override local var
            } else {
                $level_user = 'pelanggan';
            }

            $user = [
                'id' => $user_id, 'nama' => $name, 'email' => $email, 'level' => $level_user, 
                'foto' => $picture, 'cabang_id' => NULL, 'nama_cabang' => NULL
            ];
        }

        // Set Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['level'] = $user['level'];
        $_SESSION['foto'] = $user['foto'];
        $_SESSION['cabang_id'] = $user['cabang_id'];
        
        // Redirect Sukses
        if ($user['level'] == 'admin' || $user['level'] == 'karyawan') {
            $_SESSION['cabang_name'] = ($user['level'] == 'admin') ? 'Cabang Pusat (Global)' : ($user['nama_cabang'] ?? 'Cabang Pusat');
            $_SESSION['view_cabang_id'] = 'pusat';
            header("Location: ../admin/");
        } else {
            header("Location: ../pelanggan/");
        }
        exit;

    } catch (Exception $e) {
        // TAMPILKAN ERROR DI LAYAR (Jangan Redirect Dulu)
        die("<h1>LOGIN GAGAL</h1><p>Error: " . $e->getMessage() . "</p><p>Silakan screenshot dan kirim ke developer.</p><a href='../login.php'>Kembali</a>");
    }
} else {
    // Jika tidak ada code (akses langsung), lempar balik
    header("Location: ../login");
    exit;
}
?>