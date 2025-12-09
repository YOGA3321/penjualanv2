<?php
require_once 'auth/koneksi.php';

$sql_file = 'migration_gudang.sql';

if (!file_exists($sql_file)) {
    die("File migration_gudang.sql tidak ditemukan.");
}

$sql_contents = file_get_contents($sql_file);
$queries = explode(';', $sql_contents);

echo "<pre>";
foreach ($queries as $query) {
    if (trim($query) != '') {
        echo "Executing: " . substr(trim($query), 0, 100) . "...<br>";
        if ($koneksi->query($query) === TRUE) {
            echo "<strong style='color:green'>SUCCESS</strong><br><hr>";
        } else {
            echo "<strong style='color:red'>ERROR: " . $koneksi->error . "</strong><br><hr>";
        }
    }
}
echo "</pre>";
echo "<h3>Migration Completed.</h3>";
?>
