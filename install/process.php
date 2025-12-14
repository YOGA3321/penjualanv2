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

// 4. Buat File Konfigurasi (auth/config.php)
$configContent = "<?php\n";
$configContent .= "// Auto-generated configuration by Installer\n";
$configContent .= "define('DB_HOST', '" . addslashes($host) . "');\n";
$configContent .= "define('DB_USER', '" . addslashes($user) . "');\n";
$configContent .= "define('DB_PASS', '" . addslashes($pass) . "');\n";
$configContent .= "define('DB_NAME', '" . addslashes($name) . "');\n";
$configContent .= "?>";

$configFile = dirname(__DIR__) . '/auth/config.php';
if (file_put_contents($configFile, $configContent)) {
    // Redirect ke halaman sukses / login
    // Kita arahkan ke root, nanti koneksi.php akan redirect ke login jika belum login
    header("Location: ../index.php?install=success");
} else {
    $error = "Gagal menulis file config.php. Cek permission folder auth.";
    header("Location: index.php?error=" . urlencode($error));
}
?>
