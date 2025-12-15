<?php
// auth/koneksi.php
// Modified to support automatic redirect to /install if DB is not ready
// while maintaining original multi-environment support.

// 1. LOAD ENVIRONMENT VARIABLES (.ENV)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }
}

// 2. DETEKSI LINGKUNGAN (LOCALHOST VS HOSTING)
$whitelist_ip = array('::1', '127.0.0.1', 'localhost');
$is_localhost_env = false;
if (in_array($_SERVER['REMOTE_ADDR'], $whitelist_ip) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    $is_localhost_env = true;
}

// 3. KONFIGURASI URL & PROTOKOL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host_server = $_SERVER['HTTP_HOST'];

// Fix Protocol jika di belakang Proxy/Cloudflare (Sering terjadi error disini)
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $protocol = "https://";
}

// Override Host jika di Hosting (Sesuai .env)
if (!$is_localhost_env && !empty($_ENV['HOSTING_DOMAIN'])) {
    if (strpos($host_server, $_ENV['HOSTING_DOMAIN']) !== false) {
        $host_server = $_ENV['HOSTING_DOMAIN'];
        // FORCE HTTPS jika di hosting (Agar Google Login tidak error redirect_uri_mismatch)
        $protocol = "https://"; 
    }
}

// Deteksi Folder Path
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dir_root = str_replace('\\', '/', dirname(__DIR__)); 
$base_path = str_replace($doc_root, '', $dir_root);
if ($base_path == '.') $base_path = '';

// Susun BASE_URL Default
$base_url = $protocol . $host_server . $base_path;
$base_url = rtrim($base_url, '/') . '/'; // Pastikan akhiran slash

// [SUPER FIX] Override dengan APP_URL dari .env jika ada (Sangat Disarankan)
if (!empty($_ENV['APP_URL'])) {
    $base_url = rtrim($_ENV['APP_URL'], '/') . '/';
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}

// 4. SET TIMEZONE
date_default_timezone_set('Asia/Jakarta');

// 5. KONEKSI DATABASE
if ($is_localhost_env) {
    // === SETTING LOCALHOST ===
    $host = $_ENV['LOCALHOST_DB_HOST'] ?? 'localhost';
    $db_user = $_ENV['LOCALHOST_DB_USER'] ?? 'root';
    $db_pass = $_ENV['LOCALHOST_DB_PASS'] ?? '';
    $db_name = $_ENV['LOCALHOST_DB_NAME'] ?? ''; 
} else {
    // === SETTING HOSTING (LIVE) ===
    $host = $_ENV['HOSTING_DB_HOST'] ?? 'localhost';
    $db_user = $_ENV['HOSTING_DB_USER'] ?? ''; 
    $db_pass = $_ENV['HOSTING_DB_PASS'] ?? '';            
    $db_name = $_ENV['HOSTING_DB_NAME'] ?? ''; 
}

$install_needed = false;

// Matikan error reporting mysqli agar bisa ditangkap try-catch (PHP 8.1+)
mysqli_report(MYSQLI_REPORT_OFF);

try {
    // Coba koneksi
    $koneksi = @mysqli_connect($host, $db_user, $db_pass, $db_name);
    
    if (!$koneksi) {
        throw new Exception("Gagal koneksi ke database");
    }

    // Cek apakah tabel users ada (indikator sudah install)
    $check_table = @mysqli_query($koneksi, "SELECT 1 FROM users LIMIT 1");
    if (!$check_table) {
        $install_needed = true; // DB Connect tapi tabel kosong
    }

} catch (Exception $e) {
    // DB Gagal Connect
    $install_needed = true;
}

// 6. REDIRECT KE INSTALLER JIKA PERLU
if ($install_needed) {
    // Cek agar tidak looping redirect jika sudah di folder install
    $current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    if (strpos($current_script, '/install/') === false) {
        header("Location: " . BASE_URL . "install/");
        exit;
    }
}

// Set Timezone Database
if (isset($koneksi) && $koneksi) {
    $koneksi->query("SET time_zone = '+07:00'");
}
?>