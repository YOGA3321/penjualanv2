<?php
session_start(); // Wajib ada di paling atas

// Hapus token Google jika ada
if (isset($_SESSION['access_token'])) {
    require_once 'vendor/autoload.php';
    $client = new Google_Client();
    $client->revokeToken($_SESSION['access_token']);
}

// Hapus semua variabel session
session_unset();

// Hancurkan session
session_destroy();

// Redirect ke halaman login
header("Location: login");
exit;
?>