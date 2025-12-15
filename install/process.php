<?php
// install/process.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$mode = $_POST['mode'];
$host = $_POST['db_host'];
$user = $_POST['db_user'];
$pass = $_POST['db_pass'];
$name = $_POST['db_name'];

// API Credentials (Optional)
$google_id = $_POST['google_id'] ?? '';
$google_secret = $_POST['google_secret'] ?? '';
$midtrans_client = $_POST['midtrans_client'] ?? '';
$midtrans_server = $_POST['midtrans_server'] ?? '';

// 1. Coba koneksi ke MySQL Server (tanpa pilih DB dulu)
$conn = @mysqli_connect($host, $user, $pass);

if (!$conn) {
    $error = "Gagal koneksi ke MySQL: " . mysqli_connect_error();
    header("Location: index.php?error=" . urlencode($error));
    exit;
}

// 2. Logic Mode Otomatis vs Manual
if ($mode === 'auto') {
    // Buat database baru
    $sql = "CREATE DATABASE QAIF `$name`"; // Just in case, try to create. 
    // Wait, CREATE DATABASE IF NOT EXISTS is safer.
    $sql = "CREATE DATABASE IF NOT EXISTS `$name`";
    if (!mysqli_query($conn, $sql)) {
         $error = "Gagal membuat database: " . mysqli_error($conn);
         header("Location: index.php?error=" . urlencode($error));
         exit;
    }
} 

// Pilih Database
if (!mysqli_select_db($conn, $name)) {
    // Jika manual dan DB tidak ada
    $error = "Database '$name' tidak ditemukan. Pastikan sudah dibuat atau gunakan Mode Otomatis.";
    header("Location: index.php?error=" . urlencode($error));
    exit;
}

// 3. Import SQL (Master Data)
// Kita jalankan Master SQL jika: Mode Auto OR (Mode Manual tapi tabel 'users' belum ada - asumsi DB kosong)
$should_import = false;
if ($mode === 'auto') {
    $should_import = true;
} else {
    // Cek tabel users
    $check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($check) == 0) {
        $should_import = true;
    }
}

if ($should_import) {
    $sqlFile = __DIR__ . '/master.sql';
    if (file_exists($sqlFile)) {
        $queries = file_get_contents($sqlFile);
        
        // Hapus comments agar parsing lebih aman (basic)
        $lines = explode("\n", $queries);
        $clean_query = "";
        foreach ($lines as $line) {
            if (substr(trim($line), 0, 2) == '--' || substr(trim($line), 0, 1) == '#') continue;
            $clean_query .= $line . "\n";
        }
        
        // Split by semicolon
        $statements = explode(";", $clean_query);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (!empty($stmt)) {
                if (!mysqli_query($conn, $stmt)) {
                    // Jangan die, mungkin error minor (seperti drop table not exists), lanjut aja
                    // Atau log error
                }
            }
        }
    }
}

// 4. Buat/Update File .env
$envFile = dirname(__DIR__) . '/.env';
$envExample = dirname(__DIR__) . '/.env.example';

// Ambil template dari .env.example jika ada
if (file_exists($envExample)) {
    $envContent = file_get_contents($envExample);
} else {
    // Fallback template jika .env.example hilang
    $envContent = "LOCALHOST_DB_HOST=localhost\nLOCALHOST_DB_NAME=penjualan2\nLOCALHOST_DB_USER=root\nLOCALHOST_DB_PASS=\n\nHOSTING_DB_HOST=localhost\nHOSTING_DB_NAME=u116133173_penjualan2\nHOSTING_DB_USER=u116133173_penjualan2\nHOSTING_DB_PASS=@Yogabd46\nHOSTING_DOMAIN=waroengmodern.com\n\nGOOGLE_CLIENT_ID=\nGOOGLE_CLIENT_SECRET=\n\nMIDTRANS_CLIENT_KEY=\nMIDTRANS_SERVER_KEY=\nMIDTRANS_IS_PRODUCTION=false";
}

// Replace DB Credentials untuk Localhost (Asumsi install di local/hybrid)
// Kita update kedua set (Local & Hosting) dengan value yang sama dari input installer
// Supaya aman dimanapun dideploy sementara waktu.
$envContent = preg_replace('/^LOCALHOST_DB_HOST=.*$/m', 'LOCALHOST_DB_HOST=' . $host, $envContent);
$envContent = preg_replace('/^LOCALHOST_DB_NAME=.*$/m', 'LOCALHOST_DB_NAME=' . $name, $envContent);
$envContent = preg_replace('/^LOCALHOST_DB_USER=.*$/m', 'LOCALHOST_DB_USER=' . $user, $envContent);
$envContent = preg_replace('/^LOCALHOST_DB_PASS=.*$/m', 'LOCALHOST_DB_PASS=' . $pass, $envContent);

// 5. Detect Domain & Protocol for Hosting Config
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = "https://";
}
$current_host = $_SERVER['HTTP_HOST'];

// Detect current path to determine APP_URL
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dir_root = str_replace('\\', '/', dirname(__DIR__)); 
$sub_path = str_replace($doc_root, '', $dir_root);
if ($sub_path == '.') $sub_path = '';

$detected_app_url = $protocol . $current_host . $sub_path;
// Ensure trailing slash
$detected_app_url = rtrim($detected_app_url, '/') . '/';

// Update .env content with detected values
$envContent = preg_replace('/^HOSTING_DB_HOST=.*$/m', 'HOSTING_DB_HOST=' . $host, $envContent);
$envContent = preg_replace('/^HOSTING_DB_NAME=.*$/m', 'HOSTING_DB_NAME=' . $name, $envContent);
$envContent = preg_replace('/^HOSTING_DB_USER=.*$/m', 'HOSTING_DB_USER=' . $user, $envContent);
$envContent = preg_replace('/^HOSTING_DB_PASS=.*$/m', 'HOSTING_DB_PASS=' . $pass, $envContent);

$envContent = preg_replace('/^HOSTING_DOMAIN=.*$/m', 'HOSTING_DOMAIN=' . $current_host, $envContent);
$envContent = preg_replace('/^# APP_URL=.*$/m', 'APP_URL=' . $detected_app_url, $envContent); // Uncomment and set
$envContent = preg_replace('/^APP_URL=.*$/m', 'APP_URL=' . $detected_app_url, $envContent); // Just in case it's already uncommented

// Update API Keys if provided
if (!empty($google_id)) {
    $envContent = preg_replace('/^GOOGLE_CLIENT_ID=.*$/m', 'GOOGLE_CLIENT_ID=' . $google_id, $envContent);
}
if (!empty($google_secret)) {
    $envContent = preg_replace('/^GOOGLE_CLIENT_SECRET=.*$/m', 'GOOGLE_CLIENT_SECRET=' . $google_secret, $envContent);
}
if (!empty($midtrans_client)) {
    $envContent = preg_replace('/^MIDTRANS_CLIENT_KEY=.*$/m', 'MIDTRANS_CLIENT_KEY=' . $midtrans_client, $envContent);
}
if (!empty($midtrans_server)) {
    $envContent = preg_replace('/^MIDTRANS_SERVER_KEY=.*$/m', 'MIDTRANS_SERVER_KEY=' . $midtrans_server, $envContent);
}

if (file_put_contents($envFile, $envContent)) {
    // Redirect Sukses
    header("Location: ../index.php?install=success");
} else {
    $error = "Gagal menulis file .env. Cek permission folder root.";
    header("Location: index.php?error=" . urlencode($error));
}
?>
