<?php
require_once 'db_connect.php';

$id = 1;

$stmt = $db->prepare("SELECT name, bundle FROM bundles WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($bundle_name, $base64_zip_data);
$stmt->fetch();
$stmt->close();

if ($base64_zip_data && $bundle_name) {
 
    $binary_zip_data = base64_decode($base64_zip_data);

    $destination = "/home/bundler/Bundles/" . $bundle_name;
    if (file_put_contents($destination, $binary_zip_data) !== false) {
        echo "Zip file saved to $destination!\n";
    } else {
        echo "Failed to write $bundle_name to $destination\n";
    }
} else {
    echo "Bundle not found!\n";
}
?>