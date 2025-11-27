<?php
session_start();

// Cek apakah file vendor ada. Jika tidak ada, lewati fungsi Google Revoke
if (file_exists('vendor/autoload.php') && isset($_SESSION['access_token'])) {
    require_once 'vendor/autoload.php';
    try {
        $client = new Google_Client();
        $client->revokeToken($_SESSION['access_token']);
    } catch (Exception $e) {
        // Biarkan error google diam, jangan crash aplikasi
    }
}

session_unset();
session_destroy();
header("Location: login");
exit;
?>