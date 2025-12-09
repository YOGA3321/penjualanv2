<?php
// Explicit Localhost Connection for CLI Migration
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'penjualan2';

$koneksi = new mysqli($host, $db_user, $db_pass, $db_name);

if ($koneksi->connect_error) {
    die("Connection failed: " . $koneksi->connect_error);
}

$sql_file = 'migration_gudang.sql';

if (!file_exists($sql_file)) {
    die("File migration_gudang.sql tidak ditemukan.");
}

$sql_contents = file_get_contents($sql_file);
$queries = explode(';', $sql_contents);

echo "Starting Migration...\n";
foreach ($queries as $query) {
    if (trim($query) != '') {
        $q = trim($query);
        try {
            if ($koneksi->query($q) === TRUE) {
                echo "SUCCESS: " . substr($q, 0, 50) . "...\n";
            } else {
                // Ignore "Duplicate column" or "Table exists" errors for idempotency if needed
                echo "NOTE: " . $koneksi->error . "\n";
            }
        } catch (Exception $e) {
             echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}
echo "Migration Completed.\n";
?>
