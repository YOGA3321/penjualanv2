<?php
require_once dirname(__FILE__) . '/../vendor/autoload.php';
require_once dirname(__FILE__) . '/koneksi.php'; 
$clientID = '1060271441614-jmrpg91p772uekptqmkg88uu8lc40pk6.apps.googleusercontent.com'; 
$clientSecret = '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';
// 3. Gunakan BASE_URL agar otomatis menyesuaikan (Localhost / Online)
$redirectUri = BASE_URL . '/auth/google_callback.php'; 

$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");
?>