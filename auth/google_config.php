<?php
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/koneksi.php'; 
$clientID = $_ENV['GOOGLE_CLIENT_ID']; 
$clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
// 3. Gunakan BASE_URL agar otomatis menyesuaikan (Localhost / Online)
$redirectUri = BASE_URL . 'auth/google_callback.php'; 

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
?>