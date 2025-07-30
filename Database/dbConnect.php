#!/usr/bin/php
<?php
require_once(__DIR__ . '/../Logger/Logger.inc');
require_once(__DIR__ . '/../vendor/autoload.php');

mysqli_report(MYSQLI_REPORT_STRICT);

use Dotenv\Dotenv;

// Have to use absolute path for .env
$dotenvPath = '/home/db/systems-integration'; 
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->load();

$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

try {
    $db = new mysqli("localhost", $dbUser, $dbPass, "cyberlab");
    echo "Successfully connected to database" . PHP_EOL;
    return $db;
} catch (mysqli_sql_exception $e) {
    $errorMsg = "Failed to connect to database: " . $e->getMessage();
    echo $errorMsg . PHP_EOL;
    Logger\sendLog("BACKEND", "FATAL: $errorMsg");
    exit(1);
}
?>
