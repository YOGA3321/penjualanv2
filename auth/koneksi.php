<?php
// auth/koneksi.php
// Modified to support automatic redirect to /install if DB is not ready
// while maintaining original multi-environment support.

// 1. Calculate BASE_URL first (needed for redirection)

// Load .env
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host_server = $_SERVER['HTTP_HOST'];

// Dynamic Base Path Detection
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dir_root = str_replace('\\', '/', dirname(__DIR__)); // Root folder of app
$base_path = str_replace($doc_root, '', $dir_root);
if ($base_path == '.') $base_path = '';

// Fix for specific hosting domains if needed (as per original file)
$is_localhost_env = false;
$whitelist_ip = array('::1', '127.0.0.1', 'localhost');
if (in_array($_SERVER['REMOTE_ADDR'], $whitelist_ip) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    $is_localhost_env = true;
}

if (!$is_localhost_env) {
    $fixed_domain = 'sale.lopyta.com'; 
    if (strpos($host_server, $fixed_domain) !== false) {
        $host_server = $fixed_domain; 
    }
}

$base_url = $protocol . $host_server . $base_path;

if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url); // Removed trailing slash to prevent double slash in generated paths
}

// 2. Set Timezone
date_default_timezone_set('Asia/Jakarta');

// 3. Environment Headers (Original Logic)
$whitelist = array('::1', 'localhost', '192.168.0.192');
$is_localhost = false;
if (in_array($_SERVER['REMOTE_ADDR'], $whitelist) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '192.168.') !== false) {
    $is_localhost = true;
}

if ($is_localhost) {
    // === SETTING LOCALHOST ===
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_user = $_ENV['DB_USER'] ?? 'root';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_name = $_ENV['DB_NAME'] ?? 'penjualan2';
} else {
    // === SETTING HOSTING (LIVE) ===
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $db_user = $_ENV['DB_USER'] ?? 'u116133173_penjualan2'; 
    $db_pass = $_ENV['DB_PASS'] ?? '@Yogabd46';             
    $db_name = $_ENV['DB_NAME'] ?? 'u116133173_penjualan2'; 
}

// 4. Try Connection
// Suppress warnings (@) to handle redirect gracefully
$koneksi = @mysqli_connect($host, $db_user, $db_pass, $db_name);

$install_needed = false;

if (!$koneksi) {
    // Connection Failed (DB might not exist or credentials wrong)
    $install_needed = true;
} else {
    // Connection Success, but check if Tables exist (e.g. 'users')
    $check_table = @mysqli_query($koneksi, "SELECT 1 FROM users LIMIT 1");
    if (!$check_table) {
        $install_needed = true;
    }
}

// 5. Redirect if Installation Needed
if ($install_needed) {
    // Prevent redirect loop if we are already in the install folder
    $current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    if (strpos($current_script, '/install/') === false) {
        // Redirect to Install Page using Absolute URL
        header("Location: " . BASE_URL . "install/");
        exit;
    }
}

// Set Timezone for DB
if ($koneksi) {
    $koneksi->query("SET time_zone = '+07:00'");
}

?>