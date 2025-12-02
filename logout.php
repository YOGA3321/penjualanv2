<?php
session_start();
if (file_exists('vendor/autoload.php') && isset($_SESSION['access_token'])) {
    require_once 'vendor/autoload.php';
    try {
        $client = new Google_Client();
        $client->revokeToken($_SESSION['access_token']);
    } catch (Exception $e) {
        // Abaikan error revoke
    }
}
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
header("Location: login");
exit;
?>